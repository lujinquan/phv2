<?php
namespace app\house\model;

use think\Model;

class House extends Model
{
	// 设置模型名称
    protected $name = 'house';
    // 设置主键
    protected $pk = 'house_id';
    // 定义时间戳字段名
    protected $createTime = 'house_ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'house_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function ban()
    {
        return $this->belongsTo('ban', 'ban_id', 'ban_id')->bind('ban_owner_id,ban_inst_id,ban_address,ban_units,ban_floors,ban_struct_id');
    }

    public function tenant()
    {
        return $this->hasOne('tenant', 'tenant_id', 'tenant_id')->bind('tenant_name,tenant_tel,tenant_card');
    }

    public function house_room()
    {
        return $this->hasMany('house_room', 'house_number', 'house_number');
    }

    // public function tenant()
    // {
    //     return $this->belongsTo('tenant', 'tenant_number', 'tenant_number')->bind('tenant_name,tenant_tel,tenant_card');
    // }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $group = isset($data['group'])?$data['group']:'y';
        $where = [];
        $where['ban'] = $where['house'] = $where['tenant'] = [];
        $where['house'][] = ($group == 'y')?[['house_status','eq',1]]:[['house_status','neq',1]];
        // 检索【租户】姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where['tenant'][] = ['tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索【房屋】编号
        if(isset($data['house_number']) && $data['house_number']){
            $where['house'][] = ['house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索【房屋】使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where['house'][] = ['house_use_id','eq',$data['house_use_id']];
        }
        // 检索【楼栋】编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $where['ban'][] = ['ban_number','like','%'.$data['ban_number'].'%'];
        }
        // 检索【楼栋】地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where['ban'][] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索【楼栋】产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where['ban'][] = ['ban_owner_id','eq',$data['ban_owner_id']];
        }
        //检索管段
        $insts = config('inst_ids');
        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        $where['ban'][] = ['ban_inst_id','in',$insts[$instid]];

        return $where;
    }

    public function dataFilter($data)
    {
        $data['house_cuid'] = ADMIN_ID;
        $maxHouseNumber = self::where([['house_number', 'like', $data['ban_number'] . '%']])->max('house_number');
        if (!$maxHouseNumber) {
            $data['house_number'] = $data['ban_number'] . '0001';
        } else {
            $data['house_number'] = $maxHouseNumber + 1;
        }
        return $data; 
    }
}