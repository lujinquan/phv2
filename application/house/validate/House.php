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
use app\deal\model\ChangeNew as ChangeNewModel;

/**
 * 房屋验证器
 * @package app\admin\validate
 */
class House extends Validate
{
    //定义验证规则
    protected $rule = [
        'house_id' => 'isAllowChange',
        'ban_id|楼栋编号' => 'require|existInBan',
        'tenant_id|租户编号' => 'require|existInTenant',
        'house_unit_id|单元号' => 'require|number',
        'house_floor_id|楼层号' => 'require|number',
        'house_use_id|使用性质' => 'require|number',
        'house_oprice|房屋原价' => 'gt:0',
        'house_area|建筑面积' => 'gt:0',
        'house_pre_rent|规定租金' => 'require|gt:0',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    protected function isAllowChange($value, $rule='', $data)
    { 
        $row = ChangeNewModel::where([['house_id','in',$value],['change_status','>',2]])->value('id');
        return $row?'该房屋已经在新发租异动中':true;  
    }

    protected function existInBan($value, $rule='', $data)
  	{
  		  $row = BanModel::where([['ban_id','eq',$value]])->value('ban_id');
      	return $row?true:'楼栋编号格式错误';	
  	}

  	protected function existInTenant($value, $rule='', $data)
  	{
  		  $row = TenantModel::where([['tenant_id','eq',$value]])->value('tenant_id');
      	return $row?true:'租户编号格式错误';	
  	}

    //添加
    public function sceneForm()
    {
        return $this->only(['ban_id','tenant_id','house_unit_id','house_floor_id','house_use_id','house_oprice','house_area','house_pre_rent']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['house_id','ban_id','tenant_id','house_unit_id','house_floor_id','house_use_id','house_oprice','house_area','house_pre_rent']);
    }

    // 编辑
    public function sceneDel()
    {
        return $this->only(['house_id']);
    }
}