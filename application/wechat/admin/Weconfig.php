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

namespace app\wechat\admin;

use think\Db;
use app\system\admin\Admin;
use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 微信小程序用户版
 */
class Weconfig extends Admin
{
	public function index()
	{
		$group = input('group','index');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '小程序设置',
                'url' => '?group=index',
            ],
            [
                'title' => '幻灯片管理',
                'url' => '?group=banner_conf',
            ],
            [
                'title' => '小程序路径',
                'url' => '?group=pageurl_conf',
            ],
            [
                'title' => '办事指引',
                'url' => '?group=guide_conf',
            ],
            [
                'title' => '服务协议',
                'url' => '?group=service_conf',
            ]
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch($group);
	}

	// public function baseConf()
	// {
	// 	return $this->fetch();
	// }

	// public function bannerConf()
	// {
	// 	return $this->fetch();
	// }

	// public function pageurlConf()
	// {
	// 	return $this->fetch();
	// }
	// public function guideConf()
	// {
	// 	return $this->fetch();
	// }
	// public function serviceConf()
	// {
	// 	return $this->fetch();
	// }
}