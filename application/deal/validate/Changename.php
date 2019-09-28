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
use app\deal\model\ChangeName as ChangeNameModel;

/**
 * 别字更正验证器
 * @package app\system\validate
 */
class Changename extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'house_id|房屋编号' => 'require|isAllow',
        'tenant_id|租户编号' => 'require',
        'old_tenant_name|原租户姓名' => 'require',
        'new_tenant_name|新租户姓名' => 'require|different:old_tenant_name',  
        'old_tenant_card|身份证号' => 'require',    
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！',
        'new_tenant_name.different' => '新租户姓名不能与原租户姓名相同！',
    ];

    // 判断当前房屋是否可以申请使用权变更
    protected function isAllow($value, $rule='', $data)
  	{
        $msg = '';
        $houseStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($houseStatus != 1){
            $msg = '房屋状态异常！';
        }
  		$row = ChangeNameModel::where([['house_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
      	return $msg?$msg:true;
  	}

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','tenant_id','old_tenant_name','new_tenant_name']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['tenant_id','old_tenant_name','new_tenant_name']);
    }

    
}