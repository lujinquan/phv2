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
use app\deal\model\ChangeRentAdd as ChangeRentAddModel;

/**
 * 租金追加调整验证器
 * @package app\system\validate
 */
class Changerentadd extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'house_id|房屋编号' => 'require|isAllow',
        'before_year_rent|追加以前年金额' => 'float',
        'before_month_rent|追加以前月金额' => 'float|hasoneRequire',
      
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！', 
    ];

    // 判断当前房屋是否可以申请异动
    protected function isAllow($value, $rule='', $data)
  	{
        $msg = '';
        $houseStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($houseStatus != 1){
            $msg = '房屋状态异常！';
        }
  		$row = ChangeRentAddModel::where([['house_id','eq',$value],['change_status','>',1],['dtime','eq',0]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
      	return $msg?$msg:true;
  	}

    // 判断当前房屋是否可以申请异动
    protected function hasoneRequire($value, $rule='', $data)
    {
        $msg = '';
        if(!$value && !$data['before_year_rent']){
           $msg = '以前年以前月数据必须有一个不为空！'; 
        }
        return $msg?$msg:true;
    }

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','before_year_rent','before_month_rent']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id','before_year_rent','before_month_rent']);
    }

    
}