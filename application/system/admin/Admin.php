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

        // 判断登陆
        $login = $model->isLogin();
        if (!$login['uid']) {
            return $this->error('请登陆之后在操作', ROOT_DIR.config('sys.admin_path'));
        }

        define('INST',session('admin_user.inst_id'));
        define('INST_LEVEL',session('admin_user.inst_level'));
        if (!defined('ADMIN_ID')) {
            define('ADMIN_ID', $login['uid']);
            define('ADMIN_ROLE', $login['role_id']);
        
            $curMenu = MenuModel::getInfo();
            if ($curMenu) {

                if (!RoleModel::checkAuth($curMenu['id']) && 
                    $curMenu['url'] != 'system/index/index') {
                    return $this->error('['.$curMenu['title'].'] 访问权限不足');
                }
                
            } else if (config('sys.admin_whitelist_verify')) {

                return $this->error('节点不存在或者已禁用！');

            } else {

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
                //halt(MenuModel::getMainMenu());
                // 获取面包屑导航
                $breadCrumbs = MenuModel::getBrandCrumbs($curMenu['id']);
                $this->assign('hisiBreadcrumb', $breadCrumbs);
                // 获取当前访问的菜单信息
                $this->assign('hisiCurMenu', $curMenu);
                // 获取当前菜单的顶级节点
                $this->assign('hisiCurParents', $menuParents);
                // 获取导航菜单
                //halt(MenuModel::getMainMenu());
                $this->assign('hisiMenus', MenuModel::getMainMenu());
                // 分组切换类型 0无需分组切换，1单个分组，2分组切换[无链接]，3分组切换[有链接]，具体请看后台layout.html
                $this->assign('hisiTabType', 0);
                // 获取所有参数
                $params = ParamModel::getCparams();
                //$users = session('systemusers');
                //halt($users[47]);
                $this->assign('inst_level',INST_LEVEL);
                $this->assign('params',$params);
                $this->assign('systemusers',session('systemusers'));
                // 当前用户的权限
                $this->assign('auths',$auths);
                $this->assign('paramsJson',json_encode($params));
//halt($auths);
                //当前用户是否拥有“提交工单”权限
                //$addOrderAuthBool = RoleModel::checkAuth(218);
                $this->assign('addOrderAuthBool',in_array(218,$auths));
                //当前用户是否拥有“我的工单”权限
                //$myOrderAuthBool = RoleModel::checkAuth(219);
                $this->assign('myOrderAuthBool',in_array(219,$auths));
                //halt(json_encode($params));
                // tab切换数据
                // $hisiTabData = [
                //     ['title' => '后台首页', 'url' => 'system/index/index'],
                // ];
                // current 可不传
                // $this->assign('hisiTabData', ['menu' => $hisiTabData, 'current' => 'system/index/index']);
                
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

            $queryWhere = $this->request->get();
            
            $queryType = input('param.type', 'house');
            $changeType = input('param.change_type');
            $queryTypeArr = ['house','ban','tenant'];
            if(!in_array($queryType,$queryTypeArr)){
                return '查询器类型不合法';
            }
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $where = [];

            switch ($queryType) {
                case 'ban':
                    $where[] = [
                        ['ban_status','eq',1], // 默认查询正常状态下的房屋
                    ];
                    $where[] = [
                        ['ban_inst_id','in',config('inst_ids')[INST]], // 默认查询当前机构下的房屋
                    ];
                    
                    if(isset($queryWhere['ban_number']) && $queryWhere['ban_number']){ //查询楼栋编号
                        $where[] = ['ban_number','like','%'.$queryWhere['ban_number'].'%'];
                    }
                    if(isset($queryWhere['ban_address']) && $queryWhere['ban_address']){ //查询楼栋地址
                        $where['ban'][] = ['ban_address','like','%'.$queryWhere['ban_address'].'%'];
                    }
                    if(isset($queryWhere['ban_inst_id']) && $queryWhere['ban_inst_id']){ //查询机构
                        $where[] = ['ban_inst_id','in',config('inst_ids')[$queryWhere['ban_inst_id']]];
                    }
                    if(isset($queryWhere['ban_status'])){ //查询房屋状态
                        $where[] = ['ban_status','eq',$queryWhere['ban_status']];
                    }

                    $BanModel = new BanModel;

                    $fields = 'ban_id,ban_number,ban_inst_id,ban_address,ban_owner_id,ban_damage_id,ban_struct_id,ban_civil_area,ban_party_area,ban_career_area,(ban_civil_area + ban_party_area + ban_career_area) as ban_area,ban_civil_num,ban_party_num,ban_career_num,(ban_civil_num+ban_party_num+ban_career_num) as ban_num,ban_civil_rent,ban_party_rent,ban_career_rent,(ban_civil_rent + ban_party_rent + ban_career_rent) as ban_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_use_area';

                    $data = [];
                    //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
                    $data['data'] = $BanModel->field($fields)->where($where)->page($page)->order('ban_ctime desc')->limit($limit)->select();
                    $data['count'] = $BanModel->where($where)->count();

                    break;

                case 'tenant':
                    $where[] = [
                        ['tenant_status','eq',1], // 默认查询正常状态下的租户
                    ];
                    $where[] = [
                        ['tenant_inst_id','in',config('inst_ids')[INST]], // 默认查询当前机构下的租户
                    ];
                    
                    if(isset($queryWhere['tenant_number']) && $queryWhere['tenant_number']){ //查询租户编号
                        $where[] = ['tenant_number','like','%'.$queryWhere['tenant_number'].'%'];
                    }
                    if(isset($queryWhere['tenant_name']) && $queryWhere['tenant_name']){ //查询租户姓名
                        $where['ban'][] = ['tenant_name','like','%'.$queryWhere['tenant_name'].'%'];
                    }
                    if(isset($queryWhere['tenant_inst_id']) && $queryWhere['tenant_inst_id']){ //查询机构
                        $where[] = ['tenant_inst_id','in',config('inst_ids')[$queryWhere['tenant_inst_id']]];
                    }
                    if(isset($queryWhere['tenant_status'])){ //查询房屋状态
                        $where[] = ['tenant_status','eq',$queryWhere['tenant_status']];
                    }
                    
                    $TenantModel = new TenantModel;

                    $fields = 'tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card';

                    $data = [];
                    //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
                    $data['data'] = $TenantModel->field($fields)->where($where)->page($page)->order('tenant_ctime desc')->limit($limit)->select();
                    $data['count'] = $TenantModel->where($where)->count();

                    break;

                case 'house':
                    $where['house'] = [
                        ['house_status','eq',1], // 默认查询正常状态下的房屋
                    ];
                    $where['ban'] = [
                        ['ban_inst_id','in',config('inst_ids')[INST]], // 默认查询当前机构下的房屋
                    ];
                    $where['tenant'] = [
                    ];
                    if(isset($queryWhere['house_number']) && $queryWhere['house_number']){ //查询房屋编号
                        $where['house'][] = ['house_number','like','%'.$queryWhere['house_number'].'%'];
                    }
                    if(isset($queryWhere['tenant_name']) && $queryWhere['tenant_name']){ //查询租户姓名
                        $where['tenant'][] = ['tenant_name','like','%'.$queryWhere['tenant_name'].'%'];
                    }
                    if(isset($queryWhere['ban_address']) && $queryWhere['ban_address']){ //查询楼栋地址
                        $where['ban'][] = ['ban_address','like','%'.$queryWhere['ban_address'].'%'];
                    }
                    if(isset($queryWhere['ban_number']) && $queryWhere['ban_number']){ //查询楼栋编号
                        $where['ban'][] = ['ban_number','like','%'.$queryWhere['ban_number'].'%'];
                    }
                    if(isset($queryWhere['ban_inst_id']) && $queryWhere['ban_inst_id']){ //查询机构
                        $where['ban'][] = ['ban_inst_id','in',config('inst_ids')[$queryWhere['ban_inst_id']]];
                    }
                    if(isset($queryWhere['house_status'])){ //查询房屋状态
                        $where['house'][] = ['house_status','eq',$queryWhere['house_status']];
                    }
                    
                    if($changeType){ //如果异动类型有值，则验证房屋是否符合暂停计租要求
                        switch ($changeType) {
                            case 4: //陈欠核销 【待优化】
                                $houseids = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->group('house_id')->column('house_id');
                                //halt($houseids);
                                $where['house'][] = ['house_id','in',$houseids];
                                $where['house'][] = ['house_status','eq',1];
                                break;

                            case 13: //使用权变更
                                $where['house'][] = ['house_status','eq',1];
                                break;

                            case 16: //减免年审
                                $houseids = Db::name('change_cut')->where([['change_status','eq',1]])->column('house_id');
                                $where['house'][] = ['house_id','in',$houseids];
                                $where['house'][] = ['house_status','eq',1];
                                break;
                            
                            default:
                                # code...
                                break;
                        }
                        
                    }

                    $HouseModel = new HouseModel;

                    $fields = 'house_id,house_balance,house_pre_rent,house_cou_rent,house_use_id,house_unit_id,house_floor_id,house_lease_area,house_area,(house_pre_rent + house_diff_rent + house_pump_rent) as house_yue_rent';

                    $data = [];
                    //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
                    $data['data'] = $HouseModel->withJoin([
                         'ban'=> function($query)use($where){ //注意闭包传参的方式
                             $query->where($where['ban']);
                         },
                         'tenant'=> function($query)use($where){
                             $query->where($where['tenant']);
                         },
                         ],'left')->field($fields)->where($where['house'])->page($page)->order('house_ctime desc')->limit($limit)->select();
                    $data['count'] = $HouseModel->withJoin([
                         'ban'=> function($query)use($where){ //注意闭包传参的方式
                             $query->where($where['ban']);
                         },
                         ],'left')->where($where['house'])->count();

                    break;
                
                default:
                    # code...
                    break;
            }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
    }


}
