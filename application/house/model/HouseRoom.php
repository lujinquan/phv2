<?php
namespace app\house\model;

use app\system\model\SystemBase;

class HouseRoom extends SystemBase
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
        return $this->belongsTo('house', 'house_id', 'house_id')->bind('room_id,house_id');
    }
}