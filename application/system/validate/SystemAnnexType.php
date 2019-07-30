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
 * 配置验证器
 * @package app\system\validate
 */
class SystemAnnexType extends Validate
{
    //定义验证规则
    protected $rule = [
        'file_name|附件分类名称'	=> 'require|unique:system_annex_type',
        'file_type|英文标识' => 'require|unique:system_annex_type',
        
    ];
}