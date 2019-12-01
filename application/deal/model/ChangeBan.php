<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\BanTai as BanTaiModel;
use app\deal\model\Process as ProcessModel;

class ChangeBan extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_ban';

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
        // 检索楼栋编号
        if(isset($data['ban_number']) && $data['ban_number']){
            $where[] = ['d.ban_number','like','%'.$data['ban_number'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索楼栋调整类型
        if(isset($data['ban_change_id']) && $data['ban_change_id']){
            $where[] = ['a.ban_change_id','eq',$data['ban_change_id']];
        }
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索申请时间
        if(isset($data['ctime']) && $data['ctime']){
            $endTime = date('Y-m',strtotime('+1 month',strtotime($data['ctime'])));
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ctime','between time',[$data['ctime'],$endTime]];
        }
        // 检索完成时间
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
            if($row['is_back']){ // 如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }
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
        if(isset($data['ban_change_id'])){
            if($data['ban_change_id'] == 1){ //如果是楼层调整
                $houseDetail = [];
                if(isset($data['detail_house_number'])){
                    $count = count($data['detail_house_number']);
                    for ($i=0; $i < $count; $i++) { 
                        $houseDetail[$i]['detail_house_number'] = $data['detail_house_number'][$i];  //房屋编号
                        $houseDetail[$i]['detail_tenant_name'] = $data['detail_tenant_name'][$i]; // 承租人
                        $houseDetail[$i]['detail_old_floor'] = $data['detail_old_floor'][$i]; // 原楼层
                        $houseDetail[$i]['detail_new_floor'] = $data['detail_new_floor'][$i]; // 现楼层
                        $houseDetail[$i]['detail_house_rent'] = $data['detail_house_rent'][$i]; // 原规定租金
                        $houseDetail[$i]['detail_house_old_cou_rent'] = $data['detail_house_old_cou_rent'][$i]; // 计算租金（异动前）
                        $houseDetail[$i]['detail_diff_cou_rent'] = $data['detail_diff_cou_rent'][$i]; // 租金变化
                        $houseDetail[$i]['detail_house_new_cou_rent'] = $data['detail_house_new_cou_rent'][$i]; // 计算租金（异动后）
                    }
                }
                $data['data_json'] = [
                    'houseDetail'=> $houseDetail, //异动明细
                    'changeDetail'=>[ // 租赁异动
                        // 异动前
                        'floor_household' => $data['floor_household'],
                        'floor_prescribed' => $data['floor_prescribed'],
                        'floor_areaofuse' => $data['floor_areaofuse'],
                        'floor_builtuparea' => $data['floor_builtuparea'],
                        'floor_original' => $data['floor_original'],
                        'floor_tung' => $data['floor_tung'],
                        // 异动中
                        'floor_before_0edit' => $data['floor_before_0edit'],
                        'floor_before_1edit' => $data['floor_before_1edit'],
                        'floor_before_2edit' => $data['floor_before_2edit'],
                        'floor_before_3edit' => $data['floor_before_3edit'],
                        'floor_before_4edit' => $data['floor_before_4edit'],
                        'floor_before_5edit' => $data['floor_before_5edit'],
                        // 异动后
                        'floor_changes_household' => $data['floor_changes_household'],
                        'floor_changes_prescribed' => $data['floor_changes_prescribed'],
                        'floor_changes_areaofuse' => $data['floor_changes_areaofuse'],
                        'floor_changes_builtuparea' => $data['floor_changes_builtuparea'],
                        'floor_changes_original' => $data['floor_changes_original'],
                        'floor_changes_tung' => $data['floor_changes_tung'],
                    ],
                ]; 
            }elseif($data['ban_change_id'] == 2){
                $data['data_json'] = [
                    'houseDetail'=>[],
                    'changeDetail'=>[
                        // 异动前
                        'endloss_class' => $data['endloss_class'],
                        'endloss_household' => $data['endloss_household'],
                        'endloss_prescribed' => $data['endloss_prescribed'],
                        'endloss_areaofuse' => $data['endloss_areaofuse'],
                        'endloss_builtuparea' => $data['endloss_builtuparea'],
                        'endloss_original' => $data['endloss_original'],
                        'endloss_tung' => $data['endloss_tung'],
                        // 异动后
                        'endloss_changes_class' => $data['endloss_changes_class'],
                        'endloss_changes_household' => $data['endloss_changes_household'],
                        'endloss_changes_prescribed' => $data['endloss_changes_prescribed'],
                        'endloss_changes_areaofuse' => $data['endloss_changes_areaofuse'],
                        'endloss_changes_builtuparea' => $data['endloss_changes_builtuparea'],
                        'endloss_changes_original' => $data['endloss_changes_original'],
                        'endloss_changes_tung' => $data['endloss_changes_tung'],
                    ],
                ];
            }else{
                $data['data_json'] = [
                    'houseDetail'=>[],
                    'changeDetail'=>[
                        // 异动前
                        'structure_class' => $data['structure_class'],
                        'structure_household' => $data['structure_household'],
                        'structure_prescribed' => $data['structure_prescribed'],
                        'structure_areaofuse' => $data['structure_areaofuse'],
                        'structure_builtuparea' => $data['structure_builtuparea'],
                        'structure_original' => $data['structure_original'],
                        'structure_tung' => $data['structure_tung'],
                        // 异动后
                        'structure_changes_class' => $data['structure_changes_class'],
                        'structure_changes_household' => $data['structure_changes_household'],
                        'structure_changes_prescribed' => $data['structure_changes_prescribed'],
                        'structure_changes_areaofuse' => $data['structure_changes_areaofuse'],
                        'structure_changes_builtuparea' => $data['structure_changes_builtuparea'],
                        'structure_changes_original' => $data['structure_changes_original'],
                        'structure_changes_tung' => $data['structure_changes_tung'],
                    ],
                ];
            }

        }

        
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 14; //楼栋调整
        $data['change_order_number'] = date('Ym').'14'.random(14);

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
        $row['ban_info'] = BanModel::where([['ban_id','eq',$row['ban_id']]])->find();
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

                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新使用权表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            }else if(!isset($data['change_reason']) && ($changeRow['change_status'] == $finalStep)){

                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['entry_time'] = date('Y-m');
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeRow['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
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
     * 终审审核成功后的数据处理 【完成，可优化】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 判断改变的类型
        if($finalRow['ban_change_id'] == 1){ // 如果是调整层高

            // 1、修改楼栋层高
            $BanModel = new BanModel;
            $BanModel->where([['ban_id','eq',$finalRow['ban_id']]])->update(['ban_floors'=>$finalRow['new_floors']]);

            // 2、添加楼栋台账
            $taiBanData = [];
            $taiBanData['ban_id'] = $finalRow['ban_id'];
            $taiBanData['ban_tai_type'] = 5;
            $taiBanData['cuid'] = $finalRow['cuid'];
            $taiBanData['ban_tai_remark'] = '楼栋调整异动单号：'.$finalRow['change_order_number'];
            $taiBanData['data_json'] = [
                'ban_floors' => [
                    'old' => $finalRow['old_floors'],
                    'new' => $finalRow['new_floors'],
                ],
            ];
            $BanTaiModel = new BanTaiModel;
            $BanTaiModel->allowField(true)->create($taiBanData);

            // 3、批量处理房屋计算租金变化


        }elseif($finalRow['ban_change_id'] == 2){ // 如果是调整完损等级

            // 1、修改楼栋完损等级
            $BanModel = new BanModel;
            $BanModel->where([['ban_id','eq',$finalRow['ban_id']]])->update(['ban_damage_id'=>$finalRow['new_damage']]);

            // 2、添加楼栋台账
            $taiBanData = [];
            $taiBanData['ban_id'] = $finalRow['ban_id'];
            $taiBanData['ban_tai_type'] = 5;
            $taiBanData['cuid'] = $finalRow['cuid'];
            $taiBanData['ban_tai_remark'] = '楼栋调整异动单号：'.$finalRow['change_order_number'];
            $taiBanData['data_json'] = [
                'ban_damage_id' => [
                    'old' => $finalRow['old_damage'],
                    'new' => $finalRow['new_damage'],
                ],
            ];
            $BanTaiModel = new BanTaiModel;
            $BanTaiModel->allowField(true)->create($taiBanData);

        }else{ // 如果是调整结构类别

            // 1、修改楼栋结构类别
            $BanModel = new BanModel;
            $BanModel->where([['ban_id','eq',$finalRow['ban_id']]])->update(['ban_struct_id'=>$finalRow['new_struct']]);

            // 2、添加楼栋台账
            $taiBanData = [];
            $taiBanData['ban_id'] = $finalRow['ban_id'];
            $taiBanData['ban_tai_type'] = 5;
            $taiBanData['cuid'] = $finalRow['cuid'];
            $taiBanData['ban_tai_remark'] = '楼栋调整异动单号：'.$finalRow['change_order_number'];
            $taiBanData['data_json'] = [
                'ban_struct_id' => [
                    'old' => $finalRow['old_struct'],
                    'new' => $finalRow['new_struct'],
                ],
            ];
            $BanTaiModel = new BanTaiModel;
            $BanTaiModel->allowField(true)->create($taiBanData);

        }

    }

}