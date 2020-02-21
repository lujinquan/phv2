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

namespace app\weichat\admin;

use think\Db;
use app\system\admin\Admin;
use app\weichat\model\Weixin as WeixinModel;

/**
 * 微信小程序用户版
 */
class Weixin extends Admin
{
	public function userIndex()
	{
		return $this->fetch();
	}

	public function noticeIndex()
	{
		return $this->fetch();
	}

	public function tempIndex()
	{
		return $this->fetch();
	}

	public function payRecord()
	{
		return $this->fetch();
	}
}