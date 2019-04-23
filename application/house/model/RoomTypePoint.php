<?php
namespace app\house\model;

use think\Model;

class RoomTypePoint extends Model
{
	// 设置模型名称
    protected $name = 'room_type_point';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];
}