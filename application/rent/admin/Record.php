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

/**
 * 租金记录
 */
class Record extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RentModel = new RentModel;
            $where = $RentModel->checkWhere($getData,'record');
            $fields = 'a.rent_order_id,a.is_invoice,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $data = [];
            $data['data'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.rent_order_date desc')->select();
            $data['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     *  批量撤回
     */
    public function payBackList()
    {
        $ids = $this->request->param('id/a'); 
        $nowDate = date('Ym');
        $RentModel = new RentModel;
        $res = $RentModel->payBackList($ids,date('Ym'));
        if($res){
            $this->success('撤回成功，本次撤回'.$res.'条账单！');
        }else{
            $this->error('撤回失败，请检查订单期是否为 '.$nowDate.'!');
        }
    }

    public function detail()
    {
        $id = input('param.id/d');
        $RentModel = new RentModel;      
        $row = $RentModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }
    
}