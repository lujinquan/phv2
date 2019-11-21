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

use think\Validate;
use app\common\controller\Common;
use app\system\model\SystemUser as UserModel;

/**
 * 后台公共控制器
 * @package app\system\admin
 */
class Publics extends Common
{
    /**
     * 登录页面
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        $model = new UserModel;
        if ($this->request->isPost()) {

            /*关联1.0登录代码     开始 》》》*/
            $key = input('key');
            $name = input('username');
            if($key && $name){
                $re = $model->where([['username','eq',$name]])->find();
                $results = [];
                if($re){
                    $results['code'] = 1;
                    $results['msg'] = '工单系统登录成功！';
                    $results['url'] = get_domain().url('index/index');
                    $results['user_id'] = $re['id'];
                    $results['key'] = md5(md5($re));
                    //$reuslts['data'][] = ['url' => url('index/index')];
                    
                }else{
                    $results['code'] = 0;
                    $results['msg'] = '请联系超级管理员开通工单权限！';
                }
                return json($results);
                exit;
            }
            /*关联1.0登录代码 （注意删除if和else）    结束 《《《*/

            $username = $this->request->post('username/s');
            $password = $this->request->post('password/s');

            $data = $this->request->post();

            $validate = new Validate([
                'captcha|验证码' => 'require|captcha',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError(), url('index'));
            }
            if (!$model->login($username, $password)) {
                $data = [];
                $data['token'] = $this->request->token();
                return $this->error($model->getError(), url('index'), $data);
            }
            return $this->success('登录成功，页面跳转中...', url('index/index'));
        }

        if ($model->isLogin()) {
            $this->redirect(url('index/index', '', true, true));
        }

        $this->view->engine->layout(false);
        return $this->fetch();
    }

    /**
     * 退出登录
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function logout(){
        model('SystemUser')->logout();
        $this->redirect(ROOT_DIR);
    }


    /**
     * 图标选择
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function icon() {
        return $this->fetch();
    }

    /**
     * 解锁屏幕
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function unlocked()
    {
        $_pwd = $this->request->post('password/s');
        $model = model('SystemUser');
        $login = $model->isLogin();
        
        if (!$login) {
            return $this->error('登录信息失效，请重新登录！');
        }

        $password = $model->where('id', $login['uid'])->value('password');
        if (!$password) {
            return $this->error('登录异常，请重新登录！');
        }

        if (!password_verify($_pwd, $password)) {
            return $this->error('密码错误，请重新输入！');
        }

        return $this->success('解锁成功');
    }
}
