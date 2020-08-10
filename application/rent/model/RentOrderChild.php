<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\common\model\Cparam as ParamModel;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\Recharge as RechargeModel;
use app\rent\model\RentRecycle as RentRecycleModel;

class RentOrderChild extends Model
{
	// 设置模型名称
    protected $name = 'rent_order_child';
    // 设置主键
    protected $pk = 'id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ptime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }

        $where = [];

        $where[] = ['rent_order_status','eq',1];
        // 检索月【租金】订单编号
        if(isset($data['rent_order_number']) && $data['rent_order_number']){
            $where[] = ['rent_order_number','like','%'.$data['rent_order_number'].'%'];
        }
        // 检索月【租金】订单编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索【收欠】支付方式
        if(isset($data['pay_way']) && $data['pay_way']){
            $where[] = ['pay_way','eq',$data['pay_way']];
        }
        // 检索【收欠】支付金额
        if(isset($data['rent_order_paid']) && $data['rent_order_paid']){
            $where[] = ['rent_order_paid','eq',$data['rent_order_paid']];
        }
        // 检索【租户】姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索【楼栋】地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索【楼栋】产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索【房屋】使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['house_use_id','in',explode(',',$data['house_use_id'])];
        }
        // 检索订单月份
        if(isset($data['rent_order_date']) && $data['rent_order_date']){
            $queryMonth = substr($data['rent_order_date'],0,4).substr($data['rent_order_date'],-2);
            $where[] = ['rent_order_date','eq',$queryMonth];
        }
        // 缴纳日期
        if(!isset($data['ptime'])){
            $pStartDate = strtotime(date('Y-m').'-01');
            $pEndDate = strtotime(date('Y-m',strtotime('1 month')).'-01');
            //dump($pStartDate);halt($pEndDate);
            $where[] = ['ptime','between',[$pStartDate,$pEndDate]]; 
        }
        if(isset($data['ptime']) && $data['ptime']){
            $pStartDate = strtotime(substr($data['ptime'],0,10));
            $pEndDate = strtotime(substr($data['ptime'],13,11));
            //dump($pStartDate);halt($pEndDate);
            $where[] = ['ptime','between',[$pStartDate,$pEndDate]];
        }
        // 检索机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $insts = explode(',',$data['ban_inst_id']);
            $instid_arr = [];
            foreach ($insts as $inst) {
                foreach (config('inst_ids')[$inst] as $instid) {
                    $instid_arr[] = $instid;
                }
            }
            $where[] = ['ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where[] = ['ban_inst_id','in',config('inst_ids')[INST]];
        }
        
        return $where;
    }

    public function detail($id)
    {
        $fields = "a.rent_order_id,a.rent_order_number,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.house_id,from_unixtime(a.ptime,'%Y-%m-%d %H:%i:%S') as ptime,a.pay_way,b.house_use_id,b.house_number,b.house_pre_rent,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['id','eq',$id]])->find();
        //halt($row);
        $row['pay_info'] = Db::name('rent_order')->where([['house_id','eq',$row['house_id']],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive-rent_order_paid) as total_unpaid_rent,sum(rent_order_paid) as total_paid_rent')->find();
        
        return $row;
    }
}