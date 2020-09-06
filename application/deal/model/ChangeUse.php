<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\deal\model\Process as ProcessModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\deal\model\ChangeTable as ChangeTableModel;
use app\deal\model\ChangeRecord as ChangeRecordModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;


class ChangeUse extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_use';

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

    // protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','审批通过','终审通过'];

    // protected $processDesc = ['失败','成功','打回给房管员','待资料员初审','待经租会计审批','待经管所长审批','待经管科长终审'];

    // protected $processRole = ['2'=>4,'3'=>5,'4'=>6,'5'=>8,'6'=>9];

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回给房管员','待经租会计审批','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>4,'3'=>6,'4'=>8,'5'=>9];

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
        // 检索原租户
        if(isset($data['old_tenant_name']) && $data['old_tenant_name']){
            $where[] = ['a.old_tenant_name','like','%'.$data['old_tenant_name'].'%'];
        }
        // 检索变更后租户
        if(isset($data['new_tenant_name']) && $data['new_tenant_name']){
            $where[] = ['a.new_tenant_name','like','%'.$data['new_tenant_name'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
         // 检索转让类型
        if(isset($data['change_use_type']) && $data['change_use_type']){
            $where[] = ['a.change_use_type','eq',$data['change_use_type']];
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
        // halt($data);
        if(isset($data['id'])){
            $row = $this->get($data['id']); 
            if($row['is_back']){ //如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }
        if($data['save_type'] == 'save'){ //保存
            $data['change_status'] = 2;
        //保存并提交，提交则生成子记录
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
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 13; //使用权变更
        if($flag === 'add'){
            $data['change_order_number'] = date('Ym').'13'.random(14);   
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
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['house_info'] = HouseModel::where([['house_id','eq',$row['house_id']]])->find();
        $row['ban_info'] = BanModel::where([['ban_id','eq',$row['ban_id']]])->find();
        $oldTenantRow = TenantModel::where([['tenant_id','eq',$row['old_tenant_id']]])->field('tenant_number,tenant_card')->find();
        $row['old_tenant_number'] = $oldTenantRow['tenant_number'];
        $row['old_tenant_card'] = $oldTenantRow['tenant_card'];
        $newTenantRow = TenantModel::where([['tenant_id','eq',$row['new_tenant_id']]])->field('tenant_number,tenant_card')->find();
        $row['new_tenant_number'] = $newTenantRow['tenant_number'];
        $row['new_tenant_card'] = $newTenantRow['tenant_card'];
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

            // 更新子表
            $changeRow->allowField(['child_json','is_back','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
            $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];
        }else{
            /* 如果审批通过，且非终审：更新子表的child_json、change_status，更新审批表change_desc、curr_role */
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
                $changeUpdateData['entry_date'] = date( "Y-m", strtotime( "first day of next month" ) );  // 次月生效
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
     * 终审审核成功后的数据处理 【已完成】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 异动记录
        $ChangeRecordModel = new ChangeRecordModel;
        $ChangeRecordModel->save([
            'change_type' => 13,
            'change_order_number' => $finalRow['change_order_number'],
            'ban_id' => $finalRow['ban_id'],
            'ctime' => $finalRow->getData('ctime'),
            'ftime' => time(),
            'change_status' => 1,
        ]);

        // 1、改变房屋绑定的租户;
        HouseModel::where([['house_id','eq',$finalRow['house_id']]])->update(['tenant_id'=>$finalRow['new_tenant_id']]);
        // 1、变更后的租户的状态改成正常
        TenantModel::where([['tenant_id','eq',$finalRow['new_tenant_id']],['tenant_status','eq',0]])->update(['tenant_status'=>1]);
        
        // 2、添加房屋台账记录
        $taiData = [];
        $taiData['house_id'] = $finalRow['house_id'];
        $taiData['tenant_id'] = $finalRow['old_tenant_id'];
        $TenantModel = new TenantModel;
        $oldTenantRow = $TenantModel->where([['tenant_id','eq',$finalRow['old_tenant_id']]])->field('tenant_number')->find();
        $newTenantRow = $TenantModel->where([['tenant_id','eq',$finalRow['new_tenant_id']]])->field('tenant_number')->find();
        $taiData['cuid'] = $finalRow['cuid'];
        $taiData['house_tai_type'] = 6;
        $taiData['data_json'] = [
            'tenant_number' => [
                'old' => $oldTenantRow['tenant_number'],
                'new' => $newTenantRow['tenant_number'],
            ],
            'tenant_name' => [
                'old' => $finalRow['old_tenant_name'],
                'new' => $finalRow['new_tenant_name'],
            ],
        ];
        // 3、如果有减免，则需要让减免失效
        ChangeTableModel::where([['change_type','eq',1],['house_id','eq',$finalRow['house_id']]])->update(['change_status'=>0]);
        Db::name('change_cut')->where([['change_status','eq',1],['house_id','eq',$finalRow['house_id']]])->update(['is_valid'=>0,'end_date'=>date('Ym')]);

        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiData);
        // 4、使用权变更后原租约失效
        Db::name('change_lease')->where([['house_id','eq',$finalRow['house_id']],['tenant_id','eq',$finalRow['old_tenant_id']]])->update(['is_valid'=>0]);
        $qrcodeUrl = Db::name('change_lease')->where([['house_id','eq',$finalRow['house_id']],['tenant_id','eq',$finalRow['old_tenant_id']]])->value('qrcode');
        if($qrcodeUrl){
            @unlink($_SERVER['DOCUMENT_ROOT'].$qrcodeUrl);
        }
        $szno = HouseModel::where([['house_id','eq',$finalRow['house_id']]])->value('house_szno');
        
        if ($finalRow['is_create_lease']) {
            // 5、自动生成租约异动
            $ChangeLeaseModel = new ChangeLeaseModel;
            $changeleaseData = [];
            $changeleaseData['ban_id'] = $finalRow['ban_id'];
            $changeleaseData['house_id'] = $finalRow['house_id'];
            $changeleaseData['tenant_id'] = $finalRow['new_tenant_id'];
            $changeleaseData['cuid'] = $finalRow['cuid'];
            $changeleaseData['tenant_name'] = $finalRow['new_tenant_name'];
            $changeleaseData['szno'] = $szno;
            $ChangeLeaseModel->auto_create_changelease($changeleaseData);
        }
        
        // 6、检查微信会员是否绑定当前房屋
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['tenant_id','eq',$finalRow['old_tenant_id']]])->field('member_id')->find();
        $new_member_info = $WeixinMemberModel->where([['tenant_id','eq',$finalRow['new_tenant_id']]])->field('member_id')->find();
        if(!$new_member_info && $member_info){ // 如果原租户已进入微信系统，新租户未进入微信系统【将原租户会员绑定改房屋的认证状态改成0】
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $WeixinMemberHouseModel->where([['house_id','eq',$finalRow['house_id']],['member_id','eq',$member_info['member_id']]])->update(['is_auth'=>0]);
        // 如果新租户已进入微信系统，原租户未进入微信系统【将原租户会员绑定改房屋的认证状态改成0】
        }else if($new_member_info && !$member_info){ // 如果原租户、新租户已进入微信系统
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $find = $WeixinMemberHouseModel->where([['house_id','eq',$finalRow['house_id']],['member_id','eq',$new_member_info['member_id']]])->find();
            if($find){
                $find->is_auth = 1;
                $find->save();
            }else{
                $WeixinMemberHouseModel = new WeixinMemberHouseModel;
                $WeixinMemberHouseModel->save(['house_id'=>$finalRow['house_id'],'member_id'=>$new_member_info['member_id'],'is_auth'=>1]);
            }
        }else if($new_member_info && $member_info){ // 如果原租户、新租户已进入微信系统
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $WeixinMemberHouseModel->where([['house_id','eq',$finalRow['house_id']],['member_id','eq',$member_info['member_id']]])->update(['member_id'=>$new_member_info['member_id']]);
        }
    }

}