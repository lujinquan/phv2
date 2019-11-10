<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\rent\model\RentRecycle as RentRecycleModel;
use app\common\model\Cparam as ParamModel;

class HouseReport extends Model
{
    public function makeHouseReport()
    {
        $instLst = Db::name('base_inst')->column('inst_id');
        $ownerLst = [1,2,3,5,6,7,10,11,12];
        
        for ($i = 1;$i < 6; $i++) {
            foreach ($ownerLst as $owner) {
                foreach ($instLst as $ins) {
                    $where = $belowWhere = [];
                    $where[] = ['house_status','eq',1];
                    $where[] = ['ban_owner_id','eq',$owner];
                    $where[] = ['ban_inst_id','in',config('inst_ids')[$ins]];
       
                    $belowWhere[] = ['house_status','eq',1];
                    $belowWhere[] = ['house_use_id','in', [1,2,3]];
                    $belowWhere[] = ['ban_owner_id','eq',$owner];
                    $belowWhere[] = ['ban_inst_id','in',config('inst_ids')[$ins]];
                    //halt($belowWhere);
                    $below = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->where($belowWhere)->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids'); //底部的户数统计

                    switch ($i) {
                        case 1:
                            $results[1][$owner][$ins] = $this->get_by_damage($owner,$ins);
                            break;
                        case 2:
                            $results[2][$owner][$ins] = $this->get_by_useNature($owner,$ins);
                            break;
                        case 3;
                            $results[3][$owner][$ins] = $this->get_by_institution($owner,$ins);
                            break;
                        case 4:
                            $results[4][$owner][$ins] = $this->get_by_year($owner,$ins);
                            break;
                        case 5:
                            $results[5][$owner][$ins] = $this->get_by_value($owner,$ins);
                            break;
                        default:  //默认按
                            break;
                    }
                }
            }
        }

        return $results;
    }

	public function index($type,$owner,$inst){
        //dump($owner);halt($inst);
        switch ($type) {
            case 1:
                $results = $this->get_by_damage($owner,$inst);
                break;
            case 2:
                $results = $this->get_by_useNature($owner,$inst);
                break;
            case 3;
                $results = $this->get_by_institution($owner,$inst);
                break;
            case 4:
                $results = $this->get_by_year($owner,$inst);
                break;
            case 5:
                $results = $this->get_by_value($owner,$inst);
                break;
            default:  //默认按
                break;
        }
        return $results;
	}

	public function get_by_damage($owner,$ins){

        $where = $belowWhere = [];

        if($owner < 10) {
            $where[] = ['ban_owner_id','eq',$owner]; 
        }elseif($owner == 10){
            $where[] = ['ban_owner_id','in',[1,3,7]];
        }elseif($owner == 11){
            $where[] = ['ban_owner_id','in',[1,2,3,7]];
        }elseif($owner == 12){
            $where[] = ['ban_owner_id','in',[1,2,3,5,6,7]];
        }
        $where[] = ['ban_inst_id','in',config('inst_ids')[$ins]];

        $below = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->where($belowWhere)->where([['house_status','eq',1]])->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids');; //底部的户数统计

//halt($below);

        //$k = 1 ,2 ,3 ,4 ,5 ,6 ,7 分别表示结构等级为 钢混 ，砖木三等 ，砖木二等 ，砖混一等 ，砖混二等 ，砖木一等 ，简易
        $structureTypes = array(1=>'钢混',4=>'砖混一等',5=>'砖混二等',6=>'砖木一等',3=>'砖木二等',2=>'砖木三等',7=>'简易');
        $q = 0;
        foreach($structureTypes as $k1 => $v1){
            $q++;
            //$i = 1 ,2 ,3 ,4 ,5 分别表示完损等级为 完好 ，基本 ，一般 ，严重 ，危险
            for($i = 1; $i<6; $i++){

                $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('(sum(ban_civil_num) + sum(ban_party_num) + sum(ban_career_num)) as ban_nums,(sum(ban_civil_area) + sum(ban_party_area) + sum(ban_career_area)) as ban_areas, sum(ban_career_area) as ban_career_areas')
                    ->where($where)
                    ->where([['ban_struct_id','eq',$k1],['ban_damage_id','eq',$i]])
                    ->find();  //计算每一个（结构等级，使用性质）的结果集

                foreach ($datas[$q][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }
                $datas[$q][$i]['struct_type_name'] = $v1;
            }
        }

        // 将$v5[0],用作计算左侧合计部分
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['ban_nums'] = $v5[1]['ban_nums'] + $v5[2]['ban_nums'] + $v5[3]['ban_nums'] + $v5[4]['ban_nums'] + $v5[5]['ban_nums'];
            $v5[0]['ban_areas'] = $v5[1]['ban_areas'] + $v5[2]['ban_areas'] + $v5[3]['ban_areas'] + $v5[4]['ban_areas'] + $v5[5]['ban_areas'];
            $v5[0]['ban_career_areas'] = $v5[1]['ban_career_areas'] + $v5[2]['ban_career_areas'] + $v5[3]['ban_career_areas'] + $v5[4]['ban_career_areas'] + $v5[5]['ban_career_areas'];
        }

        foreach ($datas as $k6 => $v6) {
            for($j = 0; $j <6; $j++){
                $total['banids_arr'][$j][] = $datas[$k6][$j]['ban_nums'];
                $total['ban_areas_arr'][$j][] = $datas[$k6][$j]['ban_areas'];
                $total['ban_career_areas'][$j][] = $datas[$k6][$j]['ban_career_areas'];
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
                    if($k9 == 'struct_type_name') continue;
                    $result[$k7][] = $v9;
                }
            }
            array_unshift($result[$k7] ,$v7[1]['struct_type_name']);
        }

