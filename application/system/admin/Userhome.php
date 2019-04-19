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


/**
 * 后台管理员个人中心
 * @package app\system\admin
 */
class Userhome extends Admin
{
	/**
     * 我的工单
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
    	return $this->fetch();
    }

    /**
     * 添加工单
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add()
    {
    	return $this->fetch('form');
    }
}