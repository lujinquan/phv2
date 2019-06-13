<?php

namespace app\order\admin;
use app\system\admin\Admin;
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
    	//halt($operateAdmins);
    	$this->assign('data',$data);
    	$this->assign('operateAdmins',$operateAdmins);
    	return $this->fetch();
    }
}