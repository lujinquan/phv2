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
        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $where = [];
        $where[] = ['a.rent_order_date','<=',$month];
        $where[] = ['a.is_deal','eq',1];
        if($useid != 0){
            $where[] = ['b.house_use_id','in',explode(',',$useid)];
        }
        if($ownerid != 0){
            $where[] = ['d.ban_owner_id','in',explode(',',$ownerid)];
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
        
        //$curMonth = '2020-08';

        $nextMonth = date('Y-m',strtotime('1 month'));
        //halt($lastMonth);
        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $where = [];
        //$where[] = ['a.rent_order_date','eq',$month];
        //$where[] = ['a.is_deal','eq',1];
        if($useid != 0){
            $where[] = ['b.house_use_id','in',explode(',',$useid)];
        }
        if($ownerid != 0){
            $where[] = ['d.ban_owner_id','in',explode(',',$ownerid)];
        }
        $where[] = ['ptime','between',[strtotime($curMonth),strtotime($nextMonth)]];
        //$where[] = ['a.rent_order_receive','>','a.rent_order_paid'];
        //$where[] = ['rent_order_receive','eq',rent_order_paid];
        $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,a.ptime,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
        $result = $data = [];
        $baseData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();
        //halt($baseData);
//halt(Db::name('rent_order_child')->getLastSql());
        // $houses = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('a.house_id,a.house_number,c.tenant_name')->select();
        // foreach ($houses as $b) {
        //     $data[$b['house_id']]['number'] = $b['house_number'];
        // }
        //halt($baseData);
        //dump($where);halt($baseData);
        //dump($month);dump($separate);
        $total_cur_month_paid_rent = 0;
        $total_before_month_paid_rent = 0;
        $total_before_year_paid_rent = 0;
        foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);
//halt($b);

            if($b['rent_order_paid'] == 0){
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
                $data[$b['house_id']]['curMonthPaidRent'] = $b['rent_order_paid'];
                $total_cur_month_paid_rent += $b['rent_order_paid'];
            }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                
                $data[$b['house_id']]['beforeMonthPaidRent'] += $b['rent_order_paid'];
                $total_before_month_paid_rent += $b['rent_order_paid'];
            }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                $data[$b['house_id']]['beforeYearPaidRent'] += $b['rent_order_paid'];
                $total_before_year_paid_rent += $b['rent_order_paid'];
            }
            //halt($data[$b['house_id']]);
            $data[$b['house_id']]['total'] += $b['rent_order_paid'];
            $data[$b['house_id']]['remark'] = '';
        }

        $result['data'] = $data;
        $result['total_cur_month_paid_rent'] = $total_cur_month_paid_rent;
        $result['total_before_month_paid_rent'] = $total_before_month_paid_rent;
        $result['total_before_year_paid_rent'] = $total_before_year_paid_rent;
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
    public function getPrePaidRent()
    {
        $ownerid = input('param.owner_id/d'); //默认查询市属
        $instid = input('param.inst_id/d',INST); //默认查询当前机构
        $useid = input('param.use_id/d'); //默认查询住宅
        $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
        
        //$curMonth = '2020-08';

        $nextMonth = date('Y-m',strtotime('1 month'));
        //halt($lastMonth);
        $month = str_replace('-','',$curMonth);
        $params = ParamModel::getCparams();
        $separate = substr($month,0,4).'00';
        $where = [];
        //$where[] = ['a.rent_order_date','eq',$month];
        //$where[] = ['a.is_deal','eq',1];
        if($useid != 0){
            $where[] = ['b.house_use_id','in',explode(',',$useid)];
        }
        if($ownerid != 0){
            $where[] = ['d.ban_owner_id','in',explode(',',$ownerid)];
        }
        $where[] = ['ptime','between',[strtotime($curMonth),strtotime($nextMonth)]];
        //$where[] = ['a.rent_order_receive','>','a.rent_order_paid'];
        //$where[] = ['rent_order_receive','eq',rent_order_paid];
        $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,a.ptime,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
        $result = $data = [];
        $baseData = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();
        //halt($baseData);
//halt(Db::name('rent_order_child')->getLastSql());
        // $houses = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('a.house_id,a.house_number,c.tenant_name')->select();
        // foreach ($houses as $b) {
        //     $data[$b['house_id']]['number'] = $b['house_number'];
        // }
        //halt($baseData);
        //dump($where);halt($baseData);
        //dump($month);dump($separate);
        $total_cur_month_paid_rent = 0;
        $total_before_month_paid_rent = 0;
        $total_before_year_paid_rent = 0;
        foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);
//halt($b);

            if($b['rent_order_paid'] == 0){
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
                $data[$b['house_id']]['curMonthPaidRent'] = $b['rent_order_paid'];
                $total_cur_month_paid_rent += $b['rent_order_paid'];
            }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                
                $data[$b['house_id']]['beforeMonthPaidRent'] += $b['rent_order_paid'];
                $total_before_month_paid_rent += $b['rent_order_paid'];
            }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                $data[$b['house_id']]['beforeYearPaidRent'] += $b['rent_order_paid'];
                $total_before_year_paid_rent += $b['rent_order_paid'];
            }
            //halt($data[$b['house_id']]);
            $data[$b['house_id']]['total'] += $b['rent_order_paid'];
            $data[$b['house_id']]['remark'] = '';
        }

        $result['data'] = $data;
        $result['total_cur_month_paid_rent'] = $total_cur_month_paid_rent;
        $result['total_before_month_paid_rent'] = $total_before_month_paid_rent;
        $result['total_before_year_paid_rent'] = $total_before_year_paid_rent;
        //$result['op'] = $params['insts'][$instid].'_'.$params['owners'][$ownerid].'_'.$params['uses'][$useid].'_';
        return $result;
    }
    

}