<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\common\model\Cparam as ParamModel;

class RadixReport extends Model
{
    // 基数异动统计
	public function radix($cacheDate)
	{   //$cacheDate = '2021-03';
		$cacheDate = str_replace('-','',$cacheDate);
//halt($cacheDate);
        //获取基数异动//房屋出售的挑出去,减免的挑出去
        $changeData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,sum(change_area) as change_areas ,sum(change_use_area) as change_use_areas ,sum(change_oprice) as change_oprices ,sum(change_ban_num) as change_ban_nums ,sum(change_house_num) as change_house_nums ,change_type')->group('use_id,owner_id,inst_id,change_type')
        ->where([['change_type','not in','1,8'],['order_date','eq',$cacheDate],['change_status','eq',1]])
        ->select();
//        halt($changeData);
        
        // 统计基数异动中的房屋出售的数据
        $changeZhuxiaoData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,sum(change_area) as change_areas ,sum(change_use_area) as change_use_areas ,sum(change_oprice) as change_oprices ,sum(change_ban_num) as change_ban_nums ,sum(change_house_num) as change_house_nums ,change_type')->group('use_id,owner_id,inst_id,change_type')
            ->where([['change_cancel_type','neq',1],['change_type','eq',8],['order_date','eq',$cacheDate],['change_status','eq',1]])
            ->select();
// halt(Db::name('change_table')->getLastSql());
        // 统计基数异动中的房屋出售的数据
        $changeChushouData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents ,sum(change_month_rent) as change_month_rents ,sum(change_year_rent) as change_year_rents ,sum(change_area) as change_areas ,sum(change_use_area) as change_use_areas ,sum(change_oprice) as change_oprices ,sum(change_ban_num) as change_ban_nums ,sum(change_house_num) as change_house_nums ,change_type')->group('use_id,owner_id,inst_id,change_type')
            ->where([['change_cancel_type','eq',1],['change_type','eq',8],['order_date','eq',$cacheDate],['change_status','eq',1]])
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

        foreach($changeZhuxiaoData as $k9 => $v9){
            $changeZhuxiaodata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']][$v9['change_type']] = [
                'change_rents' => $v9['change_rents'],
                'change_month_rents' => $v9['change_month_rents'],
                'change_year_rents' => $v9['change_year_rents'],
                'change_areas' => $v9['change_areas'],
                'change_use_areas' => $v9['change_use_areas'],
                'change_oprices' => $v9['change_oprices'],
                'change_ban_nums' => $v9['change_ban_nums'],
                'change_house_nums' => $v9['change_house_nums'],
            ];
        }

