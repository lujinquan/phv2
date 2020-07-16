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

namespace app\rent\admin;

use think\Db;
use app\system\admin\Admin;
use app\common\model\SystemExport;
use app\house\model\House as HouseModel;
use app\rent\model\Rent as RentModel;
use app\house\model\HouseTai as HouseTaiModel;

/**
 * 催缴单
 */
class Ask extends Admin
{

    public function index()
    {
    	return $this->fetch();
    }
}