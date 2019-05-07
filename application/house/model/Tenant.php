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

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $group = isset($data['group'])?$data['group']:'y';
        $where = ($group == 'y')?[['tenant_status','eq',1]]:[['tenant_status','eq',0]];
        // 检索楼栋编号
        if(isset($data['tenant_number']) && $data['tenant_number']){
            $where[] = ['tenant_number','like','%'.$data['tenant_number'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索产别
        if(isset($data['tenant_tel']) && $data['tenant_tel']){
            $where[] = ['tenant_tel','like','%'.$data['tenant_tel'].'%'];
        }
        // 检索结构类别
        if(isset($data['tenant_card']) && $data['tenant_card']){
            $where[] = ['tenant_card','like','%'.$data['tenant_card'].'%'];
        }
        // 检索管段
        $instid = (isset($data['tenant_inst_id']) && $data['tenant_inst_id'])?$data['tenant_inst_id']:INST;
        $where[] = ['tenant_inst_id','in',config('inst_ids')[$instid]];

        return $where;
    }

    public function dataFilter($data)
    {
        if(isset($data['tenant_inst_id']) && $data['tenant_inst_id']){
            $data['tenant_inst_id'] = $data['tenant_inst_id'];
        }else{
            $data['tenant_inst_id'] = INST;
        }
        $data['tenant_cuid'] = ADMIN_ID;
        $data['tenant_number'] = (self::max('tenant_number') + 1);
        if($data['tenant_inst_id'] < 4){
            return '请选择正确的管段';
        }else{
            return $data;
        }
    }
}