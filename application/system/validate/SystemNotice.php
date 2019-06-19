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
class SystemNotice extends Validate
{
	//定义验证规则
    protected $rule = [
        'title|标题'       => 'require|unique:system_notice',
        'inst_id|可见范围'    => 'require|number',
        'type|公告类型'    => 'require|number',
        'content|内容'      => 'require',
        '__token__'      => 'require|token',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入标题',
        'inst_id.require'  => '请选择可见范围',
        'type.require'    => '请选择公告类型',
        'content.require'    => '内容不能为空',
    ];

}