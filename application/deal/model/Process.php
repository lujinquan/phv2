<?php

namespace app\deal\model;

use app\system\model\SystemBase;
use app\deal\model\ChangeUse as ChangeUseModel;

class Process extends SystemBase
{
    // 设置模型名称
    protected $name = 'change_process';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function ban()
    {
        return $this->hasOne('app\house\model\ban', 'ban_id', 'ban_id')->bind('ban_address');
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
        $where[] = ['a.status','eq',1];
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索申请时间
        if(isset($data['ctime']) && $data['ctime']){
            $startTime = strtotime($data['ctime']);
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ctime','between time',[$startTime,$startTime+3600*24]];
        }
        // 检索楼栋机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $where[] = ['d.ban_inst_id','eq',$data['ban_inst_id']];
        }else{
            //检索管段
            $insts = config('inst_ids');
            $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
            $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        }
        return $where;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data)
    {
        if(isset($data['file']) && $data['file']){
            $data['change_imgs'] = implode(',',$data['file']);
        }
        $data['change_order_number'] = '113'.random(10,1);
        $data['cuid'] = ADMIN_ID;
        return $data; 
    }

    public function process($change_type,$data)
    {
        $process = self::where([['change_type','eq',$change_type],['change_id','eq',$data['id']]])->find();
        switch ($change_type) {
                case '1':
                    # code...
                    break;
                case '2':
                    # code...
                    break;
                case '3':
                    # code...
                    break;
                case '4':
                    # code...
                    break;
                case '5':
                    # code...
                    break;

                case '6':
                    # code...
                    break;
                case '7':
                    # code...
                    break;

                case '8':
                    # code...
                    break;
                case '9':
                    # code...
                    break;
                case '10':
                    # code...
                    break;

                case '11':
                    # code...
                    break;
                case '12':
                    # code...
                    break;

                case '13':
                    $ChangeUseModel = new ChangeUseModel;
                    $result = $ChangeUseModel->process($data);

                    break;
                default:
                    # code...
                    break;
            }

            if($result){
                $process->change_desc = $result['change_desc'];
                if(isset($result['curr_role'])){
                    $process->curr_role = $result['curr_role'];
                }
                if(isset($result['ftime'])){
                    $process->ftime = $result['ftime'];
                }
                if(isset($result['status'])){
                    $process->status = $result['status'];
                }
                
                $res = $process->save();
            }

            return $res;
    }

}