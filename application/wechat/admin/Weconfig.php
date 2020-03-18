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
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinBanner as WeixinBannerModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 微信小程序用户版
 */
class Weconfig extends Admin
{
	public function index()
	{
		$group = input('group','banner_conf');
        $tabData = [];
        $tabData['menu'] = [
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
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            switch ($group) {
            	case 'banner_conf':
            		$WeixinBannerModel = new WeixinBannerModel;
		            $where = $WeixinBannerModel->checkWhere($getData);
		            $data = [];
		            $temp = $WeixinBannerModel->where($where)->page($page)->order('sort asc')->limit($limit)->select()->toArray();
		            foreach ($temp as $k => &$v) {
		            	$SystemAnnex = new SystemAnnex;
		            	$v['file'] = $SystemAnnex->where([['id','eq',$v['banner_img']]])->value('file');
		            	//halt($v);
		            }
		            $data['data'] = $temp;
		            $data['count'] = $WeixinBannerModel->where($where)->count();
            		break;
            	case 'pageurl_conf':
            		# code...
            		break;
            	case 'guide_conf':
            		# code...
            		break;
            	case 'service_conf':
            		# code...
            		break;
            	default:
            		# code...
            		break;
            }
            
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);

        }
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch($group);
	}

	public function bannerAdd()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinBanner');
            if($result !== true) {
                return $this->error($result);
            }
            $WeixinBannerModel = new WeixinBannerModel;
            if(isset($data['file']) && $data['file']){
                $data['banner_img'] = $data['file'];
            }
            //halt($data);
            // 入库
            if (!$WeixinBannerModel->allowField(true)->create($data)) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
		return $this->fetch();
	}

	public function bannerEdit()
	{
        $WeixinBannerModel = new WeixinBannerModel;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinBanner');
            if($result !== true) {
                return $this->error($result);
            }
            if(isset($data['file']) && $data['file']){
                $data['banner_img'] = $data['file'];
            }
            // 入库
            if (!$WeixinBannerModel->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $WeixinBannerModel->find($id);
        $row['file'] = SystemAnnex::where([['id','eq',$row['banner_img']]])->value('file');;
        //halt($row);
        $this->assign('data_info',$row);
		return $this->fetch();
	}

	public function bannerDel()
	{
		$ids = $this->request->param('id/a'); 
        $WeixinBannerModel = new WeixinBannerModel;       
        $res = $WeixinBannerModel->where([['id','in',$ids]])->delete();
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
        $WeixinBannerModel = new WeixinBannerModel;
        $info = $WeixinBannerModel->find($id);
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
	
}