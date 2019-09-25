<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;

class ChangeOffset extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_offset';

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

    protected $processAction = ['审批不通过','审批成功','打回','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','打回','待经租会计初审','待经管所长审批','待经管科长终审'];

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
        // 检索原租户
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['a.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
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
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $where[] = ['d.ban_inst_id','eq',$data['ban_inst_id']];
        }else{
            //检索管段
            $insts = config('inst_ids');
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
    public function dataFilter($data)
    {
        if(isset($data['file']) && $data['file']){
            $data['change_imgs'] = implode(',',$data['file']);
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
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 4; //暂停计租
        $data['change_order_number'] = date('Ym').'04'.random(14);
        $data['child_json'][] = [
            'step' => 1,
            'action' => '提交申请',
            'time' => date('Y-m-d H:i:s'),
            'uid' => ADMIN_ID,
            'img' => '',
        ];
        
            //$rentorderdates = explode(',',$data['rent_order_date']);
            $fields = 'a.rent_order_number,d.ban_owner_id,b.house_use_id,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.rent_order_date,d.ban_address,c.tenant_name';
            $data['data_json'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['a.house_id','eq',$data['house_id']],['a.rent_order_date','in',$data['rent_order_date']]])->select();
            //$data['data_json'] = RentModel::with(['tenant'])->where([['house_id','in',$houseids]])->field('house_number,tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent')->select()->toArray();
        

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
        return $row;
    }

    public function process($data)
    {
        // 判断是否通过
        $changeoffsetRow = self::get($data['id']);

        // 获取最后一步的step
        $processRoles = $this->processRole;
        $steps = array_keys($processRoles);
        $finalStep = array_pop($steps);
        // 获取审批动作
        $processActions = $this->processAction;
        // 获取审批描述
        $processDescs = $this->processDesc;

        $changeOffsetUpdateData = $processUpdateData = [];

        /*  如果是打回  */
        if(isset($data['back_reason'])){
            $changeOffsetUpdateData['change_status'] = 2;
            $changeOffsetUpdateData['is_back'] = 1;
            $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
            $changeOffsetUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[2].'，原因：'.$data['back_reason'],
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];

            // 更新使用权变更表
            $changeoffsetRow->allowField(['child_json','is_back','change_status'])->save($changeOffsetUpdateData, ['id' => $data['id']]);;
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
            $processUpdateData['curr_role'] = $processRoles[$changeOffsetUpdateData['change_status']];
        }else{
            /* 如果审批通过，且非终审：更新使用权变更表的child_json、change_status，更新审批表change_desc、curr_role */
            if(!isset($data['change_reason']) && ($changeoffsetRow['change_status'] < $finalStep)){
                $changeOffsetUpdateData['change_status'] = $changeoffsetRow['change_status'] + 1;
                $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
                $changeOffsetUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeOffsetUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                if(isset($data['file']) && $data['file']){
                    $changeOffsetUpdateData['change_imgs'] = implode(',',$data['file']);
                }
                // 更新使用权变更表
                $changeoffsetRow->allowField(['child_json','change_imgs','change_status'])->save($changeOffsetUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeOffsetUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新暂停计租表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            }else if(!isset($data['change_reason']) && ($changeoffsetRow['change_status'] == $finalStep)){

                $changeOffsetUpdateData['change_status'] = 1;
                $changeOffsetUpdateData['ftime'] = time();
                $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
                $changeOffsetUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeOffsetUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                //终审成功后的数据处理
                try{$this->finalDeal($changeoffsetRow);}catch(\Exception $e){return false;}
                // 更新暂停计租表
                $changeoffsetRow->allowField(['child_json','change_status','ftime'])->save($changeOffsetUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeOffsetUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新暂停计租的child_json、change_status，更新审批表change_desc、curr_role */
            }else if (isset($data['change_reason'])){
                $changeOffsetUpdateData['change_status'] = 0;
                $changeOffsetUpdateData['ftime'] = time();
                $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
                $changeOffsetUpdateData['child_json'][] = [
                    'success' => 0,
                    'action' => $processActions[$changeOffsetUpdateData['change_status']].'，原因：'.$data['change_reason'],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
                $changeoffsetRow->allowField(['child_json','change_status','ftime'])->save($changeOffsetUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeOffsetUpdateData['ftime'];
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
        // 将涉及的所有房屋，设置成暂停计租状态
        //HouseModel::where([['house_id','in',$finalRow['house_id']]])->update(['house_status'=>2]);
        
    }
}