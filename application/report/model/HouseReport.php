<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\rent\model\RentRecycle as RentRecycleModel;
use app\common\model\Cparam as ParamModel;

class HouseReport extends Model
{
	public function makeHouseReport($cacheDate){
		$instLst = Db::name('institution')->column('id');
        $ownerLst = [1,2,3,5,6,7];
        //halt($where);
        for ($i = 1;$i < 6; $i++) {

            $belowWhere = array();

            $where['house_status'] = array('eq', 1);
            $belowWhere['house_status'] = array('eq', 1);
            $belowWhere['house_use_id'] = array('in', [1,2,3]);
            $where['ban_owner_id'] = array('eq', $owner);
            $belowWhere['ban_owner_id'] = array('eq', $owner);
            //$belowWhere['InstitutionID'] = $where['TubulationID'];


            $below = Db::name('house')->where($belowWhere)->group('UseNature')->column('house_use_id ,count(house_id) as house_ids'); //底部的户数统计

            switch ($i) {
                case 1:
                    $results[1]['top'] = $this->get_by_damage($where);
                    $results[1]['below'] = $below;
                    break;
                // case 2:
                //     $results[2]['top'] = $this->get_by_useNature($where);
                //     $results[2]['below'] = $below;
                //     break;
                // case 3;
                //     $results[3]['top'] = $this->get_by_institution($where);
                //     $results[3]['below'] = $below;
                //     break;
                // case 4:
                //     $results[4]['top'] = $this->get_by_year($where);
                //     $results[4]['below'] = $below;
                //     break;
                // case 5:
                //     $results[5]['top'] = $this->get_by_value($where);
                //     $results[5]['below'] = $below;
                //     break;
                default:  //默认按
                    break;
            }
        }
        return $results;
	}

