<?php
namespace app\house\model;

use think\Model;

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
}