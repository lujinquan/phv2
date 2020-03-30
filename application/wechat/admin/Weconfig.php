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
use app\wechat\model\WeixinGuide as WeixinGuideModel;
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
            		$WeixinGuideModel = new WeixinGuideModel;
                    $where = $WeixinGuideModel->checkWhere($getData);
                    $data = [];
                    $data['data'] = $WeixinGuideModel->where($where)->page($page)->order('sort asc')->limit($limit)->select();
                    $data['count'] = $WeixinGuideModel->where($where)->count();
            
            		break;
            	case 'service_conf':
                    $is_show = input('is_show');
                    $value = input('value');
                    if(!$value){
                        $this->error('内容不能为空');
                    }
            		Db::name('weixin_service_config')->where([['id','eq',1]])->update(['is_show'=>$is_show,'value'=>$value]);
                    $this->success('修改成功');
            		break;
            	default:
            		# code...
            		break;
            }
            
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);

        }

        switch ($group) {
            case 'banner_conf':
                
                break;
            case 'pageurl_conf':
                # code...
                break;
            case 'guide_conf':
                # code...
                break;
            case 'service_conf':
                $row = Db::name('weixin_service_config')->find();
                //halt($row);
                $this->assign('data_info',$row);
                break;
            default:
                # code...
                break;
        }

        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch($group);
	}

    public function guideAdd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinGuide');
            if($result !== true) {
                return $this->error($result);
            }
            $WeixinGuideModel = new WeixinGuideModel;
            $data['cuid'] = ADMIN_ID;
            $data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$WeixinGuideModel->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功');
        }
        return $this->fetch();
    }

    public function guideEdit()
    {
        $WeixinGuideModel = new WeixinGuideModel;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinGuide');
            if($result !== true) {
                return $this->error($result);
            }
            // 入库
            if (!$WeixinGuideModel->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $WeixinGuideModel->find($id);
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function guideDetail()
    {
        $WeixinGuideModel = new WeixinGuideModel;
        $id = input('param.id/d');
        $row = $WeixinGuideModel->find($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function guideDel()
    {
        $ids = $this->request->param('id/a'); 
        $WeixinGuideModel = new WeixinGuideModel;       
        $res = $WeixinGuideModel->where([['id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
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
    public function guideIsShow()
    {
        $id = input('id');
        $WeixinGuideModel = new WeixinGuideModel;
        $info = $WeixinGuideModel->find($id);
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