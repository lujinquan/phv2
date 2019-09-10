<?php
namespace app\house\model;

use app\system\model\SystemBase;
use app\house\model\Room as RoomModel;
use app\house\model\Tenant as TenantModel;

class House extends SystemBase
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
        return $this->belongsTo('ban', 'ban_id', 'ban_id')->bind('ban_owner_id,ban_number,ban_inst_id,ban_address,ban_units,ban_floors,ban_struct_id,ban_is_levator');
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

        switch ($group) {
            case 'y':
                $where['house'][] = [['house_status','eq',1]];
                break;
            case 'x':
                $where['house'][] = [['house_status','eq',0]];
                break;
            case 'z':
                $where['house'][] = [['house_status','>',1]];
                break;
            default:
                $where['house'][] = [['house_status','eq',1]];
                break;
        }
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

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
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

    /**
     * [计算房屋计算租金]
     * @param  [type] $houseid [房屋编号]
     * @return [type]        
     */
    public function count_house_rent($houseid){
        // 特殊的房屋计算租金
        if(in_array($houseid,array(666,888,999))){
            return 0;
        }
        $row = self::with('ban')->find($houseid);
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->column('room_id');

        //halt($row->house_room()->column('room_id'));
        $roomRents = RoomModel::where([['room_id','in',$rooms],['room_status','<=',1]])->column('room_id,room_cou_rent'); 
        $sumrent = 0;
        if($roomRents){
            $rent = [];
            foreach ($roomRents as $k=>$v) {
                $rent[$k] = $v;         
            }
            $sumrent = array_sum($rent);
        }

        if($row['ban_number'] == '1050053295'){
            return $row['house_pre_rent'];
        }else{
            //PlusRent加计租金（面盆浴盆，5米以上，5米以下什么的），DiffRent租差，ProtocolRent协议租金
            $houseRent = $sumrent  + $row['house_diff_rent'] + $row['house_protocol_rent'];
            // 民用的四舍五入保留一位，机关企业的四舍五入保留两位 
            return ($row['house_use_id'] == 1)?round($houseRent,1):round($houseRent,2); 
        }

    }
}