        $result[0][0] = '合计';
        $result[0][1] = $total['banids_arr'][0];
        $result[0][2] = $total['ban_areas_arr'][0];
        $result[0][3] = $total['ban_career_areas'][0];
        $result[0][4] = $total['banids_arr'][1];
        $result[0][5] = $total['ban_areas_arr'][1];
        $result[0][6] = $total['ban_career_areas'][1];
        $result[0][7] = $total['banids_arr'][2];
        $result[0][8] = $total['ban_areas_arr'][2];
        $result[0][9] = $total['ban_career_areas'][2];
        $result[0][10] = $total['banids_arr'][3];
        $result[0][11] = $total['ban_areas_arr'][3];
        $result[0][12] = $total['ban_career_areas'][3];
        $result[0][13] = $total['banids_arr'][4];
        $result[0][14] = $total['ban_areas_arr'][4];
        $result[0][15] = $total['ban_career_areas'][4];
        $result[0][16] = $total['banids_arr'][5];
        $result[0][17] = $total['ban_areas_arr'][5];
        $result[0][18] = $total['ban_career_areas'][5];

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

    public function get_by_useNature($owner,$ins){  //右侧的顺序是住宅，企业，机关

        $where = $belowWhere = [];

        if($owner < 10) {
            $where[] = ['ban_owner_id','eq',$owner]; 
        }elseif($owner == 10){
            $where[] = ['ban_owner_id','in',[1,3,7]];
        }elseif($owner == 11){
            $where[] = ['ban_owner_id','in',[1,2,3,7]];
        }elseif($owner == 12){
            $where[] = ['ban_owner_id','in',[1,2,3,5,6,7]];
        }
        $where[] = ['ban_inst_id','in',config('inst_ids')[$ins]];

        $below = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->where($belowWhere)->where([['house_status','eq',1]])->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids');; //底部的户数统计

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
                        ->field('sum(ban_civil_num) as ban_nums , sum(ban_civil_area) as ban_areas,sum(ban_use_area) as  ban_use_areas, sum(ban_civil_rent) as ban_pre_rents ')
                        ->where($where)
                        ->where([['ban_struct_id','eq',$k1]])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                    //unset($datas[$k1][$i]['BanUseareas']);
                }elseif($i == 2){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(ban_career_num) as ban_nums , sum(ban_career_area) as ban_areas ,sum(ban_career_rent) as ban_pre_rents')
                        ->where($where)
                        ->where([['ban_struct_id','eq',$k1]])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }elseif($i == 3){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(ban_party_num) as ban_nums , sum(ban_party_area) as ban_areas ,sum(ban_party_rent) as ban_pre_rents')
                        ->where($where)
                        ->where([['ban_struct_id','eq',$k1]])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }

