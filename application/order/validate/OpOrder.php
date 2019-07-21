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

namespace app\order\validate;

use think\Validate;

/**
 * 工单验证器
 * @package app\admin\validate
 */
class OpOrder extends Validate
{
    //定义验证规则
    protected $rule = [
        'op_order_type|问题类型' => 'require|number',
        'remark|问题描述' => 'require',
        'replay|回复' => 'require',
        'transfer_to|转交人' => 'require',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    //定义验证场景
    protected $scene = [
        //新增
        'sceneForm'  =>  ['op_order_type','remark'],
        // 转交工单
        'sceneTransfer'  =>  ['transfer_to','replay'],   
        // 完结工单     
        'sceneEnd'  =>  ['replay'],  
        // 退回至发起人     
        'sceneBackToFirst'  =>  ['replay'],    
    ];
}