<?php
namespace app\house\model;

use app\system\model\SystemBase;

class TenantTai extends SystemBase
{
	// 设置模型名称
    protected $name = 'tenant_tai';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'data_json' => 'json',
    ];

    // public function tenant()
    // {
    //     return $this->belongsTo('tenant', 'tenant_id', 'tenant_id')->bind('tenant_name,tenant_number');
    // }

    public function SystemUser()
    {
        return $this->hasOne('app\system\model\SystemUser', 'id', 'cuid')->bind('nick');
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
     	// 检索租户编号
        
        $where[] = ['tenant_id','eq',$data['id']];
        
        // 检索业务类型
        if(isset($data['tenant_tai_type']) && $data['tenant_tai_type']){
            $where[] = ['tenant_tai_type','eq',$data['tenant_tai_type']];
        }
        // 检索描述内容
        if(isset($data['tenant_tai_remark']) && $data['tenant_tai_remark']){
            $where[] = ['tenant_tai_remark','like','%'.$data['tenant_tai_remark'].'%'];
        }
        return $where;
    }

    public static function store($data)
    {

    }
}