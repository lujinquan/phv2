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

use app\system\admin\Admin;
use app\order\model\OpType;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 工单
 */
class Report extends Admin
{
    public function index()
    {
    	$OpOrderModel = new OpOrderModel;
    	$data = $OpOrderModel->statistics();
    	$operateAdmins = UserModel::where([['role_id','eq',11],['status','eq',1]])->field('id,nick')->select();
    	$opTypeModel = new OpType;
        $opTypeArr = $opTypeModel->where([['status','eq',1],['pid','eq',0]])->column('id,title');
        //halt($opTypeArr);
        $this->assign('data',$data);
    	$this->assign('opTypeArr',$opTypeArr);
    	$this->assign('operateAdmins',$operateAdmins);
    	return $this->fetch();
    }
}