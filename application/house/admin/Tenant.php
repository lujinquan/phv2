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

use app\system\admin\Admin;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;

class Tenant extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $TenantModel = new TenantModel;
            $where = $TenantModel->checkWhere($getData);
            $fields = 'tenant_id,tenant_inst_id,tenant_inst_pid,tenant_number,tenant_name,tenant_tel,tenant_card';
            $data = [];
            $data['data'] = TenantModel::field($fields)->where($where)->page($page)->order('tenant_ctime desc')->limit($limit)->select();
            $data['count'] = TenantModel::where($where)->count('tenant_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $group = input('group','y');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '正常',
                'url' => '?group=y',
            ],
            [
                'title' => '新发',
                'url' => '?group=x',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Tenant.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $TenantModel = new TenantModel();
            // 数据过滤
            $filData = $TenantModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$TenantModel->allowField(true)->create($filData)) {
                return $this->error('新增失败');
            }
            return $this->success('新增成功');
        }
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Tenant.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $TenantModel = new TenantModel();
            // 入库
            if (!$TenantModel->allowField(true)->update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $id = input('param.id/d');
        $row = TenantModel::get($id);
        $row['tenant_imgs'] = SystemAnnex::changeFormat($row['tenant_imgs']);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function detail()
    {
        $id = input('param.id/d');
        $row = TenantModel::with(['system_user'])->find($id);
        $row['tenant_imgs'] = SystemAnnex::changeFormat($row['tenant_imgs']);
        // 获取租户的余额
        $row['tenant_balance'] = HouseModel::where([['tenant_id','eq',$row['tenant_id']]])->sum('house_balance');

        //获取租户的合计欠租情况
        $rentRow = RentModel::where([['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive) as rent_order_receives,sum(rent_order_paid) as rent_order_paid')->find(); //欠租
        $row['rent_order_unpaid'] = $rentRow['rent_order_receives']-$rentRow['rent_order_paid'];
        
        $group = input('param.group');
        $this->assign('group',$group);

        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function del()
    {
        $ids = $this->request->param('id/a');        
        $res = TenantModel::where([['tenant_id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }  
    }
}