	public function get_by_damage($where){
        //halt($where);
        //$structureTypes = Db::name('ban_structure_type')->column('id,StructureType');

        if ($where['ban_owner_id'] == 10) {
            $where['ban_owner_idban_owner_id'] = array('in',[1,3,7]);
            $wheres['ban_owner_id'] = array('in',[1,3,7]);
        } elseif($where['ban_owner_id'] < 10){
            $wheres['ban_owner_id'] = $where['ban_owner_id'];
        }elseif($where['ban_owner_id'] == 11){
            $where['ban_owner_id'] = array('in',[1,2,3,7]);
            $wheres['ban_owner_id'] = array('in',[1,2,3,7]);
        }elseif($where['ban_owner_id'] == 12){
            $where['ban_owner_id'] = array('in',[1,2,3,5,6,7]);
            $wheres['ban_owner_id'] = array('in',[1,2,3,5,6,7]);
        }

        if(isset($where['TubulationID'])){
            $wheres['InstitutionID'] = $where['TubulationID'];
        }
        if(isset($where['InstitutionID'])){
            $wheres['InstitutionPID'] = $where['InstitutionID'];
        }

        //$wheres['InstitutionID'] = isset($where['TubulationID'])?$where['TubulationID']:$where['InstitutionID'];
        //halt($wheres);
        $below = Db::name('house')->where($wheres)->where('Status',1)->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids'); //底部的户数统计

        //$k = 1 ,2 ,3 ,4 ,5 ,6 ,7 分别表示结构等级为 钢混 ，砖木三等 ，砖木二等 ，砖混一等 ，砖混二等 ，砖木一等 ，简易

        $structureTypes = array(1=>'钢混',4=>'砖混一等',5=>'砖混二等',6=>'砖木一等',3=>'砖木二等',2=>'砖木三等',7=>'简易');
        $q = 0;
        foreach($structureTypes as $k1 => $v1){
            $q++;
            //$i = 1 ,2 ,3 ,4 ,5 分别表示完损等级为 完好 ，基本 ，一般 ，严重 ，危险
            for($i = 1; $i<6; $i++){

                $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(TotalNum) as BanIDS , sum(TotalArea) as TotalAreas ,sum(EnterpriseArea) as EnterpriseAreas')
                    ->where($where)
                    ->where(['StructureType'=>$k1,'DamageGrade'=>$i])
                    ->find();  //计算每一个（结构等级，使用性质）的结果集

                foreach ($datas[$q][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$q][$i]['StructureTypeName'] = $v1;
                //$datas[$q][$i]['qizhong'] = $datas[$q][$i]['TotalAreas'] - $datas[$q][$i]['CivilAreas'];

                //unset($datas[$q][$i]['CivilAreas']);

            }
        }
        // 将$v5[0],用作计算左侧合计部分

        foreach ($datas as $k5 => &$v5) {
            $v5[0]['BanIDS'] = $v5[1]['BanIDS'] + $v5[2]['BanIDS'] + $v5[3]['BanIDS'] + $v5[4]['BanIDS'] + $v5[5]['BanIDS'];
            $v5[0]['TotalAreas'] = $v5[1]['TotalAreas'] + $v5[2]['TotalAreas'] + $v5[3]['TotalAreas'] + $v5[4]['TotalAreas'] + $v5[5]['TotalAreas'];
            $v5[0]['EnterpriseAreas'] = $v5[1]['EnterpriseAreas'] + $v5[2]['EnterpriseAreas'] + $v5[3]['EnterpriseAreas'] + $v5[4]['EnterpriseAreas'] + $v5[5]['EnterpriseAreas'];
        }

        foreach ($datas as $k6 => $v6) {
            for($j = 0; $j <6; $j++){
                $total['banidsArr'][$j][] = $datas[$k6][$j]['BanIDS'];
                $total['areasArr'][$j][] = $datas[$k6][$j]['TotalAreas'];
                $total['EnterpriseAreas'][$j][] = $datas[$k6][$j]['EnterpriseAreas'];
            }
        }

        // $total为最下面的合计部分
        foreach ($total as $k3 => $v3) {  //最下面的合计
            foreach ($v3 as $k4 => $v4) {
                $total[$k3][$k4] = array_sum($v4);
            }
        }

        //将两个数组整合成一个数组，便于前端遍历显示
        foreach ($datas as $k7 => $v7) {
            ksort($v7);
            foreach ($v7 as $k8 => $v8) {
                foreach ($v8 as $k9 => $v9) {
                    if($k9 == 'StructureTypeName') continue;
                    $result[$k7][] = $v9;
                }
            }
            array_unshift($result[$k7] ,$v7[1]['StructureTypeName']);
        }

        $result[0][0] = '合计';
        $result[0][1] = $total['banidsArr'][0];
        $result[0][2] = $total['areasArr'][0];
        $result[0][3] = $total['EnterpriseAreas'][0];
        $result[0][4] = $total['banidsArr'][1];
        $result[0][5] = $total['areasArr'][1];
        $result[0][6] = $total['EnterpriseAreas'][1];
        $result[0][7] = $total['banidsArr'][2];
        $result[0][8] = $total['areasArr'][2];
        $result[0][9] = $total['EnterpriseAreas'][2];
        $result[0][10] = $total['banidsArr'][3];
        $result[0][11] = $total['areasArr'][3];
        $result[0][12] = $total['EnterpriseAreas'][3];
        $result[0][13] = $total['banidsArr'][4];
        $result[0][14] = $total['areasArr'][4];
        $result[0][15] = $total['EnterpriseAreas'][4];
        $result[0][16] = $total['banidsArr'][5];
        $result[0][17] = $total['areasArr'][5];
        $result[0][18] = $total['EnterpriseAreas'][5];

        foreach ($result as &$ree) {
            foreach ($ree as &$rev) {
                if($rev === 0 || $rev === 0.00 || $rev === '0.00'){
                    $rev = '';
                }
            }
        }
        //halt($result);

        $results['top'] = $result;
        $results['below'] = $below;
        return $results;
    }

    public function get_by_useNature($where){  //右侧的顺序是住宅，企业，机关

        if ($where['OwnerType'] == 10) {
            $where['OwnerType'] = array('in',[1,3,7]);
            $wheres['OwnerType'] = array('in',[1,3,7]);
        } elseif($where['OwnerType'] < 10){
            $wheres['OwnerType'] = $where['OwnerType'];
        }elseif($where['OwnerType'] == 11){
            $where['OwnerType'] = array('in',[1,2,3,7]);
            $wheres['OwnerType'] = array('in',[1,2,3,7]);
        }elseif($where['OwnerType'] == 12){
            $where['OwnerType'] = array('in',[1,2,3,5,6,7]);
            $wheres['OwnerType'] = array('in',[1,2,3,5,6,7]);
        }

        if(isset($where['TubulationID'])){
            $wheres['InstitutionID'] = $where['TubulationID'];
        }
        if(isset($where['InstitutionID'])){
            $wheres['InstitutionPID'] = $where['InstitutionID'];
        }
        $below = Db::name('house')->where($wheres)->where('Status',1)->group('UseNature')->column('UseNature ,count(HouseID) as HouseIDS'); //底部的户数统计

        $structureTypes = array(1=>'钢混',4=>'砖混一等',5=>'砖混二等',6=>'砖木一等',3=>'砖木二等',2=>'砖木三等',7=>'简易');
        $q = 0;
        $nArr = array(2,1,3);
        //$k = 1 ,2 ,3 ,4 ,5 ,6 ,7 分别表示结构等级为 钢混 ，砖木三等 ，砖木二等 ，砖混一等 ，砖混二等 ，砖木一等 ，简易
        foreach($structureTypes as $k1 => $v1){
            $q++;
            //$i = 1 ,2 ,3 分别表示使用性质为 住宅 ，企业 ，机关

            foreach($nArr as $i){

                if($i == 1){  //只有住宅需要统计使用面积
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                        ->field('sum(CivilNum) as BanIDS , sum(CivilArea) as TotalAreas,sum(BanUsearea) as  BanUseareas ,sum(CivilRent) as BanPrerents ')
                        ->where($where)
                        ->where(['StructureType'=>$k1])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                    //unset($datas[$k1][$i]['BanUseareas']);
                }elseif($i == 2){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(EnterpriseNum) as BanIDS , sum(EnterpriseArea) as TotalAreas ,sum(EnterpriseRent) as BanPrerents')
                        ->where($where)
                        ->where(['StructureType'=>$k1])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }elseif($i == 3){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(PartyNum) as BanIDS , sum(PartyArea) as TotalAreas ,sum(PartyRent) as BanPrerents')
                        ->where($where)
                        ->where(['StructureType'=>$k1])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }

                foreach ($datas[$q][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$q][$i]['StructureTypeName'] = $v1;

            }
        }
        // 将$v5[0],用作计算左侧合计部分
        //$totalTotalAreas = 0;
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['BanIDS'] = $v5[1]['BanIDS'] + $v5[2]['BanIDS'] + $v5[3]['BanIDS'];
            $v5[0]['TotalAreas'] = $v5[1]['TotalAreas'] + $v5[2]['TotalAreas'] + $v5[3]['TotalAreas'];
            $v5[0]['BanPrerents'] = $v5[1]['BanPrerents'] + $v5[2]['BanPrerents'] + $v5[3]['BanPrerents'];
        }

        foreach ($datas as $k6 => $v6) {

            for($j = 0; $j <4; $j++){
                $total['banidsArr'][$j][] = $datas[$k6][$j]['BanIDS'];
                $total['areasArr'][$j][] = $datas[$k6][$j]['TotalAreas'];
                if($j == 1){
                    $total['banUseareas'][$j][] = $datas[$k6][$j]['BanUseareas'];
                }

                $total['banPrerents'][$j][] = $datas[$k6][$j]['BanPrerents'];
            }

        }

        // $total为最下面的合计部分
        foreach ($total as $k3 => $v3) {  //最下面的合计
            foreach ($v3 as $k4 => $v4) {
                $total[$k3][$k4] = array_sum($v4);
            }
        }

        //将两个数组整合成一个数组，便于前端遍历显示
        foreach ($datas as $k7 => $v7) {
            $arr1 = array(0,2,1,3);
            foreach ($arr1 as $av) {
                $temp = $v7[$av];
                foreach ($temp as $k8 => $v8) {
                        if($k8 == 'StructureTypeName') continue;
                        $result[$k7][] = $v8;
                }

            }
            array_unshift($result[$k7] ,$v7[1]['StructureTypeName']);

        }

        $result[0][0] = '合计';
        $result[0][1] = $total['banidsArr'][0];
        $result[0][2] = $total['areasArr'][0];
        $result[0][3] = $total['banPrerents'][0];
        $result[0][4] = $total['banidsArr'][2];
        $result[0][5] = $total['areasArr'][2];
        $result[0][6] = $total['banPrerents'][2];
        $result[0][7] = $total['banidsArr'][1];
        $result[0][8] = $total['areasArr'][1];
        $result[0][9] = $total['banUseareas'][1];
        $result[0][10] = $total['banPrerents'][1];
        $result[0][11] = $total['banidsArr'][3];
        $result[0][12] = $total['areasArr'][3];
        $result[0][13] = $total['banPrerents'][3];

        foreach ($result as &$ree) {
            foreach ($ree as &$rev) {
                if($rev === 0 || $rev === 0.00 || $rev === '0.00'){
                    $rev = '';
                }
            }
        }

        $results['top'] = $result;
        $results['below'] = $below;

        return $results;
    }

    public function get_by_institution($where){

        //$wheres['OwnerType'] = $where['OwnerType'];
        if ($where['ban_owner_id'] == 10) {
            $where['ban_owner_id'] = array('in',[1,3,7]);
            $wheres['ban_owner_id'] = array('in',[1,3,7]);
        } elseif($where['ban_owner_id'] < 10){
            $wheres['ban_owner_id'] = $where['ban_owner_id'];
        }elseif($where['ban_owner_id'] == 11){
            $where['ban_owner_id'] = array('in',[1,2,3,7]);
            $wheres['ban_owner_id'] = array('in',[1,2,3,7]);
        }elseif($where['ban_owner_id'] == 12){
            $where['ban_owner_id'] = array('in',[1,2,3,5,6,7]);
            $wheres['ban_owner_id'] = array('in',[1,2,3,5,6,7]);
        }
        
        if(isset($where['TubulationID'])){
            $wheres['InstitutionID'] = $where['TubulationID'];
        }
        if(isset($where['InstitutionID'])){
            $wheres['InstitutionPID'] = $where['InstitutionID'];
        }
        $below = Db::name('house')->where($wheres)->where('Status',1)->group('UseNature')->column('UseNature ,count(HouseID) as HouseIDS'); //底部的户数统计


        if(isset($where['InstitutionID'])){
            $institutions = Db::name('institution')->where('pid','eq',$where['InstitutionID'])->column('id,Institution');
            unset($where['InstitutionID']);

        }elseif(isset($where['TubulationID'])){
            $institutions = Db::name('institution')->where('id','eq',$where['TubulationID'])->column('id,Institution');
            unset($where['TubulationID']);
            //dump($where);halt($institutions);
        }else{
            $institutions = Db::name('institution')->where('id','gt',3)->column('id,Institution');
        }

        //$k = 1 ,2 ,3 ,4 ,5 ,6 ,7 分别表示结构等级为 钢混 ，砖木三等 ，砖木二等 ，砖混一等 ，砖混二等 ，砖木一等 ，简易
        foreach($institutions as $k1 => $v1){
            $wheres = array();
            if($k1 < 4){
                $wheres['InstitutionID'] = array('eq' ,$k1);
            }else{
                $wheres['TubulationID'] = array('eq' ,$k1);
            }
//dump($where);dump($wheres);exit;
            //$i = 1 ,2 ,3  分别表示使用性质为 住宅 ，企业 ，机关
            for($i = 1; $i<4; $i++){

                if ($i == 1) {
                    $datas[$k1][$i] = Db::name('ban')//根据使用性质和结构类型分类
                    ->field('sum(CivilNum) as BanIDS ,sum(CivilHolds) as TotalHouseholds, sum(CivilArea) as TotalAreas,sum(BanUsearea) as BanUseareas,sum(Prerent) as BanPrerents')
                        ->where($where)
                        //->where(['UseNature' => $i])
                        ->where($wheres)
                        ->find();
                } elseif($i == 2){
                    $datas[$k1][$i] = Db::name('ban')//根据使用性质和结构类型分类
                    ->field('sum(EnterpriseNum) as BanIDS ,sum(EnterpriseHolds) as TotalHouseholds, sum(EnterpriseArea) as TotalAreas,sum(BanUsearea) as BanUseareas,sum(Prerent) as BanPrerents')
                        ->where($where)
                        //->where(['UseNature' => $i])
                        ->where($wheres)
                        ->find();
                }elseif($i == 3){
                    $datas[$k1][$i] = Db::name('ban')//根据使用性质和结构类型分类
                    ->field('sum(PartyNum) as BanIDS ,sum(PartyHolds) as TotalHouseholds, sum(PartyArea) as TotalAreas,sum(BanUsearea) as BanUseareas,sum(Prerent) as BanPrerents')
                        ->where($where)
                        //->where(['UseNature' => $i])
                        ->where($wheres)
                        ->find();
                }

//                $datas[$k1][$i] = Db::name('ban')//根据使用性质和结构类型分类
//                ->field('sum(BanID) as BanIDS ,sum(TotalHouseholds) as TotalHouseholds, sum(TotalArea) as TotalAreas,sum(BanUsearea) as BanUseareas,sum(Prerent) as BanPrerents')
//                    ->where($where)
//                    ->where(['UseNature' => $i])
//                    ->where($wheres)
//                    ->find();
                //计算每一个（结构等级，使用性质）的结果集

                foreach ($datas[$k1][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                if($i != 1){  //只有住宅需要统计使用面积
                    unset($datas[$k1][$i]['BanUseareas']);
                }

                $datas[$k1][$i]['InstitutionName'] = $v1;

            }
        }

        //halt($datas);

        // 将$v5[0],用作计算左侧合计部分
        $totalTotalAreas = 0;

        if(!isset($datas)){
            return array();
        }

        foreach ($datas as $k5 => &$v5) {
            $v5[0]['BanIDS'] = $v5[1]['BanIDS'] + $v5[2]['BanIDS'] + $v5[3]['BanIDS'];
            $v5[0]['TotalHouseholds'] = $v5[1]['TotalHouseholds'] + $v5[2]['TotalHouseholds'] + $v5[3]['TotalHouseholds'];
            $v5[0]['TotalAreas'] = $v5[1]['TotalAreas'] + $v5[2]['TotalAreas'] + $v5[3]['TotalAreas'];
            $v5[0]['BanPrerents'] = $v5[1]['BanPrerents'] + $v5[2]['BanPrerents'] + $v5[3]['BanPrerents'];

            if($v5[0]['TotalAreas']){
                $datas[$k5][1]['Percent'] = round($datas[$k5][1]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
                $datas[$k5][2]['Percent'] = round($datas[$k5][2]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
                $datas[$k5][3]['Percent'] = round($datas[$k5][3]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
            }else{
                $datas[$k5][1]['Percent'] = 0;
                $datas[$k5][2]['Percent'] = 0;
                $datas[$k5][3]['Percent'] = 0;
            }


            $totalTotalAreas += $v5[0]['TotalAreas'];
        }

        foreach ($datas as $k6 => $v6) {

            if($totalTotalAreas){
                $datas[$k6][0]['Percent'] = round($datas[$k6][0]['TotalAreas'] / $totalTotalAreas ,4) * 100;
            }else{
                $datas[$k6][0]['Percent'] = 0;
            }

            for($j = 0; $j <4; $j++){
                //halt($datas[$k6][$j]);
                $total['banidsArr'][$j][] = $datas[$k6][$j]['BanIDS'];
                $total['householdsArr'][$j][] = $datas[$k6][$j]['TotalHouseholds'];
                $total['areasArr'][$j][] = $datas[$k6][$j]['TotalAreas'];
                $total['Percent'][$j][] = $datas[$k6][$j]['Percent'];
                if($j == 1){ //名用多一个使用面积
                    $total['useAreas'][$j][] = $datas[$k6][$j]['BanUseareas'];
                }
                $total['banPrerents'][$j][] = $datas[$k6][$j]['BanPrerents'];
                //转换下顺序
                $results[$k6][$j]['BanIDS'] = $datas[$k6][$j]['BanIDS'];
                $results[$k6][$j]['TotalHouseholds'] = $datas[$k6][$j]['TotalHouseholds'];
                $results[$k6][$j]['TotalAreas'] = $datas[$k6][$j]['TotalAreas'];
                $results[$k6][$j]['Percent'] = $datas[$k6][$j]['Percent'];
                //$results[$k6][$j]['Percent'] = round($datas[$k6][$j]['TotalAreas'] / $totalTotalAreas ,4) * 100;
                if($j == 1){ //名用多一个使用面积
                    $results[$k6][$j]['BanUseareas'] = $datas[$k6][$j]['BanUseareas'];
                }
                $results[$k6][$j]['BanPrerents'] = $datas[$k6][$j]['BanPrerents'];    
                if(isset($datas[$k6][$j]['InstitutionName'])){
                    $results[$k6][$j]['InstitutionName'] = $datas[$k6][$j]['InstitutionName'];
                }
                

            }

        }

        // $total为最下面的合计部分
        foreach ($total as $k3 => $v3) {  //最下面的合计
            foreach ($v3 as $k4 => $v4) {
                $total[$k3][$k4] = array_sum($v4);
            }
        }

        //将两个数组整合成一个数组，便于前端遍历显示
        foreach ($results as $k7 => $v7) {
            ksort($v7);
            foreach ($v7 as $k8 => $v8) {
                foreach ($v8 as $k9 => $v9) {
                    if($k9 == 'InstitutionName') continue;
                    $result[$k7][] = $v9;
                }
            }
            array_unshift($result[$k7] ,$v7[1]['InstitutionName']);
        }

        //halt($total);

        //halt($result);

        $result[0][0] = '合计';
        $result[0][1] = $total['banidsArr'][0];
        $result[0][2] = $total['householdsArr'][0];
        $result[0][3] = $total['areasArr'][0];
        $result[0][4] = 100;
        $result[0][5] = $total['banPrerents'][0];
        $result[0][6] = $total['banidsArr'][1];
        $result[0][7] = $total['householdsArr'][1];
        $result[0][8] = $total['areasArr'][1];
        $result[0][9] = $total['areasArr'][0]?round($total['areasArr'][1] / $total['areasArr'][0] ,4) * 100:0;
        $result[0][10] = $total['useAreas'][1];
        $result[0][11] = $total['banPrerents'][1];
        $result[0][12] = $total['banidsArr'][2];
        $result[0][13] = $total['householdsArr'][2];
        $result[0][14] = $total['areasArr'][2];
        $result[0][15] = $total['areasArr'][0]?round($total['areasArr'][2] / $total['areasArr'][0] ,4) * 100:0;
        $result[0][16] = $total['banPrerents'][2];
        $result[0][17] = $total['banidsArr'][3];
        $result[0][18] = $total['householdsArr'][3];
        $result[0][19] = $total['areasArr'][3];
        $result[0][20] = $total['areasArr'][0]?round($total['areasArr'][3] / $total['areasArr'][0] ,4) * 100:0;
        $result[0][21] = $total['banPrerents'][3];

        sort($result);
        $results['top'] = $result;
        $results['below'] = $below;
        //halt($result);
        return $results;
    }

    public function get_by_year($where){

        //$wheress['OwnerType'] = $where['OwnerType'];
        if ($where['OwnerType'] == 10) {
            $where['OwnerType'] = array('in',[1,3,7]);
            $wheress['OwnerType'] = array('in',[1,3,7]);
        } elseif($where['OwnerType'] < 10){
            $wheress['OwnerType'] = $where['OwnerType'];
        }elseif($where['OwnerType'] == 11){
            $where['OwnerType'] = array('in',[1,2,3,7]);
            $wheress['OwnerType'] = array('in',[1,2,3,7]);
        }elseif($where['OwnerType'] == 12){
            $where['OwnerType'] = array('in',[1,2,3,5,6,7]);
            $wheress['OwnerType'] = array('in',[1,2,3,5,6,7]);
        }

        if(isset($where['TubulationID'])){
            $wheress['InstitutionID'] = $where['TubulationID'];
        }
        if(isset($where['InstitutionID'])){
            $wheress['InstitutionPID'] = $where['InstitutionID'];
        }
        
        $below = Db::name('house')->where($wheress)->where('Status',1)->group('UseNature')->column('UseNature ,count(HouseID) as HouseIDS'); //底部的户数统计
        //halt($wheres);
        $arr = ['1' => '1937年代' ,'2' => '40年代' ,'3' => '50年代' ,'4' => '60年代' ,'5' => '70年代' ,'6' =>'80年代以后'];
        foreach($arr as $k1 => $v1){
            switch ($k1) {
                case '1':
                    $wheres['BanYear'] = array('elt',1939);
                    break;
                case '2':
                    $wheres['BanYear'] = array('between',[1940,1949]);
                    break;
                case '3':
                    $wheres['BanYear'] = array('between',[1950,1959]);
                    break;
                case '4':
                    $wheres['BanYear'] = array('between',[1960,1969]);
                    break;
                case '5':
                    $wheres['BanYear'] = array('between',[1970,1979]);
                    break;
                case '6':
                    $wheres['BanYear'] = array('egt',1980);
                    break;
            }
            //$i = 1 ,2 ,3 ,4 ,5 分别表示完损等级为 完好 ，基本 ，一般 ，严重 ，危险
            for($i = 1; $i<6; $i++){

                $datas[$k1][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(TotalNum) as BanIDS ,sum(TotalHouseholds) as TotalHouseholds, sum(TotalArea) as TotalAreas')
                    ->where($where)
                    ->where($wheres)
                    ->where('DamageGrade',$i)
                    ->find();  //计算每一个（建成年份，完损等级）的结果集

                foreach ($datas[$k1][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$k1][$i]['YearName'] = $v1;

            }
        }

        // 将$v5[0],用作计算左侧合计部分
        $totalTotalAreas = 0;
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['BanIDS'] = $v5[1]['BanIDS'] + $v5[2]['BanIDS'] + $v5[3]['BanIDS'] + $v5[4]['BanIDS'] + $v5[5]['BanIDS'];
            $v5[0]['TotalHouseholds'] = $v5[1]['TotalHouseholds'] + $v5[2]['TotalHouseholds'] + $v5[3]['TotalHouseholds'] + $v5[4]['TotalHouseholds'] + $v5[5]['TotalHouseholds'];
            $v5[0]['TotalAreas'] = $v5[1]['TotalAreas'] + $v5[2]['TotalAreas'] + $v5[3]['TotalAreas'] + $v5[4]['TotalAreas'] + $v5[5]['TotalAreas'];

            if($v5[0]['TotalAreas']){
                $datas[$k5][1]['Percent'] = round($datas[$k5][1]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
                $datas[$k5][2]['Percent'] = round($datas[$k5][2]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
                $datas[$k5][3]['Percent'] = round($datas[$k5][3]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
                $datas[$k5][4]['Percent'] = round($datas[$k5][4]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
                $datas[$k5][5]['Percent'] = round($datas[$k5][5]['TotalAreas'] / $v5[0]['TotalAreas'] ,4) * 100;
            }else{
                $datas[$k5][1]['Percent'] = 0;
                $datas[$k5][2]['Percent'] = 0;
                $datas[$k5][3]['Percent'] = 0;
                $datas[$k5][4]['Percent'] = 0;
                $datas[$k5][5]['Percent'] = 0;
            }


            $totalTotalAreas += $v5[0]['TotalAreas'];
        }

        foreach ($datas as $k6 => $v6) {

            if($totalTotalAreas){
                $datas[$k6][0]['Percent'] = round($datas[$k6][0]['TotalAreas'] / $totalTotalAreas ,4) * 100;
            }else{
                $datas[$k6][0]['Percent'] = 0;
            }

            for($j = 0; $j <6; $j++){
                $total['banidsArr'][$j][] = $datas[$k6][$j]['BanIDS'];
                $total['householdsArr'][$j][] = $datas[$k6][$j]['TotalHouseholds'];
                $total['areasArr'][$j][] = $datas[$k6][$j]['TotalAreas'];
                //$total['Percent'][$j][] = $datas[$k6][$j]['Percent'];
            }

        }

        // $total为最下面的合计部分
        foreach ($total as $k3 => $v3) {  //最下面的合计
            foreach ($v3 as $k4 => $v4) {
                $total[$k3][$k4] = array_sum($v4);
            }
        }

        //将两个数组整合成一个数组，便于前端遍历显示
        foreach ($datas as $k7 => $v7) {
            ksort($v7);
            foreach ($v7 as $k8 => $v8) {
                foreach ($v8 as $k9 => $v9) {
                    if($k9 == 'YearName') continue;
                    $result[$k7][] = $v9;
                }
            }
            array_unshift($result[$k7] ,$v7[1]['YearName']);
        }

        $result[0][0] = '合计';
        $result[0][1] = $total['banidsArr'][0];
        $result[0][2] = $total['householdsArr'][0];
        $result[0][3] = $total['areasArr'][0];
        $result[0][4] = 100;
        $result[0][5] = $total['banidsArr'][1];
        $result[0][6] = $total['householdsArr'][1];
        $result[0][7] = $total['areasArr'][1];
        $result[0][8] = $totalTotalAreas?round($total['areasArr'][1] / $totalTotalAreas ,4) * 100:0;
        $result[0][9] = $total['banidsArr'][2];
        $result[0][10] = $total['householdsArr'][2];
        $result[0][11] = $total['areasArr'][2];
        $result[0][12] = $totalTotalAreas?round($total['areasArr'][2] / $totalTotalAreas ,4) * 100:0;
        $result[0][13] = $total['banidsArr'][3];
        $result[0][14] = $total['householdsArr'][3];
        $result[0][15] = $total['areasArr'][3];
        $result[0][16] = $totalTotalAreas?round($total['areasArr'][3] / $totalTotalAreas ,4) * 100:0;
        $result[0][17] = $total['banidsArr'][4];
        $result[0][18] = $total['householdsArr'][4];
        $result[0][19] = $total['areasArr'][4];
        $result[0][20] = $totalTotalAreas?round($total['areasArr'][4] / $totalTotalAreas ,4) * 100:0;
        $result[0][21] = $total['banidsArr'][5];
        $result[0][22] = $total['householdsArr'][5];
        $result[0][23] = $total['areasArr'][5];
        $result[0][24] = $totalTotalAreas?round($total['areasArr'][5] / $totalTotalAreas ,4) * 100:0;
        //halt($result);

        sort($result);
        $results['top'] = $result;
        $results['below'] = $below;

        return $results;

    }

    public function get_by_value($where){  //右侧的顺序是住宅，企业，机关

        if ($where['OwnerType'] == 10) {
            $where['OwnerType'] = array('in',[1,3,7]);
            $wheres['OwnerType'] = array('in',[1,3,7]);
        } elseif($where['OwnerType'] < 10){
            $wheres['OwnerType'] = $where['OwnerType'];
        }elseif($where['OwnerType'] == 11){
            $where['OwnerType'] = array('in',[1,2,3,7]);
            $wheres['OwnerType'] = array('in',[1,2,3,7]);
        }elseif($where['OwnerType'] == 12){
            $where['OwnerType'] = array('in',[1,2,3,5,6,7]);
            $wheres['OwnerType'] = array('in',[1,2,3,5,6,7]);
        }

        if(isset($where['TubulationID'])){
            $wheres['InstitutionID'] = $where['TubulationID'];
        }
        if(isset($where['InstitutionID'])){
            $wheres['InstitutionPID'] = $where['InstitutionID'];
        }
        if(!isset($wheres)){
            $wheres = 1;
        }
        //halt($wheres);
        $below = Db::name('house')->where($wheres)->where('Status',1)->group('UseNature')->column('UseNature ,count(HouseID) as HouseIDS'); //底部的户数统计

        $structureTypes = array(1=>'钢混',4=>'砖混一等',5=>'砖混二等',6=>'砖木一等',3=>'砖木二等',2=>'砖木三等',7=>'简易');
        $q = 0;
        $nArr = array(2,1,3);
        //$k = 1 ,2 ,3 ,4 ,5 ,6 ,7 分别表示结构等级为 钢混 ，砖木三等 ，砖木二等 ，砖混一等 ，砖混二等 ，砖木一等 ，简易
        foreach($structureTypes as $k1 => $v1){
            $q++;
            //$i = 1 ,2 ,3 分别表示使用性质为 住宅 ，企业 ，机关
            foreach($nArr as $i){

                if($i == 1){  //只有住宅需要统计使用面积
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(CivilNum) as BanIDS , sum(CivilArea) as TotalAreas,sum(BanUsearea) as  BanUseareas ,sum(CivilOprice) as TotalOprices')
                        ->where($where)
                        ->where(['StructureType'=>$k1])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                    //unset($datas[$k1][$i]['BanUseareas']);
                }elseif($i == 2){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(EnterpriseNum) as BanIDS , sum(EnterpriseArea) as TotalAreas ,sum(EnterpriseOprice) as TotalOprices')
                        ->where($where)
                        ->where(['StructureType'=>$k1])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }elseif($i == 3){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(PartyNum) as BanIDS , sum(PartyArea) as TotalAreas ,sum(PartyOprice) as TotalOprices')
                        ->where($where)
                        ->where(['StructureType'=>$k1])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }

                foreach ($datas[$q][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$q][$i]['StructureTypeName'] = $v1;

            }
        }
        // 将$v5[0],用作计算左侧合计部分
        //$totalTotalAreas = 0;
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['BanIDS'] = $v5[1]['BanIDS'] + $v5[2]['BanIDS'] + $v5[3]['BanIDS'];
            $v5[0]['TotalAreas'] = $v5[1]['TotalAreas'] + $v5[2]['TotalAreas'] + $v5[3]['TotalAreas'];
            $v5[0]['TotalOprices'] = $v5[1]['TotalOprices'] + $v5[2]['TotalOprices'] + $v5[3]['TotalOprices'];
        }

        foreach ($datas as $k6 => $v6) {

            for($j = 0; $j <4; $j++){
                $total['banidsArr'][$j][] = $datas[$k6][$j]['BanIDS'];
                $total['areasArr'][$j][] = $datas[$k6][$j]['TotalAreas'];
                if($j == 1){
                    $total['banUseareas'][$j][] = $datas[$k6][$j]['BanUseareas'];
                }

                $total['totalOprices'][$j][] = $datas[$k6][$j]['TotalOprices'];
            }

        }

        // $total为最下面的合计部分
        foreach ($total as $k3 => $v3) {  //最下面的合计
            foreach ($v3 as $k4 => $v4) {
                $total[$k3][$k4] = array_sum($v4);
            }
        }

        //将两个数组整合成一个数组，便于前端遍历显示
        foreach ($datas as $k7 => $v7) {
            $arr1 = array(0,2,1,3);
            foreach ($arr1 as $av) {
                $temp = $v7[$av];
                foreach ($temp as $k8 => $v8) {
                    if($k8 == 'StructureTypeName') continue;
                    $result[$k7][] = $v8;
                }

            }
            array_unshift($result[$k7] ,$v7[1]['StructureTypeName']);

        }

        $result[0][0] = '合计';
        $result[0][1] = $total['banidsArr'][0];
        $result[0][2] = $total['areasArr'][0];
        $result[0][3] = $total['totalOprices'][0];
        $result[0][4] = $total['banidsArr'][2];
        $result[0][5] = $total['areasArr'][2];
        $result[0][6] = $total['totalOprices'][2];
        $result[0][7] = $total['banidsArr'][1];
        $result[0][8] = $total['areasArr'][1];
        $result[0][9] = $total['banUseareas'][1];
        $result[0][10] = $total['totalOprices'][1];
        $result[0][11] = $total['banidsArr'][3];
        $result[0][12] = $total['areasArr'][3];
        $result[0][13] = $total['totalOprices'][3];

//halt($result);
        foreach ($result as &$ree) {
            foreach ($ree as &$rev) {
                if($rev === 0 || $rev === 0.00 || $rev === '0.00'){
                    $rev = '';
                }
            }
        }
        $results['top'] = $result;
        $results['below'] = $below;

        return $results;
    }
}