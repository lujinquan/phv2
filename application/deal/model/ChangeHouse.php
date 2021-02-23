<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\BanTai as BanTaiModel;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\house\model\HouseTemp as HouseTempModel;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeTable as ChangeTableModel;
use app\deal\model\ChangeRecord as ChangeRecordModel;

class ChangeHouse extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_house';

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
        // 检索异动单号
        if(isset($data['change_order_number']) && $data['change_order_number']){
            $where[] = ['a.change_order_number','like','%'.$data['change_order_number'].'%'];
        }
        // 检索楼栋编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $where[] = ['d.ban_number','like','%'.$data['ban_number'].'%'];
        }
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索房屋编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索租户姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索房屋使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['b.house_use_id','in',explode(',',$data['house_use_id'])];
        }
        // 检索楼栋调整类型
        if(isset($data['ban_change_id']) && $data['ban_change_id']){
            $where[] = ['a.ban_change_id','eq',$data['ban_change_id']];
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
            if($row['is_back']){ // 如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }
        $before_base_info = HouseModel::get($data['house_id']);
        if($data['save_type'] == 'save'){ // 保存
            $data['change_status'] = 2;
        // 保存并提交
        }else{ 
            $data['change_status'] = 3;
            $data['child_json'][] = [
                'success' => 1, 
                'action' => '提交申请',
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
        }
        //halt($data);
        $data['data_json'] = [
            'ban' => $data['Ban'],
            'before_base_info' => $before_base_info,
        ];


        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 9; //楼栋调整
        if($flag === 'add'){
            $data['change_order_number'] = date('Ym').'09'.random(14);   
        }

        // 审批表数据
        $processRoles = $this->processRole;
        $processDescs = $this->processDesc;
        $data['change_desc'] = $processDescs[3];
        $data['curr_role'] = $processRoles[3];
        
        return $data; 
    }

    public function dataFilter_old($data,$flag = 'add')
    {

        if(($flag === 'add' && isset($data['file']) && $data['file']) || ($flag === 'edit' && isset($data['file']))){
            $data['change_imgs'] = trim(implode(',',$data['file']),',');
        }
        if($flag === 'edit' && !isset($data['file'])){
            $data['change_imgs'] = '';
        }
        if(isset($data['id'])){
            $row = $this->get($data['id']); 
            if($row['is_back']){ // 如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }
        $before_base_info = HouseModel::get($data['house_id']);
        if($data['save_type'] == 'save'){ // 保存
            $data['change_status'] = 2;
        // 保存并提交
        }else{ 
            $data['change_status'] = 3;
            $data['child_json'][] = [
                'success' => 1, 
                'action' => '提交申请',
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
        }
        $data['data_json'] = [
            'before' => [
                'floor_households' => $data['floor_households'],
                'floor_prescribed' => $data['floor_prescribed'],
                'floor_areaofuse' => $data['floor_areaofuse'],
                'floor_builtuparea' => $data['floor_builtuparea'],
                'floor_original' => $data['floor_original'],
                'floor_buildings' => $data['floor_buildings'],
            ],
            'change' => [
                'cancel_before_0edit' => $data['cancel_before_0edit'],
                'cancel_before_1edit' => $data['cancel_before_1edit'],
                'cancel_before_2edit' => $data['cancel_before_2edit'],
                'cancel_before_3edit' => $data['cancel_before_3edit'],
                'cancel_before_4edit' => $data['cancel_before_4edit'],
                'cancel_before_5edit' => $data['cancel_before_5edit'],
            ],
            'after' => [
                'changes_floor_households' => $data['changes_floor_households'],
                'changes_floor_prescribed' => $data['floor_prescribed'],
                'changes_floor_areaofuse' => $data['changes_floor_areaofuse'],
                'changes_floor_builtuparea' => $data['changes_floor_builtuparea'],
                'changes_floor_original' => $data['changes_floor_original'],
                'changes_floor_buildings' => $data['changes_floor_buildings'],
            ],
            'before_base_info' => $before_base_info,
        ];


        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 9; //楼栋调整
        $data['change_order_number'] = date('Ym').'09'.random(14);

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
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['ban_info'] = BanModel::where([['ban_id','eq',$row['ban_id']]])->find();
        $HouseModel = new HouseModel;
        $row['house_info'] = HouseModel::with('tenant')->where([['house_id','eq',$row['house_id']]])->find();
        $row['house_table'] = $HouseModel->get_house_renttable($row['house_id']);
        //halt($row);
        //$row['new_house_info'] = HouseTempModel::with('tenant')->where([['house_id','eq',$row['house_id']]])->find();
        //halt($row);
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
                if($changeRow['change_status'] == 3){ 
                    $change_imgs = trim($changeRow['change_imgs']);
                    if(isset($data['HouseChangeState']) && $data['HouseChangeState']){
                        $change_imgs .= ','.implode(',',$data['HouseChangeState']);
                    }
                    if(isset($data['HouseChangeExtra']) && $data['HouseChangeExtra']){
                        $change_imgs .=  ','.implode(',',$data['HouseChangeExtra']);
                    }
                    $changeUpdateData['change_imgs'] = $change_imgs;

                }
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
                $changeUpdateData['entry_date'] = date( "Y-m", strtotime( "first day of next month" ) );  // 次月生效
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                
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
    {
        $nextMonth = date( "Ym", strtotime( "first day of next month" ) );
        // 异动记录
        $ChangeRecordModel = new ChangeRecordModel;
        $ChangeRecordModel->save([
            'change_type' => 9,
            'change_order_number' => $finalRow['change_order_number'],
            'ban_id' => $finalRow['ban_id'],
            'ctime' => $finalRow->getData('ctime'),
            'ftime' => time(),
            'change_status' => 1,
        ]);

        $houseInfo = Db::name('house')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where([['house_id','eq',$finalRow['house_id']]])->field('house_pre_rent,house_use_id,ban_owner_id,ban_inst_id,ban_inst_pid')->find();

        foreach ($finalRow['data_json']['ban'] as $k => $v) {
            $curr_ban_info = Db::name('ban')->where([['ban_number','eq',$v['HABanID']]])->field('ban_id')->find();
            // dump($curr_ban_info);
            // 1、调整楼栋信息
            if($houseInfo['house_use_id'] == 1){
                BanModel::where([['ban_id','eq',$curr_ban_info['ban_id']]])->update([
                    'ban_civil_rent'=>Db::raw('ban_civil_rent+'.$v['HARent']),
                    'ban_civil_area'=>Db::raw('ban_civil_area+'.$v['HABanArea']),
                    'ban_civil_oprice'=>Db::raw('ban_civil_oprice+'.$v['HAPrice']),
                    'ban_use_area'=>Db::raw('ban_use_area+'.$v['HALeasedArea']),
                ]);
            }elseif($houseInfo['house_use_id'] == 2){
                BanModel::where([['ban_id','eq',$curr_ban_info['ban_id']]])->update([
                    'ban_career_rent'=>Db::raw('ban_career_rent+'.$v['HARent']),
                    'ban_career_area'=>Db::raw('ban_career_area+'.$v['HABanArea']),
                    'ban_career_oprice'=>Db::raw('ban_career_oprice+'.$v['HAPrice']),
                ]);
            }else{
                BanModel::where([['ban_id','eq',$curr_ban_info['ban_id']]])->update([
                    'ban_party_rent'=>Db::raw('ban_party_rent+'.$v['HARent']),
                    'ban_party_area'=>Db::raw('ban_party_area+'.$v['HABanArea']),
                    'ban_party_oprice'=>Db::raw('ban_party_oprice+'.$v['HAPrice']),
                ]);
            }
            // halt(1);
            // 3、添加楼栋调整
            $taiBanData = [];
            $taiBanData['ban_id'] = $curr_ban_info['ban_id'];
            $taiBanData['ban_tai_type'] = 7;
            $taiBanData['cuid'] = $finalRow['cuid'];
            $taiBanData['ban_tai_remark'] = '房屋调整异动单号：'.$finalRow['change_order_number'];
            $taiBanData['data_json'] = [];
            $taiBanData['change_type'] = 9;
            $taiBanData['change_id'] = $finalRow['id'];
            $BanTaiModel = new BanTaiModel;
            $BanTaiModel->allowField(true)->create($taiBanData);

            // 2、调整房屋信息
            Db::name('house')->where([['house_id','eq',$finalRow['house_id']]])->update([
                'house_pre_rent'=>Db::raw('house_pre_rent+'.$v['HARent']),
                'house_area'=>Db::raw('house_area+'.$v['HABanArea']),
                'house_oprice'=>Db::raw('house_oprice+'.$v['HAPrice']),
            ]);


            // 5、写入到table表
            $tableData = [];       
            $tableData['change_type'] = 12;
            $tableData['change_order_number'] = $finalRow['change_order_number'];
            $tableData['house_id'] = $finalRow['house_id'];
            $tableData['ban_id'] = $curr_ban_info['ban_id'];
            $tableData['inst_id'] = $houseInfo['ban_inst_id'];
            $tableData['inst_pid'] = $houseInfo['ban_inst_pid'];
            $tableData['owner_id'] = $houseInfo['ban_owner_id'];
            $tableData['use_id'] = $houseInfo['house_use_id'];

            $tableData['change_rent'] = $v['HARent']; 
            $tableData['change_area'] = $v['HABanArea']; 
            $tableData['change_oprice'] = $v['HAPrice'];
            $tableData['change_use_area'] = $v['HALeasedArea']; 

            $tableData['tenant_id'] = $finalRow['tenant_id']; 
            $tableData['cuid'] = $finalRow['cuid'];
            $tableData['order_date'] = $nextMonth;  // 次月生效
            $ChangeTableModel = new ChangeTableModel;
            $ChangeTableModel->save($tableData);
        }
        // 如果调整了租金，检查是否调整的房屋是暂停计租的房子，如果是的，则将暂停计租的房子失效，失效日期为次月，同时生成一条新的暂停计租的记录，生效时间是下月。
        $newHouseInfo = Db::name('house')->where([['house_id','eq',$finalRow['house_id']]])->field('house_pre_rent')->find();
        if($houseInfo['house_pre_rent'] != $newHouseInfo['house_pre_rent']){ // 规租发生了变化
            // 判断是否有正在生效的暂停计租
            $change_pause_info = Db::name('change_table')->where([['change_type','eq',3],['change_status','eq',1],['house_id','eq',$finalRow['house_id']],['end_date','eq',0]])->find();
            if(!empty($change_pause_info)){

                Db::name('change_table')->where([['change_type','eq',3],['change_status','eq',1],['house_id','eq',$finalRow['house_id']]])->update(['end_date'=>$nextMonth]);
                // 再生成一条暂停计租的记录
                $tableData = [];       
                $tableData['change_type'] = 3;
                $tableData['cut_type'] = 0;
                $tableData['change_order_number'] = $change_pause_info['change_order_number'];
                $tableData['house_id'] = $change_pause_info['house_id'];
                $tableData['ban_id'] = $change_pause_info['ban_id'];
                $tableData['inst_id'] = $change_pause_info['inst_id'];
                $tableData['inst_pid'] = $change_pause_info['inst_pid'];
                $tableData['owner_id'] = $change_pause_info['owner_id'];
                $tableData['use_id'] = $change_pause_info['use_id'];
                $tableData['change_rent'] = $newHouseInfo['house_pre_rent']; 
                $tableData['tenant_id'] = $change_pause_info['tenant_id']; 
                $tableData['order_date'] = $nextMonth;  // 次月生效
                $ChangeTableModel = new ChangeTableModel;
                $ChangeTableModel->save($tableData);
            }
        }

        // 4、添加房屋台账
        $taiHouseData = [];
        $taiHouseData['house_id'] = $finalRow['house_id'];
        $taiHouseData['tenant_id'] = $finalRow['tenant_id'];
        $taiHouseData['house_tai_type'] = 12;
        $taiHouseData['cuid'] = $finalRow['cuid'];
        $taiHouseData['house_tai_remark'] = '房屋调整异动单号：'.$finalRow['change_order_number'];
        $taiHouseData['data_json'] = [];
        $taiHouseData['change_type'] = 9;
        $taiHouseData['change_id'] = $finalRow['id'];
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiHouseData);
        
        
        
    }

    /**
     * 终审审核成功后的数据处理【完成，待优化】
     * @return [type] [description]
     */
    private function finalDeal_old($finalRow)
    {//halt($finalRow);

        // 1、更新房屋信息（临时表的房屋id替换主表，房间信息，房屋房间中间表信息）
        $houseChangeSql = "replace into ".config('database.prefix')."house select * from ".config('database.prefix')."house_temp where house_id = ".$finalRow['house_id'];
        Db::execute($houseChangeSql);
        // 2、更新房间信息（临时表的房屋id替换主表，房间信息，房屋房间中间表信息）【待优化】
        $roomids = Db::name('house_room')->where([['house_id','eq',$finalRow['house_id']]])->column('room_id');
        $roomChangeSql = "replace into ".config('database.prefix')."room select * from ".config('database.prefix')."room_temp where room_id in (".implode(',',$roomids).")";
        Db::execute($roomChangeSql);
        // 3、更新房屋房间中间表【待优化】
        Db::name('house_room')->where([['room_id','in',$roomids]])->delete();
        $houseRoomData = [];
        $tempRoomids = Db::name('house_room_temp')->where([['house_id','eq',$finalRow['house_id']]])->field('room_id,house_id')->select();
        //如果有减免，则需要让减免失效
        ChangeTableModel::where([['change_type','eq',1],['house_id','eq',$finalRow['house_id']]])->update(['change_status'=>0]);
        Db::name('change_cut')->where([['change_status','eq',1],['house_id','eq',$finalRow['house_id']]])->update(['end_date'=>date('Ym')]);

        Db::name('house_room')->insertAll($tempRoomids);
        // 4、添加房屋台账
        $taiHouseData = [];
        $taiHouseData['house_id'] = $finalRow['house_id'];
        $taiHouseData['tenant_id'] = $finalRow['tenant_id'];
        $taiHouseData['house_tai_type'] = 12;
        $taiHouseData['cuid'] = $finalRow['cuid'];
        $taiHouseData['house_tai_remark'] = '房屋调整异动单号：'.$finalRow['change_order_number'];
        $taiHouseData['data_json'] = [];
        $taiHouseData['change_type'] = 9;
        $taiHouseData['change_id'] = $finalRow['id'];
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiHouseData);

        // 5、更新房屋临时表
        Db::execute('call syn_temp_table');

        // $rowTemp = HouseTempModel::get($finalRow['house_id']);
        // $houseChangeData = [
        //     'house_diff_rent' => $rowTemp['house_diff_rent'],
        //     'house_pump_rent' => $rowTemp['house_pump_rent'],
        //     'house_protocol_rent' => $rowTemp['house_protocol_rent'],
        //     'house_area' => $rowTemp['house_area'],
        //     'house_oprice' => $rowTemp['house_oprice'],
        // ];
        // $row = HouseModel::get($finalRow['house_id']);
        // HouseModel::where([['house_id','eq',$finalRow['house_id']]])->update($houseChangeData);

        //dump($rowTemp);halt($row);
        // 添加台账记录
    }

}