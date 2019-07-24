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
    	//halt(ini_get('save_mode'));
    	return $this->fetch();
    }

}