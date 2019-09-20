<?php

namespace app\deal\model;

use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;

class ChangePause extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_pause';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回修改','初审通过','审批通过','终审通过'];

    protected $processDesc = ['失败','成功','待房管员打回修改','待经租会计初审','待经管所长审批','待经管科长终审'];

    protected $processRole = ['2'=>'4','3'=>6,'4'=>8,'5'=>9];

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
            // 我的工单
            case 'myorder':
              
                break;
            // 已受理工单
            case 'filished':
                
                break;
            default:
                # code...
                break;
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
        }
        //检索管段
        $insts = config('inst_ids');
        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        //$where[] = ['d.ban_inst_id','in',$insts[$instid]];
        
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
        $data['change_status'] = 3;
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 03; //使用权变更
        $data['change_order_number'] = date('Ym').'03'.random(14);
        $data['child_json'] = [];
        $data['child_json'][] = [
            'step' => 1,
            'action' => '提交申请',
            'time' => date('Y-m-d H:i:s'),
            'uid' => ADMIN_ID,
            'img' => '',
        ];
        if($data['house_id']){
            $houseids = explode(',',$data['house_id']);
            $data['data_line'] = HouseModel::with(['tenant'])->where([['house_id','in',$houseids]])->field('house_number,tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent')->select()->toArray();
        }

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
        $row['house_number'] = HouseModel::where([['house_id','eq',$row['house_id']]])->value('house_number');
        $oldTenantRow = TenantModel::where([['tenant_id','eq',$row['old_tenant_id']]])->field('tenant_number,tenant_card')->find();
        $row['old_tenant_number'] = $oldTenantRow['tenant_number'];
        $row['old_tenant_card'] = $oldTenantRow['tenant_card'];
        $newTenantRow = TenantModel::where([['tenant_id','eq',$row['new_tenant_id']]])->field('tenant_number,tenant_card')->find();
        $row['new_tenant_number'] = $newTenantRow['tenant_number'];
        $row['new_tenant_card'] = $newTenantRow['tenant_card'];
        //$process_config = ['失败','成功','待房管员处理','待经租会计处理','待经管所长处理','待经管科处理'];
        //halt($row);
        return $row;
    }

    public static function process($id)
    {
        
    }
}