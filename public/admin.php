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

// [ 后台入口文件 ]
namespace think;

header('Content-Type:text/html;charset=utf-8');
header('Access-Control-Allow-Origin:*'); //工单接入公房1.0用
// die();
// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');

// 定义入口为admin
define('ENTRANCE', 'admin');

define('EXTEND_PATH', __DIR__ .'/../extend/');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 检查是否安装
if(!is_file('./../install.lock')) {
    header('location: /');
} else {
    Container::get('app')->run()->send();
}