<?php

namespace app\deal\model;

use app\system\model\SystemBase;
use app\deal\model\ChangeBan as ChangeBanModel;
use app\deal\model\ChangeHouse as ChangeHouseModel;
use app\deal\model\ChangeCancel as ChangeCancelModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;
use app\deal\model\ChangeName as ChangeNameModel;
use app\deal\model\ChangeNew as ChangeNewModel;
use app\deal\model\ChangeOffset as ChangeOffsetModel;
use app\deal\model\ChangePause as ChangePauseModel;
use app\deal\model\ChangeRentAdd as ChangeRentAddModel;
use app\deal\model\ChangeInst as ChangeInstModel;
use app\deal\model\ChangeUse as ChangeUseModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\deal\model\ChangeCutYear as ChangeCutYearModel;

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

    public function checkWhere($data , $type = 'index')
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        $where[] = ['a.status','eq',1];
        //$where[] = ['a.curr_role','neq',4]; // 剔除由房管员处理的异动（租约数据太多太杂了）
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        if(isset($data['change_type']) && $data['change_type']){
            $where[] = ['change_type','eq',$data['change_type']];
        }
        // 检索异动单号
        if(isset($data['change_order_number']) && $data['change_order_number']){
            $where[] = ['a.change_order_number','like','%'.$data['change_order_number'].'%'];
        }
        // 检索审核当前的状态
        if(isset($data['change_desc']) && $data['change_desc']){
            $where[] = ['a.change_desc','like','%'.$data['change_desc'].'%'];
        }
        // 检索申请时间
        if(isset($data['ctime']) && $data['ctime']){
            $endTime = date('Y-m',strtotime('+1 month',strtotime($data['ctime'])));
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ctime','between time',[$data['ctime'],$endTime]];
        }
        // 检索楼栋机构
        $insts = config('inst_ids');
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $where[] = ['d.ban_inst_id','in',$insts[$data['ban_inst_id']]];
        }else{
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
                case '1': // 租金减免
                    $ChangeCutModel = new ChangeCutModel;
                    $result = $ChangeCutModel->process($data);
                    break;
                case '2': // 空租
                    
                    break;
                case '3': // 暂停计租
                    $ChangePauseModel = new ChangePauseModel;
                    $result = $ChangePauseModel->process($data);
                    break;
                case '4': // 陈欠核销
                    $ChangeOffsetModel = new ChangeOffsetModel;
                    $result = $ChangeOffsetModel->process($data);
                    break;
                case '5': // 房改
                   
                    break;

                case '6': // 维修
                   
                    break;
                case '7': // 新发租
                    $ChangeNewModel = new ChangeNewModel;
                    $result = $ChangeNewModel->process($data);
                    break;

                case '8': // 注销
                    $ChangeCancelModel = new ChangeCancelModel;
                    $result = $ChangeCancelModel->process($data);
                    break;
                case '9': // 房屋调整
                    $ChangeHouseModel = new ChangeHouseModel;
                    $result = $ChangeHouseModel->process($data);
                    break;
                case '10': // 管段调整
                    $ChangeInstModel = new ChangeInstModel;
                    $result = $ChangeInstModel->process($data);
                    break;

                case '11': // 租金追加调整
                    $ChangeRentAddModel = new ChangeRentAddModel;
                    $result = $ChangeRentAddModel->process($data);
                    break;
                case '12': //租金调整
                    
                    break;

                case '13': // 使用权变更
                    $ChangeUseModel = new ChangeUseModel;
                    $result = $ChangeUseModel->process($data);

                    break;
                case '14': // 楼栋调整
                    $ChangeBanModel = new ChangeBanModel;
                    $result = $ChangeBanModel->process($data);

                    break;
                case '16': // 租金减免年审
                    $ChangeCutYearModel = new ChangeCutYearModel;
                    $result = $ChangeCutYearModel->process($data);
                    break;
                case '17': // 别字更正
                    $ChangeNameModel = new ChangeNameModel;
                    $result = $ChangeNameModel->process($data);
                    break;
                case '18': // 租约管理
                    $ChangeLeaseModel = new ChangeLeaseModel;
                    $result = $ChangeLeaseModel->process($data);
                    break;
                    default:
                    # code...
                    break;
            }

            if(is_array($result) && $result && !isset($result['error_msg'])){
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
                return $res;
            }else{
                return $result;
            }

            //return false;
            
    }

    public function detail($change_type,$id,$change_order_number = '')
    {
        switch ($change_type) {
            case '1': // 租金减免
                $ChangeModel = new ChangeCutModel;
                $template = 'deal@changecut/detail_x';              
                break;
            case '2': // 空租
                # code...
                break;
            case '3': //暂停计租
                $ChangeModel = new ChangePauseModel;
                $template = 'deal@changepause/detail'; 
                break;
            case '4': // 陈欠核销
                $ChangeModel = new ChangeOffsetModel;
                $template = 'deal@changeoffset/detail';
                break;
            case '5': // 房改
                # code...
                break;
            case '6': // 维修
                # code...
                break;
            case '7': // 新发租
                $ChangeModel = new ChangeNewModel;
                $template = 'deal@changenew/detail';
                break;
            case '8': //注销
                $ChangeModel = new ChangeCancelModel;
                $template = 'deal@changecancel/detail';
                break;
            case '9': // 房屋调整
                $ChangeModel = new ChangeHouseModel;
                $template = 'deal@changehouse/detail';
                break;
            case '10': // 管段调整
                $ChangeModel = new ChangeInstModel;
                $template = 'deal@changeinst/detail';
                break;
            case '11': // 租金追加调整
                $ChangeModel = new ChangeRentAddModel;
                $template = 'deal@changerentadd/detail';
                break;
            case '12': //租金调整
                # code...
                break;
            case '13': //使用权变更
                $ChangeModel = new ChangeUseModel;
                $template = 'deal@changeuse/detail';
                break;
            case '14': //楼栋调整
                $ChangeModel = new ChangeBanModel;
                $template = 'deal@changeban/detail';
                break;
            case '16': // 租金减免年审
                $ChangeModel = new ChangeCutYearModel;
                $template = 'deal@changecut/detail_y';
                break;
            case '17': // 别字更正
                $ChangeModel = new ChangeNameModel;
                $template = 'deal@changename/detail';
                break;
            case '18': // 租约管理
                $ChangeModel = new ChangeLeaseModel;
                $template = 'deal@changelease/detail';
                break;
            default:
                # code...
                break;
        }
        $row = $ChangeModel->detail($id,$change_order_number);
        $result = [];
        if($change_type == 16){
            $ChangeCutModel = new ChangeCutModel;
            $cutRow = $ChangeCutModel->where([['house_id','eq',$row['house_id']],['change_status','eq',1]])->order('ftime desc')->find();
            $result['old_data_info'] = $ChangeCutModel->detail($cutRow['id']);
        }
        $result['row'] = $row;
        $result['template'] = $template;
        //halt($result);
        return $result;
    }

}