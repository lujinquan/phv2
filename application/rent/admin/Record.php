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
use app\rent\model\Rent as RentModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;

/**
 * 租金记录
 */
class Record extends Admin
{
    /*public function demo()
    {
        //halt(1);
        set_time_limit(0);

        $rent_order_ids = Db::name('rent_recycle')->group('rent_order_id')->column('rent_order_id');

        $where = [];
        $where[] = ['rent_order_paid','>',0];
        $where[] = ['rent_order_status','eq',1];
        //$where[] = ['rent_order_id','between',[1,10000]];
        //$where[] = ['rent_order_id','between',[50001,100000]];
        $rent_orders = Db::name('rent_order')->where($where)->field('rent_order_id,rent_order_paid,rent_order_date,house_id,tenant_id,ctime,ptime')->limit(100000)->select();
        foreach ($rent_orders as $k => $v) {
            
            if(in_array($v['rent_order_id'], $rent_order_ids)){ //能找到表示，又收欠

            }else{ // 没有表示，没收欠，要写入数据
                $data = [];
                $data['rent_order_id'] = $v['rent_order_id'];
                $data['house_id'] = $v['house_id'];
                $data['tenant_id'] = $v['tenant_id'];
                $data['pay_rent'] = $v['rent_order_paid'];

                $data['ctime'] = $v['ptime'];
                $data['cdate'] = date('Ym',$v['ptime']);

                $data['pay_month'] = $v['rent_order_date'];
                $data['pay_year'] = substr($v['rent_order_date'], 0 , 4);
                
                $data['pay_way'] = 1;
                
                Db::name('rent_recycle_copy')->insert($data);
                Db::name('rent_order')->where([['rent_order_id','eq',$v['rent_order_id']]])->update(['rent_order_status'=>0]);
                //halt($data);
            }
            
            
        }
        halt($where);
        
    }*/

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RentOrderChildModel = new RentOrderChildModel;
            $where = $RentOrderChildModel->checkWhere($getData);
            $fields = "a.id,a.rent_order_id,a.pay_way,a.is_invoice,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,from_unixtime(a.ptime, '%Y-%m-%d %H-%i-%s') as ptime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.ptime desc')->select();
            //halt($data['data']);
            $data['count'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
            // 统计
            $totalRow = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(a.rent_order_receive) as total_rent_order_receive,  sum(b.house_pre_rent) as total_house_pre_rent,  sum(a.rent_order_paid) as total_rent_order_paid, sum(a.rent_order_cut) as total_rent_order_cut, sum(a.rent_order_diff) as total_rent_order_diff, sum(a.rent_order_pump) as total_rent_order_pump, sum(b.house_cou_rent) as total_house_cou_rent')->find();
            if($totalRow){
                $data['total_rent_order_receive'] = $totalRow['total_rent_order_receive'];
                $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
                $data['total_rent_order_paid'] = $totalRow['total_rent_order_paid'];
                $data['total_rent_order_cut'] = $totalRow['total_rent_order_cut'];
                $data['total_rent_order_diff'] = $totalRow['total_rent_order_diff'];
                $data['total_rent_order_pump'] = $totalRow['total_rent_order_pump'];
                $data['total_house_cou_rent'] = $totalRow['total_house_cou_rent'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    // public function index()
    // {

    //     if ($this->request->isAjax()) {
    //         set_time_limit(0);

    //         $page = input('param.page/d', 1);
    //         $limit = input('param.limit/d', 10);
    //         $getData = $this->request->get();
    //         $RentModel = new RentModel;
    //         $where = $RentModel->checkWhere($getData,'record');
    //         $fields = "a.rent_order_id,a.pay_way,e.is_invoice,e.rent_order_date,e.rent_order_number,e.rent_order_receive,a.pay_rent,e.rent_order_diff,e.rent_order_pump,e.rent_order_cut,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
    //         $data = [];
    //         $data['data'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('rent_order e','a.rent_order_id = e.rent_order_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('e.rent_order_date desc')->select();
    //         //halt($data['data']);
    //         $data['count'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('rent_order e','a.rent_order_id = e.rent_order_id','left')->where($where)->count('a.rent_order_id');
    //         // 统计
    //         // $totalRow = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('rent_order e','a.rent_order_id = e.rent_order_id','left')->where($where)->field('sum(e.rent_order_receive) as total_rent_order_receive, sum(b.house_pre_rent) as total_house_pre_rent,  sum(a.pay_rent) as total_pay_rent, sum(e.rent_order_cut) as total_rent_order_cut, sum(e.rent_order_diff) as total_rent_order_diff, sum(e.rent_order_pump) as total_rent_order_pump, sum(b.house_cou_rent) as total_house_cou_rent')->find();
    //         // if($totalRow){
    //         //     $data['total_rent_order_receive'] = $totalRow['total_rent_order_receive'];
    //         //     $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
    //         //     $data['total_pay_rent'] = $totalRow['total_pay_rent'];
    //         //     $data['total_rent_order_cut'] = $totalRow['total_rent_order_cut'];
    //         //     $data['total_rent_order_diff'] = $totalRow['total_rent_order_diff'];
    //         //     $data['total_rent_order_pump'] = $totalRow['total_rent_order_pump'];
    //         //     $data['total_house_cou_rent'] = $totalRow['total_house_cou_rent'];
    //         // }
    //         $data['code'] = 0;
    //         $data['msg'] = '';
    //         return json($data);
    //     }
    //     return $this->fetch();
    // }  

    // public function index()
    // {
    //     if ($this->request->isAjax()) {
    //         $page = input('param.page/d', 1);
    //         $limit = input('param.limit/d', 10);
    //         $getData = $this->request->get();
    //         $RentModel = new RentModel;
    //         $where = $RentModel->checkWhere($getData,'record');
    //         $fields = "a.rent_order_id,a.pay_way,a.is_invoice,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,from_unixtime(a.ptime, '%Y-%m-%d') as ptime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
    //         $data = [];
    //         //halt($where);
    //         $data['data'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('rent_record e','a.rent_order_id = e.rent_order_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.rent_order_date desc')->select();
    //         //halt($data);
    //         $data['count'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
    //         // 统计
    //         $totalRow = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(a.rent_order_receive) as total_rent_order_receive,  sum(b.house_pre_rent) as total_house_pre_rent,  sum(a.rent_order_paid) as total_rent_order_paid, sum(a.rent_order_cut) as total_rent_order_cut, sum(a.rent_order_diff) as total_rent_order_diff, sum(a.rent_order_pump) as total_rent_order_pump, sum(b.house_cou_rent) as total_house_cou_rent')->find();
    //         if($totalRow){
    //             $data['total_rent_order_receive'] = $totalRow['total_rent_order_receive'];
    //             $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
    //             $data['total_rent_order_paid'] = $totalRow['total_rent_order_paid'];
    //             $data['total_rent_order_cut'] = $totalRow['total_rent_order_cut'];
    //             $data['total_rent_order_diff'] = $totalRow['total_rent_order_diff'];
    //             $data['total_rent_order_pump'] = $totalRow['total_rent_order_pump'];
    //             $data['total_house_cou_rent'] = $totalRow['total_house_cou_rent'];
    //         }
    //         $data['code'] = 0;
    //         $data['msg'] = '';
    //         return json($data);
    //     }
    //     return $this->fetch();
    // }

    /**
     *  批量撤回
     */
    public function payBackList()
    {
        $ids = $this->request->param('id/a');
        $RentModel = new RentModel;
        $res = $RentModel->payBackList($ids,date('Y-m'), 'record');
        // halt($res);
        if($res['error_code']){
            $this->error($res['msg']);
        }else{
            
            $this->success($res['msg']);
        }
    }

    public function detail()
    {
        $id = input('param.id/d');
        // $RentModel = new RentModel;      
        // $row = $RentModel->detail($id);

        $RentOrderChildModel = new RentOrderChildModel;
        $row = $RentOrderChildModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    // public function detail()
    // {
    //     $id = input('param.id/d');
    //     $RentModel = new RentModel;      
    //     $row = $RentModel->detail($id);
    //     $this->assign('data_info',$row);
    //     return $this->fetch();
    // }

    public function export()
    {   
        if ($this->request->isAjax()) {
            //ini_set('memory_limit', '300M');
            $getData = $this->request->get();
            $RentOrderChildModel = new RentOrderChildModel;
            $where = $RentOrderChildModel->checkWhere($getData);
            $fields = "a.pay_way,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.rent_order_diff,a.rent_order_pump,b.house_protocol_rent,a.rent_order_cut,from_unixtime(a.ptime, '%Y-%m-%d %H-%i-%s') as ptime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $tableData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ptime desc')->select();

            //halt($tableData);
            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '租金订单号', 'field' => 'rent_order_number', 'width' => 24,'type' => 'string'),
                    array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                    array('title' => '账单期', 'field' => 'rent_order_date', 'width' => 12,'type' => 'string'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                    array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                    array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                    array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                    array('title' => '规定租金', 'field' => 'house_pre_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '计算租金', 'field' => 'house_cou_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '减免', 'field' => 'rent_order_cut', 'width' => 12,'type' => 'number'),
                    array('title' => '租差', 'field' => 'rent_order_diff', 'width' => 12,'type' => 'number'),
                    array('title' => '泵费', 'field' => 'rent_order_pump', 'width' => 12,'type' => 'number'),
                    array('title' => '协议租金', 'field' => 'house_protocol_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '应收租金', 'field' => 'rent_order_receive', 'width' => 12,'type' => 'number'),
                    array('title' => '已缴租金', 'field' => 'rent_order_paid', 'width' => 12,'type' => 'number'),
                    array('title' => '缴纳方式', 'field' => 'pay_way', 'width' => 12,'type' => 'number'),
                    array('title' => '缴纳时间', 'field' => 'ptime', 'width' => 24,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '租金记录数据',
                    'Title' => '租金记录数据',
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