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
use app\wechat\model\WeixinConfig as WeixinConfigModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 微信小程序用户版
 */
class Wechat extends Admin
{
	public function index()
	{
		$group = input('group','index');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '用户版小程序',
                'url' => '?group=index',
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
		return $this->fetch($group);
	}

	public function noticeIndex()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinNoticeModel = new WeixinNoticeModel;
            $where = $WeixinNoticeModel->checkWhere($getData);
            $data = [];
            $data['data'] = $WeixinNoticeModel->where($where)->page($page)->order('sort asc')->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = $WeixinNoticeModel->where($where)->count();
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);

        }
		return $this->fetch();
	}

	public function noticeAdd()
	{
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinNotice');
            if($result !== true) {
                return $this->error($result);
            }
            $WeixinNoticeModel = new WeixinNoticeModel;
            $data['cuid'] = ADMIN_ID;
            $data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$WeixinNoticeModel->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功');
        }
		return $this->fetch();
	}

	public function noticeEdit()
	{
        $WeixinNoticeModel = new WeixinNoticeModel;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinNotice');
            if($result !== true) {
                return $this->error($result);
            }
            // 入库
            if (!$WeixinNoticeModel->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $WeixinNoticeModel->find($id);
        //halt($row);
        $this->assign('data_info',$row);
		return $this->fetch();
	}

    public function noticeDetail()
    {
        $WeixinNoticeModel = new WeixinNoticeModel;
        $id = input('param.id/d');
        $row = $WeixinNoticeModel->find($id);
        //halt($row);
        //$systemusers = session('systemusers');
        //halt($systemusers[1]);
        // 更新已读记录 
        //$WeixinNoticeModel->updateReads($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function noticeDel()
    {
        $ids = $this->request->param('id/a'); 
        $WeixinNoticeModel = new WeixinNoticeModel;       
        $res = $WeixinNoticeModel->where([['id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 功能描述：启用禁用状态切换
     * @author  Lucas 
     * 创建时间: 2020-03-09 16:30:34
     */
    public function isShow()
    {
        $id = input('id');
        $WeixinNoticeModel = new WeixinNoticeModel;
        $info = $WeixinNoticeModel->find($id);
        if($info->is_show == 1){
            $info->is_show = 0;
            $msg = '禁用成功！';
        }else{
            $info->is_show = 1;
            $msg = '启用成功！';
        }
        $result = $info->save();
        if ($result === false) {
            return $this->error('状态设置失败');
        }

        return $this->success($msg);
    }

	public function configIndex()
	{
		$group = input('group','index');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '用户版小程序',
                'url' => '?group=index',
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
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //halt($data);
            // 数据验证
            // $result = $this->validate($data, 'Ban.edit');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // if(isset($data['file']) && $data['file']){
            //     $data['ban_imgs'] = implode(',',$data['file']);
            // }else{
            //     $data['ban_imgs'] = '';
            // }
            $WeixinConfigModel = new WeixinConfigModel();
            foreach ($data as $key => $value) {
                $WeixinConfigModel->where([['name','eq',$key]])->update(['value'=>$value]);
            }
            //halt($data);
            // 入库
            // if (!$WeixinConfigModel->allowField(true)->update($data)) {
            //     return $this->error('修改失败');
            // }
            return $this->success('修改成功');
        }
        $data = WeixinConfigModel::column('name,value');
        // if($group == 'index'){ // 用户版小程序
            
        //     //halt($list);
        // }elseif($group == 'base'){

        // }elseif($group == 'leader'){

        // }
        $tabData['current'] = url('?group='.$group);
        $this->assign('data_info',$data);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch();
	}
}