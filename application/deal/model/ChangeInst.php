<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\Ban as BanModel;
use app\house\model\BanTai as BanTaiModel;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeTable as ChangeTableModel;

class ChangeInst extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_inst';

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

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回给房管员','待资料员初审','待经租会计审批','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>5,'4'=>6,'5'=>8,'6'=>9];

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
        // 检索新管段
        if(isset($data['new_inst_id']) && $data['new_inst_id']){
            $where[] = ['a.new_inst_id','eq',$data['new_inst_id']];
        }
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索申请时间(按月搜索)
        if(isset($data['ctime']) && $data['ctime']){
            $endTime = date('Y-m',strtotime('+1 month',strtotime($data['ctime'])));
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ctime','between time',[$data['ctime'],$endTime]];
        }
        // 检索申请时间(按月搜索)
        if(isset($data['ftime']) && $data['ftime']){
            $endFtime = date('Y-m',strtotime('+1 month',strtotime($data['ftime'])));
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ftime','between time',[$data['ftime'],$endFtime]];
        }
        // 检索生效时间(按月搜索)
        if(isset($data['effecttime']) && $data['effecttime']){ 
            $where[] = ['a.entry_date','eq',$data['effecttime']];
        }
        // 检索楼栋机构
        $insts = config('inst_ids');
        if(isset($data['old_inst_id']) && $data['old_inst_id']){
            $where[] = ['a.old_inst_id','in',$insts[$data['old_inst_id']]];
        }else{
            $instid = (isset($data['old_inst_id']) && $data['old_inst_id'])?$data['old_inst_id']:INST;
            $where[] = ['a.old_inst_id','in',$insts[$instid]];
        }
        
        return $where;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data,$flag = 'add')
    {
        if(($flag === 'add' && isset($data['file']) && $data['file']) || ($flag === 'edit' && isset($data['file']))){
            $data['change_imgs'] = trim(implode(',',$data['file']),',');
        }
        if($flag === 'edit' && !isset($data['file'])){
            $data['change_imgs'] = '';
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
        $data['change_type'] = 10; //别字更正
        $data['change_order_number'] = date('Ym').'10'.random(14);

        $fields = 'ban_id,ban_number,ban_inst_id,ban_address,ban_owner_id,ban_damage_id,ban_struct_id,ban_civil_area,ban_party_area,ban_career_area,(ban_civil_area + ban_party_area + ban_career_area) as ban_area,ban_civil_num,ban_party_num,ban_career_num,(ban_civil_num+ban_party_num+ban_career_num) as ban_num,ban_civil_rent,ban_party_rent,ban_career_rent,(ban_civil_rent + ban_party_rent + ban_career_rent) as ban_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_use_area';

        $data['data_json'] = BanModel::where([['ban_id','in',$data['ban_ids']]])->field($fields)->select()->toArray();//halt($data['data_json']);
        $data['change_ban_num'] = 0;
        $data['change_ban_rent'] = 0;
        $data['change_ban_area'] = 0;
        $data['change_ban_use_area'] = 0;
        $data['change_ban_oprice'] = 0;
        foreach($data['data_json'] as $v){
            $data['change_ban_num'] += $v['ban_num'];
            $data['change_ban_rent'] += $v['ban_rent'];
            $data['change_ban_area'] += $v['ban_area'];
            $data['change_ban_use_area'] += $v['ban_use_area'];
            $data['change_ban_oprice'] += $v['ban_oprice'];
        }
        $data['old_inst_id'] = $data['data_json'][0]['ban_inst_id'];

        // 审批表数据
        $data['ban_id'] = $data['ban_ids'][0];
        $data['ban_ids'] = implode(',',$data['ban_ids']);
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
        //$this->finalDeal($row);
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
        $params = ParamModel::getCparams();
        $changeUpdateData = $processUpdateData = [];

        /*  如果是打回  */
        if($data['flag'] === 'back'){
            if($data['back_reason']){
                $backReason = $data['back_reason'];
            }else{
                $backReason = $params['back_reason_type'][$data['back_reason_type']];
            }
            $changeUpdateData['change_status'] = 2;
            $changeUpdateData['is_back'] = 1;
            $changeUpdateData['child_json'] = $changeRow['child_json'];
            $changeUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[2].'，原因：'.$backReason,
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
            //if(!isset($data['change_reason']) && ($changeRow['change_status'] < $finalStep)){
            if(($data['flag'] === 'passed') && ($changeRow['change_status'] < $finalStep)){
                $changeUpdateData['change_status'] = $changeRow['change_status'] + 1;
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeRow['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];

                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            //}else if(!isset($data['change_reason']) && ($changeRow['change_status'] == $finalStep)){
            }else if(($data['flag'] === 'passed') && ($changeRow['change_status'] == $finalStep)){
                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['entry_date'] = date('Y-m');
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status','entry_date','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role */
            //}else if(isset($data['change_reason'])){
            }else if ($data['flag'] === 'change'){
                if($data['change_reason']){
                    $changeReason = $data['change_reason'];
                }else{
                    $changeReason = $params['change_reason_type'][$data['change_reason_type']];
                }
                $changeUpdateData['change_status'] = 0;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 0,
                    'action' => $processActions[$changeUpdateData['change_status']].'，原因：'.$changeReason,
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
     * 终审审核成功后的数据处理【完成，待优化】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {//halt($finalRow);
        
        // 1、将楼栋机构改成变更后的机构
        BanModel::where([['ban_id','in',$finalRow['ban_ids']]])->update(['ban_inst_id'=>$finalRow['new_inst_id']]);
        // 2、添加楼栋台账记录
        $taiBanData = [];
        $bans = explode(',', $finalRow['ban_ids']);

        $ChangeTableModel = new ChangeTableModel;

        foreach ($bans as $k => $v) {
            $finalRow['ban_info'] = Db::name('ban')->where([['ban_id','eq',$v]])->field('ban_inst_pid,ban_owner_id,ban_civil_rent,ban_party_rent,ban_career_rent')->find();

            $taiBanData[$k]['ban_id'] = $v;
            $taiBanData[$k]['ban_tai_type'] = 6;
            $taiBanData[$k]['cuid'] = $finalRow['cuid'];
            $taiBanData[$k]['ban_tai_remark'] = '管段调整异动单号：'.$finalRow['change_order_number'];
            $taiBanData[$k]['data_json'] = [
                'ban_inst_id' => [
                    'old' => $finalRow['old_inst_id'],
                    'new' => $finalRow['new_inst_id'],
                ],
            ];

            // 3、添加异动统计表记录
            $tableData = [];
            $tableData['change_type'] = 10;
            $tableData['change_order_number'] = $finalRow['change_order_number'];
            $tableData['ban_id'] = $v;
            $tableData['inst_id'] = $finalRow['old_inst_id'];
            $tableData['new_inst_id'] = $finalRow['new_inst_id'];
            $tableData['inst_pid'] = $finalRow['ban_info']['ban_inst_pid'];
            $tableData['owner_id'] = $finalRow['ban_info']['ban_owner_id']; 
            $tableData['order_date'] = date('Ym',$finalRow['ftime']);

            if($finalRow['ban_info']['ban_civil_rent'] > 0){ // 民用1
                $tableData['use_id'] = 1;
                $tableData['change_rent'] = $finalRow['ban_info']['ban_civil_rent'];
                Db::name('change_table')->insert($tableData);
                //$ChangeTableModel->save($tableData);
            }
            if($finalRow['ban_info']['ban_party_rent'] > 0){ // 机关3
                $tableData['use_id'] = 3;
                $tableData['change_rent'] = $finalRow['ban_info']['ban_party_rent'];
                Db::name('change_table')->insert($tableData);
                //$ChangeTableModel->save($tableData);
            }
            if($finalRow['ban_info']['ban_career_rent'] > 0){ // 企业2
                $tableData['use_id'] = 2;
                $tableData['change_rent'] = $finalRow['ban_info']['ban_career_rent'];
                Db::name('change_table')->insert($tableData);
                //$ChangeTableModel->save($tableData);
            }
             
        }  
        $BanTaiModel = new BanTaiModel;
        $BanTaiModel->saveAll($taiBanData);
    }

}