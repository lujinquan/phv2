<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\rent\model\RentRecycle as RentRecycleModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\common\model\Cparam as ParamModel;

class MonthReport extends Model
{
    public function makeMonthReport($cacheDate){ // 202008
   
        $cacheYear = substr($cacheDate,0,4); // 2020

        $cacheYearFirstMonth = $cacheYear.'-01'; // 2020-01
        $cacheYearZeroMonth = $cacheYear.'00';
        // $arr5 = [substr($cacheDate,0,4) . '01', $cacheDate - 1]; // 201801~201804,包含201801和201804
        // $arr6 = [substr($cacheDate,0,4) . '01', $cacheDate]; // 201801~201805,包含201801和201805
        // $arr7 = substr($cacheDate,0,4); // 2018

        $cacheFullDate = substr_replace($cacheDate,'-',4,0); // 2020-08

        $cacheFullDateToTime = strtotime($cacheFullDate);
        $nextFullDate = date('Y-m',strtotime('+1 month',$cacheFullDateToTime)); // 2020-09
        $nextDate = str_replace('-', '', $nextFullDate); // 202009
        //dump($cacheDate);dump($cacheFullDate);dump($nextFullDate);halt($nextDate);
        //从往期欠租表中,获取当月收缴到的以前月的【以前月实收】租金
        $rentOldMonthData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.rent_order_paid) as pay_rents')->where([['a.ptime','between time',[$cacheFullDate,$nextFullDate]],['a.rent_order_date','>',$cacheYearZeroMonth],['a.rent_order_date','<',$cacheDate],['a.rent_order_status','eq',1]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //halt(Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.rent_order_paid) as pay_rents')->where([['a.ptime','between time',[$cacheFullDate,$nextFullDate]],['a.rent_order_date','>',$cacheYearZeroMonth],['a.rent_order_date','<',$cacheDate],['a.rent_order_status','eq',1]])->select());
        // $xx = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,a.rent_order_paid')->where([['a.ptime','between time',[$cacheFullDate,$nextFullDate]],['a.rent_order_date','between',[$cacheYearZeroMonth,$cacheDate]]])->fetchSql(true)->select();
        // halt($xx);
        //从往期欠租表中,获取今年【以前月实收累计】收缴到的以前月的租金
        $rentOldTotalMonthData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.rent_order_paid) as pay_rents')->where([['a.ptime','between time',[$cacheYearFirstMonth,$nextFullDate]],['a.rent_order_date','>',$cacheYearZeroMonth],['a.rent_order_date','<',$cacheDate],['a.rent_order_status','eq',1]])->where('a.rent_order_date != from_unixtime(a.ptime, \'%Y%m\')')->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();
