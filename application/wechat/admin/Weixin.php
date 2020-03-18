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
use app\rent\model\Rent as RentModel;
use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;

/**
 * 微信小程序用户版
 */
class Weixin extends Admin
{
	public function userIndex()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinMemberModel = new WeixinMemberModel;
            $where = $WeixinMemberModel->checkWhere($getData);
            $fields = 'member_id,tenant_id,member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
            $data = [];
            $data['data'] = WeixinMemberModel::field($fields)->where($where)->page($page)->order('create_time desc')->limit($limit)->select();
            $data['count'] = WeixinMemberModel::where($where)->count('member_id');//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}

	public function noticeIndex()
	{
		return $this->fetch();
	}

	// public function tempIndex()
	// {
	// 	return $this->fetch();
	// }

	public function payRecord()
	{	
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinOrderModel = new WeixinOrderModel;
            $where = $WeixinOrderModel->checkWhere($getData);
            // $fields = 'member_id,tenant_id,member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
            $data = [];
            $temp = WeixinOrderModel::with('weixinMember')->where($where)->page($page)->order('ctime desc')->limit($limit)->select()->toArray();
            foreach ($temp as $k => &$v) {
            	$rent_order_id = WeixinOrderTradeModel::where([['out_trade_no','eq',$v['out_trade_no']]])->value('rent_order_id');
            	//halt($rent_order_id);
				$info = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_number,d.ban_address')->where([['a.rent_order_id','eq',$rent_order_id]])->find();
				if($info){
					$v['house_number'] = $info['house_number'];
					$v['ban_address'] = $info['ban_address'];
				}
				
            }
            //halt($temp);
            $data['data'] = $temp;
            $data['count'] = WeixinOrderModel::where($where)->count();//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}

	/**
	 * 功能描述：支付记录详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function payDetail()
	{
		$id = input('id');
		//halt($id);
		$WeixinOrderModel = new WeixinOrderModel;
		$order_info = $WeixinOrderModel->with('weixinMember')->find($id)->toArray();
		if($order_info['order_status'] == 2){ //如果状态是已退款
			$WeixinOrderRefundModel = new WeixinOrderRefundModel;
			$order_refund_info = $WeixinOrderRefundModel->where([['order_id','eq',$id]])->find();
			$this->assign('order_refund_info',$order_refund_info);
		}
		
		//halt($order_info);
		$this->assign('data_info',$order_info);
		//获取绑定的房屋数量
		// $WeixinMemberHouseModel = new WeixinMemberHouseModel;
		// $houselist = $WeixinMemberHouseModel->house_list($id);
		// $this->assign('houselist',$houselist);
		
		return $this->fetch();
	}

	/**
	 * 功能描述：支付记录详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function payRefund()
	{
		$id = input('id');
		
		// halt($id);
		$WeixinOrderModel = new WeixinOrderModel;
		$order_info = $WeixinOrderModel->with('weixinMember')->find($id);
		// halt($order_info);
		$this->assign('data_info',$order_info);
		// 获取绑定的房屋数量
		// $WeixinMemberHouseModel = new WeixinMemberHouseModel;
		// $houselist = $WeixinMemberHouseModel->house_list($id);
		// $this->assign('houselist',$houselist);
		return $this->fetch();
	}

	public function bindHouselist()
	{
		$id = input('id');
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$houselist = $WeixinMemberHouseModel->house_list($id);
		//halt($houselist);
		$this->assign('houselist',$houselist);
		return $this->fetch();
	}

	/**
	 * 功能描述：用户详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function memberDetail()
	{
		$id = input('id');
		//halt($id);
		$WeixinMemberModel = new WeixinMemberModel;
		$member_info = $WeixinMemberModel->with('tenant')->find($id);
		$this->assign('data_info',$member_info);
		//获取绑定的房屋数量
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$houselist = $WeixinMemberHouseModel->house_list($id);
		$this->assign('houselist',$houselist);
		
		return $this->fetch();
	}

	/**
	 * 功能描述：认证详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function AuthDetail()
	{
		$id = input('id');
		$WeixinMemberModel = new WeixinMemberModel;
		$member_info = $WeixinMemberModel->with('tenant')->find($id);
		$this->assign('data_info',$member_info);
		return $this->fetch();
	}

	/**
	 * 功能描述：启用禁用状态切换
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:30:34
	 */
	public function isShow()
	{
		$id = input('id');
		$WeixinMemberModel = new WeixinMemberModel;
		$memberInfo = $WeixinMemberModel->find($id);
		if($memberInfo->is_show == 1){
			$memberInfo->is_show = 2;
			$msg = '禁用成功！';
		}else{
			$memberInfo->is_show = 1;
			$msg = '启用成功！';
		}
		$result = $memberInfo->save();
		if ($result === false) {
            return $this->error('状态设置失败');
        }

        return $this->success($msg);
	}
}