<?php

namespace app\deal\model;

use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;

class ChangeCancel extends SystemBase
{
	// 设置模型名称
    protected $name = 'change_cancel';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
        'data_json' => 'json',
    ];

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
        $data['change_order_number'] = '113'.random(10,1);
        $data['child_json'] = [];
        $data['child_json'][] = [
            'step' => 1,
            'action' => '提交申请',
            'time' => date('Y-m-d H:i:s'),
            'uid' => ADMIN_ID,
            'img' => '',
        ];
        $data['cuid'] = ADMIN_ID;
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