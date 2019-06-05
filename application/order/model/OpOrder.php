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
        return $this->hasOne('app\system\model\SystemUser', 'id', 'cuid')->bind('nick');
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
                    $where[] = [['inst_id','in',$inst_ids]];
                }else{ //如果角色不是运营中心,必须是处理流程中包含当前人员id的
                    $where[] = [['duid','like','%,'.ADMIN_ID]];
                }
                //halt($where);
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
            // 组内待受理工单[只有运营中心]
            case 'grouporder':
                
                
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
            $where[] = ['op_order_type','eq',$data['op_order_type']];
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
                $data['inst_id'] = INST;
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
                // 【更新】经手人+
                if(count($jsonarr) == 1){ //如果序列化数据为空，表示由运营人员刚接手(写入运营人员id + 转交人id)
                    $data['duid'] = $find['duid'].','.ADMIN_ID.','.$data['transfer_to']; 
                }else{ //写入 转交人id
                   $data['duid'] = $find['duid'].','.$data['transfer_to'];
                }
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
                unset($data['replay']);
                unset($data['transfer_to']);
                break;

            // 完成工单
            case 'complete':

                $find = $this->get($data['id']);

                $jsonarr = json_decode($find['jsondata'],true);

                // 如果不是运营中心的人，那么此处的完成工单指的是默认转交回去
                if(ADMIN_ROLE != 11){
                    $findDuids = explode(',',$find['duid']);
                    $comp = $findDuids[1];
                   
                    $data['duid'] = $find['duid'].','.ADMIN_ID.','.$comp;  // 完结的转交人就是，申请人
                    
                    $jsonarr[] = [
                        'FromUid' => ADMIN_ID,
                        'Img' => '',
                        'ToUid' => $comp,
                        'Desc' => $data['replay'],
                        'Time' => time(),
                        'Action' => '转交至',
                    ];
                    // 【更新】序列化数据
                    $data['jsondata'] = json_encode($jsonarr);
                }else{
                    $comp = $find['cuid'];
                    // 【更新】经手人+
                    if(count($jsonarr) == 1){ //如果序列化数据为空，表示由运营人员刚接手(写入运营人员id + 转交人id)
                        $data['duid'] = $find['duid'].','.ADMIN_ID.','.$comp; // 完结的转交人就是，申请人
                    }else{ //写入 转交人id
                       $data['duid'] = $find['duid'].','.$comp;  // 完结的转交人就是，申请人
                    }
                    $jsonarr[] = [
                        'FromUid' => ADMIN_ID,
                        'Img' => '',
                        'ToUid' => $comp,
                        'Desc' => $data['replay'],
                        'Time' => time(),
                        'Action' => '转交至',
                    ];
                    // 【更新】序列化数据
                    $data['jsondata'] = json_encode($jsonarr);
                    $data['dtime'] = time(); 
                }
                
                unset($data['replay']);
                break;
            // 确认完结工单
            case 'affirm':

                $find = $this->get($data['id']);
                $jsonarr = json_decode($find['jsondata'],true);

                $jsonarr[] = [
                    'FromUid' => ADMIN_ID,
                    'Img' => '',
                    'ToUid' => '',
                    'Desc' => '',
                    'Time' => time(),
                    'Action' => '确认完结工单',
                ];
                // 【更新】序列化数据
                $data['jsondata'] = json_encode($jsonarr);
                $data['ftime'] = time();

                break;
            default:
                # code...
                break;
        }
        
        return $data; 
    }

    /**
     * [getAcceptCount 获取当前用户工单待处理数量，显示在左侧菜单中]
     * @return [type] [待处理工单数]
     */
    public function getAcceptCount(){
        $where = $this->checkWhere([],'accept');    
        $data = [];
        $temps = $this->where($where)->select();
        foreach($temps as $k => &$v){
            if(strpos($v['duid'],',') !== false){  
                $uids = explode(',',$v['duid']);
                $current_uid = array_pop($uids);
                if($current_uid != ADMIN_ID){ //保证是待受理的工单
                   unset($temps[$k]); 
                }
            }
        }
        return count($temps);
    }

}