<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\house\model\Tenant as TenantModel;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeTable as ChangeTableModel;
use app\deal\model\ChangeRecord as ChangeRecordModel;

class ChangeCut extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_cut';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'etime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'etime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回给房管员','待经租会计初审','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>6,'4'=>8,'5'=>9];

    public function tenant()
    {
        return $this->hasOne('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_number,tenant_tel,tenant_card,tenant_name');
    }

    public function house()
    {
        return $this->hasOne('app\house\model\House', 'house_id', 'house_id')->bind('house_number,house_pre_rent,house_cou_rent,house_use_id');
    }

    // public function ban()
    // {
    //     return $this->hasOne('app\house\model\Ban', 'house_id', 'house_id')->bind('ban_number,house_pre_rent,house_cou_rent,house_use_id');
    // }

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
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索是否有效
        if(isset($data['is_valid']) && $data['is_valid'] !== ''){
            $where[] = ['a.is_valid','eq',$data['is_valid']];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['b.house_use_id','in',explode(',',$data['house_use_id'])];
        }
        // 检索房屋编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索减免类型
        if(isset($data['cut_type']) && $data['cut_type']){
            $where[] = ['a.cut_type','eq',$data['cut_type']];
        }
        // 检索减免金额
        if(isset($data['cut_rent']) && $data['cut_rent']){
            $where[] = ['a.cut_rent','eq',$data['cut_rent']];
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
        // 检索机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $insts = explode(',',$data['ban_inst_id']);
            $instid_arr = [];
            foreach ($insts as $inst) {
                foreach (config('inst_ids')[$inst] as $instid) {
                    $instid_arr[] = $instid;
                }
            }
            $where[] = ['d.ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[INST]];
        }
        // 检索楼栋机构
        // $insts = config('inst_ids');
        // if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
        //     $where[] = ['d.ban_inst_id','in',$insts[$data['ban_inst_id']]];
        // }else{
        //     $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        //     $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        // }
        
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
            $data['child_json'][] = [
                'success' => 1, 
                'action' => '提交申请',
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ]; 
        }
        
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 1; //减免
        $data['change_order_number'] = date('Ym').'01'.random(14);

        // 审批表数据
        $processRoles = $this->processRole;
        $processDescs = $this->processDesc;
        $data['change_desc'] = $processDescs[3];
        $data['curr_role'] = $processRoles[3];

        return $data; 
    }

    public function detail($id,$change_order_number = '')
    {
        if($id){
            $row = self::with(['house','tenant'])->find($id);
        }else{
            $row = self::with(['house','tenant'])->where([['change_order_number','eq',$change_order_number]])->find(); 
        }
        // $row = self::with(['house','tenant'])->get($id);
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['ban_info'] = BanModel::get($row['ban_id']);
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
//halt($data);
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
            /* 如果审批通过，且非终审：更新子表的child_json、change_status，更新审批表change_desc、curr_role */
            //if(!isset($data['change_reason']) && ($changeRow['change_status'] < $finalStep)){
            if(($data['flag'] === 'passed') && ($changeRow['change_status'] < $finalStep)){
                //如果是第二步经租会计（则可以修改附件）
                if($changeRow['change_status'] == 3){ 
                    // if(isset($data['file']) && $data['file']){
                    //     $changeUpdateData['change_imgs'] = trim($changeRow['change_imgs'] . ','.implode(',',$data['file']));
                    // }
                    if(isset($data['ChangeCutForm']) && $data['ChangeCutForm']){
                        $changeUpdateData['change_imgs'] = trim($changeRow['change_imgs'] . ','.implode(',',$data['ChangeCutForm']),',');
                    }else{
                        $fileUploadConfig = Db::name('config')->where([['title','eq','changecut_file_upload']])->value('value');
                        if(strpos($fileUploadConfig, 'ChangeCutForm') !== false){
                            return ['error_msg' => '请上传会审单'];
                        }
                        
                    }
                }

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
                $changeRow->allowField(['child_json','change_imgs','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            //}else if(!isset($data['change_reason']) && ($changeRow['change_status'] == $finalStep)){
            }else if(($data['flag'] === 'passed') && ($changeRow['change_status'] == $finalStep)){
                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['is_valid'] = 1;
                $changeUpdateData['end_date'] = (date('Y')+1).'01';
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
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}

                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status','ftime','entry_date','is_valid','end_date'])->save($changeUpdateData, ['id' => $data['id']]);
                
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
     * 终审审核成功后的数据处理【完成】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 异动记录
        $ChangeRecordModel = new ChangeRecordModel;
        $ChangeRecordModel->save([
            'change_type' => 1,
            'change_order_number' => $finalRow['change_order_number'],
            'ban_id' => $finalRow['ban_id'],
            'ctime' => $finalRow->getData('ctime'),
            'ftime' => $finalRow->getData('ftime'),
            'change_status' => $finalRow['change_status'],
        ]);
        
        // 1、添加房屋台账
        $taiHouseData = [];
        $taiHouseData['house_id'] = $finalRow['house_id'];
        $taiHouseData['tenant_id'] = $finalRow['tenant_id'];
        $taiHouseData['house_tai_type'] = 10;
        $taiHouseData['cuid'] = $finalRow['cuid'];
        $taiHouseData['house_tai_remark'] = '租金减免异动单号：'.$finalRow['change_order_number'];
        $taiHouseData['data_json'] = [];
        $taiHouseData['change_type'] = 1;
        $taiHouseData['change_id'] = $finalRow['id'];
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiHouseData);

        // 2、将数据写入到异动统计表
        $houseInfo = Db::name('house')->where([['house_id','eq',$finalRow['house_id']]])->find();
        $banInfo = Db::name('ban')->where([['ban_id','eq',$finalRow['ban_id']]])->find();
        $tableData = [];       
        $tableData['change_type'] = 1; 
        $tableData['change_order_number'] = $finalRow['change_order_number'];
        $tableData['house_id'] = $finalRow['house_id'];
        $tableData['ban_id'] = $finalRow['ban_id'];
        $tableData['inst_id'] = $banInfo['ban_inst_id'];
        $tableData['inst_pid'] = $banInfo['ban_inst_pid'];
        $tableData['owner_id'] = $banInfo['ban_owner_id'];
        $tableData['use_id'] = $houseInfo['house_use_id'];
        $tableData['change_rent'] = $finalRow['cut_rent']; //减免金额
        $tableData['end_date'] = (date('Y')+1).'01'; //减免失效时间
        $tableData['cut_type'] = $finalRow['cut_type']; //减免类型
        $tableData['tenant_id'] = $finalRow['tenant_id']; 
        $tableData['cuid'] = $finalRow['cuid'];
        $tableData['order_date'] = date('Ym'); 
        $ChangeTableModel = new ChangeTableModel;
        $ChangeTableModel->save($tableData);

        // 3、检查如果当前房屋正在租金减免异动生效的过程中，则将原生效的减免异动自动失效掉
        Db::name('change_cut')->where([['house_id','eq',$finalRow['house_id']],['change_status','eq',1],['end_date','>',date('Ym')],['change_order_number','neq',$finalRow['change_order_number']]])->update(['end_date'=>date('Ym'),'is_valid'=>0]);
        Db::name('change_table')->where([['house_id','eq',$finalRow['house_id']],['change_type','eq',1],['end_date','>',date('Ym')],['change_order_number','neq',$finalRow['change_order_number']]])->delete();
        
    }

}