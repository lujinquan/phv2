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
use app\system\model\SystemAffiche;

use think\Db;

/**
 * 系统公告控制器
 * @package app\system\admin
 */
class Affiche extends Admin
{

    /**
     * 初始化方法
     */
    public function index()
    {
    	return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function edit()
    {
        return $this->fetch('form');
    }

    public function detail()
    {
        return $this->fetch();
    }

    public function del()
    {
        
    }

}
