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

use think\Validate;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeOffset as ChangeOffsetModel;

/**
 * 陈欠核销验证器
 * @package app\system\validate
 */
class Changeoffset extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'house_id|房屋编号' => 'require|isAllow',
        'rent_order_date|核销订单' => 'require',
        'before_year_rent|核销以前年订单' => 'float',
        'before_month_rent|核销以前月订单' => 'float',
        'this_month_rent|核销当月订单' => 'float',      
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！', 
    ];

    // 判断当前房屋是否可以申请使用权变更
    protected function isAllow($value, $rule='', $data)
  	{
        $msg = '';
        $houseStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($houseStatus != 1){
            $msg = '房屋状态异常！';
        }
  		$row = ChangeOffsetModel::where([['house_id','eq',$value],['change_status','>',1],['dtime','eq',0]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
      	return $msg?$msg:true;
  	}

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','rent_order_date','before_year_rent','before_month_rent','this_month_rent']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id','rent_order_date','before_year_rent','before_month_rent','this_month_rent']);
    }

    
}