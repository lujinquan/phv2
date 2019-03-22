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
use app\system\model\SystemMenu as MenuModel;

/**
 * 菜单验证器
 * @package app\system\validate
 */
class SystemMenu extends Validate
{
    //定义验证规则
    protected $rule = [
        'url|菜单链接'      => 'requireWith:url|checkUrl:thinkphp',
        'module|所属模块'   => 'require',
        'pid|所属菜单'      => 'require',
        'title|菜单名称'    => 'require',
    ];

    //定义验证提示
    protected $message = [
        'module.require' => '请选择所属模块',
        'pid.require'    => '请选择所属菜单',
        'url.require'    => '菜单链接已存在',
    ];

    // 自定义菜单链接验证规则
    protected function checkUrl($value, $rule, $data)
    {
        return true;
        $map = [];
        $map['url'] = $value;
        $map['param'] = $data['param'];
        if (isset($data['id']) && $data['id'] > 0) {
            $map['id'] = ['neq', $data['id']];
        }
        $res = MenuModel::where($map)->find();

        if ($data['param']) {
            return $res ? '菜单链接和扩展参数已存在' : true;
        }
        return $res ? '菜单链接已存在' : true;
    }
}
