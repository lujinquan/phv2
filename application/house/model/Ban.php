<?php
namespace app\house\model;

use think\Db;
use app\system\model\SystemBase;

class Ban extends SystemBase
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

    public function house()
    {
        return $this->hasMany('ban', 'ban_number', 'ban_number')->bind('ban_owner_id,ban_inst_id,ban_address,ban_units,ban_floors');
    }

    public function checkWhere($data)
    {
        // $parse = $this->parse_params();
        // halt($parse);
        if(!$data){
            $data = request()->param();
        }
        $group = isset($data['group'])?$data['group']:'y';
        $option = '';
        switch ($group) {
            case 'y':
                $where = [['ban_status','eq',1]];
                break;
            case 'x':
                $where = [['ban_status','eq',0]];
                break;
            case 'z':
                $where = [['ban_status','>',1]];
                break;
            default:
                $where = [['ban_status','eq',1]];
                break;
        }
        // 检索楼栋编号
        if(isset($data['ban_number']) && $data['ban_number']){
            if(strpos($data['ban_number'], ',') !== false){
                $where[] = ['ban_number','in',explode(',',$data['ban_number'])];
            }else{
                $where[] = ['ban_number','like','%'.$data['ban_number'].'%'];
            }           
        }
        // 检索楼栋栋号
        if(isset($data['ban_door']) && $data['ban_door']){
            $where[] = ['ban_door','eq',$data['ban_door']];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋建成年份
        if(isset($data['ban_build_year']) && $data['ban_build_year']){
            $where[] = ['ban_build_year','eq',$data['ban_build_year']];
        }
        // 检索产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索产别
        if(isset($data['ban_use_id']) && $data['ban_use_id']){
            $where[] = ['ban_use_id','in',explode(',',$data['ban_use_id'])];
        }
        // 检索结构类别
        if(isset($data['ban_struct_id']) && $data['ban_struct_id']){
            $where[] = ['ban_struct_id','in',explode(',',$data['ban_struct_id'])];
        }
        // 检索完损等级
        if(isset($data['ban_damage_id']) && $data['ban_damage_id']){
            $where[] = ['ban_damage_id','in',explode(',',$data['ban_damage_id'])];
        }
        // 检索楼栋社区
        if(isset($data['ban_area_three']) && $data['ban_area_three']){
            $where[] = ['ban_area_three','in',explode(',',$data['ban_area_three'])];
        }
        // 检索楼栋注销时间
        if(isset($data['ban_dtime']) && $data['ban_dtime']){
            $start = strtotime(substr($data['ban_dtime'],0,10));
            $end = strtotime(substr($data['ban_dtime'],-10));
            $where[] = ['ban_dtime','between',[$start,$end]];
        }
        // 检索楼栋创建日期
        if(isset($data['ban_ctime']) && $data['ban_ctime']){
            $start = strtotime($data['ban_ctime']);
            $end = strtotime('+ 1 month',$start);
            //dump($start);halt($end);
            $where[] = ['ban_ctime','between',[$start,$end]];
        }
        // 检索机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $insts = explode(',',$data['ban_inst_id']);
            $instid_arr = [];
            foreach ($insts as $inst) {
                foreach (config('inst_ids')[$inst] as $instid) {
                    $instid_arr[] = $instid;
                }
            }
            $where[] = ['ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where[] = ['ban_inst_id','in',config('inst_ids')[INST]];
        }

        return $where;
    }

    public function dataFilter($data)
    {

        $data['ban_inst_pid'] = Db::name('base_inst')->where([['inst_id','eq',$data['ban_inst_id']]])->value('inst_pid');
        if(isset($data['file']) && $data['file']){
            $data['ban_imgs'] = implode(',',$data['file']);
        }
        $data['ban_cuid'] = ADMIN_ID;

        $banID = '1009001';
        $maxBanID = self::where([['ban_number', 'like', $banID . '%']])->max('ban_number');
        $data['ban_number'] = $maxBanID?$maxBanID + 1:$banID . '001'; 
       
        return $data;
    }
}