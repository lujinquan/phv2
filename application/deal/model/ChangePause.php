<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\deal\model\ChangeTable as ChangeTableModel;
use app\deal\model\ChangeRecord as ChangeRecordModel;

class ChangePause extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_pause';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'etime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'etime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
        'data_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回给房管员','待经租会计初审','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>6,'4'=>8,'5'=>9];

    public function checkWhere($data,$type)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        $where[] = ['a.dtime','eq',0];
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
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索是否有效
        if(isset($data['is_valid']) && $data['is_valid'] !== ''){
            $where[] = ['a.is_valid','eq',$data['is_valid']];
        }
        // 检索异动单号
        if(isset($data['change_order_number']) && $data['change_order_number']){
            $where[] = ['a.change_order_number','like','%'.$data['change_order_number'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $where[] = ['d.ban_number','like','%'.$data['ban_number'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索楼栋使用性质
        if(isset($data['ban_use_id']) && $data['ban_use_id']){
            $where[] = ['d.ban_use_id','in',explode(',',$data['ban_use_id'])];
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
        // // 检索楼栋机构
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
                'step' => 1,
                'action' => '提交申请',
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
        }
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 3; //暂停计租
        if($flag === 'add'){
            $data['change_order_number'] = date('Ym').'03'.random(14);   
        }
        
        if($data['house_id']){
            $data['data_json'] = HouseModel::with(['tenant'])->where([['house_id','in',$data['house_id']]])->field('house_id,house_number,tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent')->select()->toArray();
            $data['house_id'] = implode(',',$data['house_id']);
        }

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
            $row = self::get($id);
        }else{
            $row = self::where([['change_order_number','eq',$change_order_number]])->find(); 
        }
        //$this->finalDeal($row);
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['ban_info'] = BanModel::get($row['ban_id']);
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
                if($changeRow['change_status'] == 3){ 
                    if(isset($data['ChangePauseUpload']) && $data['ChangePauseUpload']){
                        $changeUpdateData['change_imgs'] = trim($changeRow['change_imgs'] . ','.implode(',',$data['ChangePauseUpload']),',');
                    }else{
                        $fileUploadConfig = Db::name('config')->where([['title','eq','changepause_file_upload']])->value('value');
                        if(strpos($fileUploadConfig, 'ChangePauseUpload') !== false){
                            return ['error_msg' => '请上传资料'];
                        }
                        
                    }
                }
                //dump($changeUpdateData);halt($data);
                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_imgs','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新暂停计租表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            }else if(($data['flag'] === 'passed') && ($changeRow['change_status'] == $finalStep)){

                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['is_valid'] = 1;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['entry_date'] = date( "Y-m", strtotime( "first day of next month" ) );  // 次月生效
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[1],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
                $changeRow->allowField(['child_json','change_status','entry_date','ftime','is_valid'])->save($changeUpdateData, ['id' => $data['id']]);
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新暂停计租的child_json、change_status，更新审批表change_desc、curr_role */
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
                    'action' => $processActions[0].'，原因：'.$changeReason,
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
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
    {
        // 异动记录
        $ChangeRecordModel = new ChangeRecordModel;
        $ChangeRecordModel->save([
            'change_type' => 3,
            'change_order_number' => $finalRow['change_order_number'],
            'ban_id' => $finalRow['ban_id'],
            'ctime' => $finalRow->getData('ctime'),
            'ftime' => time(),
            'change_status' => 1,
        ]);

        // 1、将涉及的所有房屋，设置成暂停计租状态
        HouseModel::where([['house_id','in',$finalRow['house_id']]])->update(['house_is_pause'=>1]);
        $houseTemps = HouseModel::with('ban')->where([['house_id','in',$finalRow['house_id']]])->field('house_id,tenant_id,house_use_id,house_pre_rent,ban_id')->select()->toArray();
        $houseArr = [];
        foreach($houseTemps as $s){
            $houseArr[$s['house_id']] = $s;
        }

        // 2、添加台账记录
        $taiData = $tableData = [];
        $houses = explode(',', $finalRow['house_id']);
        foreach($houses as $key => $h){
            $taiData[$key]['house_id'] = $h;
            $taiData[$key]['tenant_id'] = $houseArr[$h]['tenant_id'];
            $taiData[$key]['cuid'] = $finalRow['cuid'];
            $taiData[$key]['house_tai_type'] = 3;
            $taiData[$key]['data_json'] = [];
            $taiData[$key]['house_tai_remark'] = '暂停计租异动单号：'.$finalRow['change_order_number'];
            $taiData[$key]['change_type'] = 3;
            $taiData[$key]['change_id'] = $finalRow['id'];
            
            // 3、如果有减免，则需要让减免失效
            ChangeTableModel::where([['change_status','eq',1],['change_type','eq',1],['house_id','eq',$h],['end_date','>',date('Ym')]])->update(['end_date'=>date( "Ym", strtotime( "first day of next month" ) )]);
            Db::name('change_cut')->where([['change_status','eq',1],['house_id','eq',$h],['is_valid','eq',1]])->update(['end_date'=>date( "Ym", strtotime( "first day of next month" ) )]);

            // 添加产权统计记录
            $tableData[$key]['change_type'] = 3;
            $tableData[$key]['change_order_number'] = $finalRow['change_order_number'];
            $tableData[$key]['house_id'] = $h;
            $tableData[$key]['ban_id'] = $houseArr[$h]['ban_id'];
            $tableData[$key]['inst_id'] = $houseArr[$h]['ban_inst_id'];
            $tableData[$key]['inst_pid'] = $houseArr[$h]['ban_inst_pid'];
            $tableData[$key]['owner_id'] = $houseArr[$h]['ban_owner_id'];
            $tableData[$key]['use_id'] = $houseArr[$h]['house_use_id'];
            $tableData[$key]['change_rent'] = $houseArr[$h]['house_pre_rent'];
            $tableData[$key]['tenant_id'] = $houseArr[$h]['tenant_id'];
            $tableData[$key]['cuid'] = $finalRow['cuid'];
            $tableData[$key]['order_date'] = date( "Ym", strtotime( "first day of next month" ) );  // 次月生效 
        }

        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->saveAll($taiData);

        $ChangeTableModel = new ChangeTableModel;
        $ChangeTableModel->saveAll($tableData);

    }
}