<?php
namespace app\order\model;

use think\Model;
use app\system\model\SystemUser as UserModel;

class OpOrder extends Model
{
	// 设置模型名称
    protected $name = 'op_order';
    // 设置主键
    protected $pk = 'id';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i',
        'ftime' => 'timestamp:Y-m-d H:i',
    ];

    public function SystemUser()
    {
        return $this->hasOne('app\system\model\SystemUser', 'id', 'cuid')->bind('nick,inst_id');
    }

    public function checkWhere($data,$type='accept')
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        switch ($type) {
            // 待受理工单
            case 'accept':
                if(ADMIN_ROLE == 11){ //如果角色是运营中心,必须是分配的管段旗下的
                    $inst_ids = explode(',',session('admin_user.inst_ids'));
                    $where[] = [['cuid','in',$inst_ids]];
                }else{ //如果角色不是运营中心,必须是处理流程中包含当前人员id的
                    $where[] = [['duid','like','%,'.ADMIN_ID]];
                }
                break;
            // 我的工单
            case 'myorder':
                if(ADMIN_ROLE == 11){ //如果角色是运营中心,必须是分配的管段旗下的
                    $inst_ids = explode(',',session('admin_user.inst_ids'));
                    $where[] = [['cuid','in',$inst_ids]];
                }else{ //如果角色不是运营中心,必须是处理流程中包含当前人员id的                    
                    if($data['group'] == 'j'){
                        $where[] = [['cuid','eq',ADMIN_ID],['ftime','eq',0]];
                    }else{
                        $where[] = [['cuid','eq',ADMIN_ID],['ftime','>',0]];
                    }
                }
                break;
            // 已受理工单
            case 'filished':
                $where[] = [['duid','like','%'.ADMIN_ID.'%']];
                
                break;
            // 组内待受理工单
            case 'grouporder':
                if(ADMIN_ROLE == 11){ //如果角色是运营中心,必须是分配的管段旗下的
                    $inst_ids = explode(',',session('admin_user.inst_ids'));
                    $where[] = [['cuid','in',$inst_ids]];
                }else{ //如果角色不是运营中心,必须是处理流程中包含当前人员id的
                    $where[] = [['duid','like','%,'.ADMIN_ID]];
                }
                
                break;
            default:
                # code...
                break;
        }
        
        //未完成的工单
        $where[] = [['status','eq',0]];
        // 检索工单编号
        if(isset($data['op_order_number']) && $data['op_order_number']){
            $where[] = ['op_order_number','like','%'.$data['op_order_number'].'%'];
        }
        // 检索工单类型
        if(isset($data['op_order_type']) && $data['op_order_type']){
            $where[] = ['op_order_type','eq',$data['room_type']];
        }
        //检索管段
        // $insts = config('inst_ids');
        // $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        // $where['ban'][] = ['ban_inst_id','in',$insts[$instid]];

        return $where;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data,$type='add')
    {
        switch ($type) {
            // 新增
            case 'add':
                $data['cuid'] = ADMIN_ID;
                $data['duid'] = ADMIN_ID;
                $data['op_order_number'] = random(12,1);
                $jsondata[] = [
                    'FromUid' => ADMIN_ID, 
                    'Img' => '',
                    'Desc' => $data['remark'],
                    'ToUid' => '',
                    'Time' => time(),
                    'Action' => '提交',
                ];
                $data['jsondata'] = json_encode($jsondata);
                break;
            // 转交工单
            case 'transfer':
                $find = $this->get($data['id']);
                $jsonarr = json_decode($find['jsondata'],true);
                $jsonarr[] = [
                    'FromUid' => ADMIN_ID,
                    'Img' => '',
                    'ToUid' => $data['transfer_to'],
                    'Desc' => $data['replay'],
                    'Time' => time(),
                    'Action' => '转交至',
                ];
                // 【更新】序列化数据
                $data['jsondata'] = json_encode($jsonarr);
                
                // 【更新】经手人+
                if($find['jsondata']){ //如果序列化数据为空，表示由运营人员刚接手(写入运营人员id + 转交人id)
                    $data['duid'] = $find['duid'].','.$data['transfer_to'];
                }else{ //写入 转交人id
                   $data['duid'] = $find['duid'].','.ADMIN_ID.','.$data['transfer_to']; 
                }
                
                unset($data['replay']);
                unset($data['transfer_to']);
                break;
            // 完结工单
            case 'complete':

                $find = $this->get($data['id']);
                $jsonarr = json_decode($find['jsondata'],true);
                $jsonarr[] = [
                    'FromUid' => ADMIN_ID,
                    'Img' => '',
                    'ToUid' => $find['cuid'],
                    'Desc' => $data['replay'],
                    'Time' => time(),
                    'Action' => '转交至',
                ];
                // 【更新】序列化数据
                $data['jsondata'] = json_encode($jsonarr);
                
                // 【更新】经手人+
                if($find['jsondata']){ //如果序列化数据为空，表示由运营人员刚接手(写入运营人员id + 转交人id)
                    $data['duid'] = $find['duid'].','.$data['transfer_to'];
                }else{ //写入 转交人id
                   $data['duid'] = $find['duid'].','.ADMIN_ID.','.$data['transfer_to']; 
                }
                $data['dtime'] = time();
                
                unset($data['replay']);
                break;
            default:
                # code...
                break;
        }
        
        return $data; 
    }
}