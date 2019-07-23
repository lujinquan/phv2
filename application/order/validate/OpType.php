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
class OpType extends Validate
{
    //定义验证规则
    protected $rule = [
        'pid|父级分类' => 'require',
        'title|分类名称' => 'require|unique:op_type',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    //定义验证场景
    protected $scene = [
        //新增
        'sceneForm'  =>  ['pid','title'],
    
    ];
}