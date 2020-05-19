<?php
namespace app\house\model;

use app\system\model\SystemBase;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\HouseRoom as HouseRoomModel;
use app\house\model\FloorPoint as FloorPointModel;

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
        return $this->belongsTo('ban', 'ban_id', 'ban_id')->bind('ban_owner_id,ban_number,ban_inst_id,ban_inst_pid,ban_address,ban_units,ban_floors,ban_struct_id,ban_damage_id,ban_is_levator');
    }

    public function tenant()
    {
        return $this->hasOne('tenant', 'tenant_id', 'tenant_id')->bind('tenant_name,tenant_number,tenant_tel,tenant_card');
    }

    public function ChangeLease()
    {
        return $this->hasOne('app\deal\model\ChangeLease', 'house_id', 'house_id')->bind('last_print_time');
    }

    public function house_room()
    {
        return $this->hasMany('house_room', 'house_id', 'house_id');
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

        switch ($group) {
            case 'y':
                $where[] = [['a.house_status','eq',1]];
                break;
            case 'x':
                $where[] = [['a.house_status','eq',0]];
                break;
            case 'z':
                $where[] = [['a.house_status','>',1]];
                break;
            default:
                $where[] = [['a.house_status','eq',1]];
                break;
        }
        //dump($group);halt($where);
        // 检索【租户】姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索【房屋】编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['a.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索【房屋】使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            if(is_array($data['house_use_id'])){
                $where[] = ['a.house_use_id','in',$data['house_use_id']];
            }else{
                $where[] = ['a.house_use_id','eq',$data['house_use_id']];
            }
        }
        // 检索【楼栋】编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $banids = BanModel::where([['ban_number','like','%'.$data['ban_number'].'%']])->column('ban_id');
            if($banids){
                //halt($banid);
                $roomids = RoomModel::where([['ban_id','in',$banids]])->column('room_id');

                if($roomids){
                    $houses = HouseRoomModel::where([['room_id','in',$roomids]])->column('house_id');

                    $bans = HouseModel::where([['house_id','in',array_unique($houses)]])->column('ban_id');
                    $where[] = ['d.ban_id','in',$bans];
                }else{
                    $where[] = ['d.ban_number','like','%'.$data['ban_number'].'%']; 
                }
            }else{
                $where[] = ['d.ban_number','like','%'.$data['ban_number'].'%'];
            }
            
            
            
        }
        // 检索【楼栋】地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索【楼栋】产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            if(is_array($data['ban_owner_id'])){
                $where[] = ['d.ban_owner_id','in',$data['ban_owner_id']];
            }else{
                $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
            }
        }
        //检索管段
        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        $where[] = ['d.ban_inst_id','in',$insts[$instid]];

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
        $banInfo = BanModel::where([['ban_id', 'eq', $data['ban_id']]])->field('ban_owner_id,ban_inst_pid')->find();
                $params = ParamModel::getCparams();
        $owner = $params['owners'][$banInfo['ban_owner_id']];
        //halt($owner);
        $data['house_szno'] = '租直昌'.mb_substr($owner,0,1).'0'.$banInfo['ban_inst_pid'].'-';
        //$data['house_cuid'] = 
        return $data; 
    }

    /**
     * [计算房屋计算租金]
     * @param  [type] $houseid [房屋编号]
     * @return [type]        
     */
    public function get_house_renttable($houseid){
        $row = HouseModel::with(['ban','tenant'])->find($houseid);
        //halt($row);
        //获取当前房屋的房间
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->order('room_id asc')->column('room_id'); 
        //定义计租表房间数组
        $roomTables = [];
        if($rooms){
            $FloorPointModel =new FloorPointModel;
            foreach($rooms as $roo){
                 $roomtype = RoomModel::where([['room_id','eq',$roo]])->find();
                 $sort = $roomtype->room_type_point()->value('sort');
                 $roomsSort[$sort][] = $roo;
            }
            //halt($roomsSort);
            ksort($roomsSort);
            //halt($roomsSort);
            foreach($roomsSort as $ro){
                foreach($ro as $r){
                    $roomRow = RoomModel::with('ban')->where([['room_id','eq',$r]])->find();
                    //动态获取层次调解率
                    $flor_point = $FloorPointModel->get_floor_point($roomRow['room_floor_id'], $roomRow['ban_floors']);
                    $roomRow['floor_point'] = ($flor_point * 100).'%';
                    $roomRow['room_rent_point'] = 100*(1 - $roomRow['room_rent_point']).'%';
                    $room_houses = $roomRow->house_room()->column('house_id');
                    $houses = HouseModel::with('tenant')->where([['house_id','in',$room_houses]])->field('house_id,house_number,tenant_id')->select()->toArray();
                    // 将被查询的房屋编号排在最前面
                    $temp = [];
                    foreach ($houses as $key => $value) {
                        $value['room_pub_num'] = count($houses);
                        if($value['house_id'] == $houseid){
                            array_unshift($temp, $value);
                        }else{
                            array_push($temp, $value);
                        }
                    }
                    //dump($roomRow);dump($temp);
                    $roomTables[] = [
                        'baseinfo' => $roomRow->toArray(),
                        'houseinfo' => $temp,
                    ];
                }
            }
        }
        //halt($roomTables);
        return $roomTables;
    }

    /**
     * [计租表发生变化后，更新房屋的计算租金、使用面积、计租面积]
     * @param  [string | array] $houseid [房屋编号]
     * @return [错误信息]        
     */
    public function update_house_info($houseid = ''){
        if(!is_array($houseid)){ //如果是更新单个房屋的数据
            $houseidArr = [$houseid];
        }else{ // 如果是批量更新一个数组内的数据
            $houseidArr = $houseid;
        }
        $houseidArr = array_filter($houseidArr);
        $RoomModel = new RoomModel;
        $HouseModel = new HouseModel;
        $HouseRoomModel = new HouseRoomModel;
        $res = 0;
        foreach ($houseidArr as $f) {
            // 获取计算租金
            $house_cou_rent = $HouseModel->count_house_rent($f); 
            $roomids = $HouseRoomModel->where([['house_id','eq',$f]])->column('room_id');
            // 获取房屋下使面合计、计租面积合计
            $roomRow = $RoomModel->where([['room_id','in',$roomids]])->field('sum(room_use_area) as room_use_area,sum(room_lease_area) as room_lease_area')->find();
            // 更新房屋的计算租金
            $res = $HouseModel->where([['house_id','eq',$f]])->update(['house_cou_rent'=>$house_cou_rent,'house_use_area'=>$roomRow['room_use_area'],'house_lease_area'=>$roomRow['room_lease_area']]);
        }
        $diff = count($houseidArr) - $res;
        if($diff){
            return '有'+$diff+'个房屋未更新成功！';
        }else{
            return true;
        }
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
            return bcaddMerge([$row['house_pre_rent'],$row['house_diff_rent'],$row['house_pump_rent'],$row['house_protocol_rent']]);
        }else{

            //PlusRent加计租金（面盆浴盆，5米以上，5米以下什么的），DiffRent租差，ProtocolRent协议租金
            
            // 民用的四舍五入保留一位，机关企业的四舍五入保留两位
            $p = ($row['house_use_id'] == 1)?1:2; //保留1位数

            return bcaddMerge([$sumrent,$row['house_diff_rent'],$row['house_pump_rent'],$row['house_protocol_rent']],$p); 
        }

    }
}