<?php
namespace app\rent\model;

use think\Model;

class Rent extends Model
{
	// 设置模型名称
    protected $name = 'rent_order';
    // 设置主键
    protected $pk = 'rent_id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('app\house\model\House', 'house_id', 'house_id')->bind('ban_number');
    }

    public function tenant()
    {
        return $this->belongsTo('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_name');
    }

    // public function ban()
    // {
    //     return $this->hasManyThrough('app\house\model\Ban','app\house\model\House','house_number','ban_number');
    // }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        
        $where = [];
        // 检索月【租金】订单编号
        if(isset($data['rent_order_number']) && $data['rent_order_number']){
            $where[] = ['rent_order_number','like','%'.$data['rent_order_number'].'%'];
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
            $where[] = ['ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索【房屋】使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['house_use_id','eq',$data['house_use_id']];
        }
        
        // 检索【楼栋】机构
        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];

        return $where;
    }
}