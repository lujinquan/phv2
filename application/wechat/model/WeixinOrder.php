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

namespace app\wechat\model;

use think\Db;
use think\Model;
use app\rent\model\Rent as RentModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 微信小程序支付订单
 */
class WeixinOrder extends Model 
{
	// 设置模型名称
    protected $name = 'weixin_order';	
    // 设置主键
    protected $pk = 'order_id';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'ptime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // 检索订单状态
        if(isset($data['order_status']) && $data['order_status']){
            $where[] = ['order_status','eq',$data['order_status']];
        }else{
            $where[] = ['order_status','neq',3];
        }
        // 检索订单编号
        if(isset($data['out_trade_no']) && $data['out_trade_no']){
            $where[] = ['out_trade_no','like','%'.$data['out_trade_no'].'%'];
        }
        // // 检索产别
        // if(isset($data['tel']) && $data['tel']){
        //     $where[] = ['tel','like','%'.$data['tel'].'%'];
        // }
        // // 检索真实姓名
        // if(isset($data['real_name']) && $data['real_name']){
        //     $where[] = ['real_name','like','%'.$data['real_name'].'%'];
        // }
        // // 检索认证状态
        // if(isset($data['tenant_id']) && $data['tenant_id'] == 1){
        //     $where[] = ['tenant_id','>',0];
        // }
        // // 检索认证状态
        // if(isset($data['tenant_id']) && $data['tenant_id'] == 2){
        //     $where[] = ['tenant_id','eq',0];
        // }
        // // 检索是否启用
        // if(isset($data['is_show']) && $data['is_show']){
        //     $where[] = ['is_show','eq',$data['is_show']];
        // }
        //$where[] = ['tenant_inst_id','in',config('inst_ids')[$instid]];

        return $where;
    }

    public function weixinMember()
    {
        return $this->hasOne('weixinMember', 'member_id', 'member_id')->bind('member_name,tel,weixin_tel,avatar,openid,card');
    }

    public function afterWeixinPay($data)
    {
        // 生成后台订单
        $row = self::where([['out_trade_no','eq',$data['out_trade_no']]])->find();

        if($row){

            // 如果当前状态是已支付状态，则跳过
            if ($row['order_status'] == 1) {
                return false;

            // 如果当前状态不是已支付的状态，
            }else{

            }

            // 使用openid检查支付用户是否存在于系统中，且member_id是否与预支付的member_id一致，若不一致，则将支付用户的信息绑定到系统中
            $pay_member_info = WeixinMemberModel::where([['openid','eq',$data['openid']]])->field('member_id')->find();
            // 如果支付用户在系统中不存在
            if(!$pay_member_info){
                $WeixinMemberModel           = new WeixinMemberModel;
                $WeixinMemberModel->openid   = $data['openid'];
                $WeixinMemberModel->save();
                // 获取自增ID
                $row->member_id = $WeixinMemberModel->member_id;
            }

            // 更新预付订单
            $row->transaction_id = $data['transaction_id'];
            $row->ptime = strtotime($data['time_end']); //支付时间
            $row->pay_money = $data['total_fee'] / 100; //支付金额，单位：分
            $row->trade_type = $data['trade_type']; //支付类型，如：JSAPI
            $row->order_status = 1; //支付状态1，支付完成
            $row->save();
            // 更新租金订单表
            $WeixinOrderTradeModel = new WeixinOrderTradeModel; 
            $rent_order_ids = $WeixinOrderTradeModel->where([['out_trade_no','eq',$data['out_trade_no']]])->column('rent_order_id');

            $house_id = '';


            foreach ($rent_order_ids as $rid) {

                // 缴纳欠租订单order
                $rent_order_info = RentModel::where([['rent_order_id','eq',$rid]])->find();
                $child_rent_order_paid = bcsub($rent_order_info['rent_order_receive'], $rent_order_info['rent_order_paid'], 2);
                if($child_rent_order_paid <= 0){
                    continue;
                }
                $rent_order_info->rent_order_paid = Db::raw('rent_order_receive'); 
                //$rent_order_info->ptime = strtotime($data['time_end']);
                //$rent_order_info->pay_way = 4; 
                $rent_order_info->is_deal = 1; 
                $rent_order_info->save();

                $house_id = $rent_order_info['house_id'];
                
                // 缴纳欠租订单order_child
                $RentOrderChildModel = new RentOrderChildModel;
                $RentOrderChildModel->house_id = $rent_order_info['house_id'];
                $RentOrderChildModel->tenant_id = $rent_order_info['tenant_id'];
                $RentOrderChildModel->rent_order_id = $rent_order_info['rent_order_id'];
                $RentOrderChildModel->rent_order_number = $rent_order_info['rent_order_number'];
                $RentOrderChildModel->rent_order_receive = $rent_order_info['rent_order_receive'];
                $RentOrderChildModel->rent_order_pre_rent = $rent_order_info['rent_order_pre_rent'];
                $RentOrderChildModel->rent_order_cou_rent = $rent_order_info['rent_order_cou_rent']; 
                $RentOrderChildModel->rent_order_cut = $rent_order_info['rent_order_cut'];
                $RentOrderChildModel->rent_order_diff = $rent_order_info['rent_order_diff'];
                $RentOrderChildModel->rent_order_pump = $rent_order_info['rent_order_pump'];
                $RentOrderChildModel->rent_order_date = $rent_order_info['rent_order_date'];
                $RentOrderChildModel->rent_order_paid = $child_rent_order_paid;
                $RentOrderChildModel->pay_way = 4; // 4是微信支付
                $RentOrderChildModel->save();


                // 添加房屋台账，记录缴费状况
                $HouseTaiModel = new HouseTaiModel;
                $HouseTaiModel->house_id = $rent_order_info['house_id'];
                $HouseTaiModel->tenant_id = $rent_order_info['tenant_id'];
                $HouseTaiModel->cuid = 0;
                $HouseTaiModel->house_tai_type = 2;
                $HouseTaiModel->house_tai_remark = '微信缴费：'.$child_rent_order_paid.'元';
                $HouseTaiModel->data_json = [];
                $HouseTaiModel->change_type = '';
                $HouseTaiModel->change_id = '';
                $HouseTaiModel->save();
            }

            // 如果支付的人，并没有绑定当前房屋，则自动绑定当前房屋(非认证状态)
            $member_house_info = WeixinMemberHouseModel::where([['member_id','eq',$row->member_id],['house_id','eq',$house_id],['dtime','eq',0]])->find();
            if(!$member_house_info){
                WeixinMemberHouseModel::create([
                    'member_id' => $row->member_id,
                    'house_id' => $house_id,
                ]);
            }
           
            // 开具电子发票
            // $InvoiceModel = new InvoiceModel;
            // $InvoiceModel->dpkj($row['order_id']);

        // 如果通过out_trae_no无法找到预付订单，则抛出错误
        }else{
            
        }
    }
}