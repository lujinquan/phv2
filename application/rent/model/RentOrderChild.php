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

    public function detail($id)
    {
        $fields = "a.rent_order_id,a.rent_order_number,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.house_id,from_unixtime(a.ptime,'%Y-%m-%d %H:%i:%S') as ptime,a.pay_way,b.house_use_id,b.house_number,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['id','eq',$id]])->find();
        //halt($row);
        $row['pay_info'] = Db::name('rent_order')->where([['house_id','eq',$row['house_id']],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive-rent_order_paid) as total_unpaid_rent,sum(rent_order_paid) as total_paid_rent')->find();
        
        return $row;
    }
}