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
 * 微信小程序用户
 */
class WeixinLeadMember extends Model 
{
	// 设置模型名称
    protected $name = 'weixin_lead_member';
    // 设置主键
    protected $pk = 'lead_member_id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',
        'auth_time' => 'timestamp:Y-m-d H:i:s',
        'app_cols' => 'json'
    ];

    // public function tenant()
    // {
    //     return $this->hasOne('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_name,tenant_number,tenant_tel,tenant_card,tenant_inst_id');
    // }

	public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // 检索会员编号
        if(isset($data['lead_member_id']) && $data['lead_member_id']){
            $where[] = ['lead_member_id','eq',$data['lead_member_id']];
        }
        // 检索会员昵称
        if(isset($data['member_name']) && $data['member_name']){
            $where[] = ['member_name','like','%'.$data['member_name'].'%'];
        }
        // 检索产别
        if(isset($data['tel']) && $data['tel']){
            $where[] = ['tel','like','%'.$data['tel'].'%'];
        }
        // 检索真实姓名
        if(isset($data['real_name']) && $data['real_name']){
            $where[] = ['real_name','like','%'.$data['real_name'].'%'];
        }
        // 检索认证状态
        if(isset($data['tenant_id']) && $data['tenant_id'] == 1){
            $where[] = ['tenant_id','>',0];
        }
        // 检索认证状态
        if(isset($data['tenant_id']) && $data['tenant_id'] == 2){
            $where[] = ['tenant_id','eq',0];
        }
        // 检索是否启用
        if(isset($data['is_show']) && $data['is_show']){
            $where[] = ['is_show','eq',$data['is_show']];
        }
        //$where[] = ['tenant_inst_id','in',config('inst_ids')[$instid]];

        return $where;
    }


}