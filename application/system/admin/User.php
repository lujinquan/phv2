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

use think\Db;
use app\system\model\SystemUser as UserModel;
use app\system\model\SystemRole as RoleModel;
use app\system\model\SystemMenu as MenuModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;

/**
 * 后台用户、角色控制器
 * @package app\system\admin
 */
class User extends Admin
{
    public $tabData = [];
    protected $hisiTable = 'SystemUser';
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();

        $tabData['menu'] = [
            [
                'title' => '管理员角色',
                'url' => 'system/user/role',
            ],
            [
                'title' => '系统管理员',
                'url' => 'system/user/index',
            ],
        ];
        $this->tabData = $tabData;
    }

    /**
     * 用户管理
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index($q = '')
    {
        //$s = UserModel::with('role')->column('id,role_id,nick');
        // $s = UserModel::with('role')->select();
        // halt($s);
        if ($this->request->isAjax()) {
            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 10);
            $queryData    = $this->request->param();
            $where[]    = ['a.id', 'neq', 1];
            if (isset($queryData['username']) && $queryData['username']) {
                $where[] = ['a.username', 'like', "%{$queryData['username']}%"];
            }
            if (isset($queryData['mobile']) && $queryData['mobile']) {
                $where[] = ['a.mobile', 'like', "%{$queryData['mobile']}%"];
            }
            if (isset($queryData['inst_id']) && $queryData['inst_id']) {
                $where[] = ['a.inst_id', 'eq', $queryData['inst_id']];
            }
            if (isset($queryData['role_id']) && $queryData['role_id']) {
                $where[] = ['a.role_id', 'eq', $queryData['role_id']];
            }
            $fields = "a.id,a.username,a.weixin_member_id,a.mobile,a.nick,a.last_login_time,a.ctime,a.inst_id,a.last_login_ip,a.intro,a.status,b.name";
            $data['data'] = UserModel::alias('a')->join('system_role b','a.role_id = b.id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('inst_id asc,a.ctime desc')->select();
            foreach($data['data'] as &$t){
                $weixin_member_id = explode(',',$t['weixin_member_id']);
                $WeixinMemberModel = new WeixinMemberModel;
                $t['weixin_member_name'] = $WeixinMemberModel->where([['member_id','in',$weixin_member_id]])->value('GROUP_CONCAT(member_name)');
            }
            //halt($data['data']);
            $data['count'] = UserModel::alias('a')->join('system_role b','a.role_id = b.id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $roleArr = RoleModel::where([['status','eq',1]])->column('id,name');
        $this->assign('hisiTabData', $this->tabData);
        $this->assign('roleArr', $roleArr);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }
	
	public function wechatAuth()
	{
        $id = input('id');
        if ($this->request->isPost()) {
            $weixin_member_id = input('weixin_member_id');
            if(empty($weixin_member_id)){
                return $this->error('参数错误');
            }else{
                if (!UserModel::where([['id','eq',$id]])->update(['weixin_member_id'=>$weixin_member_id])) {
                    return $this->error('绑定失败');
                }
                return $this->success('绑定成功');
            }
        }
        
        $this->assign('id', $id);
		return $this->fetch();
	}

    /**
     * 布局切换
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function iframe()
    {
        $val = UserModel::where('id', ADMIN_ID)->value('iframe');
        if ($val == 1) {
            $val = 0;
        } else {
            $val = 1;
        }
        if (!UserModel::where('id', ADMIN_ID)->setField('iframe', $val)) {
            return $this->error('切换失败');
        }
        cookie('hisi_iframe', $val);
        return $this->success('请稍等，页面切换中...', url('system/index/index'));
    }

    /**
     * 主题设置
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function setTheme()
    {
        $theme = $this->request->param('theme/d', 0);
        if (UserModel::setTheme($theme, true) === false) {
            return $this->error('设置失败');
        }
        return $this->success('设置成功');
    }

    /**
     * 添加用户
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function addUser()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if($data['role_id'] == 11){
                if(!isset($data['inst_ids']) || !$data['inst_ids']){
                    return $this->error('请为运营人员配置管辖管段！');
                }else{
                    $data['inst_ids'] = implode(',',$data['inst_ids']);
                }
            }
            if(!$data['password']){
                return $this->error('密码不能为空');
            }
            // 效验弱口令
            if(!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,15}$/',$data['password'])){
                return $this->error('密码必须为6-15位的数字和字母的组合');
            }
            // 优化强化密码口令
            // if(!preg_match('/(?=^.{6,15}$)(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#$%^&*]).*$/',$data['password'])){
            //     return $this->error('6-15位，至少有一个数字，一个大写字母，一个小写字母和一个特殊字符（包括!@#$%^&*），四个任意组合');
            // }
            $data['password'] = md5($data['password']);
            $data['password_confirm'] = md5($data['password_confirm']);
            // 验证
            $result = $this->validate($data, 'SystemUser');
            if($result !== true) {
                return $this->error($result);
            }
            if($data['inst_id'] == 1){
                $data['inst_level'] = 1;
            }else if($data['inst_id']<4){
                $data['inst_level'] = 2;
            }else{
                $data['inst_level'] = 3;
            }
            $number = UserModel::max('number');
            $data['number'] = $number + 1;
            unset($data['id'], $data['password_confirm']);
            $data['last_login_ip'] = '';
            $data['auth'] = '';
            if (!UserModel::create($data)) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
        
        $this->assign('menu_list', '');
        $this->assign('roleOptions', RoleModel::getOption());

        return $this->fetch('userform');
    }

    /**
     * 绑定微信
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间:  <-- 这里输入 ctrl + shift + . 自动生成当前时间戳
     * @return  返回值  
     * @version 版本  1.0
     */    
    public function bindWeixin()
    {
        $id = input('id');
        $row = UserModel::where('id', $id)->field('mobile,nick')->find();
        if($row['mobile']){
            $WeixinMemberModel = new WeixinMemberModel;
            $find = $WeixinMemberModel->where([['weixin_tel','eq',$row['mobile']]])->find();
            if($find){
                $row->weixin_openid = $find['openid'];
                if($row->save()){
                    return $this->success('授权成功！');
                }else{
                    return $this->error('未知错误');
                }
            }else{
                return $this->error('请用户“'.$row['nick'].'”先登录小程序并授权手机号，或检查手机号是否与系统一致');
            }
        }else{
            return $this->error('请先补充当前用户“'.$row['nick'].'”的手机号');
        }
    }

    /**
     * 修改用户
     * @param int $id
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function editUser($id = 0)
    {
        if ($id == 1 && ADMIN_ID != $id) {
            return $this->error('禁止修改超级管理员');
        }
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            if (!isset($data['auth'])) {
                $data['auth'] = '';
            }
            
            $row = UserModel::where('id', $id)->field('role_id,auth')->find();
            if ($data['id'] == 1 || ADMIN_ID == $id) {// 禁止更改超管角色，当前登录用户不可更改自己的角色和自定义权限
                unset($data['role_id'], $data['auth']);
                if (!$row['auth']) {
                    $data['auth'] = '';
                }
            } else if ($row['role_id'] != $data['role_id']) {// 如果分组不同，自定义权限无效
                $data['auth'] = '';
            }
            if (isset($data['inst_ids']) && $data['inst_ids']) {
                $data['inst_ids'] = implode(',',$data['inst_ids']);
            }
            if($data['inst_id'] == 1){
                $data['inst_level'] = 1;
            }else if($data['inst_id']<4){
                $data['inst_level'] = 2;
            }else{
                $data['inst_level'] = 3;
            }

            if (isset($data['role_id']) && RoleModel::where('id', $data['role_id'])->value('auth') == json_encode($data['auth'])) {// 如果自定义权限与角色权限一致，则设置自定义权限为空
                $data['auth'] = '';
            }

            if ($data['password']) {
                $data['password'] = md5($data['password']);
                $data['password_confirm'] = md5($data['password_confirm']);
            } else{
                unset($data['password']);
            }
            
            // 验证
            $result = $this->validate($data, 'SystemUser.update');
            if($result !== true) {
                return $this->error($result);
            }
//halt($data);
            if (!UserModel::update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }

        $row = UserModel::where('id', $id)->field('id,username,role_id,inst_id,inst_ids,intro,nick,email,mobile,auth,status')->find()->toArray();
        if (!$row['auth']) {
            $auth = RoleModel::where('id', $row['role_id'])->value('auth');
            $row['auth'] = json_decode($auth);
        } else {
            $row['auth'] = json_decode($row['auth']);
        }
        if ($row['inst_ids']) {
            $row['inst_ids'] = explode(',',$row['inst_ids']);
        }
        //halt($row);
        $tabData = [];
        $tabData['menu'] = [
            ['title' => '修改管理员'],
            ['title' => '设置权限'],
        ];

        $this->assign('menu_list', MenuModel::getAllChild());
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 2);
        $this->assign('roleOptions', RoleModel::getOption($row['role_id']));
        $this->assign('formData', $row);
        return $this->fetch('userform');
    }

    /**
     * 修改个人信息
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function info()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['id'] = ADMIN_ID;
            // 防止伪造篡改
            unset($data['role_id'], $data['status']);

            if ($data['password']) {
                // 效验弱口令
                if(!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,15}$/',$data['password'])){
                    return $this->error('密码必须为6-15位的数字和字母的组合');
                }
                // 优化强化密码口令
                // if(!preg_match('/(?=^.{6,15}$)(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#$%^&*]).*$/',$data['password'])){
                //     return $this->error('6-15位，至少有一个数字，一个大写字母，一个小写字母和一个特殊字符（包括!@#$%^&*），四个任意组合');
                // }
                $data['password'] = md5($data['password']);
                $data['password_confirm'] = md5($data['password_confirm']);
            } else {
                unset($data['password']);
            }

            // 验证
            $result = $this->validate($data, 'SystemUser.info');
            if($result !== true) {
                return $this->error($result);
            }

            if (!UserModel::update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }

        $row = UserModel::where('id', ADMIN_ID)->field('username,nick,email,mobile')->find()->toArray();
        $this->assign('formData', $row);
        return $this->fetch();
    }

    /**
     * 删除用户
     * @param int $id
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function delUser()
    {
        $ids   = $this->request->param('id/a');
        $model = new UserModel();
        if ($model->del($ids)) {
            return $this->success('删除成功');
        }
        return $this->error($model->getError());
    }

    // +----------------------------------------------------------------------
    // | 角色
    // +----------------------------------------------------------------------

    /**
     * 角色管理
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function role()
    {
        if ($this->request->isAjax()) {
            $data = [];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);

            $data['data'] = RoleModel::where('id', '<>', 1)->order('sort asc')->select();
            $data['count'] = RoleModel::where('id', '<>', 1)->count('id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }

        $this->assign('hisiTabData', $this->tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    /**
     * 添加角色
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function addRole()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'SystemRole');
            if($result !== true) {
                return $this->error($result);
            }
            unset($data['id']);
            if (!RoleModel::create($data)) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
        $tabData = [];
        $tabData['menu'] = [
            ['title' => '添加角色'],
            ['title' => '设置权限'],
        ];
        $this->assign('menu_list', MenuModel::getAllChild());
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 2);
        return $this->fetch('roleform');
    }

    /**
     * 修改角色
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function editRole($id = 0)
    {
        if ($id <= 1) {
            return $this->error('禁止编辑');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 当前登录用户不可更改自己的分组角色
            if (ADMIN_ROLE == $data['id']) {
                return $this->error('禁止修改当前角色(原因：您不是超级管理员)');
            }

            // 验证
            $result = $this->validate($data, 'SystemRole');
            if($result !== true) {
                return $this->error($result);
            }
            if (!RoleModel::update($data)) {
                return $this->error('修改失败');
            }

            // 更新权限缓存
            cache('role_auth_'.$data['id'], $data['auth']);

            return $this->success('修改成功');
        }
        $tabData = [];
        $tabData['menu'] = [
            ['title' => '修改角色'],
            ['title' => '设置权限'],
        ];
        $row = RoleModel::where('id', $id)->field('id,name,intro,auth,status')->find()->toArray();
        $row['auth'] = json_decode($row['auth']);
        $this->assign('formData', $row);
        $this->assign('menu_list', MenuModel::getAllChild());
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 2);
        return $this->fetch('roleform');
    }

    public function statusRole()
    {
        $this->hisiTable = 'SystemRole';
        parent::status();
    }

    /**
     * 删除角色
     * @param int $id
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function delRole()
    {
        $ids   = $this->request->param('id/a');
        $model = new RoleModel();
        if ($model->del($ids)) {
            return $this->success('删除成功');
        }
        return $this->error($model->getError());
    }
}
