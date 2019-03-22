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
namespace app\common\behavior;

use Env;
use Request;
use Route;
use think\Container;
use app\system\model\SystemModule as SystemModule;

/**
 * 应用初始化行为
 */
class Init
{
    public function run()
    {
        define('ROOT_PATH', Env::get('root_path'));
        define('IN_SYSTEM', true);
        
        if (defined('INSTALL_ENTRANCE')) return;

        $path = Request::instance()->pathinfo();
        $bind = Route::getBind();

        if ($path != '/' && strtolower($path) != 'index' && !$bind) {

            $path = explode('/', $path);

            if (isset($path[0]) && !empty($path[0])) {

                if (is_dir(Env::get('app_path').'/'.$path[0])) {
                    $bind = $path[0];
                }

            }

        }

        // 设置前台默认模块
        if (!defined('ENTRANCE') && !$bind) {

            $map    = [];
            $map[]  = ['default', '=', 1];
            $map[]  = ['status', '=', 2];

            if ($name = SystemModule::where($map)->value('name')) {
                Container::get('app')->bind($name);
            }
            
        }

        // 系统版本
        $version = include_once(Env::get('root_path').'version.php');
        config($version);
    }
}
