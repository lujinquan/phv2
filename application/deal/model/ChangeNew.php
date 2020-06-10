<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\BanTai as BanTaiModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\house\model\Tenant as TenantModel;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeTable as ChangeTableModel;

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
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索房屋规定租金
        if(isset($data['house_pre_rent']) && $data['house_pre_rent']){
            $where[] = ['b.house_pre_rent','eq',$data['house_pre_rent']];
        }
        // 检索房屋计租面积
        if(isset($data['house_lease_area']) && $data['house_lease_area']){
            $where[] = ['b.house_lease_area','eq',$data['house_lease_area']];
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
        //halt($data);
        return $data; 
    }

    public function detail($id)
    {
        $row = self::get($id);
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['ban_info'] = BanModel::get($row['ban_id']);
        $row['house_info'] = HouseModel::get($row['house_id']);
        $row['tenant_info'] = TenantModel::get($row['tenant_id']);
        $this->finalDeal($row);
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

                //如果是第二步经租会计（则可以修改附件）
                if($changeRow['change_status'] == 3){ 
                    // if(isset($data['file']) && $data['file']){
                    //     $changeUpdateData['change_imgs'] = trim($changeRow['change_imgs'] . ','.implode(',',$data['file']));
                    // }

                    if(isset($data['ChangeNewUpload']) && $data['ChangeNewUpload']){
                        $changeUpdateData['change_imgs'] = trim($changeRow['change_imgs'] . ','.implode(',',$data['ChangeNewUpload']),',');
                    }else{
                        $fileUploadConfig = Db::name('config')->where([['title','eq','changenew_file_upload']])->value('value');
                        if(strpos($fileUploadConfig, 'ChangeNewUpload') !== false){
                            return ['error_msg' => '请上传资料'];
                        }
                        
                    }
                }
                // if(isset($data['file']) && $data['file']){
                //     $changeUpdateData['change_imgs'] = implode(',',$data['file']);
                // }

                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_imgs','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
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
     * 终审审核成功后的数据处理【完成】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 1、将新发的房屋变成正常状态
        HouseModel::where([['house_id','eq',$finalRow['house_id']]])->update(['house_status'=>1]);
        Db::name('tenant')->where([['tenant_id','eq',$finalRow['tenant_id']]])->update(['tenant_status'=>1]);
        //Db::name('ban')->where([['ban_id','eq',$finalRow['ban_id']]])->update(['ban_status'=>1]);
        
        // 2、添加台账记录
        $taiHouseData = $taiBanData = [];
        $taiHouseData['house_id'] = $finalRow['house_id'];
        $taiHouseData['tenant_id'] = $finalRow['tenant_id'];
        $taiHouseData['house_tai_type'] = 1;
        $taiHouseData['cuid'] = $finalRow['cuid'];
        $taiHouseData['house_tai_remark'] = '新发租异动单号：'.$finalRow['change_order_number'];
        $taiHouseData['data_json'] = [];
        $taiHouseData['change_type'] = 7;
        $taiHouseData['change_id'] = $finalRow['id'];
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiHouseData);

        $taiBanData['ban_id'] = $finalRow['ban_id'];
        $taiBanData['ban_tai_type'] = 1;
        $taiBanData['cuid'] = $finalRow['cuid'];
        $taiBanData['house_tai_remark'] = '新发租异动单号：'.$finalRow['change_order_number'];
        $taiBanData['data_json'] = [];
        $taiBanData['change_type'] = 7;
        $taiBanData['change_id'] = $finalRow['id'];
        $BanTaiModel = new BanTaiModel;
        $BanTaiModel->allowField(true)->create($taiBanData);

        // 3、修改涉及到的房间的状态
        $roomids = Db::name('house_room')->where([['house_id','eq',$finalRow['house_id']]])->column('room_id');
        if($roomids){
            Db::name('room')->where([['room_id','in',$roomids]])->update(['room_status'=>1]);
        }

        // 2、将数据写入到异动统计表
        $houseInfo = Db::name('house')->where([['house_id','eq',$finalRow['house_id']]])->find();
        $banInfo = Db::name('ban')->where([['ban_id','eq',$finalRow['ban_id']]])->find();


        $tableData = [];
        // 1、将新发的房屋所在的楼栋变成正常状态
        if(!$banInfo['ban_status']){
           $tableData['change_ban_num'] = $banInfo['ban_civil_num']+$banInfo['ban_career_num']+$banInfo['ban_party_num'];
        }    
        $tableData['change_type'] = 7;
        $tableData['change_order_number'] = $finalRow['change_order_number'];
        $tableData['house_id'] = $finalRow['house_id'];
        $tableData['ban_id'] = $finalRow['ban_id'];
        $tableData['inst_id'] = $banInfo['ban_inst_id'];
        $tableData['inst_pid'] = $banInfo['ban_inst_pid'];
        $tableData['owner_id'] = $banInfo['ban_owner_id'];
        $tableData['use_id'] = $houseInfo['house_use_id'];
        $tableData['change_rent'] = $houseInfo['house_pre_rent']; 
        $tableData['change_area'] = $houseInfo['house_area']; 
        $tableData['change_oprice'] = $houseInfo['house_oprice'];
        $tableData['change_house_num'] = 1;
        if($houseInfo['house_use_id'] == 1){
            $tableData['change_use_area'] = $houseInfo['house_lease_area']; 
        }else{
            $tableData['change_use_area'] = $houseInfo['house_use_area'];     
        } 
        $tableData['change_send_type'] = $finalRow['new_type'];
        $tableData['tenant_id'] = $finalRow['tenant_id']; 
        $tableData['cuid'] = $finalRow['cuid'];
        $tableData['order_date'] = date('Ym'); 
        $ChangeTableModel = new ChangeTableModel;
        //halt($tableData);
        $ChangeTableModel->save($tableData);
        
        // 1、将新发的房屋基础数据加到所在的楼栋
        if($houseInfo['house_use_id'] == 1){
            BanModel::where([['ban_id','eq',$finalRow['ban_id']]])->update([
                'ban_status'=>1,
                'ban_civil_rent'=>Db::raw('ban_civil_rent+'.$houseInfo['house_pre_rent']),
                'ban_civil_area'=>Db::raw('ban_civil_area+'.$houseInfo['house_area']),
                'ban_civil_oprice'=>Db::raw('ban_civil_oprice+'.$houseInfo['house_oprice']),
                'ban_use_area'=>Db::raw('ban_use_area+'.$houseInfo['house_lease_area']),
                'ban_civil_holds'=>Db::raw('ban_civil_holds+1'),
                'ctime'=>$finalRow['ftime'],
            ]);
        }elseif($houseInfo['house_use_id'] == 2){
            BanModel::where([['ban_id','eq',$finalRow['ban_id']]])->update([
                'ban_status'=>1,
                'ban_career_rent'=>Db::raw('ban_career_rent+'.$houseInfo['house_pre_rent']),
                'ban_career_area'=>Db::raw('ban_career_area+'.$houseInfo['house_area']),
                'ban_career_oprice'=>Db::raw('ban_career_oprice+'.$houseInfo['house_oprice']),
                'ban_career_holds'=>Db::raw('ban_career_holds+1'),
                'ctime'=>$finalRow['ftime'],
            ]);
        }else{
            BanModel::where([['ban_id','eq',$finalRow['ban_id']]])->update([
                'ban_status'=>1,
                'ban_party_rent'=>Db::raw('ban_party_rent+'.$houseInfo['house_pre_rent']),
                'ban_party_area'=>Db::raw('ban_party_area+'.$houseInfo['house_area']),
                'ban_party_oprice'=>Db::raw('ban_party_oprice+'.$houseInfo['house_oprice']),
                'ban_party_holds'=>Db::raw('ban_party_holds+1'),
                'ctime'=>$finalRow['ftime'],
            ]);
        }

    }

}