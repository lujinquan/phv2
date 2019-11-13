<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\common\model\Cparam as ParamModel;

class Report extends Model
{
	// 设置模型名称
    protected $name = 'report';

    /**
     * [getUnpaidRent 获取欠缴明细统计表数据]
     * @return [array] ['result'= 数据 ,'op'= 导出excel文件前缀查询条件]
     */
    public function getUnpaidRent()
    {
    	$ownerid = input('param.owner_id/d',1); //默认查询市属
        $instid = input('param.inst_id/d',INST); //默认查询当前机构
        
        $useid = input('param.use_id/d',1); //默认查询住宅
        $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $where = [];
        $where[] = ['a.rent_order_date','<=',$month];
        $where[] = ['a.is_deal','eq',1];
        if($useid != 0){
            $where[] = ['b.house_use_id','eq',$useid];
        }
        if($ownerid != 0){
            $where[] = ['d.ban_owner_id','eq',$ownerid];
        }
        //$where[] = ['a.rent_order_receive','exp',' = '.a.rent_order_paid];
        //$where[] = ['rent_order_receive','eq',rent_order_paid];
        $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaid,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
        $result = $data = [];
        $baseData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->limit(500)->select();
        //dump($where);halt($baseData);
        //dump($month);dump($separate);
        foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);


            if($b['rent_order_unpaid'] == 0){
                continue;
            }
            $data[$b['house_id']]['number'] = $b['house_number'];
            $data[$b['house_id']]['address'] = $b['ban_address'];
            $data[$b['house_id']]['tenant'] = $b['tenant_name'];
            $data[$b['house_id']]['use'] = $params['uses'][$b['house_use_id']];
            $data[$b['house_id']]['owner'] = $params['owners'][$b['ban_owner_id']];
            $data[$b['house_id']]['inst'] = $params['insts'][$b['ban_inst_id']];
            if(!isset($data[$b['house_id']]['total'])){
              $data[$b['house_id']]['total'] = 0;  
            }
            if(!isset($data[$b['house_id']]['curMonthUnpaidRent'])){
              $data[$b['house_id']]['curMonthUnpaidRent'] = 0;  
            }
            if(!isset($data[$b['house_id']]['beforeMonthUnpaidRent'])){
              $data[$b['house_id']]['beforeMonthUnpaidRent'] = 0;  
            }
            if(!isset($data[$b['house_id']]['beforeYearUnpaidRent'])){
              $data[$b['house_id']]['beforeYearUnpaidRent'] = 0;  
            }
            if($b['rent_order_date'] == $month){ // 统计本月欠租
                $data[$b['house_id']]['curMonthUnpaidRent'] = $b['rent_order_unpaid'];
            }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                $data[$b['house_id']]['beforeMonthUnpaidRent'] += $b['rent_order_unpaid'];

            }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                $data[$b['house_id']]['beforeYearUnpaidRent'] += $b['rent_order_unpaid'];
            }
            $data[$b['house_id']]['total'] += $b['rent_order_unpaid'];
            $data[$b['house_id']]['remark'] = '';
        }

        $result['data'] = $data;
        $result['op'] = $params['insts'][$instid].'_'.$params['owners'][$ownerid].'_'.$params['uses'][$useid].'_';
        return $result;
    }

    

}