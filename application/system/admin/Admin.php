<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\system\admin;

use app\common\controller\Common;
use app\system\model\SystemMenu as MenuModel;
use app\system\model\SystemData as DataModel;
use app\system\model\SystemRole as RoleModel;
use app\system\model\SystemUser as UserModel;
use app\system\model\SystemLog as LogModel;
use app\system\model\SystemAffiche as AfficheModel;
use app\system\model\SystemAutoExecute as AutoExecuteModel;
use app\common\model\Cparam as ParamModel;
use app\order\model\OpOrder as OpOrderModel;
use app\house\model\Tenant as TenantModel;
use app\house\model\House as HouseModel;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;
use think\Db;
use app\system\model\SystemBasis;
use app\system\model\SystemConfig;

/**
 * 后台公共控制器
 * @package app\system\admin
 */
class Admin extends Common
{
    // [通用添加、修改专用] 模型名称，格式：模块名/模型名
    protected $hisiModel = '';
    // [通用添加、修改专用] 表名(不含表前缀) 
    protected $hisiTable = '';
    // [通用添加、修改专用] 验证器类，格式：app\模块\validate\验证器类名
    protected $hisiValidate = false;
    //[通用添加专用] 添加数据验证场景名
    protected $hisiAddScene = false;
    //[通用更新专用] 更新数据验证场景名
    protected $hisiEditScene = false;

    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $model = new UserModel();

        // 开启白名单验证
        if(config('sys.admin_whitelist_verify') && !in_array(get_client_ip(), parse_attr(config('sys.admin_whitelist')))){
            return $this->success('您的IP已被限制登录……','admin.php/system/publics/index','',5);
        }

        // 判断登录
        $login = $model->isLogin();
        if (!$login['uid']) {
            return $this->error('请登录之后再操作！', ROOT_DIR.config('sys.admin_path'));
        }
        $userInfo = $model::where([['id','eq',$login['uid']]])->field('password,last_login_time')->find();
        $password_is_init = $userInfo['password'] == '$2y$10$gtfqCIJN.pJVsRge/ePxguwy0WwBN6VkjY4NClDTzqAXSI2Zp12l6' ? true : false;
        $this->assign('password_is_init', $password_is_init); //检测密码是否是默认密码

        //同一个账号第二次登录会挤掉第一次的登录
        if(config('sys.login_is_only_one') && (strtotime(session('admin_user.last_login_time')) < $userInfo->getData('last_login_time'))){ 
            $model->logout();
            return $this->success('您的账号于'.date('Y年m月d日 H时i分s秒',$userInfo->getData('last_login_time')).'在另一个地方登录……','admin.php/system/publics/index','',5);
        }
        
