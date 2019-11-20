<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\house\model\BanTai as BanTaiModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\deal\model\ChangeTable as ChangeTableModel;

class ChangeCancel extends SystemBase
{
    // 设置模型名称
    protected $name = 'change_cancel';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
        'data_json' => 'json',
        'change_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回给房管员','待资料员初审','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>5,'4'=>8,'5'=>9];

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
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索异动单号
        if(isset($data['change_order_number']) && $data['change_order_number']){
            $where[] = ['a.change_order_number','like','%'.$data['change_order_number'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索注销类别
        if(isset($data['cancel_type']) && $data['cancel_type']){
            $where[] = ['a.cancel_type','eq',$data['cancel_type']];
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
        $data['change_type'] = 8; //注销
        $data['change_order_number'] = date('Ym').'08'.random(14);
        
        $banRow = BanModel::get($data['ban_id']);
        if(!isset($data['cancel_ban'])){
            $data['cancel_ban'] = 0;
        }
        $data['cancel_rent'] = $data['cancel_change_1'];
        $data['cancel_use_area'] = $data['cancel_change_2'];
        $data['cancel_area'] = $data['cancel_change_3'];
        $data['cancel_oprice'] = $data['cancel_change_4'];

        $data['change_json'] = [
            'before' => [
                'ban_total_rent' => $data['floor_prescribed'],
                'ban_total_use_area' => $data['floor_areaofuse'],
                'ban_total_area' => $data['floor_builtuparea'],
                'ban_total_oprice' => $data['floor_original'],
            ],
            'change' => [
                'ban_total_rent' => $data['cancel_change_1'],
                'ban_total_use_area' => $data['cancel_change_2'],
                'ban_total_area' => $data['cancel_change_3'],
                'ban_total_oprice' => $data['cancel_change_4'],
            ],
            'after' => [
                'ban_total_rent' => $data['changes_floor_prescribed'],
                'ban_total_use_area' => $data['changes_floor_areaofuse'],
                'ban_total_area' => $data['changes_floor_builtuparea'],
                'ban_total_oprice' => $data['changes_floor_original'],
            ],
        ];
        //halt($data);
        $count = count($data['house_id']);
        $houseDetail = [];
        for ($i=0; $i < $count; $i++) { 
            $houseDetail[$i]['house_id'] = $data['house_id'][$i];  //房屋id
            $houseDetail[$i]['house_use_id'] = $data['house_use_id'][$i];  //房屋使用性质
            $houseDetail[$i]['house_number'] = $data['house_number'][$i];  //房屋编号
            $houseDetail[$i]['tenant_id'] = $data['tenant_id'][$i]; // 承租人
            $houseDetail[$i]['tenant_name'] = $data['house_lessee'][$i]; // 承租人
            $houseDetail[$i]['house_oprice'] = $data['house_original'][$i]; // 原价
            $houseDetail[$i]['house_area'] = $data['house_builtuparea'][$i]; // 建筑面积
            $houseDetail[$i]['house_pre_rent'] = $data['house_prescribed'][$i]; // 规租
            $houseDetail[$i]['house_lease_area'] = $data['house_rentalarea'][$i]; // 计租面积
        }
        $data['data_json'] = $houseDetail;

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
        // dump(session('systemusers'));
        // halt($row);
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
                $changeRow->allowField(['child_json','change_imgs','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新暂停计租表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            }else if(!isset($data['change_reason']) && ($changeRow['change_status'] == $finalStep)){

                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeRow['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新暂停计租的child_json、change_status，更新审批表change_desc、curr_role */
            }else if (isset($data['change_reason'])){
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
     * 终审审核成功后的数据处理 【完成，可优化】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {//halt($finalRow);
        
        $taiBanData = $taiHouseData = $tableData = [];

        // 按栋注销
        if($finalRow['cancel_ban']){ 
            // 将楼栋状态改成注销
            BanModel::where([['ban_id','eq',$finalRow['ban_id']]])->update(['ban_status'=>3]);
            // 新增楼栋台账
            $taiBanData['ban_id'] = $finalRow['ban_id'];
            $taiBanData['cuid'] = $finalRow['cuid'];
            $taiBanData['ban_tai_type'] = 4;
            $taiBanData['ban_tai_remark'] = '注销异动单号：'.$finalRow['change_order_number'];
            $taiBanData['data_json'] = [];
            $taiBanData['change_type'] = 8;
            $taiBanData['change_id'] = $finalRow['id'];

            // 如果选择了房屋
            if($finalRow['data_json']){
                $finalRow['ban_info'] = Db::name('ban')->where([['ban_id','eq',$finalRow['ban_id']]])->find();
                // 1、将涉及的所有房屋，设置成注销状态,并修改房屋的原价和房屋的建面
                foreach ($finalRow['data_json'] as $k => $v) {
                    HouseModel::where([['house_number','eq',$v['house_number']]])->update([
                        'house_oprice' => $v['house_oprice'],
                        'house_area' => $v['house_area'],
                        'house_status' => 3,
                        'house_is_pause' => 0,
                    ]);
                    //如果有暂停计租，则需要让暂停计租失效
                    ChangeTableModel::where([['change_type','eq',3],['house_id','eq',$v['house_id']]])->update(['change_status'=>0]);
                    //如果有减免，则需要让减免失效
                    ChangeTableModel::where([['change_type','eq',1],['house_id','eq',$v['house_id']]])->update(['change_status'=>0]);
                    Db::name('change_cut')->where([['change_status','eq',1],['house_id','eq',$v['house_id']]])->update(['end_date'=>date('Ym')]);
                    // 添加房屋台账
                    $taiHouseData[$k]['house_id'] = $v['house_id'];
                    $taiHouseData[$k]['tenant_id'] = $v['tenant_id'];
                    $taiHouseData[$k]['cuid'] = $finalRow['cuid'];
                    $taiHouseData[$k]['house_tai_type'] = 4;
                    $taiHouseData[$k]['house_tai_remark'] = '注销异动单号：'.$finalRow['change_order_number'];
                    $taiHouseData[$k]['data_json'] = [];
                    $taiHouseData[$k]['change_type'] = 8;
                    $taiHouseData[$k]['change_id'] = $finalRow['id'];

                    // 添加统计报表记录
                    $tableData[$k]['change_type'] = 8;
                    $tableData[$k]['change_order_number'] = $finalRow['change_order_number'];
                    $tableData[$k]['house_id'] = $v['house_id'];
                    $tableData[$k]['ban_id'] = $finalRow['ban_info']['ban_id'];
                    $tableData[$k]['inst_id'] = $finalRow['ban_info']['ban_inst_id'];
                    $tableData[$k]['inst_pid'] = $finalRow['ban_info']['ban_inst_pid'];
                    $tableData[$k]['owner_id'] = $finalRow['ban_info']['ban_owner_id'];
                    $tableData[$k]['use_id'] = $v['house_use_id'];
                    $tableData[$k]['change_rent'] = $v['house_pre_rent'];
                    $tableData[$k]['tenant_id'] = $v['tenant_id'];
                    $tableData[$k]['cuid'] = $finalRow['cuid']; 
                    $tableData[$k]['order_date'] = date('Ym',$finalRow['ftime']);  
                }
            }

        // 按户注销   
        }else{
            $changeBanData = [];
            $finalRow['ban_info'] = Db::name('ban')->where([['ban_id','eq',$finalRow['ban_id']]])->find();
            // 1、将涉及的所有房屋，设置成注销状态,并修改房屋的原价和房屋的建面
            foreach ($finalRow['data_json'] as $k => $v) {
                HouseModel::where([['house_number','eq',$v['house_number']]])->update([
                    'house_oprice' => $v['house_oprice'],
                    'house_area' => $v['house_area'],
                    'house_status' => 3,
                ]);
                //如果有暂停计租，则需要让暂停计租失效
                ChangeTableModel::where([['change_type','eq',3],['house_id','eq',$v['house_id']]])->update(['change_status'=>0]);
                //如果有减免，则需要让减免失效
                ChangeTableModel::where([['change_type','eq',1],['house_id','eq',$v['house_id']]])->update(['change_status'=>0]);
                Db::name('change_cut')->where([['change_status','eq',1],['house_id','eq',$v['house_id']]])->update(['end_date'=>date('Ym')]);

                $taiHouseData[$k]['house_id'] = $v['house_id'];
                $taiHouseData[$k]['tenant_id'] = $v['tenant_id'];
                $taiHouseData[$k]['cuid'] = $finalRow['cuid'];
                $taiHouseData[$k]['house_tai_type'] = 4;
                $taiHouseData[$k]['house_tai_remark'] = '注销异动单号：'.$finalRow['change_order_number'];
                $taiHouseData[$k]['data_json'] = [];
                $taiHouseData[$k]['change_type'] = 8;
                $taiHouseData[$k]['change_id'] = $finalRow['id'];

                if($v['house_use_id'] == 1){ // 住宅
                    $changeBanData['ban_civil_rent'] = Db::raw('ban_civil_rent-'.$v['house_pre_rent']);
                    $changeBanData['ban_civil_oprice'] = Db::raw('ban_civil_oprice-'.$v['house_oprice']);
                    $changeBanData['ban_civil_area'] = Db::raw('ban_civil_area-'.$v['house_area']);
                    $changeBanData['ban_use_area'] = Db::raw('ban_use_area-'.$v['house_lease_area']);
                }else if($v['house_use_id'] == 2){ // 企业
                    $changeBanData['ban_career_rent'] = Db::raw('ban_career_rent-'.$v['house_pre_rent']);
                    $changeBanData['ban_career_oprice'] = Db::raw('ban_career_oprice-'.$v['house_oprice']);
                    $changeBanData['ban_career_area'] = Db::raw('ban_career_area-'.$v['house_area']);
                }else{ // 机关
                    $changeBanData['ban_party_rent'] = Db::raw('ban_party_rent-'.$v['house_pre_rent']);
                    $changeBanData['ban_party_oprice'] = Db::raw('ban_party_oprice-'.$v['house_oprice']);
                    $changeBanData['ban_party_area'] = Db::raw('ban_party_area-'.$v['house_area']);
                }
                BanModel::where([['ban_id','eq',$finalRow['ban_id']]])->update($changeBanData);
         
                // 添加统计报表记录
                $tableData[$k]['change_type'] = 8;
                $tableData[$k]['change_order_number'] = $finalRow['change_order_number'];
                $tableData[$k]['house_id'] = $v['house_id'];
                $tableData[$k]['ban_id'] = $finalRow['ban_info']['ban_id'];
                $tableData[$k]['inst_id'] = $finalRow['ban_info']['ban_inst_id'];
                $tableData[$k]['inst_pid'] = $finalRow['ban_info']['ban_inst_pid'];
                $tableData[$k]['owner_id'] = $finalRow['ban_info']['ban_owner_id'];
                $tableData[$k]['use_id'] = $v['house_use_id'];
                $tableData[$k]['change_rent'] = $v['house_pre_rent'];
                $tableData[$k]['tenant_id'] = $v['tenant_id'];
                $tableData[$k]['cuid'] = $finalRow['cuid']; 
                $tableData[$k]['order_date'] = date('Ym');  
            }

            // 添加楼栋台账

        }
//dump($taiHouseData);dump($taiBanData);halt($tableData);
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->saveAll($taiHouseData);

        $BanTaiModel = new BanTaiModel;
        $BanTaiModel->allowField(true)->create($taiBanData);

        $ChangeTableModel = new ChangeTableModel;
        $ChangeTableModel->saveAll($tableData);
        
    }
}