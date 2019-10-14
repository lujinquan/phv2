<?php
namespace app\house\model;

use app\system\model\SystemBase;

class HouseRoomTemp extends SystemBase
{
	// 设置模型名称
    protected $name = 'house_room_temp';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('house_temp', 'house_number', 'house_number')->bind('room_number');
    }
}