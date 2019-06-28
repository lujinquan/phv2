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


namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\common\model\Cparam as ParamModel;

class Map extends Admin
{
	public function index()
	{
		return $this->fetch();
	}
}