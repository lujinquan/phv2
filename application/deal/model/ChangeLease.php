<?php
namespace app\deal\model;

use app\system\model\SystemBase;

class ChangeLease extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_lease';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'last_print_time' => 'timestamp:Y-m-d H:i:s',
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function tenant()
    {
        return $this->hasOne('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_number,tenant_tel,tenant_card');
    }

    public function house()
    {
        return $this->hasOne('app\house\model\House', 'house_id', 'house_id')->bind('house_number,house_pre_rent,house_cou_rent');
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        //检索管段
        $insts = config('inst_ids');
        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        $where[] = ['a.change_status','eq',1];
        return $where;
    }

    public function detail($id)
    {
        
    }

    public static function process($id)
    {
        
    }


}