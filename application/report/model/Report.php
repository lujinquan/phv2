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
     * 获取欠缴明细统计表数据
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-08 17:02:28
     * @return  [array] ['result'= 数据 ,'op'= 导出excel文件前缀查询条件]
     * @version 版本  1.0
     */
    public function getUnpaidRent()
    {
        $ownerid = input('param.owner_id'); //默认查询市属
        $instid = input('param.inst_id',INST); //默认查询当前机构
        $useid = input('param.use_id'); //默认查询住宅
        $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
        $tenant_name = trim(input('param.tenant_name')); //查询租户姓名

        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $where = [];
        $where[] = ['a.rent_order_date','<=',$month];
        $where[] = ['a.rent_order_status','eq',1];
        $where[] = ['a.is_deal','eq',1];
        if($useid != 0){
            $where[] = ['b.house_use_id','in',explode(',',$useid)];
        }
        if($ownerid != 0){
            $where[] = ['d.ban_owner_id','in',explode(',',$ownerid)];
        }
        if($tenant_name){
            $where[] = ['c.tenant_name','like','%'.$tenant_name.'%'];
        }
        //$where[] = ['a.rent_order_receive','>','a.rent_order_paid'];
        //$where[] = ['rent_order_receive','eq',rent_order_paid];
        $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaid,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
        $result = $data = [];
        $baseData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();
        //dump($where);halt($baseData);
        //dump($month);dump($separate);
        $total_cur_month_unpaid_rent = 0;
        $total_before_month_unpaid_rent = 0;
        $total_before_year_unpaid_rent = 0;
        foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);

            // if($b['rent_order_unpaid'] == 0){
            //     continue;
            // }
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

            //dump($month);dump($separate);dump($b['rent_order_date']);exit;
            if($b['rent_order_date'] == $month){ // 统计本月欠租
                $data[$b['house_id']]['curMonthUnpaidRent'] = $b['rent_order_unpaid'];
                $total_cur_month_unpaid_rent += $b['rent_order_unpaid'];
            }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                
                $data[$b['house_id']]['beforeMonthUnpaidRent'] += $b['rent_order_unpaid'];
                $total_before_month_unpaid_rent += $b['rent_order_unpaid'];
            }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                $data[$b['house_id']]['beforeYearUnpaidRent'] += $b['rent_order_unpaid'];
                $total_before_year_unpaid_rent += $b['rent_order_unpaid'];
            }
            //halt($data[$b['house_id']]);
            $data[$b['house_id']]['total'] += $b['rent_order_unpaid'];
            $data[$b['house_id']]['remark'] = '';
        }
        foreach ($data as $k => $d) {
            if($d['total'] == 0){
                unset($data[$k]);
            }
        }

        $result['data'] = $data;
        $result['total_cur_month_unpaid_rent'] = $total_cur_month_unpaid_rent;
        $result['total_before_month_unpaid_rent'] = $total_before_month_unpaid_rent;
        $result['total_before_year_unpaid_rent'] = $total_before_year_unpaid_rent;
        //$result['op'] = $params['insts'][$instid].'_'.$params['owners'][$ownerid].'_'.$params['uses'][$useid].'_';
        return $result;
    }

    /**
     * 
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-08 17:02:04
     * @return  返回值  
     * @version 版本  1.0
     */
    public function getPaidRent()
    {
        $ownerid = input('param.owner_id/d'); //默认查询市属
        $instid = input('param.inst_id/d',INST); //默认查询当前机构
        $useid = input('param.use_id/d'); //默认查询住宅
        $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
        $tenant_name = trim(input('param.tenant_name')); //查询租户姓名
        
        // $curMonth = '2021-05';

        $nextMonth = date('Y-m',strtotime('1 month'));
        // $nextMonth = '2021-06';

        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $where = [];
        //$where[] = ['a.rent_order_date','eq',$month];
        $where[] = ['a.rent_order_status','eq',1];
        if($useid != 0){
            $where[] = ['b.house_use_id','in',explode(',',$useid)];
        }
        if($ownerid != 0){
            $where[] = ['d.ban_owner_id','in',explode(',',$ownerid)];
        }
        if($tenant_name){
            $where[] = ['c.tenant_name','like','%'.$tenant_name.'%'];
        }
        $where[] = ['ptime','between',[strtotime($curMonth),strtotime($nextMonth)]];
        //$where[] = ['a.rent_order_receive','>','a.rent_order_paid'];
        //$where[] = ['rent_order_receive','eq',rent_order_paid];
        $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,a.ptime,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
        $result = $data = [];
        $baseData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();

        // $baseData11 = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('sum(rent_order_paid)')->where($where)->select();

        // halt(Db::name('rent_order_child')->getLastSql());

        $total_cur_month_paid_rent = 0;
        $total_before_month_paid_rent = 0;
        $total_before_year_paid_rent = 0;
        $aa = [];
        foreach($baseData as $b){ 
            /*if($b['rent_order_paid'] == 0){
                continue;
            }*/
            $data[$b['house_id']]['number'] = $b['house_number'];
            $data[$b['house_id']]['address'] = $b['ban_address'];
            $data[$b['house_id']]['tenant'] = $b['tenant_name'];
            $data[$b['house_id']]['use'] = $params['uses'][$b['house_use_id']];
            $data[$b['house_id']]['use_id'] = $b['house_use_id'];
            $data[$b['house_id']]['owner'] = $params['owners'][$b['ban_owner_id']];
            $data[$b['house_id']]['owner_id'] = $b['ban_owner_id'];
            $data[$b['house_id']]['inst'] = $params['insts'][$b['ban_inst_id']];
            $data[$b['house_id']]['inst_id'] = $b['ban_inst_id'];
            if(!isset($data[$b['house_id']]['total'])){
              $data[$b['house_id']]['total'] = 0;  
            }
            if(!isset($data[$b['house_id']]['curMonthPaidRent'])){
              $data[$b['house_id']]['curMonthPaidRent'] = 0;  
            }
            if(!isset($data[$b['house_id']]['beforeMonthPaidRent'])){
              $data[$b['house_id']]['beforeMonthPaidRent'] = 0;  
            }
            if(!isset($data[$b['house_id']]['beforeYearPaidRent'])){
              $data[$b['house_id']]['beforeYearPaidRent'] = 0;  
            }

            //dump($month);dump($separate);dump($b['rent_order_date']);exit;
            if($b['rent_order_date'] == $month){ // 统计本月欠租
                $data[$b['house_id']]['curMonthPaidRent'] = bcaddMerge([$data[$b['house_id']]['curMonthPaidRent'],$b['rent_order_paid']]);
                $total_cur_month_paid_rent = bcaddMerge([$total_cur_month_paid_rent,$b['rent_order_paid']]);
                $data[$b['house_id']]['total'] = bcaddMerge([$data[$b['house_id']]['total'], $b['rent_order_paid']]);
            }else if($b['rent_order_date'] >= $separate && $b['rent_order_date'] < $month ){ // 统计以前月欠租
                //dump($b['rent_order_date']);halt(date('Ym',$b['ptime']));
                $data[$b['house_id']]['beforeMonthPaidRent'] = bcaddMerge([$data[$b['house_id']]['beforeMonthPaidRent'], $b['rent_order_paid']]);
                $total_before_month_paid_rent  = bcaddMerge([$total_before_month_paid_rent, $b['rent_order_paid']]);
                $data[$b['house_id']]['total'] = bcaddMerge([$data[$b['house_id']]['total'], $b['rent_order_paid']]);
            }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                $data[$b['house_id']]['beforeYearPaidRent'] = bcaddMerge([$data[$b['house_id']]['beforeYearPaidRent'], $b['rent_order_paid']]);
                $total_before_year_paid_rent = bcaddMerge([$total_before_year_paid_rent, $b['rent_order_paid']]);
                $data[$b['house_id']]['total'] = bcaddMerge([$data[$b['house_id']]['total'], $b['rent_order_paid']]);
            }else{
                $aa[] = $b;
            }
            
            $data[$b['house_id']]['remark'] = '';
        }
        $sss = [];
        
        foreach ($data as $key => $value) {
            $sss[] = $value['curMonthPaidRent'];
            if($value['total'] == 0){
                unset($data[$key]);
            }
        }
 
        $result['data'] = $data;
        $result['total_cur_month_paid_rent'] = $total_cur_month_paid_rent;
        $result['total_before_month_paid_rent'] = $total_before_month_paid_rent;
        $result['total_before_year_paid_rent'] = $total_before_year_paid_rent;
        //$result['op'] = $params['insts'][$instid].'_'.$params['owners'][$ownerid].'_'.$params['uses'][$useid].'_';
        return $result;
    }

    /**
     * 预缴明细数据
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-08 17:02:04
     * @return  返回值  
     * @version 版本  1.0
     */
    public function getPrePaidRent($curMonth)
    {
        $nextMonth = date('Y-m', strtotime( "first day of next month" ) );
        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $tenant_name = trim(input('param.tenant_name')); //查询租户姓名

        // $ownerid = input('param.owner_id'); //默认查询市属
        // $instid = input('param.inst_id',INST); //默认查询当前机构
        // $useid = input('param.use_id'); //默认查询住宅
        // $params = ParamModel::getCparams();
        $where = [];
        // if($useid != 0){
        //     $where[] = ['b.house_use_id','in',explode(',',$useid)];
        // }
        // if($ownerid != 0){
        //     $where[] = ['d.ban_owner_id','in',explode(',',$ownerid)];
        // }
        // $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        if($tenant_name){
            $where[] = ['c.tenant_name','like','%'.$tenant_name.'%'];
        }
