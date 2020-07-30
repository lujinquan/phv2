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
 * 公告验证器
 * @package app\system\validate
 */
class SystemBasis extends Validate
{
	//定义验证规则
    protected $rule = [
        'title|版本号'       => 'require',
        'content|内容'      => 'require',
        //'__token__'      => 'require|token',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入版本号',
    ];

}