<?php

namespace app\deal\model;

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

    protected $processRole = ['2'=>4,'3'=>6,'4'=>8,'5'=>9];

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
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索注销类别
        if(isset($data['cancel_type']) && $data['cancel_type']){
            $where[] = ['a.cancel_type','eq',$data['cancel_type']];
        }
        // 检索申请时间(按天搜索)
        if(isset($data['ctime']) && $data['ctime']){
            $startTime = strtotime($data['ctime']);
            $where[] = ['a.ctime','between time',[$startTime,$startTime+3600*24]];
        }
        // 检索申请时间(按天搜索)
        if(isset($data['ftime']) && $data['ftime']){
            $startFilishTime = strtotime($data['ftime']);
            $where[] = ['a.ftime','between time',[$startFilishTime,$startFilishTime+3600*24]];
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
        $data['change_type'] = 8; //使用权变更
        $data['change_order_number'] = date('Ym').'08'.random(14);
        
        $banRow = BanModel::get($data['ban_id']);

        // $data['change_json']['before']['ban_total_rent'] = bcaddMerge([$banRow['ban_civil_rent'],$banRow['ban_party_rent'],$banRow['ban_career_rent']]);
        // $data['change_json']['before']['ban_total_use_area'] = $banRow['ban_use_area'];
        // $data['change_json']['before']['ban_total_area'] = bcaddMerge([$banRow['ban_civil_area'],$banRow['ban_party_area'],$banRow['ban_career_area']]);
        // $data['change_json']['before']['ban_total_oprice'] = bcaddMerge([$banRow['ban_civil_oprice'],$banRow['ban_party_oprice'],$banRow['ban_career_oprice']]);

        // $data['change_json']['after']['ban_total_rent'] = bcsub($data['change_json']['before']['ban_total_rent'],$data['cancel_rent'],2);
        // $data['change_json']['after']['ban_total_use_area'] = bcsub($data['change_json']['before']['ban_total_use_area'],$data['cancel_use_area'],2);
        // $data['change_json']['after']['ban_total_area'] = bcsub($data['change_json']['before']['ban_total_area'],$data['cancel_area'],2);
        // $data['change_json']['after']['ban_total_oprice'] = bcsub($data['change_json']['before']['ban_total_oprice'],$data['cancel_oprice'],2);
        
        $data['cancel_ban'] = $data['changes_floor_original'];
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
        
        $count = count($data['house_number']);
        $houseDetail = [];
        for ($i=0; $i < $count; $i++) { 
            $houseDetail[$i]['house_number'] = $data['house_number'][$i];  //房屋编号
            $houseDetail[$i]['tenant_name'] = $data['house_lessee'][$i]; // 承租人
            $houseDetail[$i]['house_oprice'] = $data['house_original'][$i]; // 原价
            $houseDetail[$i]['house_area'] = $data['house_builtuparea'][$i]; // 建筑面积
            $houseDetail[$i]['house_pre_rent'] = $data['house_prescribed'][$i]; // 规租
            $houseDetail[$i]['house_lease_area'] = $data['house_rentalarea'][$i]; // 计租面积
        }
        $data['data_json'] = $houseDetail;

        // if($data['house_id']){
        //     $houseids = explode(',',$data['house_id']);
        //     $data['data_json'] = HouseModel::with(['tenant'])->where([['house_id','in',$houseids]])->field('house_number,tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_area,house_use_area,house_oprice,house_lease_area')->select()->toArray();
        // }

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
                    'action' => $processActions[$changeUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                //终审成功后的数据处理
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
     * 终审审核成功后的数据处理
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 将涉及的所有房屋，设置成注销状态
        HouseModel::where([['house_id','in',$finalRow['house_id']]])->update(['house_status'=>3]);

        $banData = [];
        if($finalRow['cancel_ban']){ //如果整栋注销

        }else{
            BanModel::where([['ban_id','eq',$finalRow['ban_id']]])->update(['ban_status'=>3]);
        }
        
        $banInfo = BanModel::get($finalRow['ban_id']);
        $houseTemps = HouseModel::with('ban')->where([['house_id','in',$finalRow['house_id']]])->field('house_id,tenant_id,house_use_id,(house_pre_rent + house_pump_rent + house_diff_rent) as house_yue_rent,ban_id')->select()->toArray();

        $houseArr = [];
        foreach($houseTemps as $s){
            $houseArr[$s['house_id']] = $s;
        }

        // 变更楼栋数据

        // 添加房屋台账记录
        $taiHouseData = $taiBanData = $tableData = [];
        $houses = explode(',', $finalRow['house_id']);
        foreach($houses as $key => $h){
            $taiHouseData[$key]['house_id'] = $h;
            $taiHouseData[$key]['tenant_id'] = $houseArr[$h]['tenant_id'];
            $taiHouseData[$key]['cuid'] = $finalRow['cuid'];
            $taiHouseData[$key]['house_tai_type'] = 4;
            $taiHouseData[$key]['data_json'] = [
                'house_status' => [
                    'old' => 1,
                    'new' => 3,
                ],
                
            ];

            // 添加产权统计记录
            $tableData[$key]['change_type'] = 8;
            $tableData[$key]['change_order_number'] = $finalRow['change_order_number'];
            $tableData[$key]['house_id'] = $h;
            $tableData[$key]['ban_id'] = $houseArr[$h]['ban_id'];
            $tableData[$key]['inst_id'] = $houseArr[$h]['ban_inst_id'];
            $tableData[$key]['inst_pid'] = $houseArr[$h]['ban_inst_pid'];
            $tableData[$key]['owner_id'] = $houseArr[$h]['ban_owner_id'];
            $tableData[$key]['use_id'] = $houseArr[$h]['house_use_id'];
            $tableData[$key]['change_rent'] = $houseArr[$h]['house_yue_rent'];
            $tableData[$key]['tenant_id'] = $houseArr[$h]['tenant_id'];
            $tableData[$key]['cuid'] = $finalRow['cuid']; 
            $tableData[$key]['order_date'] = date('Ym',$finalRow['ftime']);  
        }

        $taiBanData['ban_id'] = $finalRow['ban_id'];
        $taiBanData['cuid'] = $finalRow['cuid'];
        $taiBanData['ban_tai_type'] = 4;
        $taiBanData['data_json'] = [

        ];

        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->saveAll($taiHouseData);

        $BanTaiModel = new BanTaiModel;
        $BanTaiModel->allowField(true)->create($taiBanData);

        $ChangeTableModel = new ChangeTableModel;
        $ChangeTableModel->saveAll($tableData);
        
    }
}