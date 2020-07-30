<?php

namespace app\system\model;

use think\Model;

/**
 * 系统公告模型
 * @package app\system\model
 */
class SystemBasis extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i',
        'update_time' => 'timestamp:Y-m-d H:i',
    ];

    public function getCuidAttr($value){
        return session('systemusers')?session('systemusers')[$value]['nick']:$value;
    }
}