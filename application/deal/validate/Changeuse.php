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
use think\Db;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeUse as ChangeUseModel;

/**
 * 使用权变更验证器
 * @package app\system\validate
 */
class Changeuse extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'house_id|房屋编号' => 'require|isAllow',
        'old_tenant_id|原租户编号' => 'require',
        'old_tenant_name|原租户姓名' => 'require',
        'old_tenant_card|原租户身份证号' => 'require',
        'new_tenant_id|新租户编号' => 'require|different:old_tenant_id',
        'new_tenant_name|新租户姓名' => 'require',
        'new_tenant_card|新租户身份证号' => 'require',
        'change_use_type|变更类型' => 'require',            
        'service_rent|手续费' => 'float|egt:0',
        'transfer_rent|转让金额' => 'float|egt:0',      
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！',
        'new_tenant_id.different' => '新租户不能与原租户相同！',
        'transfer_rent.float' => '转让金额格式不正确！',
        'transfer_rent.egt' => '转让金额格式不正确！',
        'service_rent.float' => '手续费格式不正确！',
        'service_rent.egt' => '手续费格式不正确！',
    ];

    // 判断当前房屋是否可以申请使用权变更
    protected function isAllow($value, $rule='', $data)
  	{
        $msg = '';
        $houseStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($houseStatus != 1){
            $msg = '房屋状态异常！';
        }
  		$row = ChangeUseModel::where([['house_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
        // // 检查该房屋是否在注销中
        // $cancelRow = Db::name('change_cancel')->where([['house_id','eq',$value],['change_status','>',1]])->find();
        // if($cancelRow){
        //     $msg = '房屋已在注销异动中，无法申请当前异动！';
        // }
        // // 检查该房屋是否在减免年审中
        // $cancelCutYear = Db::name('change_cut_year')->where([['house_id','eq',$value],['change_status','>',1]])->find();
        // if($cancelCutYear){
        //     $msg = '房屋已在减免年审异动中，无法申请当前异动！';
        // }
        // // 检查该房屋是否在减免年审中
        // $cancelCutYear = Db::name('change_house')->where([['house_id','eq',$value],['change_status','>',1]])->find();
        // if($cancelCutYear){
        //     $msg = '房屋已在减免年审异动中，无法申请当前异动！';
        // }
      	return $msg?$msg:true;
  	}

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','old_tenant_id','old_tenant_name','old_tenant_card','new_tenant_id','new_tenant_name','new_tenant_card','change_use_type','transfer_rent','service_rent']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['new_tenant_id','new_tenant_name','new_tenant_card','change_use_type','transfer_rent','service_rent']);
    }

    
}