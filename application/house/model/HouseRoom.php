<?php
namespace app\house\model;

use think\Model;

class HouseRoom extends Model
{
	// 设置模型名称
    protected $name = 'house_room';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('house', 'house_number', 'house_number')->bind('room_number');
    }
}