//halt(Db::name('rent_order_child')->getLastSql());
        //从往期欠租表中,获取当月收缴到的【以前年度实收】的租金
        $rentOldYearData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.rent_order_paid) as pay_rents')->where([['a.ptime','between time',[$cacheFullDate,$nextFullDate]],['a.rent_order_date','<',$cacheYearZeroMonth],['a.rent_order_status','eq',1]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //从往期欠租表中，获取今年实收累计收缴到的【以前年度实收累计】的租金
        $rentOldTotalYearData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.rent_order_paid) as pay_rents')->where([['a.ptime','between time',[$cacheYearFirstMonth,$nextFullDate]],['a.rent_order_date','<',$cacheYearZeroMonth],['a.rent_order_status','eq',1]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();


        //从租金订单表中,获取规定、已缴、欠缴、应缴租金
        $rentData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,count(a.house_id) as house_ids,sum(a.rent_order_cut) as rent_order_cuts,sum(a.rent_order_receive) as rent_order_receives,sum(a.rent_order_paid) as rent_order_paids,sum(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaids,sum(a.rent_order_diff) as rent_order_diffs,sum(a.rent_order_pump) as rent_order_pumps')->where([['a.rent_order_date','eq',$cacheDate]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //从租金订单表中,欠缴租金
        $rentUnpaidData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaids')->where([['a.rent_order_date','eq',$cacheDate],['a.is_deal','eq',1]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();
        

        //从房屋表中分组获取年度欠租、租差
        $houseData = Db::name('house')->alias('b')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,count(b.house_id) as house_ids,sum(b.house_diff_rent) as house_diff_rents,sum(b.house_pump_rent) as house_pump_rents,sum(b.house_pre_rent) as house_pre_rents')->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')
        ->where([['b.house_status','eq',1]])
        ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,change_type')->group('use_id,owner_id,inst_id,change_type')
        ->where([['change_cancel_type','neq',1],['change_type','neq',1],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeGuanduanDecData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,inst_id')
        ->where([['change_type','eq',10],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeGuanduanIncData = Db::name('change_table')->field('use_id,owner_id,new_inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,new_inst_id')
        ->where([['change_type','eq',10],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();

        //获取非基数异动
        $changeNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents,change_type')->group('use_id,owner_id,inst_id,change_type')
        ->where([['order_date','<',$nextDate],['change_cancel_type','neq',1],['change_type','neq',1],['change_status','eq',1]])->where('(end_date > '.$cacheDate.' or end_date = 0)')->select();
//halt(Db::name('change_table')->getLastSql());
        //陈欠核销的是一个特别的非基数异动，以前年月必须是当月数据，不能同本月一样每个月继承
        $changeHeXiaoData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,inst_id')
        ->where([['order_date','eq',$cacheDate],['change_type','eq',4],['change_status','eq',1]])
        ->select();

        //房屋出售
        $changeSaleData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
        ->where([['order_date','eq',$cacheDate],['change_cancel_type','eq',1],['change_status','eq',1]])
        ->select();

        //政策减免
        $changeZhengceData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
        ->where([['end_date','gt',$cacheDate],['cut_type','eq',5],['change_status','eq',1]])
        ->select();

        //减免（除去政策减免）
        $changejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
        ->where([['end_date','gt',$cacheDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])
        ->select();

        //重组为规定格式的租金数据
        foreach($rentData as $k1 => $v1){
            $rentdata[$v1['ban_owner_id']][$v1['house_use_id']][$v1['ban_inst_id']] = [
                'house_ids' => $v1['house_ids'],
                'rent_order_cuts' => $v1['rent_order_cuts'],
                'rent_order_receives' => $v1['rent_order_receives'],
                'rent_order_paids' => $v1['rent_order_paids'],
                //'rent_order_unpaids' => $v1['rent_order_unpaids'],
                'rent_order_diffs' => $v1['rent_order_diffs'],
                'rent_order_pumps' => $v1['rent_order_pumps']
            ];
        }

        //重组为规定格式的租金数据
        foreach($rentUnpaidData as $k1 => $v1){
            $rentUnpaiddata[$v1['ban_owner_id']][$v1['house_use_id']][$v1['ban_inst_id']] = [
                'rent_order_unpaids' => $v1['rent_order_unpaids'],
            ];
        }

        //重组为规定格式的房屋数据
        foreach($houseData as $k2 => $v2){
            $housedata[$v2['ban_owner_id']][$v2['house_use_id']][$v2['ban_inst_id']] = [
                'house_ids' => $v2['house_ids'],
                'house_pre_rents' => $v2['house_pre_rents'],
                //'ArrearRents' => $v2['ArrearRents'],
                'house_diff_rents' => $v2['house_diff_rents'],
                'house_pump_rents' => $v2['house_pump_rents'],
            ];
        }

        //重组为规定格式的当月收到的以前月数据
        foreach($rentOldMonthData as $k3 => $v3){
            $rentOldMonthdata[$v3['ban_owner_id']][$v3['house_use_id']][$v3['ban_inst_id']] = [
                'pay_rents' => $v3['pay_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($rentOldTotalMonthData as $k5 => $v5){
            $rentOldTotalMonthdata[$v5['ban_owner_id']][$v5['house_use_id']][$v5['ban_inst_id']] = [
                'pay_rents' => $v5['pay_rents'],
            ];
        }

        //重组为规定格式的当月收到的以前年数据
        foreach($rentOldYearData as $k4 => $v4){
            $rentOldYeardata[$v4['ban_owner_id']][$v4['house_use_id']][$v4['ban_inst_id']] = [
                'pay_rents' => $v4['pay_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($rentOldTotalYearData as $k6 => $v6){
            $rentOldTotalYeardata[$v6['ban_owner_id']][$v6['house_use_id']][$v6['ban_inst_id']] = [
                'pay_rents' => $v6['pay_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据$changeNoBaseData
        foreach($changeData as $k7 => $v7){
            $changedata[$v7['owner_id']][$v7['use_id']][$v7['inst_id']][$v7['change_type']] = [
                'change_rents' => $v7['change_rents'],
                'change_month_rents' => $v7['change_month_rents'],
                'change_year_rents' => $v7['change_year_rents'],
            ];
        }

        foreach($changeGuanduanDecData as $k12 => $v12){
            $changeGuanduanDecdata[$v12['owner_id']][$v12['use_id']][$v12['inst_id']] = [
                'change_rents' => $v12['change_rents'],
                'change_month_rents' => $v12['change_month_rents'],
                'change_year_rents' => $v12['change_year_rents'],
            ];
        }

        foreach($changeGuanduanIncData as $k13 => $v13){
            $changeGuanduanIncdata[$v13['owner_id']][$v13['use_id']][$v13['new_inst_id']] = [
                'change_rents' => $v13['change_rents'],
                'change_month_rents' => $v13['change_month_rents'],
                'change_year_rents' => $v13['change_year_rents'],
            ];
        }

        foreach($changeNoBaseData as $k11 => $v11){
            $changeNoBasedata[$v11['owner_id']][$v11['use_id']][$v11['inst_id']][$v11['change_type']] = [
                'change_rents' => $v11['change_rents'],
                'change_month_rents' => $v11['change_month_rents'],
                'change_year_rents' => $v11['change_year_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($changeHeXiaoData as $k14 => $v14){
            $changeHeXiaodata[$v14['owner_id']][$v14['use_id']][$v14['inst_id']] = [
                'change_rents' => $v14['change_rents'],
                'change_month_rents' => $v14['change_month_rents'],
                'change_year_rents' => $v14['change_year_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($changeSaleData as $k8 => $v8){
            $changeSaledata[$v8['owner_id']][$v8['use_id']][$v8['inst_id']] = [
                'change_rents' => $v8['change_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($changeZhengceData as $k9 => $v9){
            $changeZhengcedata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }

        //重组为规定格式的
        foreach($changejianmianData as $k9 => $v9){
            $changejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }

        //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                for ($j=4;$j<34;$j++) {
                    if(!isset($rentdata[$owner][$i][$j])){
                        $rentdata[$owner][$i][$j] = [
                            'house_ids' => 0,
                            'rent_order_cuts' => 0,
                            'rent_order_receives' => 0,
                            'rent_order_paids' => 0,
                            //'rent_order_unpaids' => 0,
                            'rent_order_diffs' => 0,
                            'rent_order_pumps' => 0,
                        ];
                    }
                    if(!isset($rentUnpaiddata[$owner][$i][$j])){
                        $rentUnpaiddata[$owner][$i][$j] = [
                            'rent_order_unpaids' => 0,
                        ];
                    }
                    if(!isset($housedata[$owner][$i][$j])){
                        $housedata[$owner][$i][$j] = [
                            'house_ids' => 0,
                            'house_pre_rents' => 0,
                            //'ArrearRents' => 0,
                            'house_diff_rents' => 0,
                            'house_pump_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldMonthdata[$owner][$i][$j])){
                        $rentOldMonthdata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldTotalMonthdata[$owner][$i][$j])){
                        $rentOldTotalMonthdata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldYeardata[$owner][$i][$j])){
                        $rentOldYeardata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldTotalYeardata[$owner][$i][$j])){
                        $rentOldTotalYeardata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }

                    for($k=1;$k<16;$k++){
                        if(!isset($changedata[$owner][$i][$j][$k])){
                            $changedata[$owner][$i][$j][$k] = [ 
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                            ];
                        }

                    }

                    for($z=0;$z<13;$z++){
                        if(!isset($changeNoBasedata[$owner][$i][$j][$z])){
                            $changeNoBasedata[$owner][$i][$j][$z] = [ 
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                            ];
                        }

                    }
                    if(!isset($changeGuanduanDecdata[$owner][$i][$j])){
                        $changeGuanduanDecdata[$owner][$i][$j] = [
                            'change_rents' => 0,
                            'change_month_rents' => 0,
                            'change_year_rents' => 0,
                        ];
                    }
                    if(!isset($changeGuanduanIncdata[$owner][$i][$j])){
                        $changeGuanduanIncdata[$owner][$i][$j] = [
                            'change_rents' => 0,
                            'change_month_rents' => 0,
                            'change_year_rents' => 0,
                        ];
                    }
                    if(!isset($changeHeXiaodata[$owner][$i][$j])){
                        $changeHeXiaodata[$owner][$i][$j] = [
                            'change_rents' => 0,
                            'change_month_rents' => 0,
                            'change_year_rents' => 0,
                        ];
                    }
                    if(!isset($changeSaledata[$owner][$i][$j])){
                        $changeSaledata[$owner][$i][$j] = [
                            'change_rents' => 0,
                        ];
                    }
                    if(!isset($changeZhengcedata[$owner][$i][$j])){
                        $changeZhengcedata[$owner][$i][$j] = [
                            'change_rents' => 0,
                        ];
                    }
                    if(!isset($changejianmiandata[$owner][$i][$j])){
                        $changejianmiandata[$owner][$i][$j] = [
                            'change_rents' => 0,
                        ];
                    }
                    
                }
            }
        }
        $cachedate = date('Ym',strtotime($cacheDate . '01 -1 month'));

        //$reportolddata = Db::name('report')->where([['type','eq','RentReport'],['date','eq',$cachedate]])->value('data');
        $reportolddata = @file_get_contents(ROOT_PATH.'file/report/rent/'.$cachedate.'.txt');

        $datas = json_decode($reportolddata,true);
        $lastMonthData = isset($datas)?$datas:array();
        //halt($lastMonthData);
        //如果缓存的是某年的第一个月的数据
        if(substr($cacheDate,4,2) == '01' ){
            //$lastMonthData = array();
        }
        // $firstMonth = substr($cacheDate,0,4).'01';
        // $lastcacheDate = $cacheDate - 1;
        // if($lastcacheDate >= $firstMonth){ //如果缓存的程序是2月份或2月份以上
        //     for($s = $firstMonth ; $s <= $lastcacheDate; $s++){
        //         $temps[] = json_decode(Cache::store('file')->get('RentReport'.($s),''),true);
        //     }
        // }
        //halt($temps);

        //第一步：处理市、区、代、自、托的每一个管段的数据
        foreach ($ownertypes as $owners) { //处理市、区、代、自、托
            for ($j = 4; $j < 34; $j++) { //每个管段，从4开始……

                if(substr($cacheDate,4,2) != '01'){
                    $result[$owners][$j][0][1] = $lastMonthData[$owners][$j][8][1];
                    $result[$owners][$j][0][2] = $lastMonthData[$owners][$j][20][1] + $lastMonthData[$owners][$j][20][2];
                    $result[$owners][$j][0][3] = $lastMonthData[$owners][$j][20][3];
                    $result[$owners][$j][0][4] = 0.4 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][5] = 0.4 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][6] = 0.4 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][7] = 0.6 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][8] = 0.6 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][9] = 0.6 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][10] = $lastMonthData[$owners][$j][8][10];
                    $result[$owners][$j][0][11] = $lastMonthData[$owners][$j][20][10] + $lastMonthData[$owners][$j][20][11];//$lastMonthData[$owners][$j][20][10];
                    $result[$owners][$j][0][12] = $lastMonthData[$owners][$j][20][12];
                    $result[$owners][$j][0][13] = $lastMonthData[$owners][$j][8][13];
                    $result[$owners][$j][0][14] = $lastMonthData[$owners][$j][20][13] + $lastMonthData[$owners][$j][20][14];//$lastMonthData[$owners][$j][20][13];
                    $result[$owners][$j][0][15] = $lastMonthData[$owners][$j][20][15];
                    array_unshift($result[$owners][$j][0],array_sum($result[$owners][$j][0]) - $result[$owners][$j][0][1] - $result[$owners][$j][0][2] - $result[$owners][$j][0][3]);
                }else{
                    $result[$owners][$j][0][1] = $lastMonthData[$owners][$j][8][1];
                    $result[$owners][$j][0][2] = 0;
                    $result[$owners][$j][0][3] = $lastMonthData[$owners][$j][20][1] + $lastMonthData[$owners][$j][20][2] + $lastMonthData[$owners][$j][20][3];
                    $result[$owners][$j][0][4] = 0.4 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][5] = 0.4 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][6] = 0.4 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][7] = 0.6 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][8] = 0.6 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][9] = 0.6 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][10] = $lastMonthData[$owners][$j][8][10];
                    $result[$owners][$j][0][11] = 0;//$lastMonthData[$owners][$j][20][10];
                    $result[$owners][$j][0][12] = $lastMonthData[$owners][$j][20][10] + $lastMonthData[$owners][$j][20][11] + $lastMonthData[$owners][$j][20][12];
                    $result[$owners][$j][0][13] = $lastMonthData[$owners][$j][8][13];
                    $result[$owners][$j][0][14] = 0;//$lastMonthData[$owners][$j][20][13];
                    $result[$owners][$j][0][15] = $lastMonthData[$owners][$j][20][13] + $lastMonthData[$owners][$j][20][14] + $lastMonthData[$owners][$j][20][15];
                    array_unshift($result[$owners][$j][0],array_sum($result[$owners][$j][0]) - $result[$owners][$j][0][1] - $result[$owners][$j][0][2] - $result[$owners][$j][0][3]);
                }
                
                
                //新发租异动ChangeType = 7
                $result[$owners][$j][2][1] = $changedata[$owners][2][$j][7]['change_rents'];
                $result[$owners][$j][2][2] = 0;
                $result[$owners][$j][2][3] = 0;
                $result[$owners][$j][2][4] = 0.4 * $result[$owners][$j][2][1];
                $result[$owners][$j][2][5] = 0.4 * $result[$owners][$j][2][2];
                $result[$owners][$j][2][6] = 0.4 * $result[$owners][$j][2][3];
                $result[$owners][$j][2][7] = 0.6 * $result[$owners][$j][2][1];
                $result[$owners][$j][2][8] = 0.6 * $result[$owners][$j][2][2];
                $result[$owners][$j][2][9] = 0.6 * $result[$owners][$j][2][3];
                $result[$owners][$j][2][10] = $changedata[$owners][3][$j][7]['change_rents'];
                $result[$owners][$j][2][11] = 0;
                $result[$owners][$j][2][12] = 0;
                $result[$owners][$j][2][13] = $changedata[$owners][1][$j][7]['change_rents'];
                $result[$owners][$j][2][14] = 0;
                $result[$owners][$j][2][15] = 0;
                array_unshift($result[$owners][$j][2],array_sum($result[$owners][$j][2]) - $result[$owners][$j][2][1] - $result[$owners][$j][2][2] - $result[$owners][$j][2][3]);

                //调整ChangeType = 3
                $result[$owners][$j][3][1] = $changedata[$owners][2][$j][12]['change_rents'];
                $result[$owners][$j][3][2] = $changedata[$owners][2][$j][12]['change_month_rents'];
                $result[$owners][$j][3][3] = $changedata[$owners][2][$j][12]['change_year_rents'];
                $result[$owners][$j][3][4] = 0.4 * $result[$owners][$j][3][1];
                $result[$owners][$j][3][5] = 0.4 * $result[$owners][$j][3][2];
                $result[$owners][$j][3][6] = 0.4 * $result[$owners][$j][3][3];
                $result[$owners][$j][3][7] = 0.6 * $result[$owners][$j][3][1];
                $result[$owners][$j][3][8] = 0.6 * $result[$owners][$j][3][2];
                $result[$owners][$j][3][9] = 0.6 * $result[$owners][$j][3][3];
                $result[$owners][$j][3][10] = $changedata[$owners][3][$j][12]['change_rents'];
                $result[$owners][$j][3][11] = $changedata[$owners][3][$j][12]['change_month_rents'];
                $result[$owners][$j][3][12] = $changedata[$owners][3][$j][12]['change_year_rents'];
                $result[$owners][$j][3][13] = $changedata[$owners][1][$j][12]['change_rents'];
                $result[$owners][$j][3][14] = $changedata[$owners][1][$j][12]['change_month_rents'];
                $result[$owners][$j][3][15] = $changedata[$owners][1][$j][12]['change_year_rents'];
                array_unshift($result[$owners][$j][3],array_sum($result[$owners][$j][3]) - $result[$owners][$j][3][1] - $result[$owners][$j][3][2] - $result[$owners][$j][3][3]);

                //注销异动ChangeType = 8
                $result[$owners][$j][4][1] = $changedata[$owners][2][$j][8]['change_rents'];
                $result[$owners][$j][4][2] = 0;
                $result[$owners][$j][4][3] = 0;
                $result[$owners][$j][4][4] = 0.4 * $result[$owners][$j][4][1];
                $result[$owners][$j][4][5] = 0.4 * $result[$owners][$j][4][2];
                $result[$owners][$j][4][6] = 0.4 * $result[$owners][$j][4][3];
                $result[$owners][$j][4][7] = 0.6 * $result[$owners][$j][4][1];
                $result[$owners][$j][4][8] = 0.6 * $result[$owners][$j][4][2];
                $result[$owners][$j][4][9] = 0.6 * $result[$owners][$j][4][3];
                $result[$owners][$j][4][10] = $changedata[$owners][3][$j][8]['change_rents'];
                $result[$owners][$j][4][11] = 0;
                $result[$owners][$j][4][12] = 0;
                $result[$owners][$j][4][13] = $changedata[$owners][1][$j][8]['change_rents'];
                $result[$owners][$j][4][14] = 0;
                $result[$owners][$j][4][15] = 0;
                array_unshift($result[$owners][$j][4],array_sum($result[$owners][$j][4]) - $result[$owners][$j][4][1] - $result[$owners][$j][4][2] - $result[$owners][$j][4][3]);

                //租差
                $result[$owners][$j][5][1] = $changedata[$owners][2][$j][15]['change_rents'];
                $result[$owners][$j][5][2] = 0;
                $result[$owners][$j][5][3] = 0;
                $result[$owners][$j][5][4] = 0.4 * $result[$owners][$j][5][1];
                $result[$owners][$j][5][5] = 0.4 * $result[$owners][$j][5][2];
                $result[$owners][$j][5][6] = 0.4 * $result[$owners][$j][5][3];
                $result[$owners][$j][5][7] = 0.6 * $result[$owners][$j][5][1];
                $result[$owners][$j][5][8] = 0.6 * $result[$owners][$j][5][2];
                $result[$owners][$j][5][9] = 0.6 * $result[$owners][$j][5][3];
                $result[$owners][$j][5][10] = $changedata[$owners][3][$j][15]['change_rents'];
                $result[$owners][$j][5][11] = 0;
                $result[$owners][$j][5][12] = 0;
                $result[$owners][$j][5][13] = $changedata[$owners][1][$j][15]['change_rents'];
                $result[$owners][$j][5][14] = 0;
                $result[$owners][$j][5][15] = 0;
                array_unshift($result[$owners][$j][5],array_sum($result[$owners][$j][5]) - $result[$owners][$j][5][1] - $result[$owners][$j][5][2] - $result[$owners][$j][5][3]);



                //公房出售 = 出售 + 房改
                $result[$owners][$j][6][1] = $changeSaledata[$owners][2][$j]['change_rents'] + $changedata[$owners][2][$j][5]['change_rents'];
                $result[$owners][$j][6][2] = 0;
                $result[$owners][$j][6][3] = 0;
                $result[$owners][$j][6][4] = 0.4 * $result[$owners][$j][6][1];
                $result[$owners][$j][6][5] = 0.4 * $result[$owners][$j][6][2];
                $result[$owners][$j][6][6] = 0.4 * $result[$owners][$j][6][3];
                $result[$owners][$j][6][7] = 0.6 * $result[$owners][$j][6][1];
                $result[$owners][$j][6][8] = 0.6 * $result[$owners][$j][6][2];
                $result[$owners][$j][6][9] = 0.6 * $result[$owners][$j][6][3];
                $result[$owners][$j][6][10] = $changeSaledata[$owners][3][$j]['change_rents'] + $changedata[$owners][3][$j][5]['change_rents'];
                $result[$owners][$j][6][11] = 0;
                $result[$owners][$j][6][12] = 0;
                $result[$owners][$j][6][13] = $changeSaledata[$owners][1][$j]['change_rents'] + $changedata[$owners][1][$j][5]['change_rents'];
                $result[$owners][$j][6][14] = 0;
                $result[$owners][$j][6][15] = 0;
                array_unshift($result[$owners][$j][6],array_sum($result[$owners][$j][6]) - $result[$owners][$j][6][1] - $result[$owners][$j][6][2] - $result[$owners][$j][6][3]);


                //管段调整 = 增加的 - 减少的
                $result[$owners][$j][7][1] = $changeGuanduanIncdata[$owners][2][$j]['change_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_rents'];
                $result[$owners][$j][7][2] = $changeGuanduanIncdata[$owners][2][$j]['change_month_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_month_rents'];
                $result[$owners][$j][7][3] = $changeGuanduanIncdata[$owners][2][$j]['change_year_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_year_rents'];
                $result[$owners][$j][7][4] = 0.4 * $result[$owners][$j][7][1];
                $result[$owners][$j][7][5] = 0.4 * $result[$owners][$j][7][2];
                $result[$owners][$j][7][6] = 0.4 * $result[$owners][$j][7][3];
                $result[$owners][$j][7][7] = 0.6 * $result[$owners][$j][7][1];
                $result[$owners][$j][7][8] = 0.6 * $result[$owners][$j][7][2];
                $result[$owners][$j][7][9] = 0.6 * $result[$owners][$j][7][3];
                $result[$owners][$j][7][10] = $changeGuanduanIncdata[$owners][3][$j]['change_rents'] - $changeGuanduanDecdata[$owners][3][$j]['change_rents'];
                $result[$owners][$j][7][11] = $changeGuanduanIncdata[$owners][3][$j]['change_month_rents'] - $changeGuanduanDecdata[$owners][3][$j]['change_month_rents'];
                $result[$owners][$j][7][12] = $changeGuanduanIncdata[$owners][3][$j]['change_year_rents'] - $changeGuanduanDecdata[$owners][3][$j]['change_year_rents'];
                $result[$owners][$j][7][13] = $changeGuanduanIncdata[$owners][1][$j]['change_rents'] - $changeGuanduanDecdata[$owners][1][$j]['change_rents'];
                $result[$owners][$j][7][14] = $changeGuanduanIncdata[$owners][1][$j]['change_month_rents'] - $changeGuanduanDecdata[$owners][1][$j]['change_month_rents'];
                $result[$owners][$j][7][15] = $changeGuanduanIncdata[$owners][1][$j]['change_year_rents'] - $changeGuanduanDecdata[$owners][1][$j]['change_year_rents'];
                array_unshift($result[$owners][$j][7],array_sum($result[$owners][$j][7]) - $result[$owners][$j][7][1] - $result[$owners][$j][7][2] - $result[$owners][$j][7][3]);


                //基数异动增减合计
                $result[$owners][$j][1][1] = $result[$owners][$j][2][1] + $result[$owners][$j][3][1] - $result[$owners][$j][4][1] + $result[$owners][$j][5][1] - $result[$owners][$j][6][1] + $result[$owners][$j][7][1];
                $result[$owners][$j][1][2] = $result[$owners][$j][2][2] + $result[$owners][$j][3][2] - $result[$owners][$j][4][2] + $result[$owners][$j][5][2] - $result[$owners][$j][6][2] + $result[$owners][$j][7][2];
                $result[$owners][$j][1][3] = $result[$owners][$j][2][3] + $result[$owners][$j][3][3] - $result[$owners][$j][4][3] + $result[$owners][$j][5][3] - $result[$owners][$j][6][3] + $result[$owners][$j][7][3];
                $result[$owners][$j][1][4] = 0.4 * $result[$owners][$j][1][1];
                $result[$owners][$j][1][5] = 0.4 * $result[$owners][$j][1][2];
                $result[$owners][$j][1][6] = 0.4 * $result[$owners][$j][1][3];
                $result[$owners][$j][1][7] = 0.6 * $result[$owners][$j][1][1];
                $result[$owners][$j][1][8] = 0.6 * $result[$owners][$j][1][2];
                $result[$owners][$j][1][9] = 0.6 * $result[$owners][$j][1][3];
                $result[$owners][$j][1][10] = $result[$owners][$j][2][10] + $result[$owners][$j][3][10] - $result[$owners][$j][4][10] + $result[$owners][$j][5][10] - $result[$owners][$j][6][10] + $result[$owners][$j][7][10];
                $result[$owners][$j][1][11] = $result[$owners][$j][2][11] + $result[$owners][$j][3][11] - $result[$owners][$j][4][11] + $result[$owners][$j][5][11] - $result[$owners][$j][6][11] + $result[$owners][$j][7][11];
                $result[$owners][$j][1][12] = $result[$owners][$j][2][12] + $result[$owners][$j][3][12] - $result[$owners][$j][4][12] + $result[$owners][$j][5][12] - $result[$owners][$j][6][12] + $result[$owners][$j][7][12];
                $result[$owners][$j][1][13] = $result[$owners][$j][2][13] + $result[$owners][$j][3][13] - $result[$owners][$j][4][13] + $result[$owners][$j][5][13] - $result[$owners][$j][6][13] + $result[$owners][$j][7][13];
                $result[$owners][$j][1][14] = $result[$owners][$j][2][14] + $result[$owners][$j][3][14] - $result[$owners][$j][4][14] + $result[$owners][$j][5][14] - $result[$owners][$j][6][14] + $result[$owners][$j][7][14];
                $result[$owners][$j][1][15] = $result[$owners][$j][2][15] + $result[$owners][$j][3][15] - $result[$owners][$j][4][15] + $result[$owners][$j][5][15] - $result[$owners][$j][6][15] + $result[$owners][$j][7][15];
                array_unshift($result[$owners][$j][1],array_sum($result[$owners][$j][1]) - $result[$owners][$j][1][1] - $result[$owners][$j][1][2] - $result[$owners][$j][1][3]);

                //规定租金那一行 = 上期结转 + 基数异动合计
                $result[$owners][$j][8][1] = $result[$owners][$j][0][1] + $result[$owners][$j][1][1];
                $result[$owners][$j][8][2] = $result[$owners][$j][0][2] + $result[$owners][$j][1][2];
                $result[$owners][$j][8][3] = $result[$owners][$j][0][3] + $result[$owners][$j][1][3];
                $result[$owners][$j][8][4] = 0.4 * $result[$owners][$j][8][1];
                $result[$owners][$j][8][5] = 0.4 * $result[$owners][$j][8][2];
                $result[$owners][$j][8][6] = 0.4 * $result[$owners][$j][8][3];
                $result[$owners][$j][8][7] = 0.6 * $result[$owners][$j][8][1];
                $result[$owners][$j][8][8] = 0.6 * $result[$owners][$j][8][2];
                $result[$owners][$j][8][9] = 0.6 * $result[$owners][$j][8][3];
                $result[$owners][$j][8][10] = $result[$owners][$j][0][10] + $result[$owners][$j][1][10];
                $result[$owners][$j][8][11] = $result[$owners][$j][0][11] + $result[$owners][$j][1][11];
                $result[$owners][$j][8][12] = $result[$owners][$j][0][12] + $result[$owners][$j][1][12];
                $result[$owners][$j][8][13] = $result[$owners][$j][0][13] + $result[$owners][$j][1][13];
                $result[$owners][$j][8][14] = $result[$owners][$j][0][14] + $result[$owners][$j][1][14];
                $result[$owners][$j][8][15] = $result[$owners][$j][0][15] + $result[$owners][$j][1][15];
                array_unshift($result[$owners][$j][8],array_sum($result[$owners][$j][8]) - $result[$owners][$j][8][1] - $result[$owners][$j][8][2] - $result[$owners][$j][8][3]);

                //规定租金那一行 = 上期结转 + 基数异动合计
                $result[$owners][$j][30][1] = $housedata[$owners][2][$j]['house_diff_rents'];
                $result[$owners][$j][30][2] = 0;
                $result[$owners][$j][30][3] = 0;
                $result[$owners][$j][30][4] = 0;
                $result[$owners][$j][30][5] = 0;
                $result[$owners][$j][30][6] = 0;
                $result[$owners][$j][30][7] = 0;
                $result[$owners][$j][30][8] = 0;
                $result[$owners][$j][30][9] = 0;
                $result[$owners][$j][30][10] = $housedata[$owners][3][$j]['house_diff_rents'];
                $result[$owners][$j][30][11] = 0;
                $result[$owners][$j][30][12] = 0;
                $result[$owners][$j][30][13] = $housedata[$owners][1][$j]['house_diff_rents'];
                $result[$owners][$j][30][14] = 0;
                $result[$owners][$j][30][15] = 0;
                array_unshift($result[$owners][$j][30],$housedata[$owners][2][$j]['house_diff_rents'] + $housedata[$owners][3][$j]['house_diff_rents'] + $housedata[$owners][1][$j]['house_diff_rents']);

                //规定租金那一行 = 上期结转 + 基数异动合计
                $result[$owners][$j][31][1] = $housedata[$owners][2][$j]['house_pump_rents'];
                $result[$owners][$j][31][2] = 0;
                $result[$owners][$j][31][3] = 0;
                $result[$owners][$j][31][4] = 0;
                $result[$owners][$j][31][5] = 0;
                $result[$owners][$j][31][6] = 0;
                $result[$owners][$j][31][7] = 0;
                $result[$owners][$j][31][8] = 0;
                $result[$owners][$j][31][9] = 0;
                $result[$owners][$j][31][10] = $housedata[$owners][3][$j]['house_pump_rents'];
                $result[$owners][$j][31][11] = 0;
                $result[$owners][$j][31][12] = 0;
                $result[$owners][$j][31][13] = $housedata[$owners][1][$j]['house_pump_rents'];
                $result[$owners][$j][31][14] = 0;
                $result[$owners][$j][31][15] = 0;
                array_unshift($result[$owners][$j][31],$housedata[$owners][2][$j]['house_pump_rents'] + $housedata[$owners][3][$j]['house_pump_rents'] + $housedata[$owners][1][$j]['house_pump_rents']);


                //减免，取得是异动里面的减免金额
                $result[$owners][$j][10][1] = $changejianmiandata[$owners][2][$j]['change_rents'];
                $result[$owners][$j][10][2] = 0;
                $result[$owners][$j][10][3] = 0;
                $result[$owners][$j][10][4] = 0.4 * $result[$owners][$j][10][1];
                $result[$owners][$j][10][5] = 0.4 * $result[$owners][$j][10][2];
                $result[$owners][$j][10][6] = 0.4 * $result[$owners][$j][10][3];
                $result[$owners][$j][10][7] = 0.6 * $result[$owners][$j][10][1];
                $result[$owners][$j][10][8] = 0.6 * $result[$owners][$j][10][2];
                $result[$owners][$j][10][9] = 0.6 * $result[$owners][$j][10][3];
                $result[$owners][$j][10][10] = $changejianmiandata[$owners][3][$j]['change_rents'];
                $result[$owners][$j][10][11] = 0;
                $result[$owners][$j][10][12] = 0;
                $result[$owners][$j][10][13] = $changejianmiandata[$owners][1][$j]['change_rents'];
                $result[$owners][$j][10][14] = 0;
                $result[$owners][$j][10][15] = 0;
                array_unshift($result[$owners][$j][10],array_sum($result[$owners][$j][10]) - $result[$owners][$j][10][1] - $result[$owners][$j][10][2] - $result[$owners][$j][10][3]);

                //空租异动ChangeType = 2
                $result[$owners][$j][11][1] = $changeNoBasedata[$owners][2][$j][2]['change_rents'];
                $result[$owners][$j][11][2] = 0;
                $result[$owners][$j][11][3] = 0;
                $result[$owners][$j][11][4] = 0.4 * $result[$owners][$j][11][1];
                $result[$owners][$j][11][5] = 0.4 * $result[$owners][$j][11][2];
                $result[$owners][$j][11][6] = 0.4 * $result[$owners][$j][11][3];
                $result[$owners][$j][11][7] = 0.6 * $result[$owners][$j][11][1];
                $result[$owners][$j][11][8] = 0.6 * $result[$owners][$j][11][2];
                $result[$owners][$j][11][9] = 0.6 * $result[$owners][$j][11][3];
                $result[$owners][$j][11][10] = $changeNoBasedata[$owners][3][$j][2]['change_rents'];
                $result[$owners][$j][11][11] = 0;
                $result[$owners][$j][11][12] = 0;
                $result[$owners][$j][11][13] = $changeNoBasedata[$owners][1][$j][2]['change_rents'];
                $result[$owners][$j][11][14] = 0;
                $result[$owners][$j][11][15] = 0;
                array_unshift($result[$owners][$j][11],array_sum($result[$owners][$j][11]) - $result[$owners][$j][11][1] - $result[$owners][$j][11][2] - $result[$owners][$j][11][3]);

                //暂停计租异动ChangeType = 3
                $result[$owners][$j][12][1] = $changeNoBasedata[$owners][2][$j][3]['change_rents'];
                $result[$owners][$j][12][2] = 0;
                $result[$owners][$j][12][3] = 0;
                $result[$owners][$j][12][4] = 0.4 * $result[$owners][$j][12][1];
                $result[$owners][$j][12][5] = 0.4 * $result[$owners][$j][12][2];
                $result[$owners][$j][12][6] = 0.4 * $result[$owners][$j][12][3];
                $result[$owners][$j][12][7] = 0.6 * $result[$owners][$j][12][1];
                $result[$owners][$j][12][8] = 0.6 * $result[$owners][$j][12][2];
                $result[$owners][$j][12][9] = 0.6 * $result[$owners][$j][12][3];
                $result[$owners][$j][12][10] = $changeNoBasedata[$owners][3][$j][3]['change_rents'];
                $result[$owners][$j][12][11] = 0;
                $result[$owners][$j][12][12] = 0;
                $result[$owners][$j][12][13] = $changeNoBasedata[$owners][1][$j][3]['change_rents'];
                $result[$owners][$j][12][14] = 0;
                $result[$owners][$j][12][15] = 0;
                array_unshift($result[$owners][$j][12],array_sum($result[$owners][$j][12]) - $result[$owners][$j][12][1] - $result[$owners][$j][12][2] - $result[$owners][$j][12][3]);

                //政策减免ChangeType = 3
                $result[$owners][$j][14][1] = $changeZhengcedata[$owners][2][$j]['change_rents'];
                $result[$owners][$j][14][2] = 0;
                $result[$owners][$j][14][3] = 0;
                $result[$owners][$j][14][4] = 0.4 * $result[$owners][$j][14][1];
                $result[$owners][$j][14][5] = 0.4 * $result[$owners][$j][14][2];
                $result[$owners][$j][14][6] = 0.4 * $result[$owners][$j][14][3];
                $result[$owners][$j][14][7] = 0.6 * $result[$owners][$j][14][1];
                $result[$owners][$j][14][8] = 0.6 * $result[$owners][$j][14][2];
                $result[$owners][$j][14][9] = 0.6 * $result[$owners][$j][14][3];
                $result[$owners][$j][14][10] = $changeZhengcedata[$owners][3][$j]['change_rents'];
                $result[$owners][$j][14][11] = 0;
                $result[$owners][$j][14][12] = 0;
                $result[$owners][$j][14][13] = $changeZhengcedata[$owners][1][$j]['change_rents'];
                $result[$owners][$j][14][14] = 0;
                $result[$owners][$j][14][15] = 0;
                array_unshift($result[$owners][$j][14],array_sum($result[$owners][$j][14]) - $result[$owners][$j][14][1] - $result[$owners][$j][14][2] - $result[$owners][$j][14][3]);

                //政策减免ChangeType = 3
                $result[$owners][$j][16][1] = $changeNoBasedata[$owners][2][$j][0]['change_rents'];
                $result[$owners][$j][16][2] = 0;
                $result[$owners][$j][16][3] = 0;
                $result[$owners][$j][16][4] = 0.4 * $result[$owners][$j][16][1];
                $result[$owners][$j][16][5] = 0.4 * $result[$owners][$j][16][2];
                $result[$owners][$j][16][6] = 0.4 * $result[$owners][$j][16][3];
                $result[$owners][$j][16][7] = 0.6 * $result[$owners][$j][16][1];
                $result[$owners][$j][16][8] = 0.6 * $result[$owners][$j][16][2];
                $result[$owners][$j][16][9] = 0.6 * $result[$owners][$j][16][3];
                $result[$owners][$j][16][10] = $changeNoBasedata[$owners][3][$j][0]['change_rents'];
                $result[$owners][$j][16][11] = 0;
                $result[$owners][$j][16][12] = 0;
                $result[$owners][$j][16][13] = $changeNoBasedata[$owners][1][$j][0]['change_rents'];
                $result[$owners][$j][16][14] = 0;
                $result[$owners][$j][16][15] = 0;
                array_unshift($result[$owners][$j][16],array_sum($result[$owners][$j][16]) - $result[$owners][$j][16][1] - $result[$owners][$j][16][2] - $result[$owners][$j][16][3]);

                //陈欠核销ChangeType = 4
                $result[$owners][$j][15][1] = $changeNoBasedata[$owners][2][$j][0]['change_rents'];
                $result[$owners][$j][15][2] = $changeHeXiaodata[$owners][2][$j]['change_month_rents'];
                $result[$owners][$j][15][3] = $changeHeXiaodata[$owners][2][$j]['change_year_rents'];
                $result[$owners][$j][15][4] = 0.4 * $result[$owners][$j][15][1];
                $result[$owners][$j][15][5] = 0.4 * $result[$owners][$j][15][2];
                $result[$owners][$j][15][6] = 0.4 * $result[$owners][$j][15][3];
                $result[$owners][$j][15][7] = 0.6 * $result[$owners][$j][15][1];
                $result[$owners][$j][15][8] = 0.6 * $result[$owners][$j][15][2];
                $result[$owners][$j][15][9] = 0.6 * $result[$owners][$j][15][3];
                $result[$owners][$j][15][10] = $changeNoBasedata[$owners][3][$j][0]['change_rents'];
                $result[$owners][$j][15][11] = $changeHeXiaodata[$owners][3][$j]['change_month_rents'];
                $result[$owners][$j][15][12] = $changeHeXiaodata[$owners][3][$j]['change_year_rents'];
                $result[$owners][$j][15][13] = $changeNoBasedata[$owners][1][$j][0]['change_rents'];
                $result[$owners][$j][15][14] = $changeHeXiaodata[$owners][1][$j]['change_month_rents'];
                $result[$owners][$j][15][15] = $changeHeXiaodata[$owners][1][$j]['change_year_rents'];
                array_unshift($result[$owners][$j][15],array_sum($result[$owners][$j][15]) - $result[$owners][$j][15][1] - $result[$owners][$j][15][2] - $result[$owners][$j][15][3]);


                //非基数异动合计
                $result[$owners][$j][9][1] = $result[$owners][$j][10][1] + $result[$owners][$j][11][1] + $result[$owners][$j][12][1] + $result[$owners][$j][14][1] + $result[$owners][$j][15][1] - $result[$owners][$j][16][1];
                $result[$owners][$j][9][2] = $result[$owners][$j][10][2] + $result[$owners][$j][11][2] + $result[$owners][$j][12][2] + $result[$owners][$j][14][2] + $result[$owners][$j][15][2] - $result[$owners][$j][16][2];
                $result[$owners][$j][9][3] = $result[$owners][$j][10][3] + $result[$owners][$j][11][3] + $result[$owners][$j][12][3] + $result[$owners][$j][14][3] + $result[$owners][$j][15][3] - $result[$owners][$j][16][2];
                $result[$owners][$j][9][4] = 0.4 * $result[$owners][$j][9][1];
                $result[$owners][$j][9][5] = 0.4 * $result[$owners][$j][9][2];
                $result[$owners][$j][9][6] = 0.4 * $result[$owners][$j][9][3];
                $result[$owners][$j][9][7] = 0.6 * $result[$owners][$j][9][1];
                $result[$owners][$j][9][8] = 0.6 * $result[$owners][$j][9][2];
                $result[$owners][$j][9][9] = 0.6 * $result[$owners][$j][9][3];
                $result[$owners][$j][9][10] = $result[$owners][$j][10][10] + $result[$owners][$j][11][10] + $result[$owners][$j][12][10] + $result[$owners][$j][14][10] + $result[$owners][$j][15][10]- $result[$owners][$j][16][10];
                $result[$owners][$j][9][11] = $result[$owners][$j][10][11] + $result[$owners][$j][11][11] + $result[$owners][$j][12][11] + $result[$owners][$j][14][11] + $result[$owners][$j][15][11]- $result[$owners][$j][16][11];
                $result[$owners][$j][9][12] = $result[$owners][$j][10][12] + $result[$owners][$j][11][12] + $result[$owners][$j][12][12] + $result[$owners][$j][14][12] + $result[$owners][$j][15][12]- $result[$owners][$j][16][12];
                $result[$owners][$j][9][13] = $result[$owners][$j][10][13] + $result[$owners][$j][11][13] + $result[$owners][$j][12][13] + $result[$owners][$j][14][13] - $result[$owners][$j][16][13];
                $result[$owners][$j][9][14] = $result[$owners][$j][10][14] + $result[$owners][$j][11][14] + $result[$owners][$j][12][14] + $result[$owners][$j][14][14] + $result[$owners][$j][15][14]- $result[$owners][$j][16][14];
                $result[$owners][$j][9][15] = $result[$owners][$j][10][15] + $result[$owners][$j][11][15] + $result[$owners][$j][12][15] + $result[$owners][$j][14][15] + $result[$owners][$j][15][15]- $result[$owners][$j][16][15];
                array_unshift($result[$owners][$j][9],array_sum($result[$owners][$j][9]) - $result[$owners][$j][9][1] - $result[$owners][$j][9][2] - $result[$owners][$j][9][3]);

                //应缴租金那一行 = 规定租金那一行 + 非基数异动合计
                $result[$owners][$j][17][1] = $rentdata[$owners][2][$j]['rent_order_receives']; //bcsub($result[$owners][$j][8][1] , $result[$owners][$j][9][1],2);
                $result[$owners][$j][17][2] = bcsub($result[$owners][$j][8][2] , $result[$owners][$j][9][2],2);
                $result[$owners][$j][17][3] = bcsub($result[$owners][$j][8][3] , $result[$owners][$j][9][3],2);
                $result[$owners][$j][17][4] = 0.4 * $result[$owners][$j][17][1];
                $result[$owners][$j][17][5] = 0.4 * $result[$owners][$j][17][2];
                $result[$owners][$j][17][6] = 0.4 * $result[$owners][$j][17][3];
                $result[$owners][$j][17][7] = 0.6 * $result[$owners][$j][17][1];
                $result[$owners][$j][17][8] = 0.6 * $result[$owners][$j][17][2];
                $result[$owners][$j][17][9] = 0.6 * $result[$owners][$j][17][3];
                $result[$owners][$j][17][10] = $rentdata[$owners][3][$j]['rent_order_receives']; //bcsub($result[$owners][$j][8][10] , $result[$owners][$j][9][10],2);
                $result[$owners][$j][17][11] = bcsub($result[$owners][$j][8][11] , $result[$owners][$j][9][11],2);
                $result[$owners][$j][17][12] = bcsub($result[$owners][$j][8][12] , $result[$owners][$j][9][12],2);
                $result[$owners][$j][17][13] = $rentdata[$owners][1][$j]['rent_order_receives']; //bcsub($result[$owners][$j][8][13] , $result[$owners][$j][9][13],2);
                $result[$owners][$j][17][14] = bcsub($result[$owners][$j][8][14] , $result[$owners][$j][9][14],2);
                $result[$owners][$j][17][15] = bcsub($result[$owners][$j][8][15] , $result[$owners][$j][9][15],2);
                array_unshift($result[$owners][$j][17],bcaddMerge([$result[$owners][$j][17][1], $result[$owners][$j][17][2], $result[$owners][$j][17][3], $result[$owners][$j][17][10], $result[$owners][$j][17][11], $result[$owners][$j][17][12], $result[$owners][$j][17][13], $result[$owners][$j][17][14], $result[$owners][$j][17][15]]));

                //本月份实收租金 = 本月规租 - 本月订单欠租
                $result[$owners][$j][18][1] = $rentdata[$owners][2][$j]['rent_order_paids'];
                //以前月份实收租金
                $result[$owners][$j][18][2] = $rentOldMonthdata[$owners][2][$j]['pay_rents'];
                //以前年度实收租金
                $result[$owners][$j][18][3] = $rentOldYeardata[$owners][2][$j]['pay_rents'];
                $result[$owners][$j][18][4] = 0.4 * $result[$owners][$j][18][1];
                $result[$owners][$j][18][5] = 0.4 * $result[$owners][$j][18][2];
                $result[$owners][$j][18][6] = 0.4 * $result[$owners][$j][18][3];
                $result[$owners][$j][18][7] = 0.6 * $result[$owners][$j][18][1];
                $result[$owners][$j][18][8] = 0.6 * $result[$owners][$j][18][2];
                $result[$owners][$j][18][9] = 0.6 * $result[$owners][$j][18][3];
                $result[$owners][$j][18][10] = $rentdata[$owners][3][$j]['rent_order_paids'];
                $result[$owners][$j][18][11] = $rentOldMonthdata[$owners][3][$j]['pay_rents'];
                $result[$owners][$j][18][12] = $rentOldYeardata[$owners][3][$j]['pay_rents'];
                $result[$owners][$j][18][13] = $rentdata[$owners][1][$j]['rent_order_paids'];
                $result[$owners][$j][18][14] = $rentOldMonthdata[$owners][1][$j]['pay_rents'];
                $result[$owners][$j][18][15] = $rentOldYeardata[$owners][1][$j]['pay_rents'];
                array_unshift($result[$owners][$j][18],array_sum($result[$owners][$j][18]) - $result[$owners][$j][18][1] - $result[$owners][$j][18][2] - $result[$owners][$j][18][3]);

                // //本月份实收租金 = 本月规租 - 本月订单欠租
                // $result[$owners][$j][18][1] = bcsub($result[$owners][$j][17][1] , $rentdata[$owners][2][$j]['UnpaidRents'],2);
                // //以前月份实收租金
                // $result[$owners][$j][18][2] = $rentOldMonthdata[$owners][2][$j]['PayRents'];
                // //以前年度实收租金
                // $result[$owners][$j][18][3] = $rentOldYeardata[$owners][2][$j]['PayRents'];
                // $result[$owners][$j][18][4] = 0.4 * $result[$owners][$j][18][1];
                // $result[$owners][$j][18][5] = 0.4 * $result[$owners][$j][18][2];
                // $result[$owners][$j][18][6] = 0.4 * $result[$owners][$j][18][3];
                // $result[$owners][$j][18][7] = 0.6 * $result[$owners][$j][18][1];
                // $result[$owners][$j][18][8] = 0.6 * $result[$owners][$j][18][2];
                // $result[$owners][$j][18][9] = 0.6 * $result[$owners][$j][18][3];
                // $result[$owners][$j][18][10] = bcsub($result[$owners][$j][17][10] , $rentdata[$owners][3][$j]['UnpaidRents'],2);
                // $result[$owners][$j][18][11] = $rentOldMonthdata[$owners][3][$j]['PayRents'];
                // $result[$owners][$j][18][12] = $rentOldYeardata[$owners][3][$j]['PayRents'];
                // $result[$owners][$j][18][13] = bcsub($result[$owners][$j][17][13] , $rentdata[$owners][1][$j]['UnpaidRents'],2);
                // $result[$owners][$j][18][14] = $rentOldMonthdata[$owners][1][$j]['PayRents'];
                // $result[$owners][$j][18][15] = $rentOldYeardata[$owners][1][$j]['PayRents'];
                // array_unshift($result[$owners][$j][18],array_sum($result[$owners][$j][18]) - $result[$owners][$j][18][1] - $result[$owners][$j][18][2] - $result[$owners][$j][18][3]);

                //本月份实收累计 = 本月规租 + 本年度各月份的【本月实收租金】$temps
                //$result[$owners][$j][19][1] = $lastMonthData[$owners][$j][18][1] + $result[$owners][$j][18][1];  //暂时是这样的
                if(substr($cacheDate,4,2) != '01'){
                    //上个月的累计+这个月的实收 = 这个月的累计
                    $result[$owners][$j][19][1] = $lastMonthData[$owners][$j][19][1] + $result[$owners][$j][18][1];
                    //以前月份实收累计
                    $result[$owners][$j][19][2] = $rentOldTotalMonthdata[$owners][2][$j]['pay_rents'];
                    //以前年度实收累计
                    //$result[$owners][$j][19][3] = $lastMonthData[$owners][$j][18][3]+ $result[$owners][$j][18][3];
                    
                    $result[$owners][$j][19][3] = $rentOldTotalYeardata[$owners][2][$j]['pay_rents'];
                    $result[$owners][$j][19][4] = 0.4 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][5] = 0.4 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][6] = 0.4 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][7] = 0.6 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][8] = 0.6 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][9] = 0.6 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][10] = $lastMonthData[$owners][$j][19][10] + $result[$owners][$j][18][10];
                    $result[$owners][$j][19][11] = $rentOldTotalMonthdata[$owners][3][$j]['pay_rents'];
                    $result[$owners][$j][19][12] = $rentOldTotalYeardata[$owners][3][$j]['pay_rents'];
                    $result[$owners][$j][19][13] = $lastMonthData[$owners][$j][19][13] + $result[$owners][$j][18][13];
                    $result[$owners][$j][19][14] = $rentOldTotalMonthdata[$owners][1][$j]['pay_rents'];
                    $result[$owners][$j][19][15] = $rentOldTotalYeardata[$owners][1][$j]['pay_rents'];
                    array_unshift($result[$owners][$j][19],array_sum($result[$owners][$j][19]) - $result[$owners][$j][19][1] - $result[$owners][$j][19][2] - $result[$owners][$j][19][3]);
                }else{
                    //上个月的累计+这个月的实收 = 这个月的累计
                    $result[$owners][$j][19][1] = $result[$owners][$j][18][1];
                    //以前月份实收累计
                    $result[$owners][$j][19][2] = $rentOldTotalMonthdata[$owners][2][$j]['pay_rents'];
                    //以前年度实收累计
                    //$result[$owners][$j][19][3] = $lastMonthData[$owners][$j][18][3]+ $result[$owners][$j][18][3];
                    
                    $result[$owners][$j][19][3] = $result[$owners][$j][18][3];
                    $result[$owners][$j][19][4] = 0.4 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][5] = 0.4 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][6] = 0.4 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][7] = 0.6 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][8] = 0.6 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][9] = 0.6 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][10] = $result[$owners][$j][18][10];
                    $result[$owners][$j][19][11] = $rentOldTotalMonthdata[$owners][3][$j]['pay_rents'];
                    $result[$owners][$j][19][12] = $result[$owners][$j][18][12];
                    $result[$owners][$j][19][13] = $result[$owners][$j][18][13];
                    $result[$owners][$j][19][14] = $rentOldTotalMonthdata[$owners][1][$j]['pay_rents'];
                    $result[$owners][$j][19][15] = $result[$owners][$j][18][15];
                    array_unshift($result[$owners][$j][19],array_sum($result[$owners][$j][19]) - $result[$owners][$j][19][1] - $result[$owners][$j][19][2] - $result[$owners][$j][19][3]);
                }

                $result[$owners][$j][20][1] = $rentUnpaiddata[$owners][2][$j]['rent_order_unpaids']; //bcsub($result[$owners][$j][17][1] , $result[$owners][$j][18][1],2);
                $result[$owners][$j][20][2] = bcsub($result[$owners][$j][17][2] , $result[$owners][$j][18][2],2);
                $result[$owners][$j][20][3] = bcsub($result[$owners][$j][17][3] , $result[$owners][$j][18][3],2);
                $result[$owners][$j][20][4] = 0.4 * $result[$owners][$j][20][1];
                $result[$owners][$j][20][5] = 0.4 * $result[$owners][$j][20][2];
                $result[$owners][$j][20][6] = 0.4 * $result[$owners][$j][20][3];
                $result[$owners][$j][20][7] = 0.6 * $result[$owners][$j][20][1];
                $result[$owners][$j][20][8] = 0.6 * $result[$owners][$j][20][2];
                $result[$owners][$j][20][9] = 0.6 * $result[$owners][$j][20][3];
                $result[$owners][$j][20][10] = $rentUnpaiddata[$owners][3][$j]['rent_order_unpaids']; //bcsub($result[$owners][$j][17][10] , $result[$owners][$j][18][10],2);
                $result[$owners][$j][20][11] = bcsub($result[$owners][$j][17][11] , $result[$owners][$j][18][11],2);
                $result[$owners][$j][20][12] = bcsub($result[$owners][$j][17][12] , $result[$owners][$j][18][12],2);
                $result[$owners][$j][20][13] = $rentUnpaiddata[$owners][1][$j]['rent_order_unpaids']; //bcsub($result[$owners][$j][17][13] , $result[$owners][$j][18][13],2);
                $result[$owners][$j][20][14] = bcsub($result[$owners][$j][17][14] , $result[$owners][$j][18][14],2);
                $result[$owners][$j][20][15] = bcsub($result[$owners][$j][17][15] , $result[$owners][$j][18][15],2);
                array_unshift($result[$owners][$j][20],array_sum($result[$owners][$j][20]) - $result[$owners][$j][20][1] - $result[$owners][$j][20][2] - $result[$owners][$j][20][3]);

                $result[$owners][$j][100][1] = $housedata[$owners][2][$j]['house_ids']; //工商总户数
                $result[$owners][$j][100][2] = $housedata[$owners][3][$j]['house_ids']; //党政总户数
                $result[$owners][$j][100][3] = $housedata[$owners][1][$j]['house_ids']; //民用总户数
            }
        }
        
        //第一步：将所有管段加上市代托、市区代托、全部
        $ownertype = [10,11,12]; //市、区、代、自、托、市代托、市区代托、全部
        foreach ($ownertype as $ow) {
            for ($d = 33; $d >3; $d--) { //公司和所，从1到3（1公司，2紫阳，3粮道），注意顺序公司的数据由所加和得来，所以是3、2、1的顺序
                if($ow == 10){
                    $result[$ow][$d] = array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]);
                } 
                if($ow == 11 && $d > 3){
                    $result[$ow][$d] = array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]),$result[2][$d]);
                }     
                if($ow == 12 && $d > 3){
                    $result[$ow][$d] = array_merge_add(array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]),$result[2][$d]),$result[5][$d]);
                }
            }
        }


        //第二步：处理市代托，市区代托，全部下的公司，紫阳，粮道的数据（注意只有所和公司才有市代托、市区代托、全部）
        $ownertypess = [1,2,3,5,7,10,11,12]; //市、区、代、自、托、市代托、市区代托、全部
        foreach ($ownertypess as $own) {
            for ($d = 3; $d >0; $d--) { //公司和所，从1到3（1公司，2紫阳，3粮道），注意顺序公司的数据由所加和得来，所以是3、2、1的顺序
                if($own < 10 && $d ==3){
                    $result[$own][$d] = array_merge_addss($result[$own][19],$result[$own][20],$result[$own][21],$result[$own][22],$result[$own][23],$result[$own][24],$result[$own][25],$result[$own][26],$result[$own][27],$result[$own][28],$result[$own][29],$result[$own][30],$result[$own][31],$result[$own][32],$result[$own][33]);
                }elseif($own < 10 && $d ==2){
                    $result[$own][$d] = array_merge_addss($result[$own][4],$result[$own][5],$result[$own][6],$result[$own][7],$result[$own][8],$result[$own][9],$result[$own][10],$result[$own][11],$result[$own][12],$result[$own][13],$result[$own][14],$result[$own][15],$result[$own][16],$result[$own][17],$result[$own][18]);
                }elseif($own < 10 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 10 && $d > 1 && $d < 4){
                    $result[$own][$d] = array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]);
                }elseif($own == 10 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                    
                }elseif($own == 11 && $d > 1 && $d < 4){
                    $result[$own][$d] = array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]),$result[2][$d]);
                }elseif($own == 11 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 12 && $d > 1 && $d < 4){
                    $result[$own][$d] = array_merge_add(array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]),$result[2][$d]),$result[5][$d]);
                }elseif($own == 12 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }

            }
        }
        //halt($result);
        return $result;
    }

