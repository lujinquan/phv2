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
use hisi\Dir;
use app\system\admin\Admin;
use app\rent\model\Rent as RentModel;
use app\wechat\model\Weixin as WeixinModel;
use app\rent\model\Invoice as InvoiceModel;
use app\house\model\House as HouseModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinLeadMember as WeixinLeadMemberModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;

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
            //halt($where);
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

	public function leaderIndex()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinLeadMemberModel = new WeixinLeadMemberModel;
            $where = $WeixinLeadMemberModel->checkWhere($getData);
            $fields = 'lead_member_id,tenant_id,lead_member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
            $data = [];
            $data['data'] = WeixinLeadMemberModel::field($fields)->where($where)->page($page)->order('create_time desc')->limit($limit)->select();
            $data['count'] = WeixinLeadMemberModel::where($where)->count('member_id');//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}


	/**
	 * 功能描述： 支付记录列表
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-03-25 14:59:38
	 * @example 
	 * @link    文档参考地址：
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function payRecord()
	{	
		
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinOrderModel = new WeixinOrderModel;
            $where = $WeixinOrderModel->checkWhere($getData);
            //halt($where);
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
        $currExpirTime = time()-7200;
        // 删除过期的预支付订单
        $prepay_orders = WeixinOrderModel::where([['order_status','eq',3],['ctime','<',$currExpirTime]])->field('out_trade_no')->select()->toArray();
        if($prepay_orders){
        	foreach ($prepay_orders as $key => $val) {
	        	WeixinOrderTradeModel::where([['out_trade_no','eq',$val['out_trade_no']]])->delete();
	        }
	        WeixinOrderModel::where([['order_status','eq',3],['ctime','<',$currExpirTime]])->delete();
        }
        
		return $this->fetch();
	}

	// 开票
    public function dpkj()
    {
        $id = input('param.id');
        $InvoiceModel = new InvoiceModel;
        return !$InvoiceModel->dpkj($id) ? $this->error($InvoiceModel->getError()) : $this->success('开票成功') ;
    }

    // 所有已开的发票，未下载到服务器的统一，一键下载
    public function allVoiceDown()
    {
    	// 获取所有需要下载pdf的数据
        $InvoiceModel = new InvoiceModel;
        $allInvoice = $InvoiceModel->where([['pdfurl','neq',''],['local_pdfurl','eq','']])->select();

        $i = 0;
        foreach ($allInvoice as $v) {
        	
        	$url = $v['pdfurl'];
        	$file = file_get_contents($url);
        	if(strlen($file) < 10000){ // 请求的不是正常pdf文件
        		continue;
        	}else{
        		$dir = $_SERVER['DOCUMENT_ROOT'].'/upload/invoice/'.date('Ym');
        		if(!is_dir($dir)){
        			Dir::create($dir);
        			mkdir($dir, 0755, true);
        		}
        		file_put_contents($dir.'/'. $v['fpqqlsh'] .'.pdf', $file);
        		$loacl_pdfurl = '/upload/invoice/'.date('Ym').'/'. $v['fpqqlsh'] .'.pdf';

        		InvoiceModel::where([['invoice_id','eq',$v['invoice_id']]])->update(['local_pdfurl'=>$loacl_pdfurl]);
        		$i++;
        	}
        }
        $this->success('下载成功，本次下载'.$i.'张发票！') ;
        //halt($allInvoice);
        // return !$InvoiceModel->dpkj($id) ? $this->error($InvoiceModel->getError()) : $this->success('开票成功') ;
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
        if($order_info['invoice_id']){
          $InvoiceModel = new InvoiceModel;
          $invoice_info = $InvoiceModel->find($order_info['invoice_id']);
          if(!$invoice_info['local_pdfurl']){
          	$is_down = $InvoiceModel->down_loacl_pdfurl($order_info['invoice_id']);
          	if($is_down){
          		$invoice_info = $InvoiceModel->find($order_info['invoice_id']);
          	}
          }
          $this->assign('invoice_info',$invoice_info);
        }
		$WeixinOrderTradeModel = new WeixinOrderTradeModel;
		$rent_orders = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
		$rent_order_ids = array_keys($rent_orders);
		$houses = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->where([['a.rent_order_id','in',$rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
        foreach ($houses as $k => &$v) {
        	$v['rent_order_date'] = substr($v['rent_order_date'], 0,4).'-'.substr($v['rent_order_date'], 4,2);
        	$v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
     	}
		$this->assign('houses',$houses);
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
		if ($this->request->isPost()) {
            //$data = $this->request->post();

            //halt($id);
	        $ref_description = input('ref_description');
	        if(!$ref_description){
	            return  $this->error('退款备注不能为空');
	        }
	        //exit;  
	        $WeixinOrderModel = new WeixinOrderModel;
	        $order_info = $WeixinOrderModel->with('weixinMember')->find($id);

	        $WeixinOrderTradeModel = new WeixinOrderTradeModel;
	        $order_trade_info = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->find();

	        $ban_row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->where([['rent_order_id','eq',$order_trade_info['rent_order_id']]])->field('c.ban_inst_pid')->find();
	        $inst_pid = $ban_row['ban_inst_pid'];
	        include EXTEND_PATH.'wechat/include.php';
	        if($inst_pid == 2){
	            $wechat = \WeChat\Pay::instance($this->config_ziyang);
	        }else if($inst_pid == 3){
	            $wechat = \WeChat\Pay::instance($this->config_liangdao);
	        }else{
	            return  $this->error('房屋所属机构异常');
	        }

	        
	        //$wechat = \WeChat\Pay::instance($this->config_ziyang);
	        // 下面的参数注意要换成动态的
	        $options = [
	            'transaction_id' => $order_info['transaction_id'], //微信订单号 transaction_id
	            'out_refund_no'  => $order_info['out_trade_no'], //
	            'total_fee'      => $order_info['pay_money'] * 100,
	            'refund_fee'     => $order_info['pay_money'] * 100,
	        ];

	        //halt($options);
	        $result = $wechat->createRefund($options);
	        if($result['result_code'] == 'FAIL'){
	            return  $this->error($result['err_code_des']);
	        }
	        //halt($result);
	        // $result = [
	        //     'return_code' => "SUCCESS",
	        //     'return_msg' => "OK",
	        //     'appid' => "wxaac82b178a3ef1d2",
	        //     'mch_id' => "1244050802",
	        //     'nonce_str' => "LqJP9iBVC9ESsNgM",
	        //     'sign' => "BAAE65616FE0AD3701AC2B199A3585DD0D9CD621E0D076E14797B4B66F91FC35",
	        //     'result_code' => "SUCCESS",
	        //     'transaction_id' => "4200000511202003139890551158",
	        //     'out_trade_no' => "10600316847101202001",
	        //     'out_refund_no' => "10600316847101202001",
	        //     'refund_id' => "50300603632020031615181917068",
	        //     'refund_channel' => [],
	        //     'refund_fee' => "2",
	        //     'coupon_refund_fee' => "0",
	        //     'total_fee' => "2",
	        //     'cash_fee' => "2",
	        //     'coupon_refund_count' => "0",
	        //     'cash_refund_fee' => "2",
	        // ];
	        $WeixinOrderRefundModel = new WeixinOrderRefundModel;
	        $WeixinOrderRefundModel->order_id = $order_info['order_id'];
	        $WeixinOrderRefundModel->out_trade_no = $order_info['out_trade_no'];
	        $WeixinOrderRefundModel->ref_money = $result['refund_fee'] / 100;
	        $WeixinOrderRefundModel->member_id = $order_info['member_id'];
	        $WeixinOrderRefundModel->refund_id = $result['refund_id'];
	        $WeixinOrderRefundModel->out_refund_no = $result['out_refund_no'];
	        $WeixinOrderRefundModel->ref_description = $ref_description;
	        $WeixinOrderRefundModel->ptime = $order_info->getData('ptime');
	        $WeixinOrderRefundModel->save();

	        // 更新租金订单表,将缴费记录回退
	        $WeixinOrderTradeModel = new WeixinOrderTradeModel; 
	        $rent_order_ids = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id');

	        $RentModel = new RentModel;
	        foreach ($rent_order_ids as $rid) {
	            $rent_order_info = $RentModel->where([['rent_order_id','eq',$rid]])->find();
	            $rent_order_info->rent_order_paid = 0; 
	            $rent_order_info->ptime = 0;
	            $rent_order_info->pay_way = 0; 
	            $rent_order_info->is_deal = 0; 
	            $rent_order_info->save();

	            // 缴纳欠租订单order_child
	            // $RentOrderChildModel = new RentOrderChildModel;
	            // $RentOrderChildModel->house_id = $rent_order_info['house_id'];
	            // $RentOrderChildModel->tenant_id = $rent_order_info['tenant_id'];
	            // $RentOrderChildModel->rent_order_id = $rent_order_info['rent_order_id'];
	            // $RentOrderChildModel->rent_order_number = $rent_order_info['rent_order_number'];
	            // $RentOrderChildModel->rent_order_receive = $rent_order_info['rent_order_receive'];
	            // $RentOrderChildModel->rent_order_pre_rent = $rent_order_info['rent_order_pre_rent'];
	            // $RentOrderChildModel->rent_order_cou_rent = $rent_order_info['rent_order_cou_rent']; 
	            // $RentOrderChildModel->rent_order_cut = $rent_order_info['rent_order_cut'];
	            // $RentOrderChildModel->rent_order_diff = $rent_order_info['rent_order_diff'];
	            // $RentOrderChildModel->rent_order_pump = $rent_order_info['rent_order_pump'];
	            // $RentOrderChildModel->rent_order_date = $rent_order_info['rent_order_date'];
	            // $RentOrderChildModel->rent_order_paid = $data['total_fee'] / 100;
	            // $RentOrderChildModel->pay_way = 4; // 4是微信支付
	            // $RentOrderChildModel->save();
	        }

	        $order_info->order_status = 2;
	        $order_info->save();

	        
	        return  $this->success('退款成功，已退还至'.$order_info['member_name'].'，'. ($result['refund_fee']/100) .'元钱！');

        }
		// halt($id);
		$WeixinOrderModel = new WeixinOrderModel;
		$order_info = $WeixinOrderModel->with('weixinMember')->find($id);
		if($order_info['order_status'] == 2){ //如果状态是已退款
			$WeixinOrderRefundModel = new WeixinOrderRefundModel;
			$order_refund_info = $WeixinOrderRefundModel->where([['order_id','eq',$id]])->find();
			$this->assign('order_refund_info',$order_refund_info);
		}
		$WeixinOrderTradeModel = new WeixinOrderTradeModel;
		$rent_orders = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
		$rent_order_ids = array_keys($rent_orders);
		$houses = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->where([['a.rent_order_id','in',$rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
        foreach ($houses as $k => &$v) {
        	$v['rent_order_date'] = substr($v['rent_order_date'], 0,4).'-'.substr($v['rent_order_date'], 4,2);
        	$v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
     	}
		$this->assign('houses',$houses);
		// halt($order_info);
		$this->assign('data_info',$order_info);
		// 获取绑定的房屋数量
		// $WeixinMemberHouseModel = new WeixinMemberHouseModel;
		// $houselist = $WeixinMemberHouseModel->house_list($id);
		// $this->assign('houselist',$houselist);
		return $this->fetch();
	}

	public function payRecordlist()
	{
		$id = input('id');
		$memberinfo = WeixinMemberModel::where([['member_id','eq',$id]])->find();
		$orderlist = WeixinOrderModel::where([['member_id','eq',$id],['order_status','neq',3]])->select()->toArray();

		foreach ($orderlist as $k => &$v) {
        	$rent_order_id = WeixinOrderTradeModel::where([['out_trade_no','eq',$v['out_trade_no']]])->value('rent_order_id');
        	//halt($rent_order_id);
			$info = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_number,d.ban_address')->where([['a.rent_order_id','eq',$rent_order_id]])->find();
			if($info){
				$v['house_number'] = $info['house_number'];
				$v['ban_address'] = $info['ban_address'];
			}else{
				// 找不到关联的数据了，这里应该爆出错误
				$v['house_number'] = '10101222616239';
				$v['ban_address'] = '康平小区7栋';
			}
			
			$v['avatar'] = $memberinfo['avatar'];
			$v['openid'] = $memberinfo['openid'];
			$v['member_name'] = $memberinfo['member_name'];
        }
        //halt($orderlist);
		$this->assign('orderlist',$orderlist);
		return $this->fetch();
	}

	public function bindHouselist()
	{
		$id = input('id');
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$houselist = WeixinMemberHouseModel::where([['member_id','eq',$id],['dtime','eq',0]])->select()->toArray();
		foreach($houselist as &$h){
			//halt($h);
			$house_info = HouseModel::with(['ban','tenant'])->where([['house_id','eq',$h['house_id']]])->find();
			//halt($house_info);
			$h['house_number'] = $house_info['house_number'];
			$h['house_pre_rent'] = $house_info['house_pre_rent'];
			$h['ban_address'] = $house_info['ban_address'];
			$h['tenant_name'] = $house_info['tenant_name'];

		}
		$member_info = WeixinMemberModel::where([['member_id','eq',$id]])->find();
		//$houselist = $WeixinMemberHouseModel->house_list($id);
		$this->assign('member_info',$member_info);
		$this->assign('houselist',$houselist);
		return $this->fetch();
	}

	/**
	 * 添加会员与房屋的绑定
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-04-16 10:37:50
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	
	public function addbindhouse()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post();
            //halt($data);
            // 数据验证
            // $result = $this->validate($data, 'WeixinGuide');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // 
            $WeixinMemberModel = new WeixinMemberModel;
	        $member_info = $WeixinMemberModel->where([['member_id','eq',$data['member_id']]])->find();
	        if($member_info['is_show'] == 2){
	            return $this->error('用户已被禁止访问');
	        }
	        // 绑定手机号 
	        $HouseModel = new HouseModel;
	        $house_info = $HouseModel->where([['house_number','eq',$data['house_number']]])->find();
	        if(!$house_info){
	            return $this->error('房屋编号不存在');
	        }
	        if($house_info['house_status'] != 1){
	        	return $this->error('房屋已注销或未发租');
	        }
	        if($house_info['house_is_pause'] == 1){
	        	return $this->error('房屋已被暂停计租');
	        }
	        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
	        
	        //halt($houses);
	        $counts = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0],['is_auth','eq',0]])->count();
	        if($counts > 9){ //会员绑定的房屋数量达到>9个，提示超出数量
	            return $this->error('绑定房屋数量不能超过10个');
	        }
	        $find = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$house_info['house_id']],['dtime','eq',0]])->find();
	        if($find){
	            return $this->error('请勿重复绑定该房屋');
	        }
	        $WeixinMemberHouseModel->house_id = $house_info['house_id'];
	        $WeixinMemberHouseModel->member_id = $member_info['member_id'];
	        $res = $WeixinMemberHouseModel->save();
	        // 如果当前会员已认证，则每次添加房屋的时候刷新认证房屋数据
	        if($member_info['tenant_id']){
	            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
	            // 调试
	            $auth_house_ids = $HouseModel->where([['tenant_id','eq',$member_info['tenant_id']],['house_is_pause','eq',0],['house_status','eq',1]])->column('house_id');
	            $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']]])->column('house_id,is_auth');
	            foreach ($auth_house_ids as $a) {
	                if(isset($houses[$a]) && $houses[$a] == 0){
	                    $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$a]])->update(['is_auth'=>1]);
	                }
	                if(!isset($houses[$a])){
	                    $WeixinMemberHouseModel = new WeixinMemberHouseModel;
	                    $WeixinMemberHouseModel->save(['member_id'=>$member_info['member_id'],'house_id'=>$a,'is_auth'=>1]);
	                }
	            }
	        }          
            // 入库
            if (!$res) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }

	}

	/**
	 * 解除会员与房屋的绑定
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-04-01 09:11:08
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	
	public function delbindhouse()
	{
		$id = input('id');
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$info = $WeixinMemberHouseModel->get($id);
		if($info['is_auth']){
			$this->error('当前房屋已认证无法解绑');
		}
		$info->dtime = time();
		$res = $info->save();
		//$res = WeixinMemberHouseModel::where([['id','eq',$id]])->delete();
		if($res){
			$this->success('解绑成功');
		}else{
			$this->error('解绑失败');
		}
	}

	/**
	 * 功能描述：用户详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function memberDetail()
	{
		$id = input('id');
		$WeixinMemberModel = new WeixinMemberModel;
		if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            // $result = $this->validate($data, 'WeixinNotice');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // 入库
            if (!$WeixinMemberModel->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
		
		$member_info = $WeixinMemberModel->with('tenant')->find($id);
		$this->assign('data_info',$member_info);
		// 获取绑定的房屋数量
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$housePayCount = WeixinMemberHouseModel::where([['member_id','eq',$id]])->count();
		$this->assign('housePayCount',$housePayCount);
		// 获取支付的订单数
		$order_info = WeixinOrderModel::where([['order_status','eq',1],['member_id','eq',$id]])->column('pay_money');
		$orderMoneys = bcaddMerge($order_info);
		//halt($order_info);
		$this->assign('orderMoneys',$orderMoneys);
		$this->assign('orderCount',count($order_info));
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