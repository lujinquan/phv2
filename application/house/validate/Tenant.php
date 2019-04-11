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

/**
 * 租户验证器
 * @package app\admin\validate
 */
class Tenant extends Validate
{
    //定义验证规则
    protected $rule = [
        'tenant_name|租户姓名' => 'require',
        'tenant_tel|联系电话' => 'require|number',
        'tenant_card|身份证号' => 'require|number',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    //定义验证场景
    protected $scene = [
        //新增
        'sceneForm'  =>  ['tenant_name','tenant_tel','tenant_card'],
        // //修改
        // 'edit'  =>  ['ban_struct_id'],    
    ];
}