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

/**
 * HisiPHP框架默认路由，升级会被覆盖，请勿修改
 */

// 插件路由
Route::rule('plugins/:_p/:_c/:_a', 'system/plugins/index?_p=:_p&_c=:_c&_a=:_a');
Route::rule('plugins/:_p/:_c', 'system/plugins/index?_p=:_p&_c=:_c&_a=index');

// 应用商店推送
Route::rule('push/module', 'system/push/module');
Route::rule('push/plugins', 'system/push/plugins');
Route::rule('push/theme', 'system/push/theme');