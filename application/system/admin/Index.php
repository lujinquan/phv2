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

use Env;
use hisi\Dir;
use app\system\model\SystemNotice;

/**
 * 后台默认首页控制器
 * @package app\system\admin
 */

class Index extends Admin
{
    /**
     * 首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->post();
            $systemNotice = new SystemNotice;
            $where = $systemNotice->checkWhere($getData);
            $data['data'] = $systemNotice->field('id,title,type,content,cuid,reads,create_time')->where($where)->page($page)->order('sort asc')->limit($limit)->select();
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }else{
           if (cookie('hisi_iframe')) {  //单页面模式
                $this->view->engine->layout(false);
                return $this->fetch('iframe');
            } else { //ifram模式
                return $this->fetch();
            } 
        }
        
        
    }
    //楼栋选择器
	public function querier()
	{
		return $this->fetch('block/queriers/building');
	}
	//楼栋调整——楼栋选择器
	public function queriers()
	{
		return $this->fetch('block/queriers/buildings');
	}
	 //租户选择器
	public function tenant()
	{
		return $this->fetch('block/queriers/tenant');
	}
	 //房屋选择器
	public function house()
	{
        $change_type = input('param.change_type'); // 
        $this->assign('change_type',$change_type);
		return $this->fetch('block/queriers/house');
	}
	//异动注销查询器
	public function cancellation()
	{
		return $this->fetch('block/queriers/cancellation');
	}
	//楼栋选择器多选
	public function cancellations()
	{
		return $this->fetch('block/queriers/cancellations');
	}
    /**
     * 欢迎首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function welcome()
    {
        return $this->fetch('index');
    }

    /**
     * 欢迎首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function help()
    {
        return $this->fetch('./templete/helpcenter/index.html');
    }

    /**
     * 清理缓存
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function clear()
    {
        $path   = Env::get('runtime_path');
        // $cache  = $this->request->param('cache/d', 0);
        // $log    = $this->request->param('log/d', 0);
        // $temp   = $this->request->param('temp/d', 0);

        // if ($cache == 1) {
        //     Dir::delDir($path.'cache');
        // }

        // if ($temp == 1) {
        //     Dir::delDir($path.'temp');
        // }

        // if ($log == 1) {
        //     Dir::delDir($path.'log');
        // }

        Dir::delDir($path.'cache');
        Dir::delDir($path.'temp');
        Dir::delDir($path.'log');
        //(new \app\common\model\SystemAnnex)->clearAnnex();

        return $this->success('清除成功');
    }
}
