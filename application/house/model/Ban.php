<?php
namespace app\house\model;

use think\Model;

class Ban extends Model
{
	// 设置模型名称
    protected $name = 'ban';
    // 设置主键
    protected $pk = 'ban_id';
    // 定义时间戳字段名
    protected $createTime = 'ban_ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ban_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $group = isset($data['group'])?$data['group']:'y';
        $where = ($group == 'y')?[['ban_status','eq',1]]:[['ban_status','neq',1]];
        // 检索楼栋编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $where[] = ['ban_number','like','%'.$data['ban_number'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索结构类别
        if(isset($data['ban_struct_id']) && $data['ban_struct_id']){
            $where[] = ['ban_struct_id','eq',$data['ban_struct_id']];
        }
        // 检索完损等级
        if(isset($data['ban_damage_id']) && $data['ban_damage_id']){
            $where[] = ['ban_damage_id','eq',$data['ban_damage_id']];
        }

        // 检索管段
        $insts = config('insts');
        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        if(isset($insts[$instid])){
            if($insts[$instid]){
                $where[] = ['ban_inst_id','in',$insts[$instid]];
            }
        }else{
            $where[] = ['ban_inst_id','eq',$data['ban_inst_id']];
        }

        return $where;
    }

    public function dataFilter($data)
    {
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $data['ban_inst_id'] = $data['ban_inst_id'];
        }else{
            $data['ban_inst_id'] = INST;
        }
        $data['ban_cuid'] = ADMIN_ID;

        $banID = '1009001';
        $maxBanID = self::where([['ban_number', 'like', $banID . '%']])->max('ban_number');
        $data['ban_number'] = $maxBanID?$maxBanID + 1:$banID . '001'; 
       
        if($data['ban_inst_id'] < 4){
            return '请选择正确的管段';
        }else{
            return $data;
        }
    }
}