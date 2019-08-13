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
 * 楼栋验证器
 * @package app\admin\validate
 */
class Ban extends Validate
{
    //定义验证规则
    protected $rule = [
        'ban_struct_id|结构类别' => 'require|number',
        'ban_damage_id|完损等级' => 'require|number',
        'ban_owner_id|产别' => 'require|number',
        'ban_units|单元数' => 'require|number',
        'ban_floors|楼层数' => 'require|number',
        'ban_ratio|栋系数' => 'float',
        'ban_build_year|建成年份' => 'require|date',
        'ban_address|地址' => 'require',   
    ];

    //定义验证提示
    protected $message = [
        
    ];

    //定义验证场景
    protected $scene = [
        //新增
        'sceneForm'  =>  ['ban_address','ban_owner_id','ban_struct_id','ban_damage_id','ban_units','ban_floors','ban_ratio','ban_build_year'],
          
    ];
}