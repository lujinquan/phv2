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
use app\house\model\HouseTai as HouseTaiModel;

/**
 * 租金应缴
 */
class Rent extends Admin
{

    public function index()
    {

    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit', 10);
            $getData = $this->request->get();
            $RentModel = new RentModel;

            // $res = $RentModel->configRentOrder(); //生成本月份订单
            // if(!$res){
            //     $this->error('本月份订单生成失败！');
            // }
            //halt($limit);
            $where = $RentModel->checkWhere($getData,'rent');
            
            $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $data = [];
            $data['data'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            $data['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function createRentOrders()
    {
        if(INST_LEVEL != 3){
            return $this->error('请联系房管员生成本月份账单！','',['refresh'=>0]);
        }
        $RentModel = new RentModel;

        $res = $RentModel->configRentOrder(); //生成本月份订单

        if($res['code'] !== 1){
            return $this->error($res['msg'],'',['refresh'=>0]);
        }else{
            return $this->success($res['msg']);
        }
    }

    /**
     *  按上期欠缴处理（只处理全部已缴和全部未缴订单）
     *  
     */
    public function dealAsLast()
    {
        
        //验证合法性
        if(INST_LEVEL != 3){return $this->error('该功能暂时只对房管员开放');}

        $lastDate = date('Ym',strtotime('-1 month'));
        $ptime = time();

        $RentModel = new RentModel;
        // 获取上期订单
        $lastRents = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id')->join('ban c','b.ban_id = c.ban_id')->where([['a.rent_order_date','eq',$lastDate],['c.ban_inst_id','eq',INST]])->column('a.house_id,a.rent_order_cut,a.rent_order_diff,a.rent_order_pump,a.rent_order_receive,a.rent_order_paid');

        $nowRents = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id')->join('ban c','b.ban_id = c.ban_id')->where([['rent_order_date','eq',date('Ym')],['c.ban_inst_id','eq',INST],['a.ptime','eq',0]])->column('a.house_id,a.rent_order_id,a.rent_order_paid,a.rent_order_receive');

        $data = [];
        foreach($nowRents as $k => $v){
            // 过滤
            if(isset($lastRents[$k])){
                if($v['rent_order_receive'] == $lastRents[$k]['rent_order_receive']){
                    if($lastRents[$k]['rent_order_receive'] == $lastRents[$k]['rent_order_paid']){
                        $data[] = ['is_deal'=>1,'pay_way'=>1,'rent_order_id'=>$v['rent_order_id'],'ptime'=>$ptime,'rent_order_paid'=>Db::raw('rent_order_receive')];
                    }
                    if($lastRents[$k]['rent_order_paid'] == 0){
                        $data[] = ['is_deal'=>1,'rent_order_id'=>$v['rent_order_id'],'rent_order_paid'=>0];
                    }
                }
            }
            // if(in_array($key,$lastRents)){

            //     $data[] = ['is_deal'=>1,'pay_way'=>1,'rent_order_id'=>$nowRents[$v['house_id']]['rent_order_id'],'ptime'=>$ptime,'rent_order_paid'=>Db::raw('rent_order_receive')];
            // }
        }

        //halt($data);
        if($data) {
            $bool = $RentModel->saveAll($data);
            if($bool){
                return $this->success('处理成功');
            }else{
                return $this->error('处理失败');
            }
        }else{
            return $this->success('无匹配订单');
        }
    }

    /**
     *  批量缴费
     */
    public function payList()
    {
        // ini_get('max_input_vars'); 最大表单变量提交数量，一般是1000
        $ids = $this->request->param('id/a'); 
        $RentModel = new RentModel;      
        $res = $RentModel->payList($ids);
        if($res){

            $this->success('缴费成功，本次缴费'.$res.'条账单！');
        }else{
            $this->error('缴费失败');
        }
    }

    /**
     *  批量欠缴
     */
    public function unpayList()
    {
        $ids = $this->request->param('id/a'); 
        $RentModel = new RentModel;      
        $res = $RentModel->unpayList($ids);
        if($res){
            $this->success('欠缴成功，本次欠缴'.$res.'条账单！');
        }else{
            $this->error('欠缴失败');
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

    public function export()
    {   
        if ($this->request->isAjax()) {
            $getData = $this->request->post();
            $rentModel = new RentModel;
            $where = $rentModel->checkWhere($getData,'rent');
            $fields = 'a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            // 待优化，去掉limit全部取出数据会报错
            $tableData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->limit(10000)->order('a.rent_order_date desc')->select();

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
                    array('title' => '协议租金', 'field' => 'rent_order_diff', 'width' => 12,'type' => 'number'),
                    array('title' => '应收租金', 'field' => 'rent_order_receive', 'width' => 12,'type' => 'number'),
                    array('title' => '已缴租金', 'field' => 'rent_order_paid', 'width' => 12,'type' => 'number'),
                    array('title' => '欠缴租金', 'field' => 'rent_order_unpaid', 'width' => 12,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '租金应缴数据',
                    'Title' => '租金应缴数据',
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