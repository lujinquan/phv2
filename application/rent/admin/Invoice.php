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
        $InvoiceModel = new InvoiceModel;
        //halt(json_decode($InvoiceModel->dpkj(),true));
        return $this->fetch();
    }

    // 发票查询
    public function fpcx()
    {
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
        $id = input('param.id/d');
        $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
        $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        $this->assign('data_info',$row);
        return $this->fetch();
    }
}