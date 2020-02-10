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
use app\common\model\SystemExport;
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
            $fields = "a.id,a.change_order_number,a.szno,from_unixtime(a.last_print_time, '%Y-%m-%d') as last_print_time,b.house_use_id,b.house_area,b.house_oprice,c.tenant_name,c.tenant_tel,c.tenant_card,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.ctime desc')->limit($limit)->select();
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

    public function export()
    {   
        if ($this->request->isAjax()) {
            $getData = $this->request->post();
            $changeLeaseModel = new ChangeLeaseModel;
            $where = $changeLeaseModel->checkWhere($getData,'record');
            $fields = "a.change_order_number,a.szno,from_unixtime(a.last_print_time, '%Y-%m-%d') as last_print_time,a.change_status,b.house_use_id,b.house_area,b.house_oprice,c.tenant_name,c.tenant_tel,c.tenant_card,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        
            $tableData = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ctime desc')->select();
            
            //halt($tableData);
            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '租约编号', 'field' => 'szno', 'width' => 24 ,'type' => 'string'),
                    array('title' => '租约申请单号', 'field' => 'change_order_number', 'width' => 24 ,'type' => 'string'),
                    array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                    array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '联系方式', 'field' => 'tenant_tel', 'width' => 24,'type' => 'number'),
                    array('title' => '身份证号', 'field' => 'tenant_card', 'width' => 24,'type' => 'string'),
                    array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                    array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                    array('title' => '房屋建面', 'field' => 'house_area', 'width' => 12,'type' => 'number'),
                    array('title' => '房屋原价', 'field' => 'house_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '出证时间', 'field' => 'last_print_time', 'width' => 24,'type' => 'string'),
                    array('title' => '状态', 'field' => 'change_status', 'width' => 12,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '租户数据',
                    'Title' => '租户数据',
                ];
                
                return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
            }else{
                $result = [];
                $result['code'] = 0;
                $result['msg'] = '数据为空！';
                return json($result); 
            }
            
        }
        
    }

}