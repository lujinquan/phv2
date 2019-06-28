<?php
namespace app\house\model;

use app\system\model\SystemBase;

class BanStructType extends SystemBase
{
	// 设置模型名称
    protected $name = 'ban_struct_type';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];
}