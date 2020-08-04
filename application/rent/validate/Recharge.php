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

namespace app\rent\validate;

use think\Validate;
use app\house\model\House as HouseModel;

/**
 * 用户验证器
 * @package app\system\validate
 */
class Recharge extends Validate
{
    //定义验证规则
    protected $rule = [	
        'house_number|房屋编号' => 'require|existInHouse',
        'pay_rent|充值金额' => 'require',
    ];

    //定义验证提示
    protected $message = [
        // 'username.require' => '请输入账户名称',
        // 'role_id.require'  => '请选择角色分组',
    ];

    protected function existInHouse($value, $rule='', $data)
  	{
  		$row = HouseModel::where([['house_number','eq',$value]])->value('house_id');
      	return $row?true:'房屋编号格式错误';	
  	}

    //添加
    public function sceneAdd()
    {
        return $this->only(['house_number','pay_rent']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['post']);
    }

    
}