/*        public function makeMonthReport($cacheDate){  //按照原始1.0的方式统计的
        
        $cacheDate = $cacheDate;     // 201805
        $arr2 = getlastMonthDays($cacheDate); // 201804
        $arr4 = substr($cacheDate,0,4); // 2018
        $arr5 = [substr($cacheDate,0,4) . '01', $cacheDate - 1]; // 201801~201804,包含201801和201804
        $arr6 = [substr($cacheDate,0,4) . '01', $cacheDate]; // 201801~201805,包含201801和201805
        $arr7 = substr($cacheDate,0,4); // 2018

        //从往期欠租表中,获取当月收缴到的以前月的【以前月实收】租金
        $rentOldMonthData = Db::name('rent_recycle')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.pay_rent) as pay_rents')->where([['a.pay_month','eq',$cacheDate],['a.pay_year','eq',$arr4]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //从往期欠租表中,获取今年【以前月实收累计】收缴到的以前月的租金
        $rentOldTotalMonthData = Db::name('rent_recycle')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.pay_rent) as pay_rents')->where([['a.pay_month','between',$arr6],['a.pay_year','eq',$arr4]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //从往期欠租表中,获取当月收缴到的【以前年度实收】的租金
        $rentOldYearData = Db::name('rent_recycle')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.pay_rent) as pay_rents')->where([['a.pay_month','eq',$cacheDate],['a.pay_year','<',$arr7]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //从往期欠租表中，获取今年实收累计收缴到的【以前年度实收累计】的租金
        $rentOldTotalYearData = Db::name('rent_recycle')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,sum(a.pay_rent) as pay_rents')->where([['a.pay_month','between',$arr6],['a.pay_year','<',$arr7]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();


        //从租金订单表中,获取规定、已缴、欠缴、应缴租金
        $rentData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,count(a.house_id) as house_ids,sum(a.rent_order_cut) as rent_order_cuts,sum(a.rent_order_receive) as rent_order_receives,sum(a.rent_order_paid) as rent_order_paids,sum(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaids,sum(a.rent_order_diff) as rent_order_diffs,sum(a.rent_order_pump) as rent_order_pumps')->where([['a.rent_order_date','eq',$cacheDate]])->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')->select();

        //从房屋表中分组获取年度欠租、租差
        $houseData = Db::name('house')->alias('b')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_use_id,d.ban_owner_id,d.ban_inst_id,count(b.house_id) as house_ids,sum(b.house_diff_rent) as house_diff_rents,sum(b.house_pump_rent) as house_pump_rents,sum(b.house_pre_rent) as house_pre_rents')->group('b.house_use_id,d.ban_owner_id,d.ban_inst_id')
            ->where([['b.house_status','eq',1]])
            ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,change_type')->group('use_id,owner_id,inst_id,change_type')
            ->where([['change_cancel_type','neq',1],['change_type','neq',1],['order_date','eq',$cacheDate],['change_status','eq',1]])
            ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeGuanduanDecData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,inst_id')
            ->where([['change_type','eq',10],['order_date','eq',$cacheDate],['change_status','eq',1]])
            ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeGuanduanIncData = Db::name('change_table')->field('use_id,owner_id,new_inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,new_inst_id')
            ->where([['change_type','eq',10],['order_date','eq',$cacheDate],['change_status','eq',1]])
            ->select();

        //获取非基数异动
        $changeNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents,change_type')->group('use_id,owner_id,inst_id,change_type')
            ->where([['change_cancel_type','neq',1],['change_type','neq',1],['change_status','eq',1]])
            ->select();

        //陈欠核销的是一个特别的非基数异动，以前年月必须是当月数据，不能同本月一样每个月继承
        $changeHeXiaoData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$cacheDate],['change_type','eq',4],['change_status','eq',1]])
            ->select();

        //房屋出售
        $changeSaleData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$cacheDate],['change_cancel_type','eq',1],['change_status','eq',1]])
            ->select();

        //政策减免
        $changeZhengceData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','gt',$cacheDate],['cut_type','eq',5],['change_status','eq',1]])
            ->select();

        //减免（除去政策减免）
        $changejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','gt',$cacheDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])
            ->select();

        //重组为规定格式的租金数据
        foreach($rentData as $k1 => $v1){
            $rentdata[$v1['ban_owner_id']][$v1['house_use_id']][$v1['ban_inst_id']] = [
                'house_ids' => $v1['house_ids'],
                'rent_order_cuts' => $v1['rent_order_cuts'],
                'rent_order_receives' => $v1['rent_order_receives'],
                'rent_order_paids' => $v1['rent_order_paids'],
                'rent_order_unpaids' => $v1['rent_order_unpaids'],
                'rent_order_diffs' => $v1['rent_order_diffs'],
                'rent_order_pumps' => $v1['rent_order_pumps']
            ];
        }

        //重组为规定格式的房屋数据
        foreach($houseData as $k2 => $v2){
            $housedata[$v2['ban_owner_id']][$v2['house_use_id']][$v2['ban_inst_id']] = [
                'house_ids' => $v2['house_ids'],
                'house_pre_rents' => $v2['house_pre_rents'],
                //'ArrearRents' => $v2['ArrearRents'],
                'house_diff_rents' => $v2['house_diff_rents'],
                'house_pump_rents' => $v2['house_pump_rents'],
            ];
        }

        //重组为规定格式的当月收到的以前月数据
        foreach($rentOldMonthData as $k3 => $v3){
            $rentOldMonthdata[$v3['ban_owner_id']][$v3['house_use_id']][$v3['ban_inst_id']] = [
                'pay_rents' => $v3['pay_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($rentOldTotalMonthData as $k5 => $v5){
            $rentOldTotalMonthdata[$v5['ban_owner_id']][$v5['house_use_id']][$v5['ban_inst_id']] = [
                'pay_rents' => $v5['pay_rents'],
            ];
        }

        //重组为规定格式的当月收到的以前年数据
        foreach($rentOldYearData as $k4 => $v4){
            $rentOldYeardata[$v4['ban_owner_id']][$v4['house_use_id']][$v4['ban_inst_id']] = [
                'pay_rents' => $v4['pay_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($rentOldTotalYearData as $k6 => $v6){
            $rentOldTotalYeardata[$v6['ban_owner_id']][$v6['house_use_id']][$v6['ban_inst_id']] = [
                'pay_rents' => $v6['pay_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据$changeNoBaseData
        foreach($changeData as $k7 => $v7){
            $changedata[$v7['owner_id']][$v7['use_id']][$v7['inst_id']][$v7['change_type']] = [
                'change_rents' => $v7['change_rents'],
                'change_month_rents' => $v7['change_month_rents'],
                'change_year_rents' => $v7['change_year_rents'],
            ];
        }

        foreach($changeGuanduanDecData as $k12 => $v12){
            $changeGuanduanDecdata[$v12['owner_id']][$v12['use_id']][$v12['inst_id']] = [
                'change_rents' => $v12['change_rents'],
                'change_month_rents' => $v12['change_month_rents'],
                'change_year_rents' => $v12['change_year_rents'],
            ];
        }

        foreach($changeGuanduanIncData as $k13 => $v13){
            $changeGuanduanIncdata[$v13['owner_id']][$v13['use_id']][$v13['new_inst_id']] = [
                'change_rents' => $v13['change_rents'],
                'change_month_rents' => $v13['change_month_rents'],
                'change_year_rents' => $v13['change_year_rents'],
            ];
        }

        foreach($changeNoBaseData as $k11 => $v11){
            $changeNoBasedata[$v11['owner_id']][$v11['use_id']][$v11['inst_id']][$v11['change_type']] = [
                'change_rents' => $v11['change_rents'],
                'change_month_rents' => $v11['change_month_rents'],
                'change_year_rents' => $v11['change_year_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($changeHeXiaoData as $k14 => $v14){
            $changeHeXiaodata[$v14['owner_id']][$v14['use_id']][$v14['inst_id']] = [
                'change_rents' => $v14['change_rents'],
                'change_month_rents' => $v14['change_month_rents'],
                'change_year_rents' => $v14['change_year_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($changeSaleData as $k8 => $v8){
            $changeSaledata[$v8['owner_id']][$v8['use_id']][$v8['inst_id']] = [
                'change_rents' => $v8['change_rents'],
            ];
        }

        //重组为规定格式的实收累计收到的以前月数据
        foreach($changeZhengceData as $k9 => $v9){
            $changeZhengcedata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }

        //重组为规定格式的
        foreach($changejianmianData as $k9 => $v9){
            $changejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }

        //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                for ($j=4;$j<34;$j++) {
                    if(!isset($rentdata[$owner][$i][$j])){
                        $rentdata[$owner][$i][$j] = [
                            'house_ids' => 0,
                            'rent_order_cuts' => 0,
                            'rent_order_receives' => 0,
                            'rent_order_paids' => 0,
                            'rent_order_unpaids' => 0,
                            'rent_order_diffs' => 0,
                            'rent_order_pumps' => 0,
                        ];
                    }
                    if(!isset($housedata[$owner][$i][$j])){
                        $housedata[$owner][$i][$j] = [
                            'house_ids' => 0,
                            'house_pre_rents' => 0,
                            //'ArrearRents' => 0,
                            'house_diff_rents' => 0,
                            'house_pump_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldMonthdata[$owner][$i][$j])){
                        $rentOldMonthdata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldTotalMonthdata[$owner][$i][$j])){
                        $rentOldTotalMonthdata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldYeardata[$owner][$i][$j])){
                        $rentOldYeardata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }
                    if(!isset($rentOldTotalYeardata[$owner][$i][$j])){
                        $rentOldTotalYeardata[$owner][$i][$j] = [
                            'pay_rents' => 0,
                        ];
                    }

                    for($k=1;$k<16;$k++){
                        if(!isset($changedata[$owner][$i][$j][$k])){
                            $changedata[$owner][$i][$j][$k] = [ 
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                            ];
                        }

                    }

                    for($z=0;$z<13;$z++){
                        if(!isset($changeNoBasedata[$owner][$i][$j][$z])){
                            $changeNoBasedata[$owner][$i][$j][$z] = [ 
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                            ];
                        }

                    }
                    if(!isset($changeGuanduanDecdata[$owner][$i][$j])){
                        $changeGuanduanDecdata[$owner][$i][$j] = [
                            'change_rents' => 0,
                            'change_month_rents' => 0,
                            'change_year_rents' => 0,
                        ];
                    }
                    if(!isset($changeGuanduanIncdata[$owner][$i][$j])){
                        $changeGuanduanIncdata[$owner][$i][$j] = [
                            'change_rents' => 0,
                            'change_month_rents' => 0,
                            'change_year_rents' => 0,
                        ];
                    }
                    if(!isset($changeHeXiaodata[$owner][$i][$j])){
                        $changeHeXiaodata[$owner][$i][$j] = [
                            'change_rents' => 0,
                            'change_month_rents' => 0,
                            'change_year_rents' => 0,
                        ];
                    }
                    if(!isset($changeSaledata[$owner][$i][$j])){
                        $changeSaledata[$owner][$i][$j] = [
                            'change_rents' => 0,
                        ];
                    }
                    if(!isset($changeZhengcedata[$owner][$i][$j])){
                        $changeZhengcedata[$owner][$i][$j] = [
                            'change_rents' => 0,
                        ];
                    }
                    if(!isset($changejianmiandata[$owner][$i][$j])){
                        $changejianmiandata[$owner][$i][$j] = [
                            'change_rents' => 0,
                        ];
                    }
                    
                }
            }
        }
        $cachedate = date('Ym',strtotime($cacheDate . '01 -1 month'));

        $reportolddata = Db::name('report')->where([['type','eq','RentReport'],['date','eq',$cachedate]])->value('data');
        $datas = json_decode($reportolddata,true);
        $lastMonthData = isset($datas)?$datas:array();
//halt($lastMonthData);
        //如果缓存的是某年的第一个月的数据
        if(substr($cacheDate,4,2) == '01' ){
            //$lastMonthData = array();
        }
        // $firstMonth = substr($cacheDate,0,4).'01';
        // $lastcacheDate = $cacheDate - 1;
        // if($lastcacheDate >= $firstMonth){ //如果缓存的程序是2月份或2月份以上
        //     for($s = $firstMonth ; $s <= $lastcacheDate; $s++){
        //         $temps[] = json_decode(Cache::store('file')->get('RentReport'.($s),''),true);
        //     }
        // }
//halt($temps);

        //第一步：处理市、区、代、自、托的每一个管段的数据
        foreach ($ownertypes as $owners) { //处理市、区、代、自、托
            for ($j = 4; $j < 34; $j++) { //每个管段，从4开始……

                if(substr($cacheDate,4,2) != '01'){
                    $result[$owners][$j][0][1] = $lastMonthData[$owners][$j][8][1];
                    $result[$owners][$j][0][2] = $lastMonthData[$owners][$j][20][1] + $lastMonthData[$owners][$j][20][2];
                    $result[$owners][$j][0][3] = $lastMonthData[$owners][$j][20][3];
                    $result[$owners][$j][0][4] = 0.4 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][5] = 0.4 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][6] = 0.4 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][7] = 0.6 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][8] = 0.6 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][9] = 0.6 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][10] = $lastMonthData[$owners][$j][8][10];
                    $result[$owners][$j][0][11] = $lastMonthData[$owners][$j][20][10] + $lastMonthData[$owners][$j][20][11];//$lastMonthData[$owners][$j][20][10];
                    $result[$owners][$j][0][12] = $lastMonthData[$owners][$j][20][12];
                    $result[$owners][$j][0][13] = $lastMonthData[$owners][$j][8][13];
                    $result[$owners][$j][0][14] = $lastMonthData[$owners][$j][20][13] + $lastMonthData[$owners][$j][20][14];//$lastMonthData[$owners][$j][20][13];
                    $result[$owners][$j][0][15] = $lastMonthData[$owners][$j][20][15];
                    array_unshift($result[$owners][$j][0],array_sum($result[$owners][$j][0]) - $result[$owners][$j][0][1] - $result[$owners][$j][0][2] - $result[$owners][$j][0][3]);
                }else{
                    $result[$owners][$j][0][1] = $lastMonthData[$owners][$j][8][1];
                    $result[$owners][$j][0][2] = 0;
                    $result[$owners][$j][0][3] = $lastMonthData[$owners][$j][20][1] + $lastMonthData[$owners][$j][20][2] + $lastMonthData[$owners][$j][20][3];
                    $result[$owners][$j][0][4] = 0.4 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][5] = 0.4 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][6] = 0.4 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][7] = 0.6 * $result[$owners][$j][0][1];
                    $result[$owners][$j][0][8] = 0.6 * $result[$owners][$j][0][2];
                    $result[$owners][$j][0][9] = 0.6 * $result[$owners][$j][0][3];
                    $result[$owners][$j][0][10] = $lastMonthData[$owners][$j][8][10];
                    $result[$owners][$j][0][11] = 0;//$lastMonthData[$owners][$j][20][10];
                    $result[$owners][$j][0][12] = $lastMonthData[$owners][$j][20][10] + $lastMonthData[$owners][$j][20][11] + $lastMonthData[$owners][$j][20][12];
                    $result[$owners][$j][0][13] = $lastMonthData[$owners][$j][8][13];
                    $result[$owners][$j][0][14] = 0;//$lastMonthData[$owners][$j][20][13];
                    $result[$owners][$j][0][15] = $lastMonthData[$owners][$j][20][13] + $lastMonthData[$owners][$j][20][14] + $lastMonthData[$owners][$j][20][15];
                    array_unshift($result[$owners][$j][0],array_sum($result[$owners][$j][0]) - $result[$owners][$j][0][1] - $result[$owners][$j][0][2] - $result[$owners][$j][0][3]);
                }
                
              
                //新发租异动ChangeType = 7
                $result[$owners][$j][2][1] = $changedata[$owners][2][$j][7]['change_rents'];
                $result[$owners][$j][2][2] = 0;
                $result[$owners][$j][2][3] = 0;
                $result[$owners][$j][2][4] = 0.4 * $result[$owners][$j][2][1];
                $result[$owners][$j][2][5] = 0.4 * $result[$owners][$j][2][2];
                $result[$owners][$j][2][6] = 0.4 * $result[$owners][$j][2][3];
                $result[$owners][$j][2][7] = 0.6 * $result[$owners][$j][2][1];
                $result[$owners][$j][2][8] = 0.6 * $result[$owners][$j][2][2];
                $result[$owners][$j][2][9] = 0.6 * $result[$owners][$j][2][3];
                $result[$owners][$j][2][10] = $changedata[$owners][3][$j][7]['change_rents'];
                $result[$owners][$j][2][11] = 0;
                $result[$owners][$j][2][12] = 0;
                $result[$owners][$j][2][13] = $changedata[$owners][1][$j][7]['change_rents'];
                $result[$owners][$j][2][14] = 0;
                $result[$owners][$j][2][15] = 0;
                array_unshift($result[$owners][$j][2],array_sum($result[$owners][$j][2]) - $result[$owners][$j][2][1] - $result[$owners][$j][2][2] - $result[$owners][$j][2][3]);

                //调整ChangeType = 3
                $result[$owners][$j][3][1] = $changedata[$owners][2][$j][12]['change_rents'];
                $result[$owners][$j][3][2] = $changedata[$owners][2][$j][12]['change_month_rents'];
                $result[$owners][$j][3][3] = $changedata[$owners][2][$j][12]['change_year_rents'];
                $result[$owners][$j][3][4] = 0.4 * $result[$owners][$j][3][1];
                $result[$owners][$j][3][5] = 0.4 * $result[$owners][$j][3][2];
                $result[$owners][$j][3][6] = 0.4 * $result[$owners][$j][3][3];
                $result[$owners][$j][3][7] = 0.6 * $result[$owners][$j][3][1];
                $result[$owners][$j][3][8] = 0.6 * $result[$owners][$j][3][2];
                $result[$owners][$j][3][9] = 0.6 * $result[$owners][$j][3][3];
                $result[$owners][$j][3][10] = $changedata[$owners][3][$j][12]['change_rents'];
                $result[$owners][$j][3][11] = $changedata[$owners][3][$j][12]['change_month_rents'];
                $result[$owners][$j][3][12] = $changedata[$owners][3][$j][12]['change_year_rents'];
                $result[$owners][$j][3][13] = $changedata[$owners][1][$j][12]['change_rents'];
                $result[$owners][$j][3][14] = $changedata[$owners][1][$j][12]['change_month_rents'];
                $result[$owners][$j][3][15] = $changedata[$owners][1][$j][12]['change_year_rents'];
                array_unshift($result[$owners][$j][3],array_sum($result[$owners][$j][3]) - $result[$owners][$j][3][1] - $result[$owners][$j][3][2] - $result[$owners][$j][3][3]);

                //注销异动ChangeType = 8
                $result[$owners][$j][4][1] = $changedata[$owners][2][$j][8]['change_rents'];
                $result[$owners][$j][4][2] = 0;
                $result[$owners][$j][4][3] = 0;
                $result[$owners][$j][4][4] = 0.4 * $result[$owners][$j][4][1];
                $result[$owners][$j][4][5] = 0.4 * $result[$owners][$j][4][2];
                $result[$owners][$j][4][6] = 0.4 * $result[$owners][$j][4][3];
                $result[$owners][$j][4][7] = 0.6 * $result[$owners][$j][4][1];
                $result[$owners][$j][4][8] = 0.6 * $result[$owners][$j][4][2];
                $result[$owners][$j][4][9] = 0.6 * $result[$owners][$j][4][3];
                $result[$owners][$j][4][10] = $changedata[$owners][3][$j][8]['change_rents'];
                $result[$owners][$j][4][11] = 0;
                $result[$owners][$j][4][12] = 0;
                $result[$owners][$j][4][13] = $changedata[$owners][1][$j][8]['change_rents'];
                $result[$owners][$j][4][14] = 0;
                $result[$owners][$j][4][15] = 0;
                array_unshift($result[$owners][$j][4],array_sum($result[$owners][$j][4]) - $result[$owners][$j][4][1] - $result[$owners][$j][4][2] - $result[$owners][$j][4][3]);

                //租差
                $result[$owners][$j][5][1] = $changedata[$owners][2][$j][15]['change_rents'];
                $result[$owners][$j][5][2] = 0;
                $result[$owners][$j][5][3] = 0;
                $result[$owners][$j][5][4] = 0.4 * $result[$owners][$j][5][1];
                $result[$owners][$j][5][5] = 0.4 * $result[$owners][$j][5][2];
                $result[$owners][$j][5][6] = 0.4 * $result[$owners][$j][5][3];
                $result[$owners][$j][5][7] = 0.6 * $result[$owners][$j][5][1];
                $result[$owners][$j][5][8] = 0.6 * $result[$owners][$j][5][2];
                $result[$owners][$j][5][9] = 0.6 * $result[$owners][$j][5][3];
                $result[$owners][$j][5][10] = $changedata[$owners][3][$j][15]['change_rents'];
                $result[$owners][$j][5][11] = 0;
                $result[$owners][$j][5][12] = 0;
                $result[$owners][$j][5][13] = $changedata[$owners][1][$j][15]['change_rents'];
                $result[$owners][$j][5][14] = 0;
                $result[$owners][$j][5][15] = 0;
                array_unshift($result[$owners][$j][5],array_sum($result[$owners][$j][5]) - $result[$owners][$j][5][1] - $result[$owners][$j][5][2] - $result[$owners][$j][5][3]);



                //公房出售 = 出售 + 房改
                $result[$owners][$j][6][1] = $changeSaledata[$owners][2][$j]['change_rents'] + $changedata[$owners][2][$j][5]['change_rents'];
                $result[$owners][$j][6][2] = 0;
                $result[$owners][$j][6][3] = 0;
                $result[$owners][$j][6][4] = 0.4 * $result[$owners][$j][6][1];
                $result[$owners][$j][6][5] = 0.4 * $result[$owners][$j][6][2];
                $result[$owners][$j][6][6] = 0.4 * $result[$owners][$j][6][3];
                $result[$owners][$j][6][7] = 0.6 * $result[$owners][$j][6][1];
                $result[$owners][$j][6][8] = 0.6 * $result[$owners][$j][6][2];
                $result[$owners][$j][6][9] = 0.6 * $result[$owners][$j][6][3];
                $result[$owners][$j][6][10] = $changeSaledata[$owners][3][$j]['change_rents'] + $changedata[$owners][3][$j][5]['change_rents'];
                $result[$owners][$j][6][11] = 0;
                $result[$owners][$j][6][12] = 0;
                $result[$owners][$j][6][13] = $changeSaledata[$owners][1][$j]['change_rents'] + $changedata[$owners][1][$j][5]['change_rents'];
                $result[$owners][$j][6][14] = 0;
                $result[$owners][$j][6][15] = 0;
                array_unshift($result[$owners][$j][6],array_sum($result[$owners][$j][6]) - $result[$owners][$j][6][1] - $result[$owners][$j][6][2] - $result[$owners][$j][6][3]);


                //管段调整 = 增加的 - 减少的
                $result[$owners][$j][7][1] = $changeGuanduanIncdata[$owners][2][$j]['change_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_rents'];
                $result[$owners][$j][7][2] = $changeGuanduanIncdata[$owners][2][$j]['change_month_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_month_rents'];
                $result[$owners][$j][7][3] = $changeGuanduanIncdata[$owners][2][$j]['change_year_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_year_rents'];
                $result[$owners][$j][7][4] = 0.4 * $result[$owners][$j][7][1];
                $result[$owners][$j][7][5] = 0.4 * $result[$owners][$j][7][2];
                $result[$owners][$j][7][6] = 0.4 * $result[$owners][$j][7][3];
                $result[$owners][$j][7][7] = 0.6 * $result[$owners][$j][7][1];
                $result[$owners][$j][7][8] = 0.6 * $result[$owners][$j][7][2];
                $result[$owners][$j][7][9] = 0.6 * $result[$owners][$j][7][3];
                $result[$owners][$j][7][10] = $changeGuanduanIncdata[$owners][3][$j]['change_rents'] - $changeGuanduanDecdata[$owners][3][$j]['change_rents'];
                $result[$owners][$j][7][11] = $changeGuanduanIncdata[$owners][3][$j]['change_month_rents'] - $changeGuanduanDecdata[$owners][3][$j]['change_month_rents'];
                $result[$owners][$j][7][12] = $changeGuanduanIncdata[$owners][3][$j]['change_year_rents'] - $changeGuanduanDecdata[$owners][3][$j]['change_year_rents'];
                $result[$owners][$j][7][13] = $changeGuanduanIncdata[$owners][1][$j]['change_rents'] - $changeGuanduanDecdata[$owners][1][$j]['change_rents'];
                $result[$owners][$j][7][14] = $changeGuanduanIncdata[$owners][1][$j]['change_month_rents'] - $changeGuanduanDecdata[$owners][1][$j]['change_month_rents'];
                $result[$owners][$j][7][15] = $changeGuanduanIncdata[$owners][1][$j]['change_year_rents'] - $changeGuanduanDecdata[$owners][1][$j]['change_year_rents'];
                array_unshift($result[$owners][$j][7],array_sum($result[$owners][$j][7]) - $result[$owners][$j][7][1] - $result[$owners][$j][7][2] - $result[$owners][$j][7][3]);


                //基数异动增减合计
                $result[$owners][$j][1][1] = $result[$owners][$j][2][1] + $result[$owners][$j][3][1] - $result[$owners][$j][4][1] + $result[$owners][$j][5][1] - $result[$owners][$j][6][1] + $result[$owners][$j][7][1];
                $result[$owners][$j][1][2] = $result[$owners][$j][2][2] + $result[$owners][$j][3][2] - $result[$owners][$j][4][2] + $result[$owners][$j][5][2] - $result[$owners][$j][6][2] + $result[$owners][$j][7][2];
                $result[$owners][$j][1][3] = $result[$owners][$j][2][3] + $result[$owners][$j][3][3] - $result[$owners][$j][4][3] + $result[$owners][$j][5][3] - $result[$owners][$j][6][3] + $result[$owners][$j][7][3];
                $result[$owners][$j][1][4] = 0.4 * $result[$owners][$j][1][1];
                $result[$owners][$j][1][5] = 0.4 * $result[$owners][$j][1][2];
                $result[$owners][$j][1][6] = 0.4 * $result[$owners][$j][1][3];
                $result[$owners][$j][1][7] = 0.6 * $result[$owners][$j][1][1];
                $result[$owners][$j][1][8] = 0.6 * $result[$owners][$j][1][2];
                $result[$owners][$j][1][9] = 0.6 * $result[$owners][$j][1][3];
                $result[$owners][$j][1][10] = $result[$owners][$j][2][10] + $result[$owners][$j][3][10] - $result[$owners][$j][4][10] + $result[$owners][$j][5][10] - $result[$owners][$j][6][10] + $result[$owners][$j][7][10];
                $result[$owners][$j][1][11] = $result[$owners][$j][2][11] + $result[$owners][$j][3][11] - $result[$owners][$j][4][11] + $result[$owners][$j][5][11] - $result[$owners][$j][6][11] + $result[$owners][$j][7][11];
                $result[$owners][$j][1][12] = $result[$owners][$j][2][12] + $result[$owners][$j][3][12] - $result[$owners][$j][4][12] + $result[$owners][$j][5][12] - $result[$owners][$j][6][12] + $result[$owners][$j][7][12];
                $result[$owners][$j][1][13] = $result[$owners][$j][2][13] + $result[$owners][$j][3][13] - $result[$owners][$j][4][13] + $result[$owners][$j][5][13] - $result[$owners][$j][6][13] + $result[$owners][$j][7][13];
                $result[$owners][$j][1][14] = $result[$owners][$j][2][14] + $result[$owners][$j][3][14] - $result[$owners][$j][4][14] + $result[$owners][$j][5][14] - $result[$owners][$j][6][14] + $result[$owners][$j][7][14];
                $result[$owners][$j][1][15] = $result[$owners][$j][2][15] + $result[$owners][$j][3][15] - $result[$owners][$j][4][15] + $result[$owners][$j][5][15] - $result[$owners][$j][6][15] + $result[$owners][$j][7][15];
                array_unshift($result[$owners][$j][1],array_sum($result[$owners][$j][1]) - $result[$owners][$j][1][1] - $result[$owners][$j][1][2] - $result[$owners][$j][1][3]);

                //规定租金那一行 = 上期结转 + 基数异动合计
                $result[$owners][$j][8][1] = $result[$owners][$j][0][1] + $result[$owners][$j][1][1];
                $result[$owners][$j][8][2] = $result[$owners][$j][0][2] + $result[$owners][$j][1][2];
                $result[$owners][$j][8][3] = $result[$owners][$j][0][3] + $result[$owners][$j][1][3];
                $result[$owners][$j][8][4] = 0.4 * $result[$owners][$j][8][1];
                $result[$owners][$j][8][5] = 0.4 * $result[$owners][$j][8][2];
                $result[$owners][$j][8][6] = 0.4 * $result[$owners][$j][8][3];
                $result[$owners][$j][8][7] = 0.6 * $result[$owners][$j][8][1];
                $result[$owners][$j][8][8] = 0.6 * $result[$owners][$j][8][2];
                $result[$owners][$j][8][9] = 0.6 * $result[$owners][$j][8][3];
                $result[$owners][$j][8][10] = $result[$owners][$j][0][10] + $result[$owners][$j][1][10];
                $result[$owners][$j][8][11] = $result[$owners][$j][0][11] + $result[$owners][$j][1][11];
                $result[$owners][$j][8][12] = $result[$owners][$j][0][12] + $result[$owners][$j][1][12];
                $result[$owners][$j][8][13] = $result[$owners][$j][0][13] + $result[$owners][$j][1][13];
                $result[$owners][$j][8][14] = $result[$owners][$j][0][14] + $result[$owners][$j][1][14];
                $result[$owners][$j][8][15] = $result[$owners][$j][0][15] + $result[$owners][$j][1][15];
                array_unshift($result[$owners][$j][8],array_sum($result[$owners][$j][8]) - $result[$owners][$j][8][1] - $result[$owners][$j][8][2] - $result[$owners][$j][8][3]);

                //规定租金那一行 = 上期结转 + 基数异动合计
                $result[$owners][$j][30][1] = $housedata[$owners][2][$j]['house_diff_rents'];
                $result[$owners][$j][30][2] = 0;
                $result[$owners][$j][30][3] = 0;
                $result[$owners][$j][30][4] = 0;
                $result[$owners][$j][30][5] = 0;
                $result[$owners][$j][30][6] = 0;
                $result[$owners][$j][30][7] = 0;
                $result[$owners][$j][30][8] = 0;
                $result[$owners][$j][30][9] = 0;
                $result[$owners][$j][30][10] = $housedata[$owners][3][$j]['house_diff_rents'];
                $result[$owners][$j][30][11] = 0;
                $result[$owners][$j][30][12] = 0;
                $result[$owners][$j][30][13] = $housedata[$owners][1][$j]['house_diff_rents'];
                $result[$owners][$j][30][14] = 0;
                $result[$owners][$j][30][15] = 0;
                array_unshift($result[$owners][$j][30],$housedata[$owners][2][$j]['house_diff_rents'] + $housedata[$owners][3][$j]['house_diff_rents'] + $housedata[$owners][1][$j]['house_diff_rents']);

                //规定租金那一行 = 上期结转 + 基数异动合计
                $result[$owners][$j][31][1] = $housedata[$owners][2][$j]['house_pump_rents'];
                $result[$owners][$j][31][2] = 0;
                $result[$owners][$j][31][3] = 0;
                $result[$owners][$j][31][4] = 0;
                $result[$owners][$j][31][5] = 0;
                $result[$owners][$j][31][6] = 0;
                $result[$owners][$j][31][7] = 0;
                $result[$owners][$j][31][8] = 0;
                $result[$owners][$j][31][9] = 0;
                $result[$owners][$j][31][10] = $housedata[$owners][3][$j]['house_pump_rents'];
                $result[$owners][$j][31][11] = 0;
                $result[$owners][$j][31][12] = 0;
                $result[$owners][$j][31][13] = $housedata[$owners][1][$j]['house_pump_rents'];
                $result[$owners][$j][31][14] = 0;
                $result[$owners][$j][31][15] = 0;
                array_unshift($result[$owners][$j][31],$housedata[$owners][2][$j]['house_pump_rents'] + $housedata[$owners][3][$j]['house_pump_rents'] + $housedata[$owners][1][$j]['house_pump_rents']);


                //减免，取得是异动里面的减免金额
                $result[$owners][$j][10][1] = $changejianmiandata[$owners][2][$j]['change_rents'];
                $result[$owners][$j][10][2] = 0;
                $result[$owners][$j][10][3] = 0;
                $result[$owners][$j][10][4] = 0.4 * $result[$owners][$j][10][1];
                $result[$owners][$j][10][5] = 0.4 * $result[$owners][$j][10][2];
                $result[$owners][$j][10][6] = 0.4 * $result[$owners][$j][10][3];
                $result[$owners][$j][10][7] = 0.6 * $result[$owners][$j][10][1];
                $result[$owners][$j][10][8] = 0.6 * $result[$owners][$j][10][2];
                $result[$owners][$j][10][9] = 0.6 * $result[$owners][$j][10][3];
                $result[$owners][$j][10][10] = $changejianmiandata[$owners][3][$j]['change_rents'];
                $result[$owners][$j][10][11] = 0;
                $result[$owners][$j][10][12] = 0;
                $result[$owners][$j][10][13] = $changejianmiandata[$owners][1][$j]['change_rents'];
                $result[$owners][$j][10][14] = 0;
                $result[$owners][$j][10][15] = 0;
                array_unshift($result[$owners][$j][10],array_sum($result[$owners][$j][10]) - $result[$owners][$j][10][1] - $result[$owners][$j][10][2] - $result[$owners][$j][10][3]);

                //空租异动ChangeType = 2
                $result[$owners][$j][11][1] = $changeNoBasedata[$owners][2][$j][2]['change_rents'];
                $result[$owners][$j][11][2] = 0;
                $result[$owners][$j][11][3] = 0;
                $result[$owners][$j][11][4] = 0.4 * $result[$owners][$j][11][1];
                $result[$owners][$j][11][5] = 0.4 * $result[$owners][$j][11][2];
                $result[$owners][$j][11][6] = 0.4 * $result[$owners][$j][11][3];
                $result[$owners][$j][11][7] = 0.6 * $result[$owners][$j][11][1];
                $result[$owners][$j][11][8] = 0.6 * $result[$owners][$j][11][2];
                $result[$owners][$j][11][9] = 0.6 * $result[$owners][$j][11][3];
                $result[$owners][$j][11][10] = $changeNoBasedata[$owners][3][$j][2]['change_rents'];
                $result[$owners][$j][11][11] = 0;
                $result[$owners][$j][11][12] = 0;
                $result[$owners][$j][11][13] = $changeNoBasedata[$owners][1][$j][2]['change_rents'];
                $result[$owners][$j][11][14] = 0;
                $result[$owners][$j][11][15] = 0;
                array_unshift($result[$owners][$j][11],array_sum($result[$owners][$j][11]) - $result[$owners][$j][11][1] - $result[$owners][$j][11][2] - $result[$owners][$j][11][3]);

                //暂停计租异动ChangeType = 3
                $result[$owners][$j][12][1] = $changeNoBasedata[$owners][2][$j][3]['change_rents'];
                $result[$owners][$j][12][2] = 0;
                $result[$owners][$j][12][3] = 0;
                $result[$owners][$j][12][4] = 0.4 * $result[$owners][$j][12][1];
                $result[$owners][$j][12][5] = 0.4 * $result[$owners][$j][12][2];
                $result[$owners][$j][12][6] = 0.4 * $result[$owners][$j][12][3];
                $result[$owners][$j][12][7] = 0.6 * $result[$owners][$j][12][1];
                $result[$owners][$j][12][8] = 0.6 * $result[$owners][$j][12][2];
                $result[$owners][$j][12][9] = 0.6 * $result[$owners][$j][12][3];
                $result[$owners][$j][12][10] = $changeNoBasedata[$owners][3][$j][3]['change_rents'];
                $result[$owners][$j][12][11] = 0;
                $result[$owners][$j][12][12] = 0;
                $result[$owners][$j][12][13] = $changeNoBasedata[$owners][1][$j][3]['change_rents'];
                $result[$owners][$j][12][14] = 0;
                $result[$owners][$j][12][15] = 0;
                array_unshift($result[$owners][$j][12],array_sum($result[$owners][$j][12]) - $result[$owners][$j][12][1] - $result[$owners][$j][12][2] - $result[$owners][$j][12][3]);

                //政策减免ChangeType = 3
                $result[$owners][$j][14][1] = $changeZhengcedata[$owners][2][$j]['change_rents'];
                $result[$owners][$j][14][2] = 0;
                $result[$owners][$j][14][3] = 0;
                $result[$owners][$j][14][4] = 0.4 * $result[$owners][$j][14][1];
                $result[$owners][$j][14][5] = 0.4 * $result[$owners][$j][14][2];
                $result[$owners][$j][14][6] = 0.4 * $result[$owners][$j][14][3];
                $result[$owners][$j][14][7] = 0.6 * $result[$owners][$j][14][1];
                $result[$owners][$j][14][8] = 0.6 * $result[$owners][$j][14][2];
                $result[$owners][$j][14][9] = 0.6 * $result[$owners][$j][14][3];
                $result[$owners][$j][14][10] = $changeZhengcedata[$owners][3][$j]['change_rents'];
                $result[$owners][$j][14][11] = 0;
                $result[$owners][$j][14][12] = 0;
                $result[$owners][$j][14][13] = $changeZhengcedata[$owners][1][$j]['change_rents'];
                $result[$owners][$j][14][14] = 0;
                $result[$owners][$j][14][15] = 0;
                array_unshift($result[$owners][$j][14],array_sum($result[$owners][$j][14]) - $result[$owners][$j][14][1] - $result[$owners][$j][14][2] - $result[$owners][$j][14][3]);

                //政策减免ChangeType = 3
                $result[$owners][$j][16][1] = $changeNoBasedata[$owners][2][$j][0]['change_rents'];
                $result[$owners][$j][16][2] = 0;
                $result[$owners][$j][16][3] = 0;
                $result[$owners][$j][16][4] = 0.4 * $result[$owners][$j][16][1];
                $result[$owners][$j][16][5] = 0.4 * $result[$owners][$j][16][2];
                $result[$owners][$j][16][6] = 0.4 * $result[$owners][$j][16][3];
                $result[$owners][$j][16][7] = 0.6 * $result[$owners][$j][16][1];
                $result[$owners][$j][16][8] = 0.6 * $result[$owners][$j][16][2];
                $result[$owners][$j][16][9] = 0.6 * $result[$owners][$j][16][3];
                $result[$owners][$j][16][10] = $changeNoBasedata[$owners][3][$j][0]['change_rents'];
                $result[$owners][$j][16][11] = 0;
                $result[$owners][$j][16][12] = 0;
                $result[$owners][$j][16][13] = $changeNoBasedata[$owners][1][$j][0]['change_rents'];
                $result[$owners][$j][16][14] = 0;
                $result[$owners][$j][16][15] = 0;
                array_unshift($result[$owners][$j][16],array_sum($result[$owners][$j][16]) - $result[$owners][$j][16][1] - $result[$owners][$j][16][2] - $result[$owners][$j][16][3]);

                //陈欠核销ChangeType = 4
                $result[$owners][$j][15][1] = $changeNoBasedata[$owners][2][$j][0]['change_rents'];
                $result[$owners][$j][15][2] = $changeHeXiaodata[$owners][2][$j]['change_month_rents'];
                $result[$owners][$j][15][3] = $changeHeXiaodata[$owners][2][$j]['change_year_rents'];
                $result[$owners][$j][15][4] = 0.4 * $result[$owners][$j][15][1];
                $result[$owners][$j][15][5] = 0.4 * $result[$owners][$j][15][2];
                $result[$owners][$j][15][6] = 0.4 * $result[$owners][$j][15][3];
                $result[$owners][$j][15][7] = 0.6 * $result[$owners][$j][15][1];
                $result[$owners][$j][15][8] = 0.6 * $result[$owners][$j][15][2];
                $result[$owners][$j][15][9] = 0.6 * $result[$owners][$j][15][3];
                $result[$owners][$j][15][10] = $changeNoBasedata[$owners][3][$j][0]['change_rents'];
                $result[$owners][$j][15][11] = $changeHeXiaodata[$owners][3][$j]['change_month_rents'];
                $result[$owners][$j][15][12] = $changeHeXiaodata[$owners][3][$j]['change_year_rents'];
                $result[$owners][$j][15][13] = $changeNoBasedata[$owners][1][$j][0]['change_rents'];
                $result[$owners][$j][15][14] = $changeHeXiaodata[$owners][1][$j]['change_month_rents'];
                $result[$owners][$j][15][15] = $changeHeXiaodata[$owners][1][$j]['change_year_rents'];
                array_unshift($result[$owners][$j][15],array_sum($result[$owners][$j][15]) - $result[$owners][$j][15][1] - $result[$owners][$j][15][2] - $result[$owners][$j][15][3]);


                //非基数异动合计
                $result[$owners][$j][9][1] = $result[$owners][$j][10][1] + $result[$owners][$j][11][1] + $result[$owners][$j][12][1] + $result[$owners][$j][14][1] + $result[$owners][$j][15][1] - $result[$owners][$j][16][1];
                $result[$owners][$j][9][2] = $result[$owners][$j][10][2] + $result[$owners][$j][11][2] + $result[$owners][$j][12][2] + $result[$owners][$j][14][2] + $result[$owners][$j][15][2] - $result[$owners][$j][16][2];
                $result[$owners][$j][9][3] = $result[$owners][$j][10][3] + $result[$owners][$j][11][3] + $result[$owners][$j][12][3] + $result[$owners][$j][14][3] + $result[$owners][$j][15][3] - $result[$owners][$j][16][2];
                $result[$owners][$j][9][4] = 0.4 * $result[$owners][$j][9][1];
                $result[$owners][$j][9][5] = 0.4 * $result[$owners][$j][9][2];
                $result[$owners][$j][9][6] = 0.4 * $result[$owners][$j][9][3];
                $result[$owners][$j][9][7] = 0.6 * $result[$owners][$j][9][1];
                $result[$owners][$j][9][8] = 0.6 * $result[$owners][$j][9][2];
                $result[$owners][$j][9][9] = 0.6 * $result[$owners][$j][9][3];
                $result[$owners][$j][9][10] = $result[$owners][$j][10][10] + $result[$owners][$j][11][10] + $result[$owners][$j][12][10] + $result[$owners][$j][14][10] + $result[$owners][$j][15][10]- $result[$owners][$j][16][10];
                $result[$owners][$j][9][11] = $result[$owners][$j][10][11] + $result[$owners][$j][11][11] + $result[$owners][$j][12][11] + $result[$owners][$j][14][11] + $result[$owners][$j][15][11]- $result[$owners][$j][16][11];
                $result[$owners][$j][9][12] = $result[$owners][$j][10][12] + $result[$owners][$j][11][12] + $result[$owners][$j][12][12] + $result[$owners][$j][14][12] + $result[$owners][$j][15][12]- $result[$owners][$j][16][12];
                $result[$owners][$j][9][13] = $result[$owners][$j][10][13] + $result[$owners][$j][11][13] + $result[$owners][$j][12][13] + $result[$owners][$j][14][13] - $result[$owners][$j][16][13];
                $result[$owners][$j][9][14] = $result[$owners][$j][10][14] + $result[$owners][$j][11][14] + $result[$owners][$j][12][14] + $result[$owners][$j][14][14] + $result[$owners][$j][15][14]- $result[$owners][$j][16][14];
                $result[$owners][$j][9][15] = $result[$owners][$j][10][15] + $result[$owners][$j][11][15] + $result[$owners][$j][12][15] + $result[$owners][$j][14][15] + $result[$owners][$j][15][15]- $result[$owners][$j][16][15];
                array_unshift($result[$owners][$j][9],array_sum($result[$owners][$j][9]) - $result[$owners][$j][9][1] - $result[$owners][$j][9][2] - $result[$owners][$j][9][3]);

                //应缴租金那一行 = 规定租金那一行 + 非基数异动合计
                $result[$owners][$j][17][1] = bcsub($result[$owners][$j][8][1] , $result[$owners][$j][9][1],2);
                $result[$owners][$j][17][2] = bcsub($result[$owners][$j][8][2] , $result[$owners][$j][9][2],2);
                $result[$owners][$j][17][3] = bcsub($result[$owners][$j][8][3] , $result[$owners][$j][9][3],2);
                $result[$owners][$j][17][4] = 0.4 * $result[$owners][$j][17][1];
                $result[$owners][$j][17][5] = 0.4 * $result[$owners][$j][17][2];
                $result[$owners][$j][17][6] = 0.4 * $result[$owners][$j][17][3];
                $result[$owners][$j][17][7] = 0.6 * $result[$owners][$j][17][1];
                $result[$owners][$j][17][8] = 0.6 * $result[$owners][$j][17][2];
                $result[$owners][$j][17][9] = 0.6 * $result[$owners][$j][17][3];
                $result[$owners][$j][17][10] = bcsub($result[$owners][$j][8][10] , $result[$owners][$j][9][10],2);
                $result[$owners][$j][17][11] = bcsub($result[$owners][$j][8][11] , $result[$owners][$j][9][11],2);
                $result[$owners][$j][17][12] = bcsub($result[$owners][$j][8][12] , $result[$owners][$j][9][12],2);
                $result[$owners][$j][17][13] = bcsub($result[$owners][$j][8][13] , $result[$owners][$j][9][13],2);
                $result[$owners][$j][17][14] = bcsub($result[$owners][$j][8][14] , $result[$owners][$j][9][14],2);
                $result[$owners][$j][17][15] = bcsub($result[$owners][$j][8][15] , $result[$owners][$j][9][15],2);
                array_unshift($result[$owners][$j][17],$result[$owners][$j][8][0] - $result[$owners][$j][9][0]);

                //本月份实收租金 = 本月规租 - 本月订单欠租
                $result[$owners][$j][18][1] = $rentdata[$owners][2][$j]['rent_order_paids'];
                //以前月份实收租金
                $result[$owners][$j][18][2] = $rentOldMonthdata[$owners][2][$j]['pay_rents'];
                //以前年度实收租金
                $result[$owners][$j][18][3] = $rentOldYeardata[$owners][2][$j]['pay_rents'];
                $result[$owners][$j][18][4] = 0.4 * $result[$owners][$j][18][1];
                $result[$owners][$j][18][5] = 0.4 * $result[$owners][$j][18][2];
                $result[$owners][$j][18][6] = 0.4 * $result[$owners][$j][18][3];
                $result[$owners][$j][18][7] = 0.6 * $result[$owners][$j][18][1];
                $result[$owners][$j][18][8] = 0.6 * $result[$owners][$j][18][2];
                $result[$owners][$j][18][9] = 0.6 * $result[$owners][$j][18][3];
                $result[$owners][$j][18][10] = $rentdata[$owners][3][$j]['rent_order_paids'];
                $result[$owners][$j][18][11] = $rentOldMonthdata[$owners][3][$j]['pay_rents'];
                $result[$owners][$j][18][12] = $rentOldYeardata[$owners][3][$j]['pay_rents'];
                $result[$owners][$j][18][13] = $rentdata[$owners][1][$j]['rent_order_paids'];
                $result[$owners][$j][18][14] = $rentOldMonthdata[$owners][1][$j]['pay_rents'];
                $result[$owners][$j][18][15] = $rentOldYeardata[$owners][1][$j]['pay_rents'];
                array_unshift($result[$owners][$j][18],array_sum($result[$owners][$j][18]) - $result[$owners][$j][18][1] - $result[$owners][$j][18][2] - $result[$owners][$j][18][3]);

                // //本月份实收租金 = 本月规租 - 本月订单欠租
                // $result[$owners][$j][18][1] = bcsub($result[$owners][$j][17][1] , $rentdata[$owners][2][$j]['UnpaidRents'],2);
                // //以前月份实收租金
                // $result[$owners][$j][18][2] = $rentOldMonthdata[$owners][2][$j]['PayRents'];
                // //以前年度实收租金
                // $result[$owners][$j][18][3] = $rentOldYeardata[$owners][2][$j]['PayRents'];
                // $result[$owners][$j][18][4] = 0.4 * $result[$owners][$j][18][1];
                // $result[$owners][$j][18][5] = 0.4 * $result[$owners][$j][18][2];
                // $result[$owners][$j][18][6] = 0.4 * $result[$owners][$j][18][3];
                // $result[$owners][$j][18][7] = 0.6 * $result[$owners][$j][18][1];
                // $result[$owners][$j][18][8] = 0.6 * $result[$owners][$j][18][2];
                // $result[$owners][$j][18][9] = 0.6 * $result[$owners][$j][18][3];
                // $result[$owners][$j][18][10] = bcsub($result[$owners][$j][17][10] , $rentdata[$owners][3][$j]['UnpaidRents'],2);
                // $result[$owners][$j][18][11] = $rentOldMonthdata[$owners][3][$j]['PayRents'];
                // $result[$owners][$j][18][12] = $rentOldYeardata[$owners][3][$j]['PayRents'];
                // $result[$owners][$j][18][13] = bcsub($result[$owners][$j][17][13] , $rentdata[$owners][1][$j]['UnpaidRents'],2);
                // $result[$owners][$j][18][14] = $rentOldMonthdata[$owners][1][$j]['PayRents'];
                // $result[$owners][$j][18][15] = $rentOldYeardata[$owners][1][$j]['PayRents'];
                // array_unshift($result[$owners][$j][18],array_sum($result[$owners][$j][18]) - $result[$owners][$j][18][1] - $result[$owners][$j][18][2] - $result[$owners][$j][18][3]);

                //本月份实收累计 = 本月规租 + 本年度各月份的【本月实收租金】$temps
                //$result[$owners][$j][19][1] = $lastMonthData[$owners][$j][18][1] + $result[$owners][$j][18][1];  //暂时是这样的
                if(substr($cacheDate,4,2) != '01'){
                    //上个月的累计+这个月的实收 = 这个月的累计
                    $result[$owners][$j][19][1] = $lastMonthData[$owners][$j][19][1] + $result[$owners][$j][18][1];
                    //以前月份实收累计
                    $result[$owners][$j][19][2] = $rentOldTotalMonthdata[$owners][2][$j]['pay_rents'];
                    //以前年度实收累计
                    //$result[$owners][$j][19][3] = $lastMonthData[$owners][$j][18][3]+ $result[$owners][$j][18][3];
                    
                    $result[$owners][$j][19][3] = $rentOldTotalYeardata[$owners][2][$j]['pay_rents'];
                    $result[$owners][$j][19][4] = 0.4 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][5] = 0.4 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][6] = 0.4 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][7] = 0.6 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][8] = 0.6 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][9] = 0.6 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][10] = $lastMonthData[$owners][$j][19][10] + $result[$owners][$j][18][10];
                    $result[$owners][$j][19][11] = $rentOldTotalMonthdata[$owners][3][$j]['pay_rents'];
                    $result[$owners][$j][19][12] = $rentOldTotalYeardata[$owners][3][$j]['pay_rents'];
                    $result[$owners][$j][19][13] = $lastMonthData[$owners][$j][19][13] + $result[$owners][$j][18][13];
                    $result[$owners][$j][19][14] = $rentOldTotalMonthdata[$owners][1][$j]['pay_rents'];
                    $result[$owners][$j][19][15] = $rentOldTotalYeardata[$owners][1][$j]['pay_rents'];
                    array_unshift($result[$owners][$j][19],array_sum($result[$owners][$j][19]) - $result[$owners][$j][19][1] - $result[$owners][$j][19][2] - $result[$owners][$j][19][3]);
                }else{
                    //上个月的累计+这个月的实收 = 这个月的累计
                    $result[$owners][$j][19][1] = $result[$owners][$j][18][1];
                    //以前月份实收累计
                    $result[$owners][$j][19][2] = $rentOldTotalMonthdata[$owners][2][$j]['pay_rents'];
                    //以前年度实收累计
                    //$result[$owners][$j][19][3] = $lastMonthData[$owners][$j][18][3]+ $result[$owners][$j][18][3];
                    
                    $result[$owners][$j][19][3] = $result[$owners][$j][18][3];
                    $result[$owners][$j][19][4] = 0.4 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][5] = 0.4 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][6] = 0.4 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][7] = 0.6 * $result[$owners][$j][19][1];
                    $result[$owners][$j][19][8] = 0.6 * $result[$owners][$j][19][2];
                    $result[$owners][$j][19][9] = 0.6 * $result[$owners][$j][19][3];
                    $result[$owners][$j][19][10] = $result[$owners][$j][18][10];
                    $result[$owners][$j][19][11] = $rentOldTotalMonthdata[$owners][3][$j]['pay_rents'];
                    $result[$owners][$j][19][12] = $result[$owners][$j][18][12];
                    $result[$owners][$j][19][13] = $result[$owners][$j][18][13];
                    $result[$owners][$j][19][14] = $rentOldTotalMonthdata[$owners][1][$j]['pay_rents'];
                    $result[$owners][$j][19][15] = $result[$owners][$j][18][15];
                    array_unshift($result[$owners][$j][19],array_sum($result[$owners][$j][19]) - $result[$owners][$j][19][1] - $result[$owners][$j][19][2] - $result[$owners][$j][19][3]);
                }

                $result[$owners][$j][20][1] = bcsub($result[$owners][$j][17][1] , $result[$owners][$j][18][1],2);
                $result[$owners][$j][20][2] = bcsub($result[$owners][$j][17][2] , $result[$owners][$j][18][2],2);
                $result[$owners][$j][20][3] = bcsub($result[$owners][$j][17][3] , $result[$owners][$j][18][3],2);
                $result[$owners][$j][20][4] = 0.4 * $result[$owners][$j][20][1];
                $result[$owners][$j][20][5] = 0.4 * $result[$owners][$j][20][2];
                $result[$owners][$j][20][6] = 0.4 * $result[$owners][$j][20][3];
                $result[$owners][$j][20][7] = 0.6 * $result[$owners][$j][20][1];
                $result[$owners][$j][20][8] = 0.6 * $result[$owners][$j][20][2];
                $result[$owners][$j][20][9] = 0.6 * $result[$owners][$j][20][3];
                $result[$owners][$j][20][10] = bcsub($result[$owners][$j][17][10] , $result[$owners][$j][18][10],2);
                $result[$owners][$j][20][11] = bcsub($result[$owners][$j][17][11] , $result[$owners][$j][18][11],2);
                $result[$owners][$j][20][12] = bcsub($result[$owners][$j][17][12] , $result[$owners][$j][18][12],2);
                $result[$owners][$j][20][13] = bcsub($result[$owners][$j][17][13] , $result[$owners][$j][18][13],2);
                $result[$owners][$j][20][14] = bcsub($result[$owners][$j][17][14] , $result[$owners][$j][18][14],2);
                $result[$owners][$j][20][15] = bcsub($result[$owners][$j][17][15] , $result[$owners][$j][18][15],2);
                array_unshift($result[$owners][$j][20],array_sum($result[$owners][$j][20]) - $result[$owners][$j][20][1] - $result[$owners][$j][20][2] - $result[$owners][$j][20][3]);

                $result[$owners][$j][100][1] = $housedata[$owners][2][$j]['house_ids']; //工商总户数
                $result[$owners][$j][100][2] = $housedata[$owners][3][$j]['house_ids']; //党政总户数
                $result[$owners][$j][100][3] = $housedata[$owners][1][$j]['house_ids']; //民用总户数
            }
        }

        //第一步：处理市代托，市区代托，全部下的公司，紫阳，粮道的数据（注意只有所和公司才有市代托、市区代托、全部）
        $ownertypess = [1,2,3,5,7,10,11,12]; //市、区、代、自、托、市代托、市区代托、全部
        foreach ($ownertypess as $own) {

            for ($d = 3; $d >0; $d--) { //公司和所，从1到3（1公司，2紫阳，3粮道），注意顺序公司的数据由所加和得来，所以是3、2、1的顺序
                if($own < 10 && $d ==3){
                    $result[$own][$d] = array_merge_addss($result[$own][19],$result[$own][20],$result[$own][21],$result[$own][22],$result[$own][23],$result[$own][24],$result[$own][25],$result[$own][26],$result[$own][27],$result[$own][28],$result[$own][29],$result[$own][30],$result[$own][31],$result[$own][32],$result[$own][33]);
                }elseif($own < 10 && $d ==2){
                    $result[$own][$d] = array_merge_addss($result[$own][4],$result[$own][5],$result[$own][6],$result[$own][7],$result[$own][8],$result[$own][9],$result[$own][10],$result[$own][11],$result[$own][12],$result[$own][13],$result[$own][14],$result[$own][15],$result[$own][16],$result[$own][17],$result[$own][18]);
                }elseif($own < 10 && $d ==1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 10 && $d > 1){
                    $result[$own][$d] = array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]);
                }elseif($own == 10 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 11 && $d > 1){
                    $result[$own][$d] = array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]),$result[2][$d]);
                }elseif($own == 11 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 12 && $d > 1){
                    $result[$own][$d] = array_merge_add(array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[3][$d]),$result[7][$d]),$result[2][$d]),$result[5][$d]);
                }elseif($own == 12 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }

            }
        }
        //halt($result);
        return $result;
    }*/

}