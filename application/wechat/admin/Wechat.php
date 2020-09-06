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
use app\wechat\model\WeixinTemplate as WeixinTemplateModel;
use app\wechat\model\WeixinReadRecord as WeixinReadRecordModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;


/**
 * 微信小程序用户版
 */
class Wechat extends Admin
{

    /**
     * 订阅消息模板列表
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:54:52
     * @return  返回值  
     * @version 版本  1.0
     */
	public function index()
	{
		$group = input('group','index');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            // $result = $this->validate($data, 'WeixinNotice');
            // if($result !== true) {
            //     return $this->error($result);
            // }   
            foreach ($data as $k => $v) {
                if($v){
                    $WeixinTemplateModel = new WeixinTemplateModel;
                    $WeixinTemplateModel->where([['name','eq',$k]])->update(['value'=>$v]);
                }
            }
            return $this->success('提交成功');
        }
		return $this->fetch($group);
	}

    /**
     * 【列表】微信公告
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:41:57
     * @return  返回值  
     * @version 版本  1.0
     */
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

    /**
     * 【新增】微信公告
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:41:57
     * @return  返回值  
     * @version 版本  1.0
     */
	public function noticeAdd()
	{
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'WeixinNotice');
            if($result !== true) {
                return $this->error($result);
            }
            $data['content'] = $_POST['content']; //用原始的方法接收带标签的数据
            $WeixinNoticeModel = new WeixinNoticeModel;
            $data['cuid'] = ADMIN_ID;
            //$data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$WeixinNoticeModel->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功');
        }
		return $this->fetch();
	}

    /**
     * 【编辑】微信公告
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:41:57
     * @return  返回值  
     * @version 版本  1.0
     */
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
            $data['content'] = $_POST['content']; //用原始的方法接收带标签的数据
            // 入库
            if ($WeixinNoticeModel->allowField(true)->update($data) === false) {
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

    /**
     * 【详情】微信公告
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:41:57
     * @return  返回值  
     * @version 版本  1.0
     */
    public function noticeDetail()
    {
        $WeixinNoticeModel = new WeixinNoticeModel;
        $id = input('param.id/d');
        $row = $WeixinNoticeModel->find($id);
        //halt($row);
        $WeixinReadRecordModel = new WeixinReadRecordModel;
        $records = $WeixinReadRecordModel->where([['notice_id','eq',$id]])->order('ctime desc')->select()->toArray();
        //halt(htmlspecialchars_decode($row['content']));
        // 更新已读记录 
        //$WeixinNoticeModel->updateReads($id);
        $this->assign('records',$records);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     * 【删除】微信公告
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:41:57
     * @return  返回值  
     * @version 版本  1.0
     */
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
     * 【启用禁用状态切换】微信公告
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-31 09:41:57
     * @return  返回值  
     * @version 版本  1.0
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
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $WeixinConfigModel = new WeixinConfigModel();
            foreach ($data as $key => $value) {
                if(!empty($value)){
                    $WeixinConfigModel->where([['name','eq',$key]])->update(['value'=>$value]);
                }
                
            }
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
        // $this->assign('hisiTabData', $tabData);
        // $this->assign('hisiTabType', 3);
		return $this->fetch();
	}
}