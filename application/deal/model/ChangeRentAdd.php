<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\deal\model\ChangeTable as ChangeTableModel;


class ChangeRentAdd extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_rentadd';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
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
        // 检索租户
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索房屋编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索追收以前年
        if(isset($data['before_year_rent']) && $data['before_year_rent']){
            $where[] = ['a.before_year_rent','eq',$data['before_year_rent']];
        }
        // 检索追收以前月
        if(isset($data['before_month_rent']) && $data['before_month_rent']){
            $where[] = ['a.before_month_rent','eq',$data['before_month_rent']];
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
                'step' => 1,
                'action' => '提交申请',
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
        }
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 11; //暂停计租
        $data['change_order_number'] = date('Ym').'11'.random(14);
        
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
        $row['house_info'] = HouseModel::get($row['house_id']);
        $row['tenant_info'] = TenantModel::get($row['tenant_id']);
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
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
                // 更新暂停计租表
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
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
     * 终审审核成功后的数据处理 【完成】
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 1、如果有追加以前年，则增加一条以前年回收的订单
        $RentModel = new RentModel;
        $rentData = [];
        $houseInfo = Db::name('house')->where([['house_id','eq',$finalRow['house_id']]])->find();
       
        if($finalRow['before_year_rent']){ //rent_order_number,rent_order_date,rent_order_cut,rent_order_receive,house_id,tenant_id
            $rent_order_date = date('Y'.'12',strtotime('-1 year')); 
            $rentData[] = [         
                'house_id' => $finalRow['house_id'],
                'rent_order_number' => $houseInfo['house_number'].$rent_order_date,
                'tenant_id' => $finalRow['tenant_id'],
                'rent_order_date' => $rent_order_date,
                'rent_order_receive' => $finalRow['before_year_rent'],
                'rent_order_paid' => $finalRow['before_year_rent'],
                'rent_order_remark' => '租金追加调整创建的以前年回收订单，异动单号：'.$finalRow['change_order_number'],
                'pay_way' => 1,
                'ptime' => time(),
                'is_deal' => 1,
            ];  
        }
        // 2、如果有追加以前月，则增加一条以前月回收的订单
        if($finalRow['before_month_rent']){
            $rent_order_date = date('Ym',strtotime('-1 month')); 
            $rentData[] = [         
                'house_id' => $finalRow['house_id'],
                'rent_order_number' => $houseInfo['house_number'].$rent_order_date,
                'tenant_id' => $finalRow['tenant_id'],
                'rent_order_date' => $rent_order_date,
                'rent_order_receive' => $finalRow['before_month_rent'],
                'rent_order_paid' => $finalRow['before_month_rent'],
                'rent_order_remark' => '租金追加调整创建的以前月回收订单，异动单号：'.$finalRow['change_order_number'],
                'pay_way' => 1,
                'ptime' => time(),
                'is_deal' => 1,
            ];
        }
        // 3、如果有追加当月，则增加一条当月回收的订单
        if($finalRow['this_month_rent']){
            $rent_order_date = date('Ym'); 
            $rentData[] = [         
                'house_id' => $finalRow['house_id'],
                'rent_order_number' => $houseInfo['house_number'].$rent_order_date,
                'tenant_id' => $finalRow['tenant_id'],
                'rent_order_date' => $rent_order_date,
                'rent_order_receive' => $finalRow['this_month_rent'],
                'rent_order_paid' => $finalRow['this_month_rent'],
                'rent_order_remark' => '租金追加调整创建的当前月回收订单，异动单号：'.$finalRow['change_order_number'],
                'pay_way' => 1,
                'ptime' => time(),
                'is_deal' => 1,
            ];
        }
        $RentModel->insertAll($rentData);

        // 4、异动统计表中添加一条记录
        $banInfo = Db::name('ban')->where([['ban_id','eq',$finalRow['ban_id']]])->find();
        $tableData = [];       
        $tableData['change_type'] = 12;
        $tableData['change_order_number'] = $finalRow['change_order_number'];
        $tableData['house_id'] = $finalRow['house_id'];;
        $tableData['ban_id'] = $finalRow['ban_id'];
        $tableData['inst_id'] = $banInfo['ban_inst_id'];
        $tableData['inst_pid'] = $banInfo['ban_inst_pid'];
        $tableData['owner_id'] = $banInfo['ban_owner_id'];
        $tableData['use_id'] = $houseInfo['house_use_id'];
        $tableData['change_month_rent'] = $finalRow['before_month_rent'];
        $tableData['change_year_rent'] = $finalRow['before_year_rent'];
        $tableData['change_rent'] = $finalRow['this_month_rent'];
        $tableData['tenant_id'] = $finalRow['tenant_id'];
        $tableData['cuid'] = $finalRow['cuid'];
        $tableData['order_date'] = date('Ym'); 
        $ChangeTableModel = new ChangeTableModel;
        $ChangeTableModel->save($tableData);

        // 5、添加一条房屋台账记录
        $taiHouseData = [];
        $taiHouseData['house_id'] = $finalRow['house_id'];
        $taiHouseData['tenant_id'] = $finalRow['tenant_id'];
        $taiHouseData['house_tai_type'] = 9;
        $taiHouseData['cuid'] = $finalRow['cuid'];
        $taiHouseData['house_tai_remark'] = '租金追加调整异动单号：'.$finalRow['change_order_number'];
        $taiHouseData['data_json'] = [];
        $taiHouseData['change_type'] = 11;
        $taiHouseData['change_id'] = $finalRow['id'];
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiHouseData);

    }
}