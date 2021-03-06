<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 https://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\deal\validate;

use think\Db;
use think\Validate;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\rent\model\Rent as RentModel;

/**
 *减免异动验证器
 * @package app\system\validate
 */
class Changecut extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'house_id|房屋编号' => 'require|isAllow',
        'tenant_id|租户编号' => 'require',
        'cut_type|减免类型' => 'require',
        'cut_rent|减免金额' => 'require',    
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！',    
        'new_tenant_id.different' => '新租户不能与原租户相同！',
        'transfer_rent.float' => '转让金额格式不正确！',
    ];

    // 判断当前房屋是否可以申请使用权变更
    protected function isAllow($value, $rule='', $data)
  	{
        // 房屋必须是正常状态的
        $msg = '';
        $find = HouseModel::where([['house_id','eq',$value]])->field('house_status,house_pre_rent')->find();
        if($find['house_status'] != 1){
            $msg = '房屋状态异常！';
        }
        // 房屋必须未在减免异动中
  		$row = ChangeCutModel::where([['house_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
        // 房屋必须没有欠租 
        $rentOrderNumber = RentModel::where([['rent_order_status','eq',1],['house_id','eq',$value],['rent_order_receive','exp',Db::raw('!=rent_order_paid')]])->value('rent_order_number');
        if($rentOrderNumber){
            $msg = '房屋有欠缴订单未处理！';
        }
        // 减免金额必须小于规租
        if($find['house_pre_rent'] < $data['cut_rent']){
            $msg = '减免金额不能大于规租！';
        }
      	return $msg?$msg:true;
  	}

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','tenant_id','cut_type','cut_rent']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id','tenant_id','cut_type','cut_rent']);
    }

    
}