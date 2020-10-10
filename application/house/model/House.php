<?php
namespace app\house\model;

use app\system\model\SystemBase;
use app\house\model\Ban as BanModel;
use app\rent\model\Rent as RentModel;
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
        'house_dtime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function ban()
    {
        return $this->belongsTo('ban', 'ban_id', 'ban_id')->bind('ban_owner_id,ban_number,ban_inst_id,ban_inst_pid,ban_address,ban_units,ban_floors,ban_temp_floors,ban_struct_id,ban_damage_id,ban_gpsx,ban_gpsy,ban_is_levator');
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
        // 检索【房屋】暂停计租
        if(isset($data['house_is_pause']) && $data['house_is_pause'] != ''){
            $where[] = ['a.house_is_pause','eq',$data['house_is_pause']];
        }
        // 检索【房屋】规定租金
        if(isset($data['house_pre_rent']) && $data['house_pre_rent']){
            $where[] = ['a.house_pre_rent','eq',$data['house_pre_rent']];
        }
        // 检索【房屋】计算租金
        if(isset($data['house_cou_rent']) && $data['house_cou_rent']){
            $where[] = ['a.house_cou_rent','eq',$data['house_cou_rent']];
        }
        // 检索【房屋】计租面积
        if(isset($data['house_lease_area']) && $data['house_lease_area']){
            $where[] = ['a.house_lease_area','eq',$data['house_lease_area']];
        }
        // 检索【房屋】使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['a.house_use_id','in',explode(',',$data['house_use_id'])];
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
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索【楼栋】结构类别
        if(isset($data['ban_struct_id']) && $data['ban_struct_id']){
            $where[] = ['d.ban_struct_id','in',explode(',',$data['ban_struct_id'])];
        }
        // 检索【楼栋】完损等级
        if(isset($data['ban_damage_id']) && $data['ban_damage_id']){
            $where[] = ['d.ban_damage_id','in',explode(',',$data['ban_damage_id'])];
        }
        // 检索房屋注销时间
        if(isset($data['house_dtime']) && $data['house_dtime']){
            $start = strtotime(substr($data['house_dtime'],0,10));
            $end = strtotime(substr($data['house_dtime'],-10));
            $where[] = ['a.house_dtime','between',[$start,$end]];
        }
        // 检索楼栋创建日期
        if(isset($data['house_ctime']) && $data['house_ctime']){
            $start1 = strtotime($data['house_ctime']);
            $end1 = strtotime('+ 1 month',$start1);
            //dump($start);halt($end);
            $where[] = ['house_ctime','between',[$start1,$end1]];
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
            $where[] = ['d.ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[INST]];
        }
        //检索管段
        // $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        // $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
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
        $banInfo = BanModel::where([['ban_id', 'eq', $data['ban_id']]])->field('ban_owner_id,ban_inst_pid,ban_use_id')->find();
                $params = ParamModel::getCparams();
        $owner = $params['owners'][$banInfo['ban_owner_id']];
        $data['house_use_id'] = $banInfo['ban_use_id'];
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
                    // if($row['house_status'] == 2) {
                    //     $roomRow = RoomModel::with('ban')->where([['room_id','eq',$r],['room_status','eq',2]])->find();
                    // } else {
                    //     $roomRow = RoomModel::with('ban')->where([['room_id','eq',$r],['room_status','<',2]])->find();
                    // }
                    // if(!$roomRow){
                    //     $roomTables[] = [
                    //         'baseinfo' => [],
                    //         'houseinfo' => [],
                    //     ];
                    //     continue;
                    // }
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
    public function update_house_info($houseid = '',$group = 'x'){
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
            // $house_pre_rent = $HouseModel->count_house_pre_rent($f);
            $roomids = $HouseRoomModel->where([['house_id','eq',$f]])->column('room_id');
            foreach ($roomids as $k => $v) {
                $row = $RoomModel->where([['room_id','eq',$v]])->field('room_pub_num')->find();
                if($row['room_pub_num'] > 2){
                    unset($roomids[$k]);
                }
            }
            // 获取房屋下使面合计、计租面积合计
            $roomRow = $RoomModel->where([['room_id','in',$roomids]])->field('sum(room_use_area) as room_use_area,sum(room_lease_area) as room_lease_area')->find();
            // 更新房屋的计算租金
            if ($group == 'x') {
                $res = $HouseModel->where([['house_id','eq',$f]])->update(['house_cou_rent'=>$house_cou_rent,'house_pre_rent'=>$house_cou_rent,'house_use_area'=>$roomRow['room_use_area'],'house_lease_area'=>$roomRow['room_lease_area']]);
            } else {
                $res = $HouseModel->where([['house_id','eq',$f]])->update(['house_cou_rent'=>$house_cou_rent,'house_use_area'=>$roomRow['room_use_area'],'house_lease_area'=>$roomRow['room_lease_area']]);
            }
            // $res = $HouseModel->where([['house_id','eq',$f]])->update(['house_cou_rent'=>$house_cou_rent,'house_pre_rent'=>$house_cou_rent,'house_use_area'=>$roomRow['room_use_area'],'house_lease_area'=>$roomRow['room_lease_area']]);
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
     * @return [type] $type ['normal',或者,'temp']  类型   
     */
    public function count_house_rent($houseid,$type = 'normal'){
        // 特殊的房屋计算租金
        if(in_array($houseid,array(666,888,999))){
            return 0;
        }
        $row = self::with('ban')->find($houseid);
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->column('room_id');
        if($type == 'normal'){
            $fields = 'room_id,room_cou_rent';
        }else if($type == 'temp'){ //例如：楼栋调整里面，调整了楼层，统一都算一遍
            $fields = 'room_id,room_temp_cou_rent';         
        }
        $roomRents = RoomModel::where([['room_id','in',$rooms],['room_status','<=',1]])->column($fields); 
        $sumrent = 0;
        if($roomRents){
            $rent = [];
            foreach ($roomRents as $k=>$v) {
                $rent[$k] = $v;         
            }
            $sumrent = array_sum($rent);
        }
        if($row['ban_number'] == '1050053295'){
            // 不用加协议租金
            return $row['house_pre_rent'];
        }else{
            //PlusRent加计租金（面盆浴盆，5米以上，5米以下什么的），DiffRent租差，ProtocolRent协议租金
            
            // 这是1.0的写法虽然有点问题，但是为了避免问题，就这么算！
            $houseRent = $sumrent + $row['house_diff_rent'] + $row['house_pump_rent'];
            return ($row['house_use_id'] == 1)?round($houseRent,1):round($houseRent,2); 
            
            // 民用的四舍五入保留一位，机关企业的四舍五入保留两位
            // $p = ($row['house_use_id'] == 1)?1:2; //保留1位数
            // return bcaddMerge([$sumrent,$row['house_diff_rent'],$row['house_pump_rent']],$p); 
        }
    }

    /**
     * [计算房屋规定租金]
     * @param  [type] $houseid [房屋编号]
     * @return [type] $type ['normal',或者,'temp']  类型   
     */
    public function count_house_pre_rent($houseid,$type = 'normal'){
        // 特殊的房屋计算租金
        if(in_array($houseid,array(666,888,999))){
            return 0;
        }
        $row = self::with('ban')->find($houseid);
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->column('room_id');
        if($type == 'normal'){
            $fields = 'room_id,room_pre_rent';
        }else if($type == 'temp'){ //例如：楼栋调整里面，调整了楼层，统一都算一遍
            $fields = 'room_id,room_temp_cou_rent';         
        }
        $roomRents = RoomModel::where([['room_id','in',$rooms],['room_status','<=',1]])->column($fields); 
        $sumrent = 0;
        if($roomRents){
            $rent = [];
            foreach ($roomRents as $k=>$v) {
                $rent[$k] = $v;         
            }
            $sumrent = array_sum($rent);
        }
        if($row['ban_number'] == '1050053295'){
            // 不用加协议租金
            return $row['house_pre_rent'];
        }else{
            //PlusRent加计租金（面盆浴盆，5米以上，5米以下什么的），DiffRent租差，ProtocolRent协议租金
            
            // 这是1.0的写法虽然有点问题，但是为了避免问题，就这么算！
            $houseRent = $sumrent + $row['house_diff_rent'] + $row['house_pump_rent'];
            return ($row['house_use_id'] == 1)?round($houseRent,1):round($houseRent,2); 
            
            // 民用的四舍五入保留一位，机关企业的四舍五入保留两位
            // $p = ($row['house_use_id'] == 1)?1:2; //保留1位数
            // return bcaddMerge([$sumrent,$row['house_diff_rent'],$row['house_pump_rent']],$p); 
        }
    }

    // 获取房屋欠租金额
    public function get_unpaid_rents($houseid)
    {
        $row = RentModel::where([['rent_order_status','eq',1],['house_id','eq',$houseid]])->field('sum(rent_order_paid) as rent_order_paids , sum(rent_order_receive) as rent_order_receives')->find();
        if($row){
            return bcsub($row['rent_order_receives'], $row['rent_order_paids'], 2);
        }else{
            return 0;
        }
        
    }
}