// halt($where);
        
        // halt(Db::name('house')->getLastSql());
        // 获取有余额的房屋信息
        $housesWithBalancesIDS = Db::name('house')->alias('b')->join('tenant c','b.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->where([['b.house_balance','>=',0]])->column('b.house_id');
        // 获取有余额的房屋id
        //$housesWithBalancesIDS = array_keys($housesWithBalances);
        //halt($where);
        $where[] = ['a.recharge_status','eq',1]; //充值成功状态
        $where[] = ['a.ptime','between',[strtotime($curMonth),strtotime($nextMonth)]];
        
        $fields = 'a.house_id,b.house_number,b.house_balance,sum(a.pay_rent) as pay_rent,a.ctime,b.house_use_id,b.house_pre_rent,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
        $result = $data = [];
        $baseData = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->group('house_id')->where($where)->select();

        $last_month = date('Ym',strtotime('- 1 month',strtotime($curMonth)));
        // halt($last_month);
        $tempData = @file_get_contents(ROOT_PATH.'file/report/prepaid/'.$last_month.'.txt');
        //halt($tempData);
        if($tempData){ // 有缓存就读取缓存数据
            $temps = json_decode($tempData,true);
            $last_month_data_housearr = array_keys($temps);
            //halt($last_month_data_housearr);
        }
        if($tenant_name){
            $houseids = $housesWithBalancesIDS;
        }else{
            $houseids = array_unique(array_merge($housesWithBalancesIDS,$last_month_data_housearr));
        }
        
        // halt($houseids);
        //halt(Db::name('rent_recharge')->getLastSql());
        $kouData = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->group('house_id')->where($where)->where(['pay_way'=>2])->column('a.house_id,sum(a.pay_rent) as pay_rent');

        $payData = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->group('house_id')->where($where)->where([['pay_way','neq',2]])->column('a.house_id,a.tenant_id,sum(a.pay_rent) as pay_rent');

        $payHouseIds = array_keys($payData);

        $houseids = array_unique(array_merge($houseids,$payHouseIds));

        $houses = Db::name('house')->alias('b')->join('tenant c','b.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where([['b.house_id','in',$houseids]])->column('b.house_id,b.house_number,b.house_use_id,b.house_balance,b.house_pre_rent,c.tenant_name,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id,d.ban_address');

        $tenants = Db::name('tenant')->column('tenant_id,tenant_name');
        // halt($houses);
        // 合计上期结转余额
        $total_last_yue = 0;
        // 合计本期预缴
        $total_pay_rent = 0;
        // 合计本月扣缴
        $total_kou_rent = 0;
        // 合计本月余额
        $total_yue = 0;
        foreach ($houseids as $b) {
            $pay_rent = isset($payData[$b])?$payData[$b]['pay_rent']:0;
            $kou_rent = isset($kouData[$b])?abs($kouData[$b]):0;
            $last_yue = isset($temps[$b]['house_balance'])?abs($temps[$b]['house_balance']):0;
            
            if($last_yue == 0 && $pay_rent == 0 && $kou_rent == 0){
                continue;
            }
            $data[$b]['last_yue'] = $last_yue;
            $data[$b]['kou_rent'] = $kou_rent;
            $data[$b]['house_pre_rent'] = $houses[$b]['house_pre_rent'];
            //$data[$b]['house_balance'] = $houses[$b]['house_balance'];
        // dump($last_yue);dump($pay_rent);halt($kou_rent);
            $data[$b]['house_balance'] = bcadd(bcsub($last_yue, $kou_rent ,2), $pay_rent ,2);
            $data[$b]['pay_rent'] = $pay_rent;
            $data[$b]['number'] = $houses[$b]['house_number'];
            $data[$b]['address'] = $houses[$b]['ban_address'];
            $data[$b]['tenant'] = isset($payData[$b]['tenant_id'])?$tenants[$payData[$b]['tenant_id']]:$houses[$b]['tenant_name'];
            $data[$b]['use_id'] = $houses[$b]['house_use_id'];
            $data[$b]['owner_id'] = $houses[$b]['ban_owner_id'];
            $data[$b]['inst_id'] = $houses[$b]['ban_inst_id'];
            $data[$b]['use'] = $params['uses'][$houses[$b]['house_use_id']];
            $data[$b]['owner'] = $params['owners'][$houses[$b]['ban_owner_id']];
            $data[$b]['inst'] = $params['insts'][$houses[$b]['ban_inst_id']];
            $data[$b]['remark'] = '';

            /*$data[$b['house_id']]['last_yue'] = isset($temps[$b['house_id']]['house_balance'])?abs($temps[$b['house_id']]['house_balance']):0;
            $data[$b['house_id']]['kou_rent'] = isset($kouData[$b['house_id']])?abs($kouData[$b['house_id']]):0;
            $data[$b['house_id']]['house_pre_rent'] = $b['house_pre_rent'];
            $data[$b['house_id']]['house_balance'] = $b['house_balance'];
            $data[$b['house_id']]['pay_rent'] = isset($payData[$b['house_id']])?abs($payData[$b['house_id']]):0;
            $data[$b['house_id']]['number'] = $b['house_number'];
            $data[$b['house_id']]['address'] = $b['ban_address'];
            $data[$b['house_id']]['tenant'] = $b['tenant_name'];
            $data[$b['house_id']]['use'] = $params['uses'][$b['house_use_id']];
            $data[$b['house_id']]['owner'] = $params['owners'][$b['ban_owner_id']];
            $data[$b['house_id']]['inst'] = $params['insts'][$b['ban_inst_id']];
            $data[$b['house_id']]['remark'] = '';*/
        }
        // halt($data);
        // foreach($baseData as $b){

        //     if (isset($payData[$b['house_id']]) && abs($payData[$b['house_id']]) > 0) {
        //         $data[$b['house_id']]['last_yue'] = 0;
        //         $data[$b['house_id']]['kou_rent'] = isset($kouData[$b['house_id']])?abs($kouData[$b['house_id']]):0;
        //         $data[$b['house_id']]['house_pre_rent'] = $b['house_pre_rent'];
        //         $data[$b['house_id']]['house_balance'] = $b['house_balance'];
        //         $data[$b['house_id']]['pay_rent'] = isset($payData[$b['house_id']])?abs($payData[$b['house_id']]):0;
        //         $data[$b['house_id']]['number'] = $b['house_number'];
        //         $data[$b['house_id']]['address'] = $b['ban_address'];
        //         $data[$b['house_id']]['tenant'] = $b['tenant_name'];
        //         $data[$b['house_id']]['use'] = $params['uses'][$b['house_use_id']];
        //         $data[$b['house_id']]['owner'] = $params['owners'][$b['ban_owner_id']];
        //         $data[$b['house_id']]['inst'] = $params['insts'][$b['ban_inst_id']];
        //         $data[$b['house_id']]['remark'] = '';
        //     }
            
        // }
        //$result['op'] = $params['insts'][$instid].'_'.$params['owners'][$ownerid].'_'.$params['uses'][$useid].'_';
        return $data;
    }
    

}