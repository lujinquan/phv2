<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | HisiPHP提供个人非商业用途免费使用，商业需授权。
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

// [ 插件入口文件 ]
namespace think;

header('Content-Type:text/html;charset=utf-8');

// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 定义入口为插件
define('PLUGIN_ENTRANCE', true);

// 检查是否安装
if(!is_file('./../install.lock')) {
    header('location: /');
} else {
    Container::get('app')->bind('system/plugins')->run()->send();
}