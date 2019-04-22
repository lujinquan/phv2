<?php
namespace app\house\model;

use think\Model;
use app\house\model\FloorPoint as FloorPointModel;
use app\house\model\BanStructType as BanStructTypeModel;

class Room extends Model
{
	// 设置模型名称
    protected $name = 'room';
     // 设置主键
    protected $pk = 'room_id';


    public function house_room()
    {
        return $this->hasMany('house_room', 'room_number', 'room_number');
    }

    public function ban()
    {
        return $this->belongsTo('ban', 'ban_number', 'ban_number')->bind('ban_owner_id,ban_inst_id,ban_address,ban_units,ban_floors');
    }

    /**
     * [count_room_rent 房间计算租金]
     * @param  [type] $roomid  [房间编号]
     * @param  string $houseid [房屋编号，可选]
     * @return [type]          [房间计算租金]
     */
    public function count_room_rent($roomid , $houseid = ''){

        //初始数据
        $roomRow = self::with(['ban'])->find($roomid);
        //$roomOne = Db::name('room')->where('RoomID',$roomid)->field('LeasedArea,RoomPrerent,RentPoint,RoomType,UseNature,FloorID,BanID,RoomPublicStatus')->find();
        
        if($roomRow['room_pub_num'] > 2){ //三户共用直接无租金
            return 0.5;
        }

        if($roomRow['ban_number'] == '1050053295'){ //如果是新华村5栋的楼，则单独处理
            return $roomRow['room_pre_rent'];
        }else{
            // 层次调解率，与居住层，有无电梯，楼栋总层数有关
            $floorPoint = (new FloorPointModel)->get_floor_point($roomRow['room_floor_id'], $roomRow['BanFloorNum']);
            // 结构基价
            $structureTypePoint = BanStructTypeModel::where([['id','eq',$roomRow['ban_struct_id']]])->value('new_point');
            // 房间的架空率，与楼栋是否一层为架空层有关
            $emptyPoint = $roomRow['ban_is_first']?0.98:1;
            // 计算租金= 计租面积（使用面积，房间类型，是否共用） * 基价折减率（有无上下水这种折减） * 结构基价  *  架空率 * 层次调解率
            return round($roomOne['room_lease_area'] * round($roomOne['room_rent_point'] * $structureTypePoint,2) * $emptyPoint * $floorPoint,2); 
        }
        
    }


}