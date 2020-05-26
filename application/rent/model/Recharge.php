<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;

class Recharge extends Model
{
	// 设置模型名称
    protected $name = 'rent_recharge';
    // 设置主键
    protected $pk = 'id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('app\house\model\House', 'house_id', 'house_id')->bind('ban_number');
    }

    public function tenant()
    {
        return $this->belongsTo('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_name');
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }

        $where = [];
        $where[] = ['a.recharge_status','eq',1];
        // 检索【租户】姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索【房屋】编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索【楼栋】地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索【楼栋】产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索【房屋】使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['b.house_use_id','in',explode(',',$data['house_use_id'])];
        }
        // 检索【房屋】支付金额
        if(isset($data['pay_rent']) && $data['pay_rent']){
            $where[] = ['a.pay_rent','eq',$data['pay_rent']];
        }
        // 检索【收欠】支付方式
        if(isset($data['pay_way']) && $data['pay_way']){
            $where[] = ['a.pay_way','eq',$data['pay_way']];
        }
        // 检索【收欠】支付时间
        if(isset($data['ctime']) && $data['ctime']){
            $startTime = strtotime(substr($data['ctime'],0,10));
            $endTime = strtotime(substr($data['ctime'],-10));
            $where[] = ['a.ctime','between',[$startTime,$endTime]];
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
        // 检索【楼栋】机构
        // $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        // $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        //$where[] = ['rent_order_date','eq',date('Ym')];
        //halt($where);
        return $where;
    }

    public function dataFilter($data)
    {
        $row = HouseModel::where([['house_number','eq',$data['house_number']]])->field('house_id,tenant_id')->find();
        $data['house_id'] = $row['house_id'];
        $data['tenant_id'] = $row['tenant_id'];
        $data['pay_number'] = date('YmdHis') . random(6);
        unset($data['id'],$data['house_number']);
        return $data;
    }

    public function detail($id)
    {
        $fields = "a.pay_number,a.pay_rent,a.pay_way,from_unixtime(a.ctime,'%Y-%m-%d %H:%i:%S') as ctime,b.house_use_id,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['id','eq',$id]])->find();
        return $row;
    }
    
}