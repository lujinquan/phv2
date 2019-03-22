<?php
namespace app\house\model;

use think\Model;

class Tenant extends Model
{
	// 设置模型名称
    protected $name = 'tenant';
    // 设置主键
    protected $pk = 'tenant_id';
    // 定义时间戳字段名
    protected $createTime = 'tenant_ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('hosue', 'house_number', 'house_number')->bind('house_id');
    }

    
}