        foreach($changeChushouData as $k8 => $v8){
            $changeChushoudata[$v8['owner_id']][$v8['use_id']][$v8['inst_id']][$v8['change_type']] = [
                'change_rents' => $v8['change_rents'],
                'change_month_rents' => $v8['change_month_rents'],
                'change_year_rents' => $v8['change_year_rents'],
                'change_areas' => $v8['change_areas'],
                'change_use_areas' => $v8['change_use_areas'],
                'change_oprices' => $v8['change_oprices'],
                'change_ban_nums' => $v8['change_ban_nums'],
                'change_house_nums' => $v8['change_house_nums'],
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
        $ownertypes = [1,2,3,5,7,10,11,12]; //市、区、代、自、托
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
                        if(!isset($changeZhuxiaodata[$owner][$i][$j][$k])){
                            $changeZhuxiaodata[$owner][$i][$j][$k] = [
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
                        if(!isset($changeChushoudata[$owner][$i][$j][$k])){
                            $changeChushoudata[$owner][$i][$j][$k] = [
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

                // $result[$owners][$j][2][1] = $changedata[$owners][2][$j][8]['change_ban_nums'] + $changedata[$owners][3][$j][8]['change_ban_nums'] + $changedata[$owners][1][$j][8]['change_ban_nums']; // 合计【栋数】
                // $result[$owners][$j][2][2] = bcaddMerge([$changedata[$owners][2][$j][8]['change_rents'],$changedata[$owners][3][$j][8]['change_rents'],$changedata[$owners][1][$j][8]['change_rents']]); // 合计【规定租金】
                // $result[$owners][$j][2][3] = bcaddMerge([$changedata[$owners][2][$j][8]['change_use_areas'],$changedata[$owners][3][$j][8]['change_use_areas'],$changedata[$owners][1][$j][8]['change_use_areas']]); // 合计【计租面积】
                // $result[$owners][$j][2][4] = bcaddMerge([$changedata[$owners][2][$j][8]['change_areas'],$changedata[$owners][3][$j][8]['change_areas'],$changedata[$owners][1][$j][8]['change_areas']]); // 合计【建筑面积】
                // $result[$owners][$j][2][5] = bcaddMerge([$changedata[$owners][2][$j][8]['change_oprices'],$changedata[$owners][3][$j][8]['change_oprices'],$changedata[$owners][1][$j][8]['change_oprices']]); // 合计【原价】
                // $result[$owners][$j][2][6] = $changedata[$owners][2][$j][8]['change_house_nums'] + $changedata[$owners][3][$j][8]['change_house_nums'] + $changedata[$owners][1][$j][8]['change_house_nums']; // 合计【户数】

                // $result[$owners][$j][2][7] = $changedata[$owners][2][$j][8]['change_ban_nums']; // 企业【栋数】
                // $result[$owners][$j][2][8] = $changedata[$owners][2][$j][8]['change_rents']; // 企业【规定租金】
                // $result[$owners][$j][2][9] = $changedata[$owners][2][$j][8]['change_use_areas']; // 企业【计租面积】
                // $result[$owners][$j][2][10] = $changedata[$owners][2][$j][8]['change_areas']; // 企业【建筑面积】
                // $result[$owners][$j][2][11] = $changedata[$owners][2][$j][8]['change_oprices']; // 企业【原价】
                // $result[$owners][$j][2][12] = $changedata[$owners][2][$j][8]['change_house_nums']; // 企业【户数】

                // $result[$owners][$j][2][13] = $changedata[$owners][3][$j][8]['change_ban_nums']; // 机关【栋数】
                // $result[$owners][$j][2][14] = $changedata[$owners][3][$j][8]['change_rents']; // 机关【规定租金】
                // $result[$owners][$j][2][15] = $changedata[$owners][3][$j][8]['change_use_areas']; // 机关【计租面积】
                // $result[$owners][$j][2][16] = $changedata[$owners][3][$j][8]['change_areas']; // 机关【建筑面积】
                // $result[$owners][$j][2][17] = $changedata[$owners][3][$j][8]['change_oprices']; // 机关【原价】
                // $result[$owners][$j][2][18] = $changedata[$owners][3][$j][8]['change_house_nums']; // 机关【户数】

                // $result[$owners][$j][2][19] = $changedata[$owners][1][$j][8]['change_ban_nums']; // 住宅【栋数】
                // $result[$owners][$j][2][20] = $changedata[$owners][1][$j][8]['change_rents']; // 住宅【规定租金】
                // $result[$owners][$j][2][21] = $changedata[$owners][1][$j][8]['change_use_areas']; // 住宅【计租面积】
                // $result[$owners][$j][2][22] = $changedata[$owners][1][$j][8]['change_areas']; // 住宅【建筑面积】
                // $result[$owners][$j][2][23] = $changedata[$owners][1][$j][8]['change_oprices']; // 住宅【原价】
                // $result[$owners][$j][2][24] = $changedata[$owners][1][$j][8]['change_house_nums']; // 住宅【户数】

                $result[$owners][$j][2][1] = $changeZhuxiaodata[$owners][2][$j][8]['change_ban_nums'] + $changeZhuxiaodata[$owners][3][$j][8]['change_ban_nums'] + $changeZhuxiaodata[$owners][1][$j][8]['change_ban_nums']; // 合计【栋数】
                $result[$owners][$j][2][2] = bcaddMerge([$changeZhuxiaodata[$owners][2][$j][8]['change_rents'],$changeZhuxiaodata[$owners][3][$j][8]['change_rents'],$changeZhuxiaodata[$owners][1][$j][8]['change_rents']]); // 合计【规定租金】
                $result[$owners][$j][2][3] = bcaddMerge([$changeZhuxiaodata[$owners][2][$j][8]['change_use_areas'],$changeZhuxiaodata[$owners][3][$j][8]['change_use_areas'],$changeZhuxiaodata[$owners][1][$j][8]['change_use_areas']]); // 合计【计租面积】
                $result[$owners][$j][2][4] = bcaddMerge([$changeZhuxiaodata[$owners][2][$j][8]['change_areas'],$changeZhuxiaodata[$owners][3][$j][8]['change_areas'],$changeZhuxiaodata[$owners][1][$j][8]['change_areas']]); // 合计【建筑面积】
                $result[$owners][$j][2][5] = bcaddMerge([$changeZhuxiaodata[$owners][2][$j][8]['change_oprices'],$changeZhuxiaodata[$owners][3][$j][8]['change_oprices'],$changeZhuxiaodata[$owners][1][$j][8]['change_oprices']]); // 合计【原价】
                $result[$owners][$j][2][6] = $changeZhuxiaodata[$owners][2][$j][8]['change_house_nums'] + $changeZhuxiaodata[$owners][3][$j][8]['change_house_nums'] + $changeZhuxiaodata[$owners][1][$j][8]['change_house_nums']; // 合计【户数】

                $result[$owners][$j][2][7] = $changeZhuxiaodata[$owners][2][$j][8]['change_ban_nums']; // 企业【栋数】
                $result[$owners][$j][2][8] = $changeZhuxiaodata[$owners][2][$j][8]['change_rents']; // 企业【规定租金】
                $result[$owners][$j][2][9] = $changeZhuxiaodata[$owners][2][$j][8]['change_use_areas']; // 企业【计租面积】
                $result[$owners][$j][2][10] = $changeZhuxiaodata[$owners][2][$j][8]['change_areas']; // 企业【建筑面积】
                $result[$owners][$j][2][11] = $changeZhuxiaodata[$owners][2][$j][8]['change_oprices']; // 企业【原价】
                $result[$owners][$j][2][12] = $changeZhuxiaodata[$owners][2][$j][8]['change_house_nums']; // 企业【户数】

                $result[$owners][$j][2][13] = $changeZhuxiaodata[$owners][3][$j][8]['change_ban_nums']; // 机关【栋数】
                $result[$owners][$j][2][14] = $changeZhuxiaodata[$owners][3][$j][8]['change_rents']; // 机关【规定租金】
                $result[$owners][$j][2][15] = $changeZhuxiaodata[$owners][3][$j][8]['change_use_areas']; // 机关【计租面积】
                $result[$owners][$j][2][16] = $changeZhuxiaodata[$owners][3][$j][8]['change_areas']; // 机关【建筑面积】
                $result[$owners][$j][2][17] = $changeZhuxiaodata[$owners][3][$j][8]['change_oprices']; // 机关【原价】
                $result[$owners][$j][2][18] = $changeZhuxiaodata[$owners][3][$j][8]['change_house_nums']; // 机关【户数】

                $result[$owners][$j][2][19] = $changeZhuxiaodata[$owners][1][$j][8]['change_ban_nums']; // 住宅【栋数】
                $result[$owners][$j][2][20] = $changeZhuxiaodata[$owners][1][$j][8]['change_rents']; // 住宅【规定租金】
                $result[$owners][$j][2][21] = $changeZhuxiaodata[$owners][1][$j][8]['change_use_areas']; // 住宅【计租面积】
                $result[$owners][$j][2][22] = $changeZhuxiaodata[$owners][1][$j][8]['change_areas']; // 住宅【建筑面积】
                $result[$owners][$j][2][23] = $changeZhuxiaodata[$owners][1][$j][8]['change_oprices']; // 住宅【原价】
                $result[$owners][$j][2][24] = $changeZhuxiaodata[$owners][1][$j][8]['change_house_nums']; // 住宅【户数】

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

                $result[$owners][$j][5][1] = $changeChushoudata[$owners][2][$j][8]['change_ban_nums'] + $changeChushoudata[$owners][3][$j][8]['change_ban_nums'] + $changeChushoudata[$owners][1][$j][8]['change_ban_nums']; // 合计【栋数】
                $result[$owners][$j][5][2] = bcaddMerge([$changeChushoudata[$owners][2][$j][8]['change_rents'],$changeChushoudata[$owners][3][$j][8]['change_rents'],$changeChushoudata[$owners][1][$j][8]['change_rents']]); // 合计【规定租金】
                $result[$owners][$j][5][3] = bcaddMerge([$changeChushoudata[$owners][2][$j][8]['change_use_areas'],$changeChushoudata[$owners][3][$j][8]['change_use_areas'],$changeChushoudata[$owners][1][$j][8]['change_use_areas']]); // 合计【计租面积】
                $result[$owners][$j][5][4] = bcaddMerge([$changeChushoudata[$owners][2][$j][8]['change_areas'],$changeChushoudata[$owners][3][$j][8]['change_areas'],$changeChushoudata[$owners][1][$j][8]['change_areas']]); // 合计【建筑面积】
                $result[$owners][$j][5][5] = bcaddMerge([$changeChushoudata[$owners][2][$j][8]['change_oprices'],$changeChushoudata[$owners][3][$j][8]['change_oprices'],$changeChushoudata[$owners][1][$j][8]['change_oprices']]); // 合计【原价】
                $result[$owners][$j][5][6] = $changeChushoudata[$owners][2][$j][8]['change_house_nums'] + $changeChushoudata[$owners][3][$j][8]['change_house_nums'] + $changeChushoudata[$owners][1][$j][8]['change_house_nums']; // 合计【户数】

                $result[$owners][$j][5][7] = $changeChushoudata[$owners][2][$j][8]['change_ban_nums']; // 企业【栋数】
                $result[$owners][$j][5][8] = $changeChushoudata[$owners][2][$j][8]['change_rents']; // 企业【规定租金】
                $result[$owners][$j][5][9] = $changeChushoudata[$owners][2][$j][8]['change_use_areas']; // 企业【计租面积】
                $result[$owners][$j][5][10] = $changeChushoudata[$owners][2][$j][8]['change_areas']; // 企业【建筑面积】
                $result[$owners][$j][5][11] = $changeChushoudata[$owners][2][$j][8]['change_oprices']; // 企业【原价】
                $result[$owners][$j][5][12] = $changeChushoudata[$owners][2][$j][8]['change_house_nums']; // 企业【户数】

                $result[$owners][$j][5][13] = $changeChushoudata[$owners][3][$j][8]['change_ban_nums']; // 机关【栋数】
                $result[$owners][$j][5][14] = $changeChushoudata[$owners][3][$j][8]['change_rents']; // 机关【规定租金】
                $result[$owners][$j][5][15] = $changeChushoudata[$owners][3][$j][8]['change_use_areas']; // 机关【计租面积】
                $result[$owners][$j][5][16] = $changeChushoudata[$owners][3][$j][8]['change_areas']; // 机关【建筑面积】
                $result[$owners][$j][5][17] = $changeChushoudata[$owners][3][$j][8]['change_oprices']; // 机关【原价】
                $result[$owners][$j][5][18] = $changeChushoudata[$owners][3][$j][8]['change_house_nums']; // 机关【户数】

                $result[$owners][$j][5][19] = $changeChushoudata[$owners][1][$j][8]['change_ban_nums']; // 住宅【栋数】
                $result[$owners][$j][5][20] = $changeChushoudata[$owners][1][$j][8]['change_rents']; // 住宅【规定租金】
                $result[$owners][$j][5][21] = $changeChushoudata[$owners][1][$j][8]['change_use_areas']; // 住宅【计租面积】
                $result[$owners][$j][5][22] = $changeChushoudata[$owners][1][$j][8]['change_areas']; // 住宅【建筑面积】
                $result[$owners][$j][5][23] = $changeChushoudata[$owners][1][$j][8]['change_oprices']; // 住宅【原价】
                $result[$owners][$j][5][24] = $changeChushoudata[$owners][1][$j][8]['change_house_nums']; // 住宅【户数】

                $result[$owners][$j][6][1] = 0; // 合计【栋数】
                $result[$owners][$j][6][2] = 0; // 合计【规定租金】
                $result[$owners][$j][6][3] = 0; // 合计【计租面积】
                $result[$owners][$j][6][4] = 0; // 合计【建筑面积】
                $result[$owners][$j][6][5] = 0; // 合计【原价】
                $result[$owners][$j][6][6] = 0; // 合计【户数】

                $result[$owners][$j][6][7] = 0; // 企业【栋数】
                $result[$owners][$j][6][8] = 0; // 企业【规定租金】
                $result[$owners][$j][6][9] = 0; // 企业【计租面积】
                $result[$owners][$j][6][10] = 0; // 企业【建筑面积】
                $result[$owners][$j][6][11] = 0; // 企业【原价】
                $result[$owners][$j][6][12] = 0; // 企业【户数】

                $result[$owners][$j][6][13] = 0; // 机关【栋数】
                $result[$owners][$j][6][14] = 0; // 机关【规定租金】
                $result[$owners][$j][6][15] = 0; // 机关【计租面积】
                $result[$owners][$j][6][16] = 0; // 机关【建筑面积】
                $result[$owners][$j][6][17] = 0; // 机关【原价】
                $result[$owners][$j][6][18] = 0; // 机关【户数】

                $result[$owners][$j][6][19] = 0; // 住宅【栋数】
                $result[$owners][$j][6][20] = 0; // 住宅【规定租金】
                $result[$owners][$j][6][21] = 0; // 住宅【计租面积】
                $result[$owners][$j][6][22] = 0; // 住宅【建筑面积】
                $result[$owners][$j][6][23] = 0; // 住宅【原价】
                $result[$owners][$j][6][24] = 0; // 住宅【户数】
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
                    }elseif($u == 5){
                        $l[0] = '公房出售';
                    }elseif($u == 6){
                        $l[0] = '租差/泵费';
                    }
                    
                }
            }
        }

        return $result;


	}

    // 非基数异动统计
    public function noRadix($cacheDate,$nextDate)
    {
        //        dump($cacheDate);halt($nextDate);
        $cacheDate = str_replace('-','',$cacheDate);
        $nextDate = str_replace('-','',$nextDate);
        $cacheYear = substr($cacheDate,0,4); // 2020

        $cacheYearFirstMonth = $cacheYear.'-01'; // 2020-01
        $cacheYearZeroMonth = $cacheYear.'00';
        // $arr5 = [substr($cacheDate,0,4) . '01', $cacheDate - 1]; // 201801~201804,包含201801和201804
        // $arr6 = [substr($cacheDate,0,4) . '01', $cacheDate]; // 201801~201805,包含201801和201805
        // $arr7 = substr($cacheDate,0,4); // 2018

        $cacheFullDate = substr_replace($cacheDate,'-',4,0); // 2020-08

        $cacheFullDateToTime = strtotime($cacheFullDate);
        $nextFullDate = date('Y-m',strtotime('+1 month',$cacheFullDateToTime)); // 2020-09
        //        $nextDate = str_replace('-', '', $nextFullDate); // 202009

        //减免
        $changejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','<',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])->select();
        //新增减免
        $changeAddjianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])->select();
        //新增失效减免
        $changeReducejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','eq',$cacheDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])->select();

