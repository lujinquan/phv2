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

namespace app\wechat\validate;

use think\Validate;

/**
 * 办事指引验证器
 * @package app\system\validate
 */
class WeixinGuide extends Validate
{
	//定义验证规则
    protected $rule = [
        'title|标题'       => 'require|length:4,30',
        'is_show|是否启用'       => 'require|number',
        'sort|排序'       => 'require|number',
        'content|内容'      => 'require',
        //'__token__'      => 'require|token',
    ];

    //定义验证提示
    protected $message = [
        // 'title.require' => '请输入标题',
        // 'content.require'    => '内容不能为空',
    ];

}