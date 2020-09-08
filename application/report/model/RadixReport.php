<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\common\model\Cparam as ParamModel;

class RadixReport extends Model
{
	public function radix($cacheDate)
	{
		$cacheDate = str_replace('-','',$cacheDate);

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,sum(change_area) as change_areas ,sum(change_use_area) as change_use_areas ,sum(change_oprice) as change_oprices ,sum(change_ban_num) as change_ban_nums ,sum(change_house_num) as change_house_nums ,change_type')->group('use_id,owner_id,inst_id,change_type')
        ->where([['change_cancel_type','neq',1],['change_type','neq',1],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeGuanduanDecData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,sum(change_area) as change_areas ,sum(change_use_area) as change_use_areas ,sum(change_oprice) as change_oprices ,sum(change_ban_num) as change_ban_nums ,sum(change_house_num) as change_house_nums')->group('use_id,owner_id,inst_id')
        ->where([['change_type','eq',10],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();

        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeGuanduanIncData = Db::name('change_table')->field('use_id,owner_id,new_inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,sum(change_area) as change_areas ,sum(change_use_area) as change_use_areas ,sum(change_oprice) as change_oprices ,sum(change_ban_num) as change_ban_nums ,sum(change_house_num) as change_house_nums')->group('use_id,owner_id,new_inst_id')
        ->where([['change_type','eq',10],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();

        //重组为规定格式的实收累计收到的以前月数据$changeNoBaseData
        foreach($changeData as $k7 => $v7){
            $changedata[$v7['owner_id']][$v7['use_id']][$v7['inst_id']][$v7['change_type']] = [
                'change_rents' => $v7['change_rents'],
                'change_month_rents' => $v7['change_month_rents'],
                'change_year_rents' => $v7['change_year_rents'],
                'change_areas' => $v7['change_areas'],
                'change_use_areas' => $v7['change_use_areas'],
                'change_oprices' => $v7['change_oprices'],
                'change_ban_nums' => $v7['change_ban_nums'],
                'change_house_nums' => $v7['change_house_nums'],
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

        //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                for ($j=4;$j<34;$j++) {
                    for($k=1;$k<16;$k++){
                        if(!isset($changedata[$owner][$i][$j][$k])){
                            $changedata[$owner][$i][$j][$k] = [ 
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                                'change_areas' => 0,
                                'change_use_areas' => 0,
                                'change_oprices' => 0,
                                'change_ban_nums' => 0,
                                'change_house_nums' => 0,
                            ];
                        }
                        if(!isset($changeGuanduanDecdata[$owner][$i][$j])){
	                        $changeGuanduanDecdata[$owner][$i][$j] = [
	                            'change_rents' => 0,
	                            'change_month_rents' => 0,
	                            'change_year_rents' => 0,
	                            'change_areas' => 0,
                                'change_use_areas' => 0,
                                'change_oprices' => 0,
                                'change_ban_nums' => 0,
                                'change_house_nums' => 0,
	                        ];
	                    }
	                    if(!isset($changeGuanduanIncdata[$owner][$i][$j])){
	                        $changeGuanduanIncdata[$owner][$i][$j] = [
	                            'change_rents' => 0,
	                            'change_month_rents' => 0,
	                            'change_year_rents' => 0,
	                            'change_areas' => 0,
                                'change_use_areas' => 0,
                                'change_oprices' => 0,
                                'change_ban_nums' => 0,
                                'change_house_nums' => 0,
	                        ];
	                    }

                    }
                }
            }
        }
        $result = [];
        foreach ($ownertypes as $owners) { //处理市、区、代、自、托
            for ($j = 4; $j < 34; $j++) { //每个管段，从4开始……
                //新发租异动ChangeType = 7
                //$result[$owners][$j][1][0] = '新发租'; // 合计【栋数】

                $result[$owners][$j][1][1] = $changedata[$owners][2][$j][7]['change_ban_nums'] + $changedata[$owners][3][$j][7]['change_ban_nums'] + $changedata[$owners][1][$j][7]['change_ban_nums']; // 合计【栋数】
                $result[$owners][$j][1][2] = bcaddMerge([$changedata[$owners][2][$j][7]['change_rents'],$changedata[$owners][3][$j][7]['change_rents'],$changedata[$owners][1][$j][7]['change_rents']]); // 合计【规定租金】
                $result[$owners][$j][1][3] = bcaddMerge([$changedata[$owners][2][$j][7]['change_use_areas'],$changedata[$owners][3][$j][7]['change_use_areas'],$changedata[$owners][1][$j][7]['change_use_areas']]); // 合计【计租面积】
                $result[$owners][$j][1][4] = bcaddMerge([$changedata[$owners][2][$j][7]['change_areas'],$changedata[$owners][3][$j][7]['change_areas'],$changedata[$owners][1][$j][7]['change_areas']]); // 合计【建筑面积】
                $result[$owners][$j][1][5] = bcaddMerge([$changedata[$owners][2][$j][7]['change_oprices'],$changedata[$owners][3][$j][7]['change_oprices'],$changedata[$owners][1][$j][7]['change_oprices']]); // 合计【原价】
                $result[$owners][$j][1][6] = $changedata[$owners][2][$j][7]['change_house_nums'] + $changedata[$owners][3][$j][7]['change_house_nums'] + $changedata[$owners][1][$j][7]['change_house_nums']; // 合计【户数】

                $result[$owners][$j][1][7] = $changedata[$owners][2][$j][7]['change_ban_nums']; // 企业【栋数】
                $result[$owners][$j][1][8] = $changedata[$owners][2][$j][7]['change_rents']; // 企业【规定租金】
                $result[$owners][$j][1][9] = $changedata[$owners][2][$j][7]['change_use_areas']; // 企业【计租面积】
                $result[$owners][$j][1][10] = $changedata[$owners][2][$j][7]['change_areas']; // 企业【建筑面积】
                $result[$owners][$j][1][11] = $changedata[$owners][2][$j][7]['change_oprices']; // 企业【原价】
                $result[$owners][$j][1][12] = $changedata[$owners][2][$j][7]['change_house_nums']; // 企业【户数】

                $result[$owners][$j][1][13] = $changedata[$owners][3][$j][7]['change_ban_nums']; // 机关【栋数】
                $result[$owners][$j][1][14] = $changedata[$owners][3][$j][7]['change_rents']; // 机关【规定租金】
                $result[$owners][$j][1][15] = $changedata[$owners][3][$j][7]['change_use_areas']; // 机关【计租面积】
                $result[$owners][$j][1][16] = $changedata[$owners][3][$j][7]['change_areas']; // 机关【建筑面积】
                $result[$owners][$j][1][17] = $changedata[$owners][3][$j][7]['change_oprices']; // 机关【原价】
                $result[$owners][$j][1][18] = $changedata[$owners][3][$j][7]['change_house_nums']; // 机关【户数】

                $result[$owners][$j][1][19] = $changedata[$owners][1][$j][7]['change_ban_nums']; // 住宅【栋数】
                $result[$owners][$j][1][20] = $changedata[$owners][1][$j][7]['change_rents']; // 住宅【规定租金】
                $result[$owners][$j][1][21] = $changedata[$owners][1][$j][7]['change_use_areas']; // 住宅【计租面积】
                $result[$owners][$j][1][22] = $changedata[$owners][1][$j][7]['change_areas']; // 住宅【建筑面积】
                $result[$owners][$j][1][23] = $changedata[$owners][1][$j][7]['change_oprices']; // 住宅【原价】
                $result[$owners][$j][1][24] = $changedata[$owners][1][$j][7]['change_house_nums']; // 住宅【户数】

                //$result[$owners][$j][2][0] = '注销'; // 合计【栋数】

                $result[$owners][$j][2][1] = $changedata[$owners][2][$j][8]['change_ban_nums'] + $changedata[$owners][3][$j][8]['change_ban_nums'] + $changedata[$owners][1][$j][8]['change_ban_nums']; // 合计【栋数】
                $result[$owners][$j][2][2] = bcaddMerge([$changedata[$owners][2][$j][8]['change_rents'],$changedata[$owners][3][$j][8]['change_rents'],$changedata[$owners][1][$j][8]['change_rents']]); // 合计【规定租金】
                $result[$owners][$j][2][3] = bcaddMerge([$changedata[$owners][2][$j][8]['change_use_areas'],$changedata[$owners][3][$j][8]['change_use_areas'],$changedata[$owners][1][$j][8]['change_use_areas']]); // 合计【计租面积】
                $result[$owners][$j][2][4] = bcaddMerge([$changedata[$owners][2][$j][8]['change_areas'],$changedata[$owners][3][$j][8]['change_areas'],$changedata[$owners][1][$j][8]['change_areas']]); // 合计【建筑面积】
                $result[$owners][$j][2][5] = bcaddMerge([$changedata[$owners][2][$j][8]['change_oprices'],$changedata[$owners][3][$j][8]['change_oprices'],$changedata[$owners][1][$j][8]['change_oprices']]); // 合计【原价】
                $result[$owners][$j][2][6] = $changedata[$owners][2][$j][8]['change_house_nums'] + $changedata[$owners][3][$j][8]['change_house_nums'] + $changedata[$owners][1][$j][8]['change_house_nums']; // 合计【户数】

                $result[$owners][$j][2][7] = $changedata[$owners][2][$j][8]['change_ban_nums']; // 企业【栋数】
                $result[$owners][$j][2][8] = $changedata[$owners][2][$j][8]['change_rents']; // 企业【规定租金】
                $result[$owners][$j][2][9] = $changedata[$owners][2][$j][8]['change_use_areas']; // 企业【计租面积】
                $result[$owners][$j][2][10] = $changedata[$owners][2][$j][8]['change_areas']; // 企业【建筑面积】
                $result[$owners][$j][2][11] = $changedata[$owners][2][$j][8]['change_oprices']; // 企业【原价】
                $result[$owners][$j][2][12] = $changedata[$owners][2][$j][8]['change_house_nums']; // 企业【户数】

                $result[$owners][$j][2][13] = $changedata[$owners][3][$j][8]['change_ban_nums']; // 机关【栋数】
                $result[$owners][$j][2][14] = $changedata[$owners][3][$j][8]['change_rents']; // 机关【规定租金】
                $result[$owners][$j][2][15] = $changedata[$owners][3][$j][8]['change_use_areas']; // 机关【计租面积】
                $result[$owners][$j][2][16] = $changedata[$owners][3][$j][8]['change_areas']; // 机关【建筑面积】
                $result[$owners][$j][2][17] = $changedata[$owners][3][$j][8]['change_oprices']; // 机关【原价】
                $result[$owners][$j][2][18] = $changedata[$owners][3][$j][8]['change_house_nums']; // 机关【户数】

                $result[$owners][$j][2][19] = $changedata[$owners][1][$j][8]['change_ban_nums']; // 住宅【栋数】
                $result[$owners][$j][2][20] = $changedata[$owners][1][$j][8]['change_rents']; // 住宅【规定租金】
                $result[$owners][$j][2][21] = $changedata[$owners][1][$j][8]['change_use_areas']; // 住宅【计租面积】
                $result[$owners][$j][2][22] = $changedata[$owners][1][$j][8]['change_areas']; // 住宅【建筑面积】
                $result[$owners][$j][2][23] = $changedata[$owners][1][$j][8]['change_oprices']; // 住宅【原价】
                $result[$owners][$j][2][24] = $changedata[$owners][1][$j][8]['change_house_nums']; // 住宅【户数】

                //$result[$owners][$j][3][0] = '调整'; // 合计【栋数】

                $result[$owners][$j][3][1] = $changedata[$owners][2][$j][12]['change_ban_nums'] + $changedata[$owners][3][$j][12]['change_ban_nums'] + $changedata[$owners][1][$j][12]['change_ban_nums']; // 合计【栋数】
                $result[$owners][$j][3][2] = bcaddMerge([$changedata[$owners][2][$j][12]['change_rents'],$changedata[$owners][3][$j][12]['change_rents'],$changedata[$owners][1][$j][12]['change_rents']]); // 合计【规定租金】
                $result[$owners][$j][3][3] = bcaddMerge([$changedata[$owners][2][$j][12]['change_use_areas'],$changedata[$owners][3][$j][12]['change_use_areas'],$changedata[$owners][1][$j][12]['change_use_areas']]); // 合计【计租面积】
                $result[$owners][$j][3][4] = bcaddMerge([$changedata[$owners][2][$j][12]['change_areas'],$changedata[$owners][3][$j][12]['change_areas'],$changedata[$owners][1][$j][12]['change_areas']]); // 合计【建筑面积】
                $result[$owners][$j][3][5] = bcaddMerge([$changedata[$owners][2][$j][12]['change_oprices'],$changedata[$owners][3][$j][12]['change_oprices'],$changedata[$owners][1][$j][12]['change_oprices']]); // 合计【原价】
                $result[$owners][$j][3][6] = $changedata[$owners][2][$j][12]['change_house_nums'] + $changedata[$owners][3][$j][12]['change_house_nums'] + $changedata[$owners][1][$j][12]['change_house_nums']; // 合计【户数】

                $result[$owners][$j][3][7] = $changedata[$owners][2][$j][12]['change_ban_nums']; // 企业【栋数】
                $result[$owners][$j][3][8] = $changedata[$owners][2][$j][12]['change_rents']; // 企业【规定租金】
                $result[$owners][$j][3][9] = $changedata[$owners][2][$j][12]['change_use_areas']; // 企业【计租面积】
                $result[$owners][$j][3][10] = $changedata[$owners][2][$j][12]['change_areas']; // 企业【建筑面积】
                $result[$owners][$j][3][11] = $changedata[$owners][2][$j][12]['change_oprices']; // 企业【原价】
                $result[$owners][$j][3][12] = $changedata[$owners][2][$j][12]['change_house_nums']; // 企业【户数】

                $result[$owners][$j][3][13] = $changedata[$owners][3][$j][12]['change_ban_nums']; // 机关【栋数】
                $result[$owners][$j][3][14] = $changedata[$owners][3][$j][12]['change_rents']; // 机关【规定租金】
                $result[$owners][$j][3][15] = $changedata[$owners][3][$j][12]['change_use_areas']; // 机关【计租面积】
                $result[$owners][$j][3][16] = $changedata[$owners][3][$j][12]['change_areas']; // 机关【建筑面积】
                $result[$owners][$j][3][17] = $changedata[$owners][3][$j][12]['change_oprices']; // 机关【原价】
                $result[$owners][$j][3][18] = $changedata[$owners][3][$j][12]['change_house_nums']; // 机关【户数】

                $result[$owners][$j][3][19] = $changedata[$owners][1][$j][12]['change_ban_nums']; // 住宅【栋数】
                $result[$owners][$j][3][20] = $changedata[$owners][1][$j][12]['change_rents']; // 住宅【规定租金】
                $result[$owners][$j][3][21] = $changedata[$owners][1][$j][12]['change_use_areas']; // 住宅【计租面积】
                $result[$owners][$j][3][22] = $changedata[$owners][1][$j][12]['change_areas']; // 住宅【建筑面积】
                $result[$owners][$j][3][23] = $changedata[$owners][1][$j][12]['change_oprices']; // 住宅【原价】
                $result[$owners][$j][3][24] = $changedata[$owners][1][$j][12]['change_house_nums']; // 住宅【户数】


                $result[$owners][$j][4][1] = 0; // 合计【栋数】
                $result[$owners][$j][4][2] = 0; // 合计【规定租金】
                $result[$owners][$j][4][3] = 0; // 合计【计租面积】
                $result[$owners][$j][4][4] = 0; // 合计【建筑面积】
                $result[$owners][$j][4][5] = 0; // 合计【原价】
                $result[$owners][$j][4][6] = 0; // 合计【户数】

                $result[$owners][$j][4][7] = $changeGuanduanIncdata[$owners][2][$j]['change_ban_nums'] - $changeGuanduanDecdata[$owners][2][$j]['change_ban_nums']; // 企业【栋数】
                $result[$owners][$j][4][8] = $changeGuanduanIncdata[$owners][2][$j]['change_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_rents']; // 企业【规定租金】
                $result[$owners][$j][4][9] = $changeGuanduanIncdata[$owners][2][$j]['change_use_areas'] - $changeGuanduanDecdata[$owners][2][$j]['change_use_areas']; // 企业【计租面积】
                $result[$owners][$j][4][10] = $changeGuanduanIncdata[$owners][2][$j]['change_areas'] - $changeGuanduanDecdata[$owners][2][$j]['change_areas']; // 企业【建筑面积】
                $result[$owners][$j][4][11] = $changeGuanduanIncdata[$owners][2][$j]['change_oprices'] - $changeGuanduanDecdata[$owners][2][$j]['change_oprices']; // 企业【原价】
                $result[$owners][$j][4][12] = $changeGuanduanIncdata[$owners][2][$j]['change_house_nums'] - $changeGuanduanDecdata[$owners][2][$j]['change_house_nums']; // 企业【户数】

                $result[$owners][$j][4][13] = $changeGuanduanIncdata[$owners][2][$j]['change_ban_nums'] - $changeGuanduanDecdata[$owners][2][$j]['change_ban_nums']; // 机关【栋数】
                $result[$owners][$j][4][14] = $changeGuanduanIncdata[$owners][2][$j]['change_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_rents']; // 机关【规定租金】
                $result[$owners][$j][4][15] = $changeGuanduanIncdata[$owners][2][$j]['change_use_areas'] - $changeGuanduanDecdata[$owners][2][$j]['change_use_areas']; // 机关【计租面积】
                $result[$owners][$j][4][16] = $changeGuanduanIncdata[$owners][2][$j]['change_areas'] - $changeGuanduanDecdata[$owners][2][$j]['change_areas']; // 机关【建筑面积】
                $result[$owners][$j][4][17] = $changeGuanduanIncdata[$owners][2][$j]['change_oprices'] - $changeGuanduanDecdata[$owners][2][$j]['change_oprices']; // 机关【原价】
                $result[$owners][$j][4][18] = $changeGuanduanIncdata[$owners][2][$j]['change_house_nums'] - $changeGuanduanDecdata[$owners][2][$j]['change_house_nums']; // 机关【户数】

                $result[$owners][$j][4][19] = $changeGuanduanIncdata[$owners][2][$j]['change_ban_nums'] - $changeGuanduanDecdata[$owners][2][$j]['change_ban_nums']; // 住宅【栋数】
                $result[$owners][$j][4][20] = $changeGuanduanIncdata[$owners][2][$j]['change_rents'] - $changeGuanduanDecdata[$owners][2][$j]['change_rents']; // 住宅【规定租金】
                $result[$owners][$j][4][21] = $changeGuanduanIncdata[$owners][2][$j]['change_use_areas'] - $changeGuanduanDecdata[$owners][2][$j]['change_use_areas']; // 住宅【计租面积】
                $result[$owners][$j][4][22] = $changeGuanduanIncdata[$owners][2][$j]['change_areas'] - $changeGuanduanDecdata[$owners][2][$j]['change_areas']; // 住宅【建筑面积】
                $result[$owners][$j][4][23] = $changeGuanduanIncdata[$owners][2][$j]['change_oprices'] - $changeGuanduanDecdata[$owners][2][$j]['change_oprices']; // 住宅【原价】
                $result[$owners][$j][4][24] = $changeGuanduanIncdata[$owners][2][$j]['change_house_nums'] - $changeGuanduanDecdata[$owners][2][$j]['change_house_nums']; // 住宅【户数】
            }
        }
        //halt($result[1][28]);
        //$this->assign('data',$result[5][28]);
//halt($result);
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
        foreach ($result as &$s) {
            foreach ($s as &$p) {
                foreach ($p as $u => &$l) {
            
                    foreach ($l as &$t) {
                        $t = floatval($t);
                        if($t == 0){
                            $t = '';
                        }
                    }
                    if($u == 1){
                    	$l[0] = '新发租';
                    }elseif($u == 2){
                    	$l[0] = '注销';
                    }elseif($u == 3){
                    	$l[0] = '调整';
                    }elseif($u == 4){
                    	$l[0] = '管段调整';
                    }
                    
                }
            }
        }

        return $result;


	}
}