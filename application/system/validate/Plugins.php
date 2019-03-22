<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\system\validate;

use think\Validate;

/**
 * 插件验证器
 * @package app\system\validate
 */
class Plugins extends Validate
{
    //定义验证规则
    protected $rule = [
		'name|插件名'			=> 'require|alphaDash|unique:system_plugins',
		'title|插件标题'			=> 'require',
		'identifier|插件标识'		=> 'require|unique:system_plugins',
    ];
}
