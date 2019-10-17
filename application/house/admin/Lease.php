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

namespace app\house\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\ChangeLease as ChangeLeaseModel;

class Lease extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeLeaseModel = new ChangeLeaseModel;
            $where = $ChangeLeaseModel->checkWhere($getData,'record');
            //halt($where);
            $fields = 'a.id,a.change_order_number,a.szno,b.house_use_id,c.tenant_name,c.tenant_tel,c.tenant_card,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $data = [];
            $data['data'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }

    	return $this->fetch();
    }

    public function detail()
    {
    	if ($this->request->isAjax()) {
    		$id = input('param.id/d');
    		$data = [];
        	$data['data'] = ChangeLeaseModel::with(['house','tenant'])->get($id);
        	$data['code'] = 0;
            $data['msg'] = '';
            return json($data);
    	}
        $id = input('param.id/d');
        $ChangeLeaseModel = new ChangeLeaseModel;
        $row = $ChangeLeaseModel->detail($id);
        $this->assign('id',$id);
        $this->assign('data_info',$row);
    	return $this->fetch();
    }


}