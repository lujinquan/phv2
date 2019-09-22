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
 * 注销验证器
 * @package app\system\validate
 */
class Changecancel extends Validate
{
    //定义验证规则
    protected $rule = [	
        'ban_id|楼栋编号' => 'require',     
    ];

    //定义验证提示
    protected $message = [
        // 'username.require' => '请输入账户名称',
        // 'role_id.require'  => '请选择角色分组',
    ];

    

    //添加
    public function sceneForm()
    {
        return $this->only(['ban_id']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['post']);
    }

    
}