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
 * 租金应缴
 */
class Rent extends Admin
{

    public function index()
    {

    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RentModel = new RentModel;

            $res = $RentModel->configRentOrder(); //生成本月份订单
            //halt($res);
            if(!$res){
                $this->error('本月份订单生成失败！');
            }

            $where = $RentModel->checkWhere($getData,'rent');
            
            $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.is_invoice,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $data = [];
            $data['data'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            $data['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     *  按上期欠缴处理
     */
    public function dealAsLast()
    {
        
        //验证合法性
        if(INST_LEVEL != 3){return $this->error('该功能暂时只对房管员开放');}

        $lastDate = date('Ym',strtotime('-1 month'));
        $ptime = time();

        $RentModel = new RentModel;
        // 获取上期订单
        $lastRents = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id')->join('ban c','b.ban_id = c.ban_id')->where([['a.rent_order_date','eq',$lastDate],['c.ban_inst_id','eq',INST],['a.ptime','eq',0]])->column('a.house_id');

        $nowRents = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id')->join('ban c','b.ban_id = c.ban_id')->where([['rent_order_date','eq',date('Ym')],['c.ban_inst_id','eq',INST],['a.ptime','eq',0]])->column('a.house_id,a.rent_order_id,a.rent_order_paid,a.rent_order_receive');

        $data = [];

        foreach($nowRents as $key => $v){
            if(!in_array($key,$lastRents)){
                $data[] = ['is_deal'=>1,'rent_order_id'=>$nowRents[$v['house_id']]['rent_order_id'],'ptime'=>$ptime,'rent_order_paid'=>Db::raw('rent_order_receive')];
            }
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
        $ids = $this->request->param('id/a'); 
        $ptime = time();       
        $res = RentModel::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>$ptime,'rent_order_paid'=>Db::raw('rent_order_receive')]);
        if($res){
            $this->success('缴费成功，本次缴费'.$res.'条账单！');
        }else{
            $this->error('缴费失败');
        }
    }
}