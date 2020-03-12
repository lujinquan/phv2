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
use app\system\model\SystemNotice;

use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinNotice as WeixinNoticeModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 微信小程序用户版
 */
class Wechat extends Admin
{
	public function index()
	{
		
		return $this->fetch();
	}

	public function noticeIndex()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            //$getData = $this->request->get();
            $WeixinNoticeModel = new WeixinNoticeModel;
            //$where = $SystemNotice->checkWhere($getData);
            $data = [];
            $data['data'] = $WeixinNoticeModel->page($page)->order('sort asc')->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = $WeixinNoticeModel->count();
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);

        }
		return $this->fetch();
	}

	public function noticeAdd()
	{
		return $this->fetch();
	}

	public function noticeEdit()
	{
		return $this->fetch();
	}

	public function configIndex()
	{
		$group = input('group','user');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '用户版小程序',
                'url' => '?group=user',
            ],
            [
                'title' => '房管版小程序',
                'url' => '?group=base',
            ],
            [
                'title' => '高管版小程序',
                'url' => '?group=leader',
            ]
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch();
	}
}