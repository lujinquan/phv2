<?php

namespace app\deal\model;

use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\deal\model\Process as ProcessModel;

class ChangeUse extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_use';

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

    protected $processAction = ['审批不通过','审批成功','打回修改','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','待房管员打回修改','待经租会计初审','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>6,'4'=>8,'5'=>9];

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
        // 检索原租户
        if(isset($data['old_tenant_name']) && $data['old_tenant_name']){
            $where[] = ['a.old_tenant_name','like','%'.$data['old_tenant_name'].'%'];
        }
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
        $data['change_status'] = 3;
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 13; //使用权变更
        $data['change_order_number'] = date('Ym').'13'.random(14);
        $data['child_json'] = [];
        $data['child_json'][] = [
            'success' => 1, 
            'action' => '提交申请',
            'time' => date('Y-m-d H:i:s'),
            'uid' => ADMIN_ID,
            'img' => '',
        ];

        // 审批表数据
        $data['ban_id'] = HouseModel::where([['house_id','eq',$data['house_id']]])->value('ban_id');
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
        $row['house_number'] = HouseModel::where([['house_id','eq',$row['house_id']]])->value('house_number');
        $oldTenantRow = TenantModel::where([['tenant_id','eq',$row['old_tenant_id']]])->field('tenant_number,tenant_card')->find();
        $row['old_tenant_number'] = $oldTenantRow['tenant_number'];
        $row['old_tenant_card'] = $oldTenantRow['tenant_card'];
        $newTenantRow = TenantModel::where([['tenant_id','eq',$row['new_tenant_id']]])->field('tenant_number,tenant_card')->find();
        $row['new_tenant_number'] = $newTenantRow['tenant_number'];
        $row['new_tenant_card'] = $newTenantRow['tenant_card'];

        return $row;
    }

    public function process($data)
    {
       
        // 判断是否通过
        $changeuseRow = self::get($data['id']);

        // 获取最后一步的step
        $processRoles = $this->processRole;
        $steps = array_keys($processRoles);
        $finalStep = array_pop($steps);
        // 获取审批动作
        $processActions = $this->processAction;
        // 获取审批描述
        $processDescs = $this->processDesc;

        $changeUseUpdateData = $processUpdateData = [];

        /* 如果审批通过，且非终审：更新使用权变更表的child_json、change_status，更新审批表change_desc、curr_role */
        if(!isset($data['change_reason']) && ($changeuseRow['change_status'] < $finalStep)){
            $changeUseUpdateData['change_status'] = $changeuseRow['change_status'] + 1;
            $changeUseUpdateData['child_json'] = $changeuseRow['child_json'];
            $changeUseUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[$changeUseUpdateData['change_status']],
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
            // 更新使用权变更表
            $changeuseRow->allowField(['child_json','change_status'])->save($changeUseUpdateData, ['id' => $data['id']]);;
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeUseUpdateData['change_status']];
            $processUpdateData['curr_role'] = $processRoles[$changeUseUpdateData['change_status']];
        }

        /* 如果审批通过，且为终审：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
        if(!isset($data['change_reason']) && ($changeuseRow['change_status'] == $finalStep)){
            $changeUseUpdateData['change_status'] = 1;
            $changeUseUpdateData['ftime'] = time();
            $changeUseUpdateData['child_json'] = $changeuseRow['child_json'];
            $changeUseUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[$changeUseUpdateData['change_status']],
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
            // 更新使用权变更表
            $changeuseRow->allowField(['child_json','change_status','ftime'])->save($changeUseUpdateData, ['id' => $data['id']]);
            //终审成功后的数据处理
            $this->finalDeal($changeuseRow);
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeUseUpdateData['change_status']];
            $processUpdateData['ftime'] = $changeUseUpdateData['ftime'];
            $processUpdateData['status'] = 0;
        }

        
        /* 如果审批不通过：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role */
        if(isset($data['change_reason'])){
            $changeUseUpdateData['change_status'] = 0;
            $changeUseUpdateData['ftime'] = time();
            $changeUseUpdateData['child_json'] = $changeuseRow['child_json'];
            $changeUseUpdateData['child_json'][] = [
                'success' => 0,
                'action' => $processActions[$changeUseUpdateData['change_status']],
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
            // 更新使用权变更表
            $changeuseRow->allowField(['child_json','change_status','ftime'])->save($changeUseUpdateData, ['id' => $data['id']]);
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeUseUpdateData['change_status']];
            $processUpdateData['ftime'] = $changeUseUpdateData['ftime'];
            $processUpdateData['status'] = 0;
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
        HouseModel::where([['house_id','eq',$finalRow['house_id']]])->update(['tenant_id'=>$finalRow['new_tenant_id']]);
        
    }

}