                foreach ($datas[$q][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$q][$i]['struct_type_name'] = $v1;

            }
        }
        // 将$v5[0],用作计算左侧合计部分
        //$totalTotalAreas = 0;
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['ban_nums'] = $v5[1]['ban_nums'] + $v5[2]['ban_nums'] + $v5[3]['ban_nums'];
            $v5[0]['ban_areas'] = $v5[1]['ban_areas'] + $v5[2]['ban_areas'] + $v5[3]['ban_areas'];
            $v5[0]['ban_pre_rents'] = $v5[1]['ban_pre_rents'] + $v5[2]['ban_pre_rents'] + $v5[3]['ban_pre_rents'];
        }

        foreach ($datas as $k6 => $v6) {

            for($j = 0; $j <4; $j++){
                $total['banids_arr'][$j][] = $datas[$k6][$j]['ban_nums'];
                $total['ban_areas_arr'][$j][] = $datas[$k6][$j]['ban_areas'];
                if($j == 1){
                    $total['ban_use_areas'][$j][] = $datas[$k6][$j]['ban_use_areas'];
                }

                $total['ban_pre_rents'][$j][] = $datas[$k6][$j]['ban_pre_rents'];
            }

        }
//halt($datas);
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
                        if($k8 == 'struct_type_name') continue;
                        $result[$k7][] = $v8;
                }

            }
            array_unshift($result[$k7] ,$v7[1]['struct_type_name']);

        }

        $result[0][0] = '合计';
        $result[0][1] = $total['banids_arr'][0];
        $result[0][2] = $total['ban_areas_arr'][0];
        $result[0][3] = $total['ban_pre_rents'][0];
        $result[0][4] = $total['banids_arr'][2];
        $result[0][5] = $total['ban_areas_arr'][2];
        $result[0][6] = $total['ban_pre_rents'][2];
        $result[0][7] = $total['banids_arr'][1];
        $result[0][8] = $total['ban_areas_arr'][1];
        $result[0][9] = $total['ban_use_areas'][1];
        $result[0][10] = $total['ban_pre_rents'][1];
        $result[0][11] = $total['banids_arr'][3];
        $result[0][12] = $total['ban_areas_arr'][3];
        $result[0][13] = $total['ban_pre_rents'][3];

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

    public function get_by_institution($owner,$ins){
        $where = $belowWhere = [];
        if($owner < 10) {
            $where[] = ['ban_owner_id','eq',$owner]; 
        }elseif($owner == 10){
            $where[] = ['ban_owner_id','in',[1,3,7]];
        }elseif($owner == 11){
            $where[] = ['ban_owner_id','in',[1,2,3,7]];
        }elseif($owner == 12){
            $where[] = ['ban_owner_id','in',[1,2,3,5,6,7]];
        }

        $below = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->where($where)->where([['house_status','eq',1],['ban_inst_id','in',config('inst_ids')[$ins]]])->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids');; //底部的户数统计
        $institutions = config('inst_check_names')[$ins];

        if(count($institutions) > 1){
            unset($institutions[$ins]);
        }
        
        //$k = 1 ,2 ,3 ,4 ,5 ,6 ,7 分别表示结构等级为 钢混 ，砖木三等 ，砖木二等 ，砖混一等 ，砖混二等 ，砖木一等 ，简易
        foreach($institutions as $k1 => $v1){

            $wheress = [];
            $wheress[] = ['ban_inst_id','in',config('inst_ids')[$k1]]; //halt($where);          
            for($i = 1; $i<4; $i++){ //$i = 1 ,2 ,3  分别表示使用性质为 住宅 ，企业 ，机关
                if ($i == 1) {
                    $datas[$k1][$i] = Db::name('ban') //根据使用性质和结构类型分类
                        ->field('sum(ban_civil_num) as ban_nums ,sum(ban_civil_holds) as ban_holds, sum(ban_civil_area) as ban_areas,sum(ban_use_area) as ban_use_areas,(sum(ban_civil_rent)+sum(ban_party_rent)+sum(ban_career_rent)) as ban_rents')
                        ->where($where)
                        ->where($wheress)
                        ->find();
                }elseif($i == 2){
                    $datas[$k1][$i] = Db::name('ban') //根据使用性质和结构类型分类
                        ->field('sum(ban_career_num) as ban_nums ,sum(ban_career_holds) as ban_holds, sum(ban_career_area) as ban_areas,sum(ban_use_area) as ban_use_areas,(sum(ban_civil_rent)+sum(ban_party_rent)+sum(ban_career_rent)) as ban_rents')
                        ->where($where)
                        ->where($wheress)
                        ->find();
                }elseif($i == 3){
                    $datas[$k1][$i] = Db::name('ban') //根据使用性质和结构类型分类
                        ->field('sum(ban_party_num) as ban_nums ,sum(ban_party_holds) as ban_holds, sum(ban_party_area) as ban_areas,sum(ban_use_area) as ban_use_areas,(sum(ban_civil_rent)+sum(ban_party_rent)+sum(ban_career_rent)) as ban_rents')
                        ->where($where)
                        ->where($wheress)
                        ->find();
                }
                //计算每一个（结构等级，使用性质）的结果集
                foreach ($datas[$k1][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }
                if($i != 1){  //只有住宅需要统计使用面积
                    unset($datas[$k1][$i]['ban_use_areas']);
                }
                $datas[$k1][$i]['inst_name'] = $v1;
            }   
        }


        // 将$v5[0],用作计算左侧合计部分
        $totalTotalAreas = 0;

        if(!isset($datas)){
            return array();
        }

        foreach ($datas as $k5 => &$v5) {
            $v5[0]['ban_nums'] = $v5[1]['ban_nums'] + $v5[2]['ban_nums'] + $v5[3]['ban_nums'];
            $v5[0]['ban_holds'] = $v5[1]['ban_holds'] + $v5[2]['ban_holds'] + $v5[3]['ban_holds'];
            $v5[0]['ban_areas'] = $v5[1]['ban_areas'] + $v5[2]['ban_areas'] + $v5[3]['ban_areas'];
            $v5[0]['ban_rents'] = $v5[1]['ban_rents'] + $v5[2]['ban_rents'] + $v5[3]['ban_rents'];

            if($v5[0]['ban_areas']){
                $datas[$k5][1]['percent'] = round($datas[$k5][1]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
                $datas[$k5][2]['percent'] = round($datas[$k5][2]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
                $datas[$k5][3]['percent'] = round($datas[$k5][3]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
            }else{
                $datas[$k5][1]['percent'] = 0;
                $datas[$k5][2]['percent'] = 0;
                $datas[$k5][3]['percent'] = 0;
            }


            $totalTotalAreas += $v5[0]['ban_areas'];
        }

        foreach ($datas as $k6 => $v6) {

            if($totalTotalAreas){
                $datas[$k6][0]['percent'] = round($datas[$k6][0]['ban_areas'] / $totalTotalAreas ,4) * 100;
            }else{
                $datas[$k6][0]['percent'] = 0;
            }

            for($j = 0; $j <4; $j++){
                //halt($datas[$k6][$j]);
                $total['ban_nums'][$j][] = $datas[$k6][$j]['ban_nums'];
                $total['ban_holds'][$j][] = $datas[$k6][$j]['ban_holds'];
                $total['ban_areas'][$j][] = $datas[$k6][$j]['ban_areas'];
                $total['percent'][$j][] = $datas[$k6][$j]['percent'];
                if($j == 1){ //名用多一个使用面积
                    $total['ban_use_areas'][$j][] = $datas[$k6][$j]['ban_use_areas'];
                }
                $total['ban_rents'][$j][] = $datas[$k6][$j]['ban_rents'];
                //转换下顺序
                $results[$k6][$j]['ban_nums'] = $datas[$k6][$j]['ban_nums'];
                $results[$k6][$j]['ban_holds'] = $datas[$k6][$j]['ban_holds'];
                $results[$k6][$j]['ban_areas'] = $datas[$k6][$j]['ban_areas'];
                $results[$k6][$j]['percent'] = $datas[$k6][$j]['percent'];
                //$results[$k6][$j]['Percent'] = round($datas[$k6][$j]['TotalAreas'] / $totalTotalAreas ,4) * 100;
                if($j == 1){ //名用多一个使用面积
                    $results[$k6][$j]['ban_use_areas'] = $datas[$k6][$j]['ban_use_areas'];
                }
                $results[$k6][$j]['ban_rents'] = $datas[$k6][$j]['ban_rents'];    
                if(isset($datas[$k6][$j]['inst_name'])){
                    $results[$k6][$j]['inst_name'] = $datas[$k6][$j]['inst_name'];
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
                    if($k9 == 'inst_name') continue;
                    $result[$k7][] = $v9;
                }
            }
            array_unshift($result[$k7] ,$v7[1]['inst_name']);
        }

        //halt($total);

        //halt($result);

        $result[0][0] = '合计';
        $result[0][1] = $total['ban_nums'][0];
        $result[0][2] = $total['ban_holds'][0];
        $result[0][3] = $total['ban_areas'][0];
        $result[0][4] = 100;
        $result[0][5] = $total['ban_rents'][0];
        $result[0][6] = $total['ban_nums'][1];
        $result[0][7] = $total['ban_holds'][1];
        $result[0][8] = $total['ban_areas'][1];
        $result[0][9] = $total['ban_areas'][0]?round($total['ban_areas'][1] / $total['ban_areas'][0] ,4) * 100:0;
        $result[0][10] = $total['ban_use_areas'][1];
        $result[0][11] = $total['ban_rents'][1];
        $result[0][12] = $total['ban_nums'][2];
        $result[0][13] = $total['ban_holds'][2];
        $result[0][14] = $total['ban_areas'][2];
        $result[0][15] = $total['ban_areas'][0]?round($total['ban_areas'][2] / $total['ban_areas'][0] ,4) * 100:0;
        $result[0][16] = $total['ban_rents'][2];
        $result[0][17] = $total['ban_nums'][3];
        $result[0][18] = $total['ban_holds'][3];
        $result[0][19] = $total['ban_areas'][3];
        $result[0][20] = $total['ban_areas'][0]?round($total['ban_areas'][3] / $total['ban_areas'][0] ,4) * 100:0;
        $result[0][21] = $total['ban_rents'][3];

        sort($result);
        $results['top'] = $result;
        $results['below'] = $below;
        //halt($result);
        return $results;
    }

    public function get_by_year($owner,$ins){

        $where = $belowWhere = [];

        if($owner < 10) {
            $where[] = ['ban_owner_id','eq',$owner]; 
        }elseif($owner == 10){
            $where[] = ['ban_owner_id','in',[1,3,7]];
        }elseif($owner == 11){
            $where[] = ['ban_owner_id','in',[1,2,3,7]];
        }elseif($owner == 12){
            $where[] = ['ban_owner_id','in',[1,2,3,5,6,7]];
        }

        $below = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->where($belowWhere)->where([['house_status','eq',1],['ban_inst_id','in',config('inst_ids')[$ins]]])->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids');; //底部的户数统计

        //below = Db::name('house')->where($wheres)->where('Status',1)->group('UseNature')->column('UseNature ,count(HouseID) as HouseIDS'); //底部的户数统计
        $institutions = config('inst_check_names')[$ins];

        //halt($wheres);
        $arr = ['1' => '1937年代' ,'2' => '40年代' ,'3' => '50年代' ,'4' => '60年代' ,'5' => '70年代' ,'6' =>'80年代以后'];

        foreach($arr as $k1 => $v1){
            $wheres = [];
            switch ($k1) {
                case '1':
                    $wheres[] = ['ban_build_year','elt',1939];
                    break;
                case '2':
                    $wheres[] = ['ban_build_year','between',[1940,1949]];
                    break;
                case '3':
                    $wheres[] = ['ban_build_year','between',[1950,1959]];
                    break;
                case '4':
                    $wheres[] = ['ban_build_year','between',[1960,1969]];
                    break;
                case '5':
                    $wheres[] = ['ban_build_year','between',[1970,1979]];
                    break;
                case '6':
                    $wheres[] = ['ban_build_year','egt',1980];
                    break;
            }
            //dump($wheres);
            //$i = 1 ,2 ,3 ,4 ,5 分别表示完损等级为 完好 ，基本 ，一般 ，严重 ，危险
            for($i = 1; $i<6; $i++){

                $datas[$k1][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('(sum(ban_civil_num)+sum(ban_party_num)+sum(ban_career_num)) as ban_nums ,sum(ban_holds) as house_holds_arr, (sum(ban_civil_area)+sum(ban_party_area)+sum(ban_career_area)) as ban_areas')
                    ->where($where)
                    ->where($wheres)
                    ->where([['ban_damage_id','eq',$i]])
                    ->find();  //计算每一个（建成年份，完损等级）的结果集

                foreach ($datas[$k1][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$k1][$i]['year_name'] = $v1;

            }
        }
//halt($datas);
        // 将$v5[0],用作计算左侧合计部分
        $totalTotalAreas = 0;
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['ban_nums'] = $v5[1]['ban_nums'] + $v5[2]['ban_nums'] + $v5[3]['ban_nums'] + $v5[4]['ban_nums'] + $v5[5]['ban_nums'];
            $v5[0]['house_holds_arr'] = $v5[1]['house_holds_arr'] + $v5[2]['house_holds_arr'] + $v5[3]['house_holds_arr'] + $v5[4]['house_holds_arr'] + $v5[5]['house_holds_arr'];
            $v5[0]['ban_areas'] = $v5[1]['ban_areas'] + $v5[2]['ban_areas'] + $v5[3]['ban_areas'] + $v5[4]['ban_areas'] + $v5[5]['ban_areas'];

            if($v5[0]['ban_areas']){
                $datas[$k5][1]['percent'] = round($datas[$k5][1]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
                $datas[$k5][2]['percent'] = round($datas[$k5][2]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
                $datas[$k5][3]['percent'] = round($datas[$k5][3]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
                $datas[$k5][4]['percent'] = round($datas[$k5][4]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
                $datas[$k5][5]['percent'] = round($datas[$k5][5]['ban_areas'] / $v5[0]['ban_areas'] ,4) * 100;
            }else{
                $datas[$k5][1]['percent'] = 0;
                $datas[$k5][2]['percent'] = 0;
                $datas[$k5][3]['percent'] = 0;
                $datas[$k5][4]['percent'] = 0;
                $datas[$k5][5]['percent'] = 0;
            }


            $totalTotalAreas += $v5[0]['ban_areas'];
        }

        foreach ($datas as $k6 => $v6) {

            if($totalTotalAreas){
                $datas[$k6][0]['percent'] = round($datas[$k6][0]['ban_areas'] / $totalTotalAreas ,4) * 100;
            }else{
                $datas[$k6][0]['percent'] = 0;
            }

            for($j = 0; $j <6; $j++){
                $total['ban_nums'][$j][] = $datas[$k6][$j]['ban_nums'];
                $total['house_holds_arr'][$j][] = $datas[$k6][$j]['house_holds_arr'];
                $total['ban_areas'][$j][] = $datas[$k6][$j]['ban_areas'];
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
                    if($k9 == 'year_name') continue;
                    $result[$k7][] = $v9;
                }
            }
            array_unshift($result[$k7] ,$v7[1]['year_name']);
        }

        $result[0][0] = '合计';
        $result[0][1] = $total['ban_nums'][0];
        $result[0][2] = $total['house_holds_arr'][0];
        $result[0][3] = $total['ban_areas'][0];
        $result[0][4] = 100;
        $result[0][5] = $total['ban_nums'][1];
        $result[0][6] = $total['house_holds_arr'][1];
        $result[0][7] = $total['ban_areas'][1];
        $result[0][8] = $totalTotalAreas?round($total['ban_areas'][1] / $totalTotalAreas ,4) * 100:0;
        $result[0][9] = $total['ban_nums'][2];
        $result[0][10] = $total['house_holds_arr'][2];
        $result[0][11] = $total['ban_areas'][2];
        $result[0][12] = $totalTotalAreas?round($total['ban_areas'][2] / $totalTotalAreas ,4) * 100:0;
        $result[0][13] = $total['ban_nums'][3];
        $result[0][14] = $total['house_holds_arr'][3];
        $result[0][15] = $total['ban_areas'][3];
        $result[0][16] = $totalTotalAreas?round($total['ban_areas'][3] / $totalTotalAreas ,4) * 100:0;
        $result[0][17] = $total['ban_nums'][4];
        $result[0][18] = $total['house_holds_arr'][4];
        $result[0][19] = $total['ban_areas'][4];
        $result[0][20] = $totalTotalAreas?round($total['ban_areas'][4] / $totalTotalAreas ,4) * 100:0;
        $result[0][21] = $total['ban_nums'][5];
        $result[0][22] = $total['house_holds_arr'][5];
        $result[0][23] = $total['ban_areas'][5];
        $result[0][24] = $totalTotalAreas?round($total['ban_areas'][5] / $totalTotalAreas ,4) * 100:0;
        //halt($result);

        sort($result);
        $results['top'] = $result;
        $results['below'] = $below;

        return $results;

    }

    public function get_by_value($owner,$ins){  //右侧的顺序是住宅，企业，机关

        $where = $belowWhere = [];

        if($owner < 10) {
            $where[] = ['ban_owner_id','eq',$owner]; 
        }elseif($owner == 10){
            $where[] = ['ban_owner_id','in',[1,3,7]];
        }elseif($owner == 11){
            $where[] = ['ban_owner_id','in',[1,2,3,7]];
        }elseif($owner == 12){
            $where[] = ['ban_owner_id','in',[1,2,3,5,6,7]];
        }
        $where[] = [['ban_inst_id','in',config('inst_ids')[$ins]]];

        $below = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->where($belowWhere)->where([['house_status','eq',1]])->group('house_use_id')->column('house_use_id ,count(house_id) as house_ids');; //底部的户数统计

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
                    ->field('sum(ban_civil_num) as ban_nums , sum(ban_civil_area) as ban_areas,sum(ban_use_area) as ban_use_areas ,sum(ban_civil_oprice) as ban_oprices')
                        ->where($where)
                        ->where([['ban_struct_id','eq',$k1]])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                    //unset($datas[$k1][$i]['BanUseareas']);
                }elseif($i == 2){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(ban_career_num) as ban_nums , sum(ban_career_area) as ban_areas ,sum(ban_career_oprice) as ban_oprices')
                        ->where($where)
                        ->where([['ban_struct_id','eq',$k1]])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }elseif($i == 3){
                    $datas[$q][$i] = Db::name('ban')       //根据使用性质和结构类型分类
                    ->field('sum(ban_party_num) as ban_nums , sum(ban_party_area) as ban_areas ,sum(ban_party_oprice) as ban_oprices')
                        ->where($where)
                        ->where([['ban_struct_id','eq',$k1]])
                        ->find();  //计算每一个（结构等级，使用性质）的结果集
                }

                foreach ($datas[$q][$i] as &$v2) {  //保证每个结果的值不为null ，避免报错
                    if(!$v2){$v2 = 0;}
                }

                $datas[$q][$i]['struct_name'] = $v1;

            }
        }
        // 将$v5[0],用作计算左侧合计部分
        //$totalTotalAreas = 0;
        foreach ($datas as $k5 => &$v5) {
            $v5[0]['ban_nums'] = $v5[1]['ban_nums'] + $v5[2]['ban_nums'] + $v5[3]['ban_nums'];
            $v5[0]['ban_areas'] = $v5[1]['ban_areas'] + $v5[2]['ban_areas'] + $v5[3]['ban_areas'];
            $v5[0]['ban_oprices'] = $v5[1]['ban_oprices'] + $v5[2]['ban_oprices'] + $v5[3]['ban_oprices'];
        }

        foreach ($datas as $k6 => $v6) {

            for($j = 0; $j <4; $j++){
                $total['ban_nums'][$j][] = $datas[$k6][$j]['ban_nums'];
                $total['ban_areas'][$j][] = $datas[$k6][$j]['ban_areas'];
                if($j == 1){
                    $total['ban_use_areas'][$j][] = $datas[$k6][$j]['ban_use_areas'];
                }

                $total['ban_oprices'][$j][] = $datas[$k6][$j]['ban_oprices'];
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
                    if($k8 == 'struct_name') continue;
                    $result[$k7][] = $v8;
                }

            }
            array_unshift($result[$k7] ,$v7[1]['struct_name']);

        }

        $result[0][0] = '合计';
        $result[0][1] = $total['ban_nums'][0];
        $result[0][2] = $total['ban_areas'][0];
        $result[0][3] = $total['ban_oprices'][0];
        $result[0][4] = $total['ban_nums'][2];
        $result[0][5] = $total['ban_areas'][2];
        $result[0][6] = $total['ban_oprices'][2];
        $result[0][7] = $total['ban_nums'][1];
        $result[0][8] = $total['ban_areas'][1];
        $result[0][9] = $total['ban_use_areas'][1];
        $result[0][10] = $total['ban_oprices'][1];
        $result[0][11] = $total['ban_nums'][3];
        $result[0][12] = $total['ban_areas'][3];
        $result[0][13] = $total['ban_oprices'][3];


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