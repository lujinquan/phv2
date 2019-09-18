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
use app\deal\model\ChangeUse as ChangeUseModel;

/**
 * 使用权变更验证器
 * @package app\system\validate
 */
class Changeuse extends Validate
{
    //定义验证规则
    protected $rule = [	
        'house_id|房屋编号' => 'require|existInHouse',
        'old_tenant_id|原租户编号' => 'require',
        'old_tenant_name|原租户姓名' => 'require',
        'old_tenant_card|原租户身份证号' => 'require',
        'new_tenant_id|新租户编号' => 'require',
        'new_tenant_name|新租户姓名' => 'require',
        'new_tenant_card|新租户身份证号' => 'require',
        'change_type|变更类型' => 'require',      
    ];

    //定义验证提示
    protected $message = [
        // 'username.require' => '请输入账户名称',
        // 'role_id.require'  => '请选择角色分组',
    ];

    protected function existInHouse($value, $rule='', $data)
  	{
  		$row = HouseModel::get($value);
      	return $row?true:'房屋编号格式错误';	
  	}

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','old_tenant_id','old_tenant_name','old_tenant_card','new_tenant_id','new_tenant_name','new_tenant_card','change_type']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['post']);
    }

    
}