        //halt($password);
        define('INST',session('admin_user.inst_id'));
        define('INST_LEVEL',session('admin_user.inst_level'));
        if (!defined('ADMIN_ID')) {
            define('ADMIN_ID', $login['uid']);
            define('ADMIN_ROLE', $login['role_id']);
        
            $curMenu = MenuModel::getInfo();
            //halt($curMenu);
            if ($curMenu) {

                if (!RoleModel::checkAuth($curMenu['id']) && 
                    $curMenu['url'] != 'system/index/index') {
                    return $this->error('['.$curMenu['title'].'] 访问权限不足');
                }
                
            }
            else {

                $curMenu = ['title' => '', 'url' => '', 'id' => 0];

            }

            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

            // 更新工单中心的待受理工单(每个用户在刷新页面的时候，会对待受理工单节点更新)
            $acceptMenu = MenuModel::get('215');
            $acceptMenuTip = json_decode($acceptMenu['tip'],true);
            $OpOrderModel = new OpOrderModel;
            $acceptMenuTip[ADMIN_ID] = $OpOrderModel->getAcceptCount();
            MenuModel::where('id','eq',215)->setField('tip',json_encode($acceptMenuTip));

            $this->_systemLog($curMenu['title']);

            $afficheModel = new AfficheModel;
            $affiche = $afficheModel->getAffiche();//halt($affiches);
            $this->assign('affiche',$affiche);
            
            // 底部版本信息
            $SystemBasis = new SystemBasis;
            $basis = $SystemBasis->where([['delete_time','eq',0]])->order('create_time desc')->select();
            $this->assign('basis',$basis);

            // 底部版权信息
            $SystemConfig = new SystemConfig;
            $copyRightInfo = $SystemConfig->where([['name','eq','copy_right']])->find();
            //halt($copyRightInfo);
            $this->assign('copyRightInfo',$copyRightInfo);
            //halt($basis);
            // 自动执行的文件【待优化】
            $AutoExecuteModel = new AutoExecuteModel;
            $AutoExecuteModel->autocomplete();

            // 如果不是ajax请求，则读取菜单
            if (!$this->request->isAjax()) {
                $breadCrumbs = [];
                $menuParents = ['pid' => 1];

                $auths = session('role_auth_'.ADMIN_ROLE)?session('role_auth_'.ADMIN_ROLE):[];

                if ($curMenu['id']) {
                    $breadCrumbs = MenuModel::getBrandCrumbs($curMenu['id']);
                    $menuParents = current($breadCrumbs);
                }

                // 获取面包屑导航
                $breadCrumbs = MenuModel::getBrandCrumbs($curMenu['id']);
                $this->assign('hisiBreadcrumb', $breadCrumbs);

                // 获取当前访问的菜单信息
                $this->assign('hisiCurMenu', $curMenu);
                // 获取当前菜单的顶级节点
                $this->assign('hisiCurParents', $menuParents);
                // 获取导航菜单
                $this->assign('hisiMenus', MenuModel::getMainMenu());
                // 分组切换类型 0无需分组切换，1单个分组，2分组切换[无链接]，3分组切换[有链接]，具体请看后台layout.html
                $this->assign('hisiTabType', 0);
                // 获取所有参数
                $params = ParamModel::getCparams();
                $this->assign('inst_level',INST_LEVEL);
                $this->assign('can_pay',can_pay());

                $this->assign('params',$params);
                $this->assign('systemusers',session('systemusers'));
                $config = Db::name('config')->column('title,value');
                foreach($config as &$c){$c = explode(',',$c);}
  
                $this->assign('config', $config);
                // 当前用户的权限
                $this->assign('auths',$auths);
                $this->assign('paramsJson',json_encode($params));

                //当前用户是否拥有“提交工单”权限
                $this->assign('addOrderAuthBool',in_array(218,$auths));
                //当前用户是否拥有“我的工单”权限
                $this->assign('myOrderAuthBool',in_array(219,$auths));
                // tab切换数据
                // $hisiTabData = [
                //     ['title' => '后台首页', 'url' => 'system/index/index'],
                // ];
                // current 可不传
                // $this->assign('hisiTabData', ['menu' => $hisiTabData, 'current' => 'system/index/index']);
                $this->assign('hisi_iframe', input('hisi_iframe',''));
                $this->assign('hisiTabData', '');
                $this->assign('http_type', $http_type);
                // 表单数据默认变量名
                $this->assign('formData', '');
                $this->assign('login', $login);
                $this->assign('languages', model('SystemLanguage')->lists());
                $this->view->engine->layout(true);
            }
        }
    }

    /**
     * 系统日志记录
     * @author Lucas <598936602@qq.com>
     * @return string
     */
    private function _systemLog($title)
    {
        // 系统日志记录
        $log            = [];
        $log['uid']     = ADMIN_ID;
        $log['title']   = $title ? $title : '未加入系统菜单';
        $log['url']     = $this->request->url();
        $log['remark']  = '浏览数据';

        if ($this->request->isPost()) {
            $log['remark'] = '保存数据';
        }

        $result = LogModel::where($log)->find();

        $log['param']   = json_encode($this->request->param());
        $log['ip']      = $this->request->ip();

        if (!$result) {
            LogModel::create($log);
        } else {
            $log['id'] = $result->id;
            $log['count'] = $result->count+1;
            LogModel::update($log);
        }
    }

    /**
     * 获取当前方法URL
     * @author Lucas <598936602@qq.com>
     * @return string
     */
    protected function getActUrl() {
        $model      = request()->module();
        $controller = request()->controller();
        $action     = request()->action();
        return $model.'/'.$controller.'/'.$action;
    }
    
    /**
     * [通用方法]添加页面展示和保存
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {

            $hisiModel      = $this->request->param('hisiModel');
            $hisiTable      = $this->request->param('hisiTable');
            $hisiValidate   = $this->request->param('hisiValidate');
            $hisiScene      = $this->request->param('hisiScene');

            if ($hisiModel) {
                $this->hisiModel = $hisiModel;
                $this->hisiTable = '';
            }

            if ($hisiTable) {
                $this->hisiTable = $hisiTable;
                $this->hisiModel = '';
            }

            if ($hisiValidate) {
                $this->hisiValidate = $hisiValidate;
            }

            if ($hisiScene) {
                $this->hisiAddScene = $hisiScene;
            }

            $postData = $this->request->post();

            if ($this->hisiValidate) {// 数据验证

                if (strpos($this->hisiValidate, '\\') === false ) {

                    if (defined('IS_PLUGINS')) {
                        $this->hisiValidate = 'plugins\\'.$this->request->param('_p').'\\validate\\'.$this->hisiValidate;
                    } else {
                        $this->hisiValidate = 'app\\'.$this->request->module().'\\validate\\'.$this->hisiValidate;
                    }
                    
                }

                if ($this->hisiAddScene) {
                    $this->hisiValidate = $this->hisiValidate.'.'.$this->hisiAddScene;
                }

                $result = $this->validate($postData, $this->hisiValidate);
                if ($result !== true) {
                    return $this->error($result);
                }
                
            }

            if ($this->hisiModel) {// 通过Model添加

                if (defined('IS_PLUGINS')) {

                    if (strpos($this->hisiModel, '\\') === false ) {
                        $this->hisiModel = 'plugins\\'.$this->request->param('_p').'\\model\\'.$this->hisiModel;
                    }

                    $model = new $this->hisiModel;
                    
                } else {

                    if (strpos($this->hisiModel, '/') === false ) {
                        $this->hisiModel = $this->request->module().'/'.$this->hisiModel;
                    }

                    $model = model($this->hisiModel);

                }

                if (!$model->save($postData)) {
                    return $this->error($model->getError());
                }

            } else if ($this->hisiTable) {// 通过Db添加

                if (!Db::name($this->hisiTable)->insert($postData)) {
                    return $this->error('保存失败');
                }

            } else {

                return $this->error('当前控制器缺少属性（hisiModel、hisiTable至少定义一个）');

            }

            return $this->success('保存成功');
        }

        $template = $this->request->param('template', 'form');

        return $this->fetch($template);
    }

    /**
     * [通用方法]编辑页面展示和保存
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function edit()
    {

        $hisiModel = $this->request->param('hisiModel');
        $hisiTable = $this->request->param('hisiTable');

        if ($hisiModel) {
            $this->hisiModel = $hisiModel;
            $this->hisiTable = '';
        }

        if ($hisiTable) {
            $this->hisiTable = $hisiTable;
            $this->hisiModel = '';
        }

        if ($this->request->isPost()) {// 数据验证

            $hisiValidate   = $this->request->param('hisiValidate');
            $hisiScene      = $this->request->param('hisiScene');
            
            if ($hisiValidate) {
                $this->hisiValidate = $hisiValidate;
            }

            if ($hisiScene) {
                $this->hisiEditScene = $hisiScene;
            }

            $postData = $this->request->post();

            if ($this->hisiValidate) {

                if (strpos($this->hisiValidate, '\\') === false ) {

                    if (defined('IS_PLUGINS')) {
                        $this->hisiValidate = 'plugins\\'.$this->request->param('_p').'\\validate\\'.$this->hisiValidate;
                    } else {
                        $this->hisiValidate = 'app\\'.$this->request->module().'\\validate\\'.$this->hisiValidate;
                    }

                }

                if ($this->hisiEditScene) {
                    $this->hisiValidate = $this->hisiValidate.'.'.$this->hisiEditScene;
                }

                $result = $this->validate($postData, $this->hisiValidate);
                if ($result !== true) {
                    return $this->error($result);
                }

            }
        }

        if ($this->hisiModel) {// 通过Model更新

            if (defined('IS_PLUGINS')) {

                if (strpos($this->hisiModel, '\\') === false ) {
                    $this->hisiModel = 'plugins\\'.$this->request->param('_p').'\\model\\'.$this->hisiModel;
                }

                $model = new $this->hisiModel;

            } else {

                if (strpos($this->hisiModel, '/') === false ) {
                    $this->hisiModel = $this->request->module().'/'.$this->hisiModel;
                }

                $model = model($this->hisiModel);

            }

            $pk = $model->getPk();
            $id = $this->request->param($pk);
            
            if ($this->request->isPost()) {

                if ($model->save($postData, [$pk => $id]) === false) {
                    return $this->error($model->getError());
                }

                return $this->success('保存成功');
            }

            $formData = $model->get($id);

        } else if ($this->hisiTable) {// 通过Db更新

            $db = Db::name($this->hisiTable);
            $pk = $db->getPk();
            $id = $this->request->param($pk);

            if ($this->request->isPost()) {

                if (!$db->where($pk, $id)->update($postData)) {
                    return $this->error('保存失败');
                }

                return $this->success('保存成功');
            }

            $formData = $db->where($pk, $id)->find();

        } else {

            return $this->error('当前控制器缺少属性（hisiModel、hisiTable至少定义一个）');

        }

        $this->assign('formData', $formData);

        $template = $this->request->param('template', 'form');

        return $this->fetch($template);
    }

    /**
     * [通用方法]状态设置
     * 禁用、启用都是调用这个内部方法
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function status()
    {
        $val        = $this->request->param('val/d');
        $id         = $this->request->param('id/a');
        $field      = $this->request->param('field/s', 'status');
        $hisiModel  = $this->request->param('hisiModel');
        $hisiTable  = $this->request->param('hisiTable');

        if ($hisiModel) {
            $this->hisiModel = $hisiModel;
            $this->hisiTable = '';
        }

        if ($hisiTable) {
            $this->hisiTable = $hisiTable;
            $this->hisiModel = '';
        }

        if (empty($id)) {
            return $this->error('缺少id参数');
        }

        // 以下表操作需排除值为1的数据
        if ($this->hisiModel == 'SystemMenu') {

            if (in_array('1', $id) || in_array('2', $id) || in_array('3', $id)) {
                return $this->error('系统限制操作');
            }

        }
        
        if ($this->hisiModel) {

            if (defined('IS_PLUGINS')) {

                if (strpos($this->hisiModel, '\\') === false ) {
                    $this->hisiModel = 'plugins\\'.$this->request->param('_p').'\\model\\'.$this->hisiModel;
                }

                $obj = new $this->hisiModel;
                
            } else {

                if (strpos($this->hisiModel, '/') === false ) {
                    $this->hisiModel = $this->request->module().'/'.$this->hisiModel;
                }

                $obj = model($this->hisiModel);

            }

        } else if ($this->hisiTable) {

            $obj = db($this->hisiTable);

        } else {

            return $this->error('当前控制器缺少属性（hisiModel、hisiTable至少定义一个）');

        }
        
        $pk     = $obj->getPk();
        $result = $obj->where([$pk => $id])->setField($field, $val);

        if ($result === false) {
            return $this->error('状态设置失败');
        }

        return $this->success('状态设置成功');
    }

    /**
     * [通用方法]删除单条记录
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function del()
    {

        $id         = $this->request->param('id/a');
        $hisiModel  = $this->request->param('hisiModel');
        $hisiTable  = $this->request->param('hisiTable');

        if ($hisiModel) {
            $this->hisiModel = $hisiModel;
            $this->hisiTable = '';
        }

        if ($hisiTable) {
            $this->hisiTable = $hisiTable;
            $this->hisiModel = '';
        }

        if (empty($id)) {
            return $this->error('缺少id参数');
        }
        
        if ($this->hisiModel) {

            if (defined('IS_PLUGINS')) {

                if (strpos($this->hisiModel, '\\') === false ) {
                    $this->hisiModel = 'plugins\\'.$this->request->param('_p').'\\model\\'.$this->hisiModel;
                }

                $obj = new $this->hisiModel;
                
            } else {

                if (strpos($this->hisiModel, '/') === false ) {
                    $this->hisiModel = $this->request->module().'/'.$this->hisiModel;
                }

                $obj = model($this->hisiModel);

            }
            
            $row = $obj->withTrashed()->get($id);

            $result = $row->delete();

        } else if ($this->hisiTable) {

            $obj    = db($this->hisiTable);
            $pk     = $obj->getPk();
            $result = $obj->where([$pk => $id])->delete();

        } else {

            return $this->error('当前控制器缺少属性（hisiModel、hisiTable至少定义一个）');

        }

        if ($result === false) {
            return $this->error('删除失败');
        }

        return $this->success('删除成功');
    }

    /**
     * [通用方法]排序
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function sort()
    {
        $id         = $this->request->param('id/a');
        $field      = $this->request->param('field/s', 'sort');
        $val        = $this->request->param('val/d');
        $hisiModel  = $this->request->param('hisiModel');
        $hisiTable  = $this->request->param('hisiTable');

        if ($hisiModel) {
            $this->hisiModel = $hisiModel;
            $this->hisiTable = '';
        }

        if ($hisiTable) {
            $this->hisiTable = $hisiTable;
            $this->hisiModel = '';
        }

        if (empty($id)) {
            return $this->error('缺少id参数');
        }

        if ($this->hisiModel) {

            if (defined('IS_PLUGINS')) {

                if (strpos($this->hisiModel, '\\') === false ) {
                    $this->hisiModel = 'plugins\\'.$this->request->param('_p').'\\model\\'.$this->hisiModel;
                }

                $obj = new $this->hisiModel;
                
            } else {

                if (strpos($this->hisiModel, '/') === false ) {
                    $this->hisiModel = $this->request->module().'/'.$this->hisiModel;
                }

                $obj = model($this->hisiModel);

            }

        } else if ($this->hisiTable) {

            $obj = db($this->hisiTable);

        } else {

            return $this->error('当前控制器缺少属性（hisiModel、hisiTable至少定义一个）');

        }
        
        $pk     = $obj->getPk();
        $result = $obj->where([$pk => $id])->setField($field, $val);

        if ($result === false) {
            return $this->error('排序设置失败');
        }

        return $this->success('排序设置成功');
    }

    /**
     * [通用方法]上传附件
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function upload()
    {

        $model = new app\common\model\SystemAnnex;
        
        return json($model::fileUpload());

    }

    /**
     * [queryData description]
     * @param  string $queryType [查询类型：house、ban、tenant]
     * @param  array  $where     [条件：不同的查询器查询的条件不同
     *                           
     * ]
     * @return [type]            [description]
     */
    public function queryData()
    {
        if ($this->request->isAjax()) {
            $queryWhere = $this->request->param();
            if(!$queryWhere['type']){
                return $this->error('未设置查询类型！');
            }
            $DataModel = new DataModel;
            switch ($queryWhere['type']) {
                case 'ban':
                    $data = $DataModel->queryBan($queryWhere);
                    break;
                case 'tenant':
                    $data = $DataModel->queryTenant($queryWhere);
                    break;
                case 'house':
                    $data = $DataModel->queryHouse($queryWhere);
                    break;
                default:
                    break;
            }
            
            return json($data);
        }
    }


}
