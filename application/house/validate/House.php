<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | Motto ：No pains, no gains !
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>
// +----------------------------------------------------------------------
namespace app\house\validate;

use think\Validate;
use app\house\model\Ban as BanModel;
use app\house\model\Tenant as TenantModel;

/**
 * 房屋验证器
 * @package app\admin\validate
 */
class House extends Validate
{
    //定义验证规则
    protected $rule = [
        'ban_number|楼栋编号' => 'require|existInBan',
        'tenant_number|租户编号' => 'require|existInTenant',
        'house_unit_id|单元号' => 'require|number',
        'house_floor_id|楼层号' => 'require|number',
        'house_use_id|使用性质' => 'require|number',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    protected function existInBan($value, $rule='', $data)
  	{
  		$row = BanModel::where([['ban_number','eq',$value]])->value('ban_id');
      	return $row?true:'楼栋编号格式错误';	
  	}

  	protected function existInTenant($value, $rule='', $data)
  	{
  		$row = TenantModel::where([['tenant_number','eq',$value]])->value('tenant_id');
      	return $row?true:'租户编号格式错误';	
  	}

    //定义验证场景
    protected $scene = [
        //新增
        'sceneForm'  =>  ['ban_number','tenant_number','house_unit_id','house_floor_id','house_use_id'],
         
    ];
}