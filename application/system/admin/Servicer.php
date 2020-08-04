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

namespace app\system\admin;


/**
 * 服务器维护
 * @package app\system\admin
 */
class Servicer extends Admin
{
    public $tabData = [];
    protected $hisiTable = 'SystemUser';
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();

        $tabData['menu'] = [
            [
                'title' => '管理员角色',
                'url' => 'system/user/role',
            ],
            [
                'title' => '系统管理员',
                'url' => 'system/user/index',
            ],
        ];
        $this->tabData = $tabData;
    }

    /**
     * 用户管理
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index($q = '')
    {
        $memory_limit = ini_get('memory_limit');
        $max_input_vars = ini_get('max_input_vars');
        $default_socket_timeout = ini_get('default_socket_timeout');
        $enable_dl = ini_get('enable_dl');
        $report_memleaks = ini_get('report_memleaks');
        $max_execution_time = ini_get('max_execution_time');
        $save_mode = ini_get('save_mode');
        $post_max_size = ini_get('post_max_size');
        $upload_max_filesize = ini_get('upload_max_filesize');
        $error_log = ini_get('error_log');
        $this->assign([
            'memory_limit' => $memory_limit,
            'max_input_vars' => $max_input_vars,
            'default_socket_timeout' => $default_socket_timeout,
            'enable_dl' => $enable_dl,
            'report_memleaks' => $report_memleaks,
            'max_execution_time' => $max_execution_time,
            'save_mode' => $save_mode,
            'post_max_size' => $post_max_size,
            'upload_max_filesize' => $upload_max_filesize,
            'error_log' => $error_log,
        ]);
    	return $this->fetch();
    }

}