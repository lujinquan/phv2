<?php

namespace app\deal\model;

use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\deal\model\Process as ProcessModel;

class ChangeNew extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_new';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
        'data_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回给房管员','待资料员初审','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>5,'4'=>8,'5'=>9];

    public function tenant()
    {
        return $this->hasOne('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_number,tenant_tel,tenant_card');
    }

    public function house()
    {
        return $this->hasOne('app\house\model\House', 'house_id', 'house_id')->bind('house_number,house_pre_rent,house_cou_rent');
    }

    public function checkWhere($data,$type)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        switch ($type) {
            // 申请列表
            case 'apply':
                $where[] = ['a.change_status','>',1];
                break;
            // 记录列表
            case 'record':
                $where[] = ['a.change_status','<',2];
                break;

            default:
                # code...
                break;
        }
        // 检索房屋编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索租户
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索房屋规定租金
        if(isset($data['house_pre_rent']) && $data['house_pre_rent']){
            $where[] = ['b.house_pre_rent','eq',$data['house_pre_rent']];
        }
        // 检索房屋计租面积
        if(isset($data['house_lease_area']) && $data['house_lease_area']){
            $where[] = ['b.house_lease_area','eq',$data['house_lease_area']];
        }
        // 检索申请时间
        if(isset($data['ctime']) && $data['ctime']){
            $startTime = strtotime($data['ctime']);
            $where[] = ['a.ctime','between time',[$startTime,$startTime+3600*24]];
        }
        // 检索完成时间
        if(isset($data['ftime']) && $data['ftime']){
            $startTime = strtotime($data['ftime']);
            $where[] = ['a.ftime','between time',[$startTime,$startTime+3600*24]];
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
        if(isset($data['id'])){
            $row = $this->get($data['id']); 
            if($row['is_back']){ //如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }
        if($data['save_type'] == 'save'){ //保存
            $data['change_status'] = 2;
        }else{ //保存并提交
            $data['change_status'] = 3;
        }
        
        $data['child_json'][] = [
            'success' => 1, 
            'action' => '提交申请',
            'time' => date('Y-m-d H:i:s'),
            'uid' => ADMIN_ID,
            'img' => '',
        ]; 

        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 7; //新发租
        $data['change_order_number'] = date('Ym').'07'.random(14);

        // 审批表数据
        $processRoles = $this->processRole;
        $processDescs = $this->processDesc;
        $data['change_desc'] = $processDescs[3];
        $data['curr_role'] = $processRoles[3];
        
        return $data; 
    }

    public function detail($id)
    {
        $row = self::get($id);
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
                $row['ban_info'] = BanModel::get($row['ban_id']);
        $row['house_info'] = HouseModel::get($row['house_id']);
        $row['tenant_info'] = TenantModel::get($row['tenant_id']);

        return $row;
    }

    public function process($data)
    {
       
        // 判断是否通过
        $changeRow = self::get($data['id']);

        // 获取最后一步的step
        $processRoles = $this->processRole;
        $steps = array_keys($processRoles);
        $finalStep = array_pop($steps);
        // 获取审批动作
        $processActions = $this->processAction;
        // 获取审批描述
        $processDescs = $this->processDesc;

        $changeUpdateData = $processUpdateData = [];

        /*  如果是打回  */
        if(isset($data['back_reason'])){
            $changeUpdateData['change_status'] = 2;
            $changeUpdateData['is_back'] = 1;
            $changeUpdateData['child_json'] = $changeRow['child_json'];
            $changeUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[2].'，原因：'.$data['back_reason'],
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];

            // 更新使用权变更表
            $changeRow->allowField(['child_json','is_back','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
            $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];
        }else{
            /* 如果审批通过，且非终审：更新使用权变更表的child_json、change_status，更新审批表change_desc、curr_role */
            if(!isset($data['change_reason']) && ($changeRow['change_status'] < $finalStep)){
                $changeUpdateData['change_status'] = $changeRow['change_status'] + 1;
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeRow['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];

                if(isset($data['file']) && $data['file']){
                    $changeUpdateData['change_imgs'] = implode(',',$data['file']);
                }

                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            }else if(!isset($data['change_reason']) && ($changeRow['change_status'] == $finalStep)){

                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                //终审成功后的数据处理
                try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role */
            }else if(isset($data['change_reason'])){
                $changeUpdateData['change_status'] = 0;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 0,
                    'action' => $processActions[$changeUpdateData['change_status']].'，原因：'.$data['change_reason'],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;                
            }

        }
        

        return $processUpdateData;
    }

    /**
     * 终审审核成功后的数据处理
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        //halt($finalRow);
        //HouseModel::where([['house_id','eq',$finalRow['house_id']]])->update(['tenant_id'=>$finalRow['new_tenant_id']]);
        // 添加台账记录
    }

}