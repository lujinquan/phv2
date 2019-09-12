<?php
namespace app\house\model;

use app\system\model\SystemBase;

class HouseTai extends SystemBase
{
	// 设置模型名称
    protected $name = 'house_tai';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function tenant()
    {
        return $this->belongsTo('tenant', 'tenant_id', 'tenant_id')->bind('tenant_name,tenant_number');
    }

    public function SystemUser()
    {
        return $this->hasOne('app\system\model\SystemUser', 'id', 'house_tai_cuid')->bind('nick');
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
     	// 检索房屋编号
        if(isset($data['house_id']) && $data['house_id']){
            $where[] = ['house_id','eq',$data['house_id']];
        }
        // 检索业务类型
        if(isset($data['house_tai_type']) && $data['house_tai_type']){
            $where[] = ['house_tai_type','eq',$data['house_tai_type']];
        }
        // 检索描述内容
        if(isset($data['house_tai_remark']) && $data['house_tai_remark']){
            $where[] = ['house_tai_remark','like','%'.$data['house_tai_remark'].'%'];
        }
        return $where;
    }

    public static function store($data)
    {

    }
}