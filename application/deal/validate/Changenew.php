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
use app\house\model\Tenant as TenantModel;
use app\deal\model\ChangeNew as ChangeNewModel;

/**
 * 新发租验证器
 * @package app\system\validate
 */
class Changenew extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',     
        'house_id|房屋编号' => 'require|isAllow',     
        'new_type|发租类型' => 'require|number',     
        'tenant_id|租户编号' => 'require|isTenantAllow',     
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！'
    ];

    // 判断当前房屋是否可以申请使用权变更
    protected function isAllow($value, $rule='', $data)
    {
        $msg = true;
        $houseStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($houseStatus != 0){
            $msg = '房屋状态异常！';
        }
        $row = ChangeNewModel::where([['house_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
        return $msg;
    }

    // 判断当前房屋是否可以申请使用权变更
    protected function isTenantAllow($value, $rule='', $data)
    {
        $msg = true;
        $tenantInfo = TenantModel::where([['tenant_id','eq',$value]])->find();
        if($tenantInfo['tenant_status'] != 1){
            $msg = '租户状态异常！';
        }
        if (!preg_match('/^[1-9]{1}\d{5}[1-9]{2}\d{9}[Xx0-9]{1}$/', $tenantInfo['tenant_card'])) {
            $msg = '租户身份证号格式错误！';
        } 
        return $msg;
    }
 

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','new_type','tenant_id']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id','new_type']);
    }

    
}