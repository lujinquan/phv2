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
use app\rent\model\Rent as RentModel;
use app\rent\model\Invoice as InvoiceModel;

/**
 * 发票记录
 */
class Invoice extends Admin
{
    
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();

            $InvoiceModel = new InvoiceModel;
            $where = $InvoiceModel->checkWhere($getData);
            
            $fields = 'a.invoice_id,a.fpqqlsh,a.jshj,a.hjje,a.hjse,from_unixtime(a.ctime, \'%Y-%m-%d\') as ctime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';

            $data['data'] = Db::name('rent_invoice')->alias('a')->join('weixin_order e','a.invoice_id = e.invoice_id','inner')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.invoice_id desc')->select();
            // $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_advance_rent,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,from_unixtime(a.house_ctime, \'%Y-%m-%d\') as house_ctime,from_unixtime(a.house_dtime, \'%Y-%m-%d\') as house_dtime,c.tenant_id,c.tenant_name,d.ban_units,d.ban_floors,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';
            // //halt($where);
            // $data = [];
            // $data['data'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order($order)->select();

            // foreach ($data['data'] as $k => &$v) {
            //     if($v['tenant_id']){ //如果当前房屋已经绑定租户
            //         $leaseInfo = Db::name('change_lease')->where([['house_id','eq',$v['house_id']],['change_status','eq',1],['tenant_id','eq',$v['tenant_id']]])->order('id desc')->field("from_unixtime(last_print_time, '%Y-%m-%d %H:%i:%s') as last_print_time,id as change_lease_id")->find();
            //         $v['last_print_time'] = $leaseInfo['last_print_time'];
            //         $v['change_lease_id'] = $leaseInfo['change_lease_id'];
            //     }else{
            //         $v['change_lease_id'] = '';
            //         $v['last_print_time'] = '';
            //     }  
            //     //halt($v);
            // }
            // // 统计房屋建面、计租面积、规租
            // $totalRow =  Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(house_lease_area) as total_house_lease_area, sum(house_area) as total_house_area, sum(house_pre_rent) as total_house_pre_rent , sum(house_cou_rent) as total_house_cou_rent, sum(house_diff_rent) as total_house_diff_rent, sum(house_pump_rent) as total_house_pump_rent, sum(house_advance_rent) as total_house_advance_rent')->find();
            // if($totalRow){
            //     $data['total_house_lease_area'] = $totalRow['total_house_lease_area'];
            //     $data['total_house_area'] = $totalRow['total_house_area'];
            //     $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
            //     $data['total_house_cou_rent'] = $totalRow['total_house_cou_rent'];
            //     $data['total_house_diff_rent'] = $totalRow['total_house_diff_rent'];
            //     $data['total_house_pump_rent'] = $totalRow['total_house_pump_rent'];
            //     $data['total_house_advance_rent'] = $totalRow['total_house_advance_rent'];
            // }

            // $data['count'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->count('a.house_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    // 发票查询
    public function fpcx()
    {
        // http://web.phv2.com/admin.php/rent/invoice/fpcx
        $InvoiceModel = new InvoiceModel;
        $res = json_decode($InvoiceModel->fpcx($sbh = '12420106441363712E' , $lsh = 'CLD20200917125422097'),true);
        dump($res);
        halt(json_decode($res['msg'],true));

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $InvoiceModel = new InvoiceModel;
            halt(json_decode($InvoiceModel->fpcx(),true));
        }
        return $this->fetch();
    }

    // 开票
    public function dpkj()
    {
        // if ($this->request->isPost()) {
        //     $data = $this->request->post();
        //     $InvoiceModel = new InvoiceModel;
        //     halt(json_decode($InvoiceModel->dpkj(),true));
        // }
        $id = input('param.id/d');
        $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
        $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        halt($row);
        //return $this->fetch();
    }

    public function detail()
    {
        $id = input('param.id');
        $InvoiceModel = new InvoiceModel;
        $row = $InvoiceModel->detail($id);
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }
}