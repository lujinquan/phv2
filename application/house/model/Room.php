<?php
namespace app\house\model;

use app\system\model\SystemBase;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\FloorPoint as FloorPointModel;
use app\house\model\BanStructType as BanStructTypeModel;
use app\house\model\RoomTypePoint as RoomTypePointModel;

class Room extends SystemBase
{
	// 设置模型名称
    protected $name = 'room';
    // 设置主键
    protected $pk = 'room_id';
    // 定义时间戳字段名
    protected $createTime = 'room_ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function house_room()
    {
        return $this->hasMany('house_room', 'room_id', 'room_id');
    }

    public function ban()
    {
        return $this->belongsTo('ban', 'ban_id', 'ban_id')->bind('ban_owner_id,ban_number,ban_inst_id,ban_address,ban_units,ban_floors,ban_struct_id,ban_is_first');
    }

    // 待优化
    public function room_type_point()
    {
        return $this->belongsTo('room_type_point', 'room_type', 'id')->bind('sort');
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        $where['ban'] = [];
        $where['room'] = [];
        // 检索楼栋编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $where['room'][] = ['ban_number','like','%'.$data['ban_number'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where['ban'][] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索房间编号
        if(isset($data['room_number']) && $data['room_number']){
            $where['room'][] = ['room_number','like','%'.$data['room_number'].'%'];
        }
        // 检索房间类型
        if(isset($data['room_type']) && $data['room_type']){
            $where['room'][] = ['room_type','in',explode(',',$data['room_type'])];
        }
        
        // 检索房间共用状态
        if(isset($data['room_pub_num']) && $data['room_pub_num']){
            if($data['room_pub_num'] < 3){
                $where['room'][] = ['room_pub_num','eq',$data['room_pub_num']];
            }else{
                $where['room'][] = ['room_pub_num','>=',$data['room_pub_num']];      
            }
        }
        if(isset($data['room_status'])){
            $where['room'][] = [['room_status','in',explode(',',$data['room_status'])]];
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
            $where['ban'][] = ['ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where['ban'][] = ['ban_inst_id','in',config('inst_ids')[INST]];
        }
        //检索管段
        // $insts = config('inst_ids');
        // $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        // $where['ban'][] = ['ban_inst_id','in',$insts[$instid]];

        return $where;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data,$flag='add')
    {
        if($flag == 'add'){
            $data['room_cuid'] = ADMIN_ID;
            $maxNumber = self::max('room_number');
            $data['room_number'] = $maxNumber + 1;
        }else{
            $roomRow = $this->find($data['room_id']);
            $data['room_number'] = $roomRow['room_number'];
        }
        
        $data['room_rent_point'] = 1 - $data['room_rent_point']/100;
        $data['ban_id'] = BanModel::where([['ban_number','eq',$data['ban_number']]])->value('ban_id');
        $temp = array_filter($data['house_id']);
        $data['room_pub_num'] = count($temp);
        //计租面积
        //halt($data);
        $data['room_lease_area'] = $this->room_lease_area($data['room_type'],$data['room_use_area'],count($temp));
        
        //构造house_room关联表数据
        foreach($temp as $t){
            $data['house_room'][] = [
                'house_id' => $t, 
                'room_number' => $data['room_number'],
                'house_number' => HouseModel::where([['house_id','eq',$t]])->value('house_number'),
            ];
            
        }
        //$data['house_number'] = implode(',',array_filter($data['house_number']));
        $data['room_rent_pointids'] = isset($data['room_rent_pointids'])?implode(',',array_filter($data['room_rent_pointids'])):'';

        
        return $data; 
    }
    /**
     * 房间的计租面积
     * @param  [type] $room_type     [房间类型]
     * @param  [type] $room_use_area [房间实有面积]
     * @param  [type] $room_pub_num  [房间共用状态]
     * @return [type]
     */
    public function room_lease_area($room_type,$room_use_area,$room_pub_num){
        //三户及三户以上，计租面积计为0
        if($room_pub_num > 2){
            return 0;
        }else{
            $point = RoomTypePointModel::where([['id','eq',$room_type]])->value('point');
            return $room_use_area * $point / $room_pub_num;
        }
    }

    /**
     * [count_room_rent 房间计算租金]
     * @param  [type] $roomid  [房间编号]
     * @return [type]          [房间计算租金]
     */
    public function count_room_rent($roomid){

        //初始数据
        $roomRow = self::with(['ban'])->find($roomid);
        
        if($roomRow['room_pub_num'] > 2){ //三户共用直接无租金
            return 0.5;
        }

        if($roomRow['ban_number'] == '1050053295'){ //如果是新华村5栋的楼，则单独处理
            return $roomRow['room_pre_rent'];
        }else{
            // 层次调解率，与居住层，有无电梯，楼栋总层数有关
            $floorPoint = (new FloorPointModel)->get_floor_point($roomRow['room_floor_id'], $roomRow['ban_floors']);
            // 结构基价
            $structureTypePoint = BanStructTypeModel::where([['id','eq',$roomRow['ban_struct_id']]])->value('new_point');
            // 房间的架空率，与楼栋是否一层为架空层有关
            $emptyPoint = $roomRow['ban_is_first']?0.98:1;
            // 计算租金= 计租面积（使用面积，房间类型，是否共用） * 基价折减率（类似有无上下水这种折减） * 结构基价  *  架空率 * 层次调解率 + 租差 + 泵费
            return round($roomRow['room_lease_area'] * round($roomRow['room_rent_point'] * $structureTypePoint,2) * $emptyPoint * $floorPoint,2); 
        }
        
    }


}