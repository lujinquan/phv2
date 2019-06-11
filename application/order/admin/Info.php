<?php

namespace app\order\admin;
use app\system\admin\Admin;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 工单
 */
class Info extends Admin
{
    public function index()
    {
    	return $this->fetch();
    }
}