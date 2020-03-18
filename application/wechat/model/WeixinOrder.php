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
        }
        // 检索订单编号
        if(isset($data['out_order_no']) && $data['out_order_no']){
            $where[] = ['out_order_no','like','%'.$data['out_order_no'].'%'];
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
}