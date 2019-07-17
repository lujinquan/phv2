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

namespace app\order\admin;
use app\order\model\OpOrder as OpOrderModel;
use app\system\admin\Admin;
use app\system\model\SystemAffiche;
use app\system\model\SystemUser as UserModel;

/**
 * 工单分类，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Classify extends Admin 
{
    /**
     * 工单分类设置
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index() 
    {
    	return $this->fetch();
    }
}