        //暂停计租
        $changeNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','<',$nextDate],['change_status','eq',1],['change_type','eq',3]])->where('(end_date > '.$cacheDate.' or end_date = 0)')->select();
        //        halt(Db::name('change_table')->getLastSql());
        //新增暂停计租
        // $changeAddNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
        //     ->where([['order_date','eq',$nextDate],['change_status','eq',1],['change_type','eq',3]])->where('(end_date > '.$cacheDate.' or end_date = 0)')->select();
        $changeAddNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$nextDate],['change_status','eq',1],['change_type','eq',3]])->select();
        //新增失效暂停计租
        $changeReduceNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','eq',$nextDate],['change_type','eq',3],['change_status','eq',1]])->select();

        //政策减免
        $changeZhengcejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','<',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','eq',5],['change_status','eq',1]])->select();
        //新增政策减免
        $changeZhengceAddjianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','eq',5],['change_status','eq',1]])->select();
        //新增失效政策减免
        $changeZhengceReducejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','eq',$cacheDate],['change_type','eq',1],['cut_type','eq',5],['change_status','eq',1]])->select();

        // 暂停计租
        foreach($changeNoBaseData as $k11 => $v11){
            $changeNoBasedata[$v11['owner_id']][$v11['use_id']][$v11['inst_id']] = [
                'change_rents' => $v11['change_rents'],
            ];
        }
        foreach($changeAddNoBaseData as $k10 => $v10){
            $changeAddNoBasedata[$v10['owner_id']][$v10['use_id']][$v10['inst_id']] = [
                'change_rents' => $v10['change_rents'],
            ];
        }
        foreach($changeReduceNoBaseData as $k10 => $v10){
            $changeReduceNoBasedata[$v10['owner_id']][$v10['use_id']][$v10['inst_id']] = [
                'change_rents' => $v10['change_rents'],
            ];
        }
        // 减免
        foreach($changejianmianData as $k9 => $v9){
            $changejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeAddjianmianData as $k9 => $v9){
            $changeAddjianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeReducejianmianData as $k9 => $v9){
            $changeReducejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        // 政策减免
        foreach($changeZhengcejianmianData as $k9 => $v9){
            $changeZhengcejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeZhengceAddjianmianData as $k9 => $v9){
            $changeZhengceAddjianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeZhengceReducejianmianData as $k9 => $v9){
            $changeZhengceReducejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }



        //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,7,10,11,12]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                for ($j=4;$j<34;$j++) {
                    for($k=1;$k<16;$k++){
                        if(!isset($changeNoBasedata[$owner][$i][$j])){
                            $changeNoBasedata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeAddNoBasedata[$owner][$i][$j])){
                            $changeAddNoBasedata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeReduceNoBaseData[$owner][$i][$j])){
                            $changeReduceNoBasedata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changejianmiandata[$owner][$i][$j])){
                            $changejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeAddjianmiandata[$owner][$i][$j])){
                            $changeAddjianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeReducejianmiandata[$owner][$i][$j])){
                            $changeReducejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeZhengcejianmiandata[$owner][$i][$j])){
                            $changeZhengcejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeZhengceAddjianmiandata[$owner][$i][$j])){
                            $changeZhengceAddjianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeZhengceReducejianmiandata[$owner][$i][$j])){
                            $changeZhengceReducejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                    }
                }
            }
        }
        // halt($changenobasedata);
        $result = [];
        foreach ($ownertypes as $owners) { //处理市、区、代、自、托
            for ($j = 4; $j < 34; $j++) { //每个管段，从4开始……
                
                //减免异动ChangeType = 1
                //$result[$owners][$j][1][0] = '新发租'; // 合计【栋数】
                
                // $result[$owners][$j][1][5] = $lastRadixReport[$owners][$j][1][5]; // 企业【上期结转】
                $result[$owners][$j][1][5] = $changejianmiandata[$owners][2][$j]['change_rents'];; // 企业【上期结转】
                $result[$owners][$j][1][6] = $changeAddjianmiandata[$owners][2][$j]['change_rents']; // 企业【新增异动】
                $result[$owners][$j][1][7] = $changeReducejianmiandata[$owners][2][$j]['change_rents']; // 企业【失效异动】
                $result[$owners][$j][1][8] = bcaddMerge([$result[$owners][$j][1][5],$result[$owners][$j][1][6],-$result[$owners][$j][1][7]]); //企业【有效异动】 = 上期 + 新增 - 失效
                
                // $result[$owners][$j][1][9] = $lastRadixReport[$owners][$j][1][9]; // 机关【上期结转】
                $result[$owners][$j][1][9] = $changejianmiandata[$owners][3][$j]['change_rents']; // 机关【上期结转】
                $result[$owners][$j][1][10] = $changeAddjianmiandata[$owners][3][$j]['change_rents']; // 机关【新增异动】
                $result[$owners][$j][1][11] = $changeReducejianmiandata[$owners][3][$j]['change_rents']; // 机关【失效异动】
                $result[$owners][$j][1][12] = bcaddMerge([$result[$owners][$j][1][9],$result[$owners][$j][1][10],-$result[$owners][$j][1][11]]); // 机关【有效异动】 = 上期 + 新增 - 失效
                
                // $result[$owners][$j][1][13] = $lastRadixReport[$owners][$j][1][13]; // 住宅【上期结转】
                $result[$owners][$j][1][13] = $changejianmiandata[$owners][1][$j]['change_rents']; // 住宅【上期结转】
                $result[$owners][$j][1][14] = $changeAddjianmiandata[$owners][1][$j]['change_rents']; // 住宅【新增异动】
                $result[$owners][$j][1][15] = $changeReducejianmiandata[$owners][1][$j]['change_rents']; // 住宅【失效异动】
                $result[$owners][$j][1][16] = bcaddMerge([$result[$owners][$j][1][13],$result[$owners][$j][1][14],-$result[$owners][$j][1][15]]); // 住宅【有效异动】
                
                $result[$owners][$j][1][1] = bcaddMerge([$result[$owners][$j][1][5],$result[$owners][$j][1][9],$result[$owners][$j][1][13]]); // 合计【上期结转】
                $result[$owners][$j][1][2] = bcaddMerge([$result[$owners][$j][1][6],$result[$owners][$j][1][10],$result[$owners][$j][1][14]]); // 合计【新增异动】
                $result[$owners][$j][1][3] = bcaddMerge([$result[$owners][$j][1][7],$result[$owners][$j][1][11],$result[$owners][$j][1][15]]); // 合计【失效异动】
                $result[$owners][$j][1][4] = bcaddMerge([$result[$owners][$j][1][8],$result[$owners][$j][1][12],$result[$owners][$j][1][16]]); // 合计【有效异动】
                

                // 暂停计租
                // $result[$owners][$j][2][5] = $lastRadixReport[$owners][$j][2][5]; // 企业【上期结转】
                $result[$owners][$j][2][5] = $changeNoBasedata[$owners][2][$j]['change_rents']; // 企业【上期结转】
                $result[$owners][$j][2][6] = $changeAddNoBasedata[$owners][2][$j]['change_rents']; // 企业【新增异动】
                $result[$owners][$j][2][7] = $changeReduceNoBasedata[$owners][2][$j]['change_rents']; // 企业【失效异动】
                $result[$owners][$j][2][8] = bcaddMerge([$result[$owners][$j][2][5],$result[$owners][$j][2][6],-$result[$owners][$j][2][7]]); // 企业【有效异动】
                
                // $result[$owners][$j][2][9] = $lastRadixReport[$owners][$j][2][9]; // 机关【上期结转】
                $result[$owners][$j][2][9] = $changeNoBasedata[$owners][3][$j]['change_rents']; // 机关【上期结转】
                $result[$owners][$j][2][10] = $changeAddNoBasedata[$owners][3][$j]['change_rents']; // 机关【新增异动】
                $result[$owners][$j][2][11] = $changeReduceNoBasedata[$owners][3][$j]['change_rents']; // 机关【失效异动】
                $result[$owners][$j][2][12] = bcaddMerge([$result[$owners][$j][2][9],$result[$owners][$j][2][10],-$result[$owners][$j][2][11]]); // 机关【有效异动】
                
                // $result[$owners][$j][2][13] = $lastRadixReport[$owners][$j][2][13]; // 住宅【上期结转】
                $result[$owners][$j][2][13] = $changeNoBasedata[$owners][1][$j]['change_rents']; // 住宅【上期结转】
                $result[$owners][$j][2][14] = $changeAddNoBasedata[$owners][1][$j]['change_rents']; // 住宅【新增异动】
                $result[$owners][$j][2][15] = $changeReduceNoBasedata[$owners][1][$j]['change_rents']; // 住宅【失效异动】
                $result[$owners][$j][2][16] = bcaddMerge([$result[$owners][$j][2][13],$result[$owners][$j][2][14],-$result[$owners][$j][2][15]]); // 住宅【有效异动】
                
                $result[$owners][$j][2][1] = bcaddMerge([$result[$owners][$j][2][5],$result[$owners][$j][2][9],$result[$owners][$j][2][13]]); // 合计【上期结转】
                $result[$owners][$j][2][2] = bcaddMerge([$result[$owners][$j][2][6],$result[$owners][$j][2][10],$result[$owners][$j][2][14]]); // 合计【新增异动】
                $result[$owners][$j][2][3] = bcaddMerge([$result[$owners][$j][2][7],$result[$owners][$j][2][11],$result[$owners][$j][2][15]]); // 合计【失效异动】
                $result[$owners][$j][2][4] = bcaddMerge([$result[$owners][$j][2][8],$result[$owners][$j][2][12],$result[$owners][$j][2][16]]); // 合计【有效异动】
                
                // 政策减免
                // $result[$owners][$j][3][5] = $lastRadixReport[$owners][$j][3][5]; // 企业【上期结转】
                $result[$owners][$j][3][5] = $changeZhengcejianmiandata[$owners][2][$j]['change_rents']; // 企业【上期结转】
                $result[$owners][$j][3][6] = $changeZhengceAddjianmiandata[$owners][2][$j]['change_rents']; // 企业【新增异动】
                $result[$owners][$j][3][7] = $changeZhengceReducejianmiandata[$owners][2][$j]['change_rents']; // 企业【失效异动】
                $result[$owners][$j][3][8] = bcaddMerge([$result[$owners][$j][3][5],$result[$owners][$j][3][6],-$result[$owners][$j][3][7]]); // 企业【有效异动】
                
                // $result[$owners][$j][3][9] = $lastRadixReport[$owners][$j][3][9]; // 机关【上期结转】
                $result[$owners][$j][3][9] = $changeZhengcejianmiandata[$owners][3][$j]['change_rents']; // 机关【上期结转】
                $result[$owners][$j][3][10] = $changeZhengceAddjianmiandata[$owners][3][$j]['change_rents']; // 机关【新增异动】
                $result[$owners][$j][3][11] = $changeZhengceReducejianmiandata[$owners][3][$j]['change_rents']; // 机关【失效异动】
                $result[$owners][$j][3][12] = bcaddMerge([$result[$owners][$j][3][9],$result[$owners][$j][3][10],-$result[$owners][$j][3][11]]); // 机关【有效异动】
                
                // $result[$owners][$j][3][13] = $lastRadixReport[$owners][$j][3][13]; // 住宅【上期结转】
                $result[$owners][$j][3][13] = $changeZhengcejianmiandata[$owners][1][$j]['change_rents']; // 住宅【上期结转】
                $result[$owners][$j][3][14] = $changeZhengceAddjianmiandata[$owners][1][$j]['change_rents']; // 住宅【新增异动】
                $result[$owners][$j][3][15] = $changeZhengceReducejianmiandata[$owners][1][$j]['change_rents']; // 住宅【失效异动】
                $result[$owners][$j][3][16] = bcaddMerge([$result[$owners][$j][3][13],$result[$owners][$j][3][14],-$result[$owners][$j][3][15]]); // 住宅【有效异动】
                
                $result[$owners][$j][3][1] = bcaddMerge([$result[$owners][$j][3][5],$result[$owners][$j][3][9],$result[$owners][$j][3][13]]); // 合计【上期结转】
                $result[$owners][$j][3][2] = bcaddMerge([$result[$owners][$j][3][6],$result[$owners][$j][3][10],$result[$owners][$j][3][14]]); // 合计【新增异动】
                $result[$owners][$j][3][3] = bcaddMerge([$result[$owners][$j][3][7],$result[$owners][$j][3][11],$result[$owners][$j][3][15]]); // 合计【失效异动】
                $result[$owners][$j][3][4] = bcaddMerge([$result[$owners][$j][3][8],$result[$owners][$j][3][12],$result[$owners][$j][3][16]]); // 合计【有效异动】   
                
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
        //halt($result);
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
                        $l[0] = '减免';
                    }elseif($u == 2){
                        $l[0] = '暂停计租';
                    }elseif($u == 3){
                        $l[0] = '政策减免';
                    }
                    
                }
            }
        }

        return $result;


    }

    // 非基数异动统计
    public function noRadix_old($cacheDate,$nextDate)
    {
        //        dump($cacheDate);halt($nextDate);
        $cacheDate = str_replace('-','',$cacheDate);
        $nextDate = str_replace('-','',$nextDate);
        $cacheYear = substr($cacheDate,0,4); // 2020

        $cacheYearFirstMonth = $cacheYear.'-01'; // 2020-01
        $cacheYearZeroMonth = $cacheYear.'00';
        // $arr5 = [substr($cacheDate,0,4) . '01', $cacheDate - 1]; // 201801~201804,包含201801和201804
        // $arr6 = [substr($cacheDate,0,4) . '01', $cacheDate]; // 201801~201805,包含201801和201805
        // $arr7 = substr($cacheDate,0,4); // 2018

        $cacheFullDate = substr_replace($cacheDate,'-',4,0); // 2020-08

        $cacheFullDateToTime = strtotime($cacheFullDate);
        $nextFullDate = date('Y-m',strtotime('+1 month',$cacheFullDateToTime)); // 2020-09
        //        $nextDate = str_replace('-', '', $nextFullDate); // 202009

        //减免
        $changejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','<',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])->select();
        //新增减免
        $changeAddjianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])->select();
        //新增失效减免
        $changeReducejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','eq',$cacheDate],['change_type','eq',1],['cut_type','neq',5],['change_status','eq',1]])->select();

        //暂停计租
        $changeNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','<',$nextDate],['change_status','eq',1],['change_type','eq',3]])->where('(end_date > '.$cacheDate.' or end_date = 0)')->select();
        //        halt(Db::name('change_table')->getLastSql());
        //新增暂停计租
        $changeAddNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$nextDate],['change_status','eq',1],['change_type','eq',3]])->where('(end_date > '.$cacheDate.' or end_date = 0)')->select();
        //新增失效暂停计租
        $changeReduceNoBaseData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','eq',$cacheDate],['change_type','eq',3],['change_status','eq',1]])->select();

        //政策减免
        $changeZhengcejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','<',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','eq',5],['change_status','eq',1]])->select();
        //新增政策减免
        $changeZhengceAddjianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['order_date','eq',$nextDate],['end_date','gt',$nextDate],['change_type','eq',1],['cut_type','eq',5],['change_status','eq',1]])->select();
        //新增失效政策减免
        $changeZhengceReducejianmianData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents')->group('use_id,owner_id,inst_id')
            ->where([['end_date','eq',$cacheDate],['change_type','eq',1],['cut_type','eq',5],['change_status','eq',1]])->select();

        // 暂停计租
        foreach($changeNoBaseData as $k11 => $v11){
            $changeNoBasedata[$v11['owner_id']][$v11['use_id']][$v11['inst_id']] = [
                'change_rents' => $v11['change_rents'],
            ];
        }
        foreach($changeAddNoBaseData as $k10 => $v10){
            $changeAddNoBasedata[$v10['owner_id']][$v10['use_id']][$v10['inst_id']] = [
                'change_rents' => $v10['change_rents'],
            ];
        }
        foreach($changeReduceNoBaseData as $k10 => $v10){
            $changeReduceNoBasedata[$v10['owner_id']][$v10['use_id']][$v10['inst_id']] = [
                'change_rents' => $v10['change_rents'],
            ];
        }
        // 减免
        foreach($changejianmianData as $k9 => $v9){
            $changejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeAddjianmianData as $k9 => $v9){
            $changeAddjianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeReducejianmianData as $k9 => $v9){
            $changeReducejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        // 政策减免
        foreach($changeZhengcejianmianData as $k9 => $v9){
            $changeZhengcejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeZhengceAddjianmianData as $k9 => $v9){
            $changeZhengceAddjianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }
        foreach($changeZhengceReducejianmianData as $k9 => $v9){
            $changeZhengceReducejianmiandata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
            ];
        }



        //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,7,10,11,12]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                for ($j=4;$j<34;$j++) {
                    for($k=1;$k<16;$k++){
                        if(!isset($changeNoBasedata[$owner][$i][$j])){
                            $changeNoBasedata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeAddNoBasedata[$owner][$i][$j])){
                            $changeAddNoBasedata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeReduceNoBaseData[$owner][$i][$j])){
                            $changeReduceNoBasedata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changejianmiandata[$owner][$i][$j])){
                            $changejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeAddjianmiandata[$owner][$i][$j])){
                            $changeAddjianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeReducejianmiandata[$owner][$i][$j])){
                            $changeReducejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeZhengcejianmiandata[$owner][$i][$j])){
                            $changeZhengcejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeZhengceAddjianmiandata[$owner][$i][$j])){
                            $changeZhengceAddjianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                        if(!isset($changeZhengceReducejianmiandata[$owner][$i][$j])){
                            $changeZhengceReducejianmiandata[$owner][$i][$j] = [
                                'change_rents' => 0,
                            ];
                        }
                    }
                }
            }
        }
        // halt($changenobasedata);
        $result = [];
        foreach ($ownertypes as $owners) { //处理市、区、代、自、托
            for ($j = 4; $j < 34; $j++) { //每个管段，从4开始……
                
                //减免异动ChangeType = 1
                //$result[$owners][$j][1][0] = '新发租'; // 合计【栋数】

                $result[$owners][$j][1][6] = $changeAddjianmiandata[$owners][2][$j]['change_rents']; // 企业【新增异动】
                $result[$owners][$j][1][7] = $changeReducejianmiandata[$owners][2][$j]['change_rents']; // 企业【失效异动】
                $result[$owners][$j][1][8] = $changejianmiandata[$owners][2][$j]['change_rents']; // 企业【有效异动】 
                $result[$owners][$j][1][5] = bcaddMerge([$result[$owners][$j][1][8],$result[$owners][$j][1][7],-$result[$owners][$j][1][6]]); // 企业【上期结转】
                $result[$owners][$j][1][10] = $changeAddjianmiandata[$owners][3][$j]['change_rents']; // 机关【新增异动】
                $result[$owners][$j][1][11] = $changeReducejianmiandata[$owners][3][$j]['change_rents']; // 机关【失效异动】
                $result[$owners][$j][1][12] = $changejianmiandata[$owners][3][$j]['change_rents']; // 机关【有效异动】
                $result[$owners][$j][1][9] = bcaddMerge([$result[$owners][$j][1][12],$result[$owners][$j][1][11],-$result[$owners][$j][1][10]]); // 机关【上期结转】
                $result[$owners][$j][1][14] = $changeAddjianmiandata[$owners][1][$j]['change_rents']; // 住宅【新增异动】
                $result[$owners][$j][1][15] = $changeReducejianmiandata[$owners][1][$j]['change_rents']; // 住宅【失效异动】
                $result[$owners][$j][1][16] = $changejianmiandata[$owners][1][$j]['change_rents']; // 住宅【有效异动】
                $result[$owners][$j][1][13] = bcaddMerge([$result[$owners][$j][1][16],$result[$owners][$j][1][15],-$result[$owners][$j][1][14]]); // 住宅【上期结转】
                $result[$owners][$j][1][2] = bcaddMerge([$result[$owners][$j][1][6],$result[$owners][$j][1][10],$result[$owners][$j][1][14]]); // 合计【新增异动】
                $result[$owners][$j][1][3] = bcaddMerge([$result[$owners][$j][1][7],$result[$owners][$j][1][11],$result[$owners][$j][1][15]]); // 合计【失效异动】
                $result[$owners][$j][1][4] = bcaddMerge([$result[$owners][$j][1][8],$result[$owners][$j][1][12],$result[$owners][$j][1][16]]); // 合计【有效异动】
                $result[$owners][$j][1][1] = bcaddMerge([$result[$owners][$j][1][5],$result[$owners][$j][1][9],$result[$owners][$j][1][13]]); // 合计【上期结转】

                // 暂停计租
                $result[$owners][$j][2][6] = $changeAddNoBasedata[$owners][2][$j]['change_rents']; // 企业【新增异动】
                $result[$owners][$j][2][7] = $changeReduceNoBasedata[$owners][2][$j]['change_rents']; // 企业【失效异动】
                $result[$owners][$j][2][8] = $changeNoBasedata[$owners][2][$j]['change_rents']; // 企业【有效异动】
                $result[$owners][$j][2][5] = bcaddMerge([$result[$owners][$j][2][8],$result[$owners][$j][2][7],-$result[$owners][$j][2][6]]); // 企业【上期结转】
                $result[$owners][$j][2][10] = $changeAddNoBasedata[$owners][3][$j]['change_rents']; // 机关【新增异动】
                $result[$owners][$j][2][11] = $changeReduceNoBasedata[$owners][3][$j]['change_rents']; // 机关【失效异动】
                $result[$owners][$j][2][12] = $changeNoBasedata[$owners][3][$j]['change_rents']; // 机关【有效异动】
                $result[$owners][$j][2][9] = bcaddMerge([$result[$owners][$j][2][12],$result[$owners][$j][2][11],-$result[$owners][$j][2][10]]); // 机关【上期结转】
                $result[$owners][$j][2][14] = $changeAddNoBasedata[$owners][1][$j]['change_rents']; // 住宅【新增异动】
                $result[$owners][$j][2][15] = $changeReduceNoBasedata[$owners][1][$j]['change_rents']; // 住宅【失效异动】
                $result[$owners][$j][2][16] = $changeNoBasedata[$owners][1][$j]['change_rents']; // 住宅【有效异动】
                $result[$owners][$j][2][13] = bcaddMerge([$result[$owners][$j][2][16],$result[$owners][$j][2][15],-$result[$owners][$j][2][14]]); // 住宅【上期结转】
                $result[$owners][$j][2][2] = bcaddMerge([$result[$owners][$j][2][6],$result[$owners][$j][2][10],$result[$owners][$j][2][14]]); // 合计【新增异动】
                $result[$owners][$j][2][3] = bcaddMerge([$result[$owners][$j][2][7],$result[$owners][$j][2][11],$result[$owners][$j][2][15]]); // 合计【失效异动】
                $result[$owners][$j][2][4] = bcaddMerge([$result[$owners][$j][2][8],$result[$owners][$j][2][12],$result[$owners][$j][2][16]]); // 合计【有效异动】
                $result[$owners][$j][2][1] = bcaddMerge([$result[$owners][$j][2][5],$result[$owners][$j][2][9],$result[$owners][$j][2][13]]); // 合计【上期结转】

                // 政策减免
                $result[$owners][$j][3][6] = $changeZhengceAddjianmiandata[$owners][2][$j]['change_rents']; // 企业【新增异动】
                $result[$owners][$j][3][7] = $changeZhengceReducejianmiandata[$owners][2][$j]['change_rents']; // 企业【失效异动】
                $result[$owners][$j][3][8] = $changeZhengcejianmiandata[$owners][2][$j]['change_rents']; // 企业【有效异动】
                $result[$owners][$j][3][5] = bcaddMerge([$result[$owners][$j][3][8],$result[$owners][$j][3][7],-$result[$owners][$j][3][6]]); // 企业【上期结转】
                $result[$owners][$j][3][10] = $changeZhengceAddjianmiandata[$owners][3][$j]['change_rents']; // 机关【新增异动】
                $result[$owners][$j][3][11] = $changeZhengceReducejianmiandata[$owners][3][$j]['change_rents']; // 机关【失效异动】
                $result[$owners][$j][3][12] = $changeZhengcejianmiandata[$owners][3][$j]['change_rents']; // 机关【有效异动】
                $result[$owners][$j][3][9] = bcaddMerge([$result[$owners][$j][3][12],$result[$owners][$j][3][11],-$result[$owners][$j][3][10]]); // 机关【上期结转】
                $result[$owners][$j][3][14] = $changeZhengceAddjianmiandata[$owners][1][$j]['change_rents']; // 住宅【新增异动】
                $result[$owners][$j][3][15] = $changeZhengceReducejianmiandata[$owners][1][$j]['change_rents']; // 住宅【失效异动】
                $result[$owners][$j][3][16] = $changeZhengcejianmiandata[$owners][1][$j]['change_rents']; // 住宅【有效异动】
                $result[$owners][$j][3][13] = bcaddMerge([$result[$owners][$j][3][16],$result[$owners][$j][3][15],-$result[$owners][$j][3][14]]); // 住宅【上期结转】
                $result[$owners][$j][3][2] = bcaddMerge([$result[$owners][$j][3][6],$result[$owners][$j][3][10],$result[$owners][$j][3][14]]); // 合计【新增异动】
                $result[$owners][$j][3][3] = bcaddMerge([$result[$owners][$j][3][7],$result[$owners][$j][3][11],$result[$owners][$j][3][15]]); // 合计【失效异动】
                $result[$owners][$j][3][4] = bcaddMerge([$result[$owners][$j][3][8],$result[$owners][$j][3][12],$result[$owners][$j][3][16]]); // 合计【有效异动】
                $result[$owners][$j][3][1] = bcaddMerge([$result[$owners][$j][3][5],$result[$owners][$j][3][9],$result[$owners][$j][3][13]]); // 合计【上期结转】
                
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
        //halt($result);
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
                        $l[0] = '减免';
                    }elseif($u == 2){
                        $l[0] = '暂停计租';
                    }elseif($u == 3){
                        $l[0] = '政策减免';
                    }
                    
                }
            }
        }

        return $result;


    }

    // 租金异动统计
    public function rent($cacheDate)
    {
        $entry_date = $cacheDate;
        $cacheDate = str_replace('-','',$cacheDate);

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

        // 陈欠核销
        $chanqianhexiaoData = Db::name('change_table')->field('use_id,owner_id,inst_id ,sum(change_rent) as change_rents,sum(change_month_rent) as change_month_rents,sum(change_year_rent) as change_year_rents')->group('use_id,owner_id,inst_id')
        ->where([['order_date','eq',$cacheDate],['change_type','eq',4],['change_status','eq',1]])
        ->select();

        // 租金追加调整
        $zujinzhuijiatiaozhengData = Db::name('change_rentadd')->alias('a')->join('house b','a.house_id = b.house_id')->join('ban c','b.ban_id = c.ban_id')->field('b.house_use_id as use_id,c.ban_owner_id as owner_id,c.ban_inst_id as inst_id,sum(this_month_rent) as change_rents,sum(before_month_rent) as change_month_rents,sum(before_year_rent) as change_year_rents')->group('b.house_use_id,ban_owner_id,c.ban_inst_id')
        ->where([['entry_date','eq',$entry_date],['change_status','eq',1]])
        ->select();
//halt($chanqianhexiaoData);
        //重组为规定格式的
        foreach($chanqianhexiaoData as $k9 => $v9){
            $chanqianhexiaodata[$v9['owner_id']][$v9['use_id']][$v9['inst_id']] = [
                'change_rents' => $v9['change_rents'],
                'change_month_rents' => $v9['change_month_rents'],
                'change_year_rents' => $v9['change_year_rents'],
            ];
        }
        //重组为规定格式的
        foreach($zujinzhuijiatiaozhengData as $k8 => $v8){
            $zujinzhuijiatiaozhengdata[$v8['owner_id']][$v8['use_id']][$v8['inst_id']] = [
                'change_rents' => $v8['change_rents'],
                'change_month_rents' => $v8['change_month_rents'],
                'change_year_rents' => $v8['change_year_rents'],
            ];
        }

        //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,7,10,11,12]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                for ($j=4;$j<34;$j++) {
                    // for($k=1;$k<16;$k++){
                        if(!isset($chanqianhexiaodata[$owner][$i][$j])){
                            $chanqianhexiaodata[$owner][$i][$j] = [ 
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                            ];

                        }
                        if(!isset($zujinzhuijiatiaozhengdata[$owner][$i][$j])){
                            $zujinzhuijiatiaozhengdata[$owner][$i][$j] = [
                                'change_rents' => 0,
                                'change_month_rents' => 0,
                                'change_year_rents' => 0,
                            ];
                        }

                    // }
                }
            }
        }
        // halt($chanqianhexiaodata);
        $result = [];
        foreach ($ownertypes as $owners) { //处理市、区、代、自、托
            for ($j = 4; $j < 34; $j++) { //每个管段，从4开始……
            
                // 租金追加调整吗
                $result[$owners][$j][1][5] = $zujinzhuijiatiaozhengdata[$owners][2][$j]['change_rents']; // 企业【本月】
                $result[$owners][$j][1][6] = $zujinzhuijiatiaozhengdata[$owners][2][$j]['change_month_rents']; // 企业【以前月】
                $result[$owners][$j][1][7] = $zujinzhuijiatiaozhengdata[$owners][2][$j]['change_year_rents']; // 企业【以前年】
                $result[$owners][$j][1][8] = bcaddMerge([$result[$owners][$j][1][5],$result[$owners][$j][1][6],$result[$owners][$j][1][7]]); // 企业【小计】
                $result[$owners][$j][1][9] = $zujinzhuijiatiaozhengdata[$owners][3][$j]['change_rents']; // 机关【本月】
                $result[$owners][$j][1][10] = $zujinzhuijiatiaozhengdata[$owners][3][$j]['change_month_rents']; // 机关【以前月】
                $result[$owners][$j][1][11] = $zujinzhuijiatiaozhengdata[$owners][3][$j]['change_year_rents']; // 机关【以前年】
                $result[$owners][$j][1][12] = bcaddMerge([$result[$owners][$j][1][9],$result[$owners][$j][1][10],$result[$owners][$j][1][11]]); // 机关【小计】
                $result[$owners][$j][1][13] = $zujinzhuijiatiaozhengdata[$owners][1][$j]['change_rents']; // 住宅【本月】
                $result[$owners][$j][1][14] = $zujinzhuijiatiaozhengdata[$owners][1][$j]['change_month_rents']; // 住宅【以前月】
                $result[$owners][$j][1][15] = $zujinzhuijiatiaozhengdata[$owners][1][$j]['change_year_rents']; // 住宅【以前年】
                $result[$owners][$j][1][16] = bcaddMerge([$result[$owners][$j][1][13],$result[$owners][$j][1][14],$result[$owners][$j][1][15]]); // 住宅【小计】
                $result[$owners][$j][1][1] = bcaddMerge([$result[$owners][$j][1][5],$result[$owners][$j][1][9],$result[$owners][$j][1][13]]); // 合计【本月】
                $result[$owners][$j][1][2] = bcaddMerge([$result[$owners][$j][1][6],$result[$owners][$j][1][10],$result[$owners][$j][1][14]]); // 合计【以前月】
                $result[$owners][$j][1][3] = bcaddMerge([$result[$owners][$j][1][7],$result[$owners][$j][1][11],$result[$owners][$j][1][15]]); // 合计【以前年】
                $result[$owners][$j][1][4] = bcaddMerge([$result[$owners][$j][1][8],$result[$owners][$j][1][12],$result[$owners][$j][1][16]]); // 合计【小计】
                // halt($chanqianhexiaodata[$owners][2][$j]); 
                // 陈欠核销
                $result[$owners][$j][2][5] = $chanqianhexiaodata[$owners][2][$j]['change_rents']; // 企业【本月】
                $result[$owners][$j][2][6] = $chanqianhexiaodata[$owners][2][$j]['change_month_rents']; // 企业【以前月】
                $result[$owners][$j][2][7] = $chanqianhexiaodata[$owners][2][$j]['change_year_rents']; // 企业【以前年】
                $result[$owners][$j][2][8] = bcaddMerge([$result[$owners][$j][2][5],$result[$owners][$j][2][6],$result[$owners][$j][2][7]]); // 企业【小计】
                $result[$owners][$j][2][9] = $chanqianhexiaodata[$owners][3][$j]['change_rents']; // 机关【本月】
                $result[$owners][$j][2][10] = $chanqianhexiaodata[$owners][3][$j]['change_month_rents']; // 机关【以前月】
                $result[$owners][$j][2][11] = $chanqianhexiaodata[$owners][3][$j]['change_year_rents']; // 机关【以前年】
                $result[$owners][$j][2][12] = bcaddMerge([$result[$owners][$j][2][9],$result[$owners][$j][2][10],$result[$owners][$j][2][11]]); // 机关【小计】
                $result[$owners][$j][2][13] = $chanqianhexiaodata[$owners][1][$j]['change_rents']; // 住宅【本月】
                $result[$owners][$j][2][14] = $chanqianhexiaodata[$owners][1][$j]['change_month_rents']; // 住宅【以前月】
                $result[$owners][$j][2][15] = $chanqianhexiaodata[$owners][1][$j]['change_year_rents']; // 住宅【以前年】
                $result[$owners][$j][2][16] = bcaddMerge([$result[$owners][$j][2][13],$result[$owners][$j][2][14],$result[$owners][$j][2][15]]); // 住宅【小计】
                $result[$owners][$j][2][1] = bcaddMerge([$result[$owners][$j][2][5],$result[$owners][$j][2][9],$result[$owners][$j][2][13]]); // 合计【本月】
                $result[$owners][$j][2][2] = bcaddMerge([$result[$owners][$j][2][6],$result[$owners][$j][2][10],$result[$owners][$j][2][14]]); // 合计【以前月】
                $result[$owners][$j][2][3] = bcaddMerge([$result[$owners][$j][2][7],$result[$owners][$j][2][11],$result[$owners][$j][2][15]]); // 合计【以前年】
                $result[$owners][$j][2][4] = bcaddMerge([$result[$owners][$j][2][8],$result[$owners][$j][2][12],$result[$owners][$j][2][16]]); // 合计【小计】
                
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

        //halt($result);
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
                        $l[0] = '租金追加调整';
                    }elseif($u == 2){
                        $l[0] = '陈欠核销';
                    }
                    
                }
            }
        }

        return $result;


    }


}