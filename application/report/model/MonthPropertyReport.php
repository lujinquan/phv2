<?php
namespace app\report\model;

use think\Db;
use think\Model;
use app\rent\model\RentRecycle as RentRecycleModel;
use app\common\model\Cparam as ParamModel;

class MonthPropertyReport extends Model
{
	/**
     * 产权统计报表缓存数据
     * 机制：将所有产别，机构按照多维数组序列化存储，$data[产别][机构]
     * 这样要取出某个产别和机构的数据时直接读取缓存中的对应结构即可
     */
    public function makeMonthPropertyReport($cacheDate){
        //注意所有与私房、区直共有房屋还没做
        //获取所有产别，去除代管产、托管产
        //$owerLst = Db::name('ban_owner_type')->where('id','not in','3,7')->column('id');
        ////初始化查询条件，默认市属、当前月份、当前机构,市属是包含市属，代管和托管
        // $owerLst = [1,2,5,6,11];
        // $instLst = [1,2,3];
        //初始化查询条件，默认市属、当前月份、当前机构,市属是包含市属，代管和托管
        $propertyWhere = [
            ['ban_owner_id','in',[1,2,3,5,6,7]],
            ['ban_status','eq',1],
        ];

        $dengjiWhere = [
            ['ban_owner_id','in',[1,2,3,5,6,7]],
            ['ban_property_id','>',0],
            ['ban_status','eq',1],
        ];

        $xinfaWhere = [
            ['ban_owner_id','in',[1,2,3,5,6,7]],
            ['change_type' ,'eq',7],
            'Status' => array('eq',1),
            //'OrderDate' => array('between', [$year . '01', $year . '12']),
            'OrderDate' => array('eq', $year),
        ];

        $zhuxiaoWhere = [
            'OwnerType' => array('in',[1,2,3,5,6,7]),
            'ChangeType' => array('eq',8),
            'Status' => array('eq',1),
            //'OrderDate' => array('between', [$year . '01', $year . '12']),
            'OrderDate' => array('eq', $year),
        ];

        $tiaozhengWhere = [  //租金调整，上调的部分
            'OwnerType' => array('in',[1,2,3,5,6,7]),
            'ChangeType' => array('eq',9),
            'Status' => array('eq',1),
            //'Area' => array('>',0),
            //'OrderDate' => array('between', [$year . '01', $year . '12']),
            'OrderDate' => array('eq', $year),
        ];
        // $tiaozhengDelWhere = [  //租金调整，上调的部分
        //     'OwnerType' => array('in',[1,2,3,5,6,7]),
        //     'ChangeType' => array('eq',9),
        //     'Status' => array('eq',1),
        //     'Area' => array('<',0),
        //     //'OrderDate' => array('between', [$year . '01', $year . '12']),
        //     'OrderDate' => array('eq', $year),
        // ];
        
        $propertyData = Db::name('ban')->field('ban_owner_id,ban_inst_id,(sum(ban_civil_num)+sum(ban_party_num)+sum(ban_career_num)) as ban_nums, (sum(ban_civil_area) + sum(ban_party_area) + sum(ban_career_area)) as ban_areas')
                              ->where($propertyWhere)
                              ->group('ban_owner_id,ban_inst_id')
                              ->select();

        $dengjiData = Db::name('ban')->field('OwnerType,TubulationID,sum(TotalNum) as totalNums, sum(TotalArea) as totalAreas')
                              ->where($dengjiWhere)
                              ->group('OwnerType,TubulationID')
                              ->select();

        $xinfaChangeData = Db::name('rent_table')->field('OwnerType,InstitutionID,NewLeaseType, sum(Area) as areas, sum(ChangeNum) as changeNums')
                              ->where($xinfaWhere)
                              ->group('OwnerType,InstitutionID,NewLeaseType')
                              ->select();

        $zhuxiaoChangeData = Db::name('rent_table')->field('OwnerType,InstitutionID,CancelType, sum(Area) as areas, sum(ChangeNum) as changeNums')
                              ->where($zhuxiaoWhere)
                              ->group('OwnerType,InstitutionID,CancelType')
                              ->select();
        $tiaozhengData = Db::name('rent_table')->field('OwnerType,InstitutionID,CancelType, sum(Area) as areas, sum(ChangeNum) as changeNums')
                              ->where($tiaozhengWhere)
                              ->group('OwnerType,InstitutionID,CancelType')
                              ->select();

        //重组为规定格式的产权基本数据
        foreach($propertyData as $k1 => $v1){
            $propertydata[$v1['OwnerType']][$v1['TubulationID']] = [
                'totalNums' => $v1['totalNums'],
                'totalAreas' => $v1['totalAreas'],
            ];
        }

        foreach($dengjiData as $k4 => $v4){
            $dengjidata[$v4['OwnerType']][$v4['TubulationID']] = [
                'totalNums' => $v4['totalNums'],
                'totalAreas' => $v4['totalAreas'],
            ];
        }

        foreach($xinfaChangeData as $k2 => $v2){
            $xinfaChangedata[$v2['OwnerType']][$v2['InstitutionID']][$v2['NewLeaseType']] = [
                'areas' => $v2['areas'],
                'changeNums' => $v2['changeNums'],
            ];
        }

        foreach($zhuxiaoChangeData as $k3 => $v3){
            $zhuxiaoChangedata[$v3['OwnerType']][$v3['InstitutionID']][$v3['CancelType']] = [
                'areas' => $v3['areas'],
                'changeNums' => $v3['changeNums'],
            ];
        }
        foreach($tiaozhengData as $k5 => $v5){
            if($v5['areas'] > 0){
                if(!isset($xinfaChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['areas'])){
                    $xinfaChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['areas'] = 0;
                }
                if(!isset($xinfaChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['changeNums'])){
                    $xinfaChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['changeNums'] = 0;
                }
                $xinfaChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['areas'] += $v5['areas'];
                $xinfaChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['changeNums'] += $v5['changeNums'];
                    

            }
            if($v5['areas'] < 0){
                if(!isset($zhuxiaoChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['areas'])){
                    $zhuxiaoChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['areas'] = 0;
                }
                if(!isset($zhuxiaoChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['changeNums'])){
                    $zhuxiaoChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['changeNums'] = 0;
                }
                $zhuxiaoChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['areas'] -= $v5['areas']; //负号所以是减，即为加
                $zhuxiaoChangedata[$v5['OwnerType']][$v5['InstitutionID']][6]['changeNums'] -= $v5['changeNums'];      
            }
            
        }

         //保证每一个产别，机构，下的每一个字段都不缺失（没有的以0来补充）
        $ownertypes = [1,2,3,5,6,7]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($j=4;$j<36;$j++) {

                if(!isset($propertydata[$owner][$j])){
                    $propertydata[$owner][$j] = [
                        'totalNums' => 0,
                        'totalAreas' => 0, 
                    ];
                }

                if(!isset($dengjidata[$owner][$j])){
                    $dengjidata[$owner][$j] = [
                        'totalNums' => 0,
                        'totalAreas' => 0, 
                    ];
                }

                for($k=1;$k<7;$k++){
                    if(!isset($xinfaChangedata[$owner][$j][$k])){
                        $xinfaChangedata[$owner][$j][$k] = [ 
                            'areas' => 0,
                            'changeNums' => 0,
                        ];
                    }
                }

                for($i=1;$i<7;$i++){
                    if(!isset($zhuxiaoChangedata[$owner][$j][$i])){
                        $zhuxiaoChangedata[$owner][$j][$i] = [
                            'areas' => 0,
                            'changeNums' => 0,
                        ];
                    }
                }

            }
        }
        //halt($propertydata);

        $ownertype = [1,2,5,6];
        foreach ($ownertype as $owners) { //处理市、区、代、自、托
            for ($a = 4; $a < 36; $a++) { //每个管段，从4开始……
                    
                if($owners === 1){
                    $result[$owners][$a][0][0] = $propertydata[1][$a]['totalNums'] + $propertydata[3][$a]['totalNums'] + $propertydata[7][$a]['totalNums']; //市属楼栋
                    $result[$owners][$a][0][1] = $propertydata[1][$a]['totalAreas'] + $propertydata[3][$a]['totalAreas'] + $propertydata[7][$a]['totalAreas']; //市属建筑面积
                    $result[$owners][$a][0][2] = $propertydata[3][$a]['totalNums']; //代管楼栋
                    $result[$owners][$a][0][3] = $propertydata[3][$a]['totalAreas']; //代管建筑面积
                    $result[$owners][$a][0][4] = $propertydata[7][$a]['totalNums']; //托管楼栋
                    $result[$owners][$a][0][5] = $propertydata[7][$a]['totalAreas']; //托管建筑面积

                    $result[$owners][$a][5][0] = $dengjidata[1][$a]['totalNums'] + $dengjidata[3][$a]['totalNums'] + $dengjidata[7][$a]['totalNums']; //登记楼栋
                    $result[$owners][$a][5][1] = $dengjidata[1][$a]['totalAreas'] + $dengjidata[3][$a]['totalAreas'] + $dengjidata[7][$a]['totalAreas']; //登记建筑面积

                    $result[$owners][$a][1][0] = $xinfaChangedata[1][$a][1]['changeNums'] + $xinfaChangedata[1][$a][2]['changeNums'] +$xinfaChangedata[1][$a][3]['changeNums'] +$xinfaChangedata[1][$a][4]['changeNums'] +$xinfaChangedata[1][$a][5]['changeNums'] +$xinfaChangedata[1][$a][6]['changeNums'] + $xinfaChangedata[3][$a][1]['changeNums'] + $xinfaChangedata[3][$a][2]['changeNums'] +$xinfaChangedata[3][$a][3]['changeNums'] +$xinfaChangedata[3][$a][4]['changeNums'] +$xinfaChangedata[3][$a][5]['changeNums'] +$xinfaChangedata[3][$a][6]['changeNums'] +$xinfaChangedata[7][$a][1]['changeNums'] + $xinfaChangedata[7][$a][2]['changeNums'] +$xinfaChangedata[7][$a][3]['changeNums'] +$xinfaChangedata[7][$a][4]['changeNums'] +$xinfaChangedata[7][$a][5]['changeNums'] +$xinfaChangedata[7][$a][6]['changeNums']; //新发楼栋
                    
                    $result[$owners][$a][1][1] = $xinfaChangedata[1][$a][1]['areas'] + $xinfaChangedata[1][$a][2]['areas'] +$xinfaChangedata[1][$a][3]['areas'] +$xinfaChangedata[1][$a][4]['areas'] +$xinfaChangedata[1][$a][5]['areas'] +$xinfaChangedata[1][$a][6]['areas'] +$xinfaChangedata[3][$a][1]['areas'] + $xinfaChangedata[3][$a][2]['areas'] +$xinfaChangedata[3][$a][3]['areas'] +$xinfaChangedata[3][$a][4]['areas'] +$xinfaChangedata[3][$a][5]['areas'] +$xinfaChangedata[3][$a][6]['areas'] +$xinfaChangedata[7][$a][1]['areas'] + $xinfaChangedata[7][$a][2]['areas'] +$xinfaChangedata[7][$a][3]['areas'] +$xinfaChangedata[7][$a][4]['areas'] +$xinfaChangedata[7][$a][5]['areas'] +$xinfaChangedata[7][$a][6]['areas']; //新发建筑面积

                    $result[$owners][$a][1][2] = $zhuxiaoChangedata[1][$a][1]['changeNums'] + $zhuxiaoChangedata[1][$a][2]['changeNums'] +$zhuxiaoChangedata[1][$a][3]['changeNums'] +$zhuxiaoChangedata[1][$a][4]['changeNums'] +$zhuxiaoChangedata[1][$a][5]['changeNums'] +$zhuxiaoChangedata[1][$a][6]['changeNums'] +$zhuxiaoChangedata[3][$a][1]['changeNums'] + $zhuxiaoChangedata[3][$a][2]['changeNums'] +$zhuxiaoChangedata[3][$a][3]['changeNums'] +$zhuxiaoChangedata[3][$a][4]['changeNums'] +$zhuxiaoChangedata[3][$a][5]['changeNums'] +$zhuxiaoChangedata[3][$a][6]['changeNums'] +$zhuxiaoChangedata[7][$a][1]['changeNums'] + $zhuxiaoChangedata[7][$a][2]['changeNums'] +$zhuxiaoChangedata[7][$a][3]['changeNums'] +$zhuxiaoChangedata[7][$a][4]['changeNums'] +$zhuxiaoChangedata[7][$a][5]['changeNums'] +$zhuxiaoChangedata[7][$a][6]['changeNums']; //注销楼栋

                    $result[$owners][$a][1][3] = $zhuxiaoChangedata[1][$a][1]['areas'] + $zhuxiaoChangedata[1][$a][2]['areas'] +$zhuxiaoChangedata[1][$a][3]['areas'] +$zhuxiaoChangedata[1][$a][4]['areas'] +$zhuxiaoChangedata[1][$a][5]['areas'] +$zhuxiaoChangedata[1][$a][6]['areas'] +$zhuxiaoChangedata[3][$a][1]['areas'] + $zhuxiaoChangedata[3][$a][2]['areas'] +$zhuxiaoChangedata[3][$a][3]['areas'] +$zhuxiaoChangedata[3][$a][4]['areas'] +$zhuxiaoChangedata[3][$a][5]['areas'] +$zhuxiaoChangedata[3][$a][6]['areas'] +$zhuxiaoChangedata[7][$a][1]['areas'] + $zhuxiaoChangedata[7][$a][2]['areas'] +$zhuxiaoChangedata[7][$a][3]['areas'] +$zhuxiaoChangedata[7][$a][4]['areas'] +$zhuxiaoChangedata[7][$a][5]['areas'] +$zhuxiaoChangedata[7][$a][6]['areas']; //注销建筑面积

                    $result[$owners][$a][2][0] = $xinfaChangedata[1][$a][1]['changeNums'] +$xinfaChangedata[3][$a][1]['changeNums'] +$xinfaChangedata[7][$a][1]['changeNums']; //接管栋数
                    $result[$owners][$a][2][1] = $xinfaChangedata[1][$a][1]['areas'] +$xinfaChangedata[3][$a][1]['areas'] +$xinfaChangedata[7][$a][1]['areas']; //接管建面
                    $result[$owners][$a][2][2] = $xinfaChangedata[1][$a][4]['changeNums'] +$xinfaChangedata[3][$a][4]['changeNums'] +$xinfaChangedata[7][$a][4]['changeNums']; //合建栋数
                    $result[$owners][$a][2][3] = $xinfaChangedata[1][$a][4]['areas'] +$xinfaChangedata[3][$a][4]['areas'] +$xinfaChangedata[7][$a][4]['areas']; //合建建面
                    $result[$owners][$a][2][4] = $zhuxiaoChangedata[1][$a][1]['changeNums'] +$zhuxiaoChangedata[3][$a][1]['changeNums'] +$zhuxiaoChangedata[7][$a][1]['changeNums']; //出售栋数
                    $result[$owners][$a][2][5] = $zhuxiaoChangedata[1][$a][1]['areas'] +$zhuxiaoChangedata[3][$a][1]['areas'] +$zhuxiaoChangedata[7][$a][1]['areas']; //出售建面
                    $result[$owners][$a][2][6] = $zhuxiaoChangedata[1][$a][4]['changeNums'] +$zhuxiaoChangedata[3][$a][4]['changeNums'] +$zhuxiaoChangedata[7][$a][4]['changeNums']; //灭失栋数
                    $result[$owners][$a][2][7] = $zhuxiaoChangedata[1][$a][4]['areas'] +$zhuxiaoChangedata[3][$a][4]['areas'] +$zhuxiaoChangedata[7][$a][4]['areas']; //灭失建面

                    $result[$owners][$a][3][0] = $xinfaChangedata[1][$a][2]['changeNums'] +$xinfaChangedata[3][$a][2]['changeNums'] +$xinfaChangedata[7][$a][2]['changeNums']; //还建栋数
                    $result[$owners][$a][3][1] = $xinfaChangedata[1][$a][2]['areas'] +$xinfaChangedata[3][$a][2]['areas'] +$xinfaChangedata[7][$a][2]['areas']; //还建建面
                    $result[$owners][$a][3][2] = $xinfaChangedata[1][$a][5]['changeNums'] +$xinfaChangedata[3][$a][5]['changeNums'] +$xinfaChangedata[7][$a][5]['changeNums']; //加改扩栋数
                    $result[$owners][$a][3][3] = $xinfaChangedata[1][$a][5]['areas'] +$xinfaChangedata[3][$a][5]['areas'] +$xinfaChangedata[7][$a][5]['areas']; //加改扩建面
                    $result[$owners][$a][3][4] = $zhuxiaoChangedata[1][$a][2]['changeNums'] +$zhuxiaoChangedata[3][$a][2]['changeNums'] +$zhuxiaoChangedata[7][$a][2]['changeNums']; //危改拆除栋数
                    $result[$owners][$a][3][5] = $zhuxiaoChangedata[1][$a][2]['areas'] +$zhuxiaoChangedata[3][$a][2]['areas'] +$zhuxiaoChangedata[7][$a][2]['areas']; //危改拆除建面
                    $result[$owners][$a][3][6] = $zhuxiaoChangedata[1][$a][5]['changeNums'] +$zhuxiaoChangedata[3][$a][5]['changeNums'] +$zhuxiaoChangedata[7][$a][5]['changeNums']; //房屋划转栋数
                    $result[$owners][$a][3][7] = $zhuxiaoChangedata[1][$a][5]['areas'] +$zhuxiaoChangedata[3][$a][5]['areas'] +$zhuxiaoChangedata[7][$a][5]['areas']; //房屋划转建面

                    $result[$owners][$a][4][0] = $xinfaChangedata[1][$a][3]['changeNums'] +$xinfaChangedata[3][$a][3]['changeNums'] +$xinfaChangedata[7][$a][3]['changeNums']; //新建栋数
                    $result[$owners][$a][4][1] = $xinfaChangedata[1][$a][3]['areas'] +$xinfaChangedata[3][$a][3]['areas'] +$xinfaChangedata[7][$a][3]['areas']; //新建建面
                    $result[$owners][$a][4][2] = $xinfaChangedata[1][$a][6]['changeNums'] +$xinfaChangedata[3][$a][6]['changeNums'] +$xinfaChangedata[7][$a][6]['changeNums']; //其他扩栋数
                    $result[$owners][$a][4][3] = $xinfaChangedata[1][$a][6]['areas'] +$xinfaChangedata[3][$a][6]['areas'] +$xinfaChangedata[7][$a][6]['areas']; //其他建面
                    $result[$owners][$a][4][4] = $zhuxiaoChangedata[1][$a][3]['changeNums'] +$zhuxiaoChangedata[3][$a][3]['changeNums'] +$zhuxiaoChangedata[7][$a][3]['changeNums']; //落私发还栋数
                    $result[$owners][$a][4][5] = $zhuxiaoChangedata[1][$a][3]['areas'] +$zhuxiaoChangedata[3][$a][3]['areas'] +$zhuxiaoChangedata[7][$a][3]['areas']; //落私发还建面
                    $result[$owners][$a][4][6] = $zhuxiaoChangedata[1][$a][6]['changeNums'] +$zhuxiaoChangedata[3][$a][6]['changeNums'] +$zhuxiaoChangedata[7][$a][6]['changeNums']; //其他栋数
                    $result[$owners][$a][4][7] = $zhuxiaoChangedata[1][$a][6]['areas'] +$zhuxiaoChangedata[3][$a][6]['areas'] +$zhuxiaoChangedata[7][$a][6]['areas']; //其他建面
                }else{
                    $result[$owners][$a][0][0] = $propertydata[$owners][$a]['totalNums']; //市属楼栋
                    $result[$owners][$a][0][1] = $propertydata[$owners][$a]['totalAreas']; //年增加建筑面积
                    $result[$owners][$a][0][2] = 0;
                    $result[$owners][$a][0][3] = 0;
                    $result[$owners][$a][0][4] = 0;
                    $result[$owners][$a][0][5] = 0;

                    $result[$owners][$a][5][0] = $dengjidata[$owners][$a]['totalNums']; //登记楼栋
                    $result[$owners][$a][5][1] = $dengjidata[$owners][$a]['totalAreas']; //登记建筑面积
                

                    // if($a == 14 && $owners == 2){
                    //     halt($xinfaChangedata[$owners][$a]);
                    // }
                   
                    $result[$owners][$a][1][0] = $xinfaChangedata[$owners][$a][1]['changeNums'] + $xinfaChangedata[$owners][$a][2]['changeNums'] +$xinfaChangedata[$owners][$a][3]['changeNums'] +$xinfaChangedata[$owners][$a][4]['changeNums'] +$xinfaChangedata[$owners][$a][5]['changeNums'] +$xinfaChangedata[$owners][$a][6]['changeNums']; //新发楼栋
                    $result[$owners][$a][1][1] = $xinfaChangedata[$owners][$a][1]['areas'] + $xinfaChangedata[$owners][$a][2]['areas'] +$xinfaChangedata[$owners][$a][3]['areas'] +$xinfaChangedata[$owners][$a][4]['areas'] +$xinfaChangedata[$owners][$a][5]['areas'] +$xinfaChangedata[$owners][$a][6]['areas']; //新发建筑面积
                    $result[$owners][$a][1][2] = $zhuxiaoChangedata[$owners][$a][1]['changeNums'] + $zhuxiaoChangedata[$owners][$a][2]['changeNums'] +$zhuxiaoChangedata[$owners][$a][3]['changeNums'] +$zhuxiaoChangedata[$owners][$a][4]['changeNums'] +$zhuxiaoChangedata[$owners][$a][5]['changeNums'] +$zhuxiaoChangedata[$owners][$a][6]['changeNums']; //注销楼栋
                    $result[$owners][$a][1][3] = $zhuxiaoChangedata[$owners][$a][1]['areas'] + $zhuxiaoChangedata[$owners][$a][2]['areas'] +$zhuxiaoChangedata[$owners][$a][3]['areas'] +$zhuxiaoChangedata[$owners][$a][4]['areas'] +$zhuxiaoChangedata[$owners][$a][5]['areas'] +$zhuxiaoChangedata[$owners][$a][6]['areas']; //注销建筑面积

                    $result[$owners][$a][2][0] = $xinfaChangedata[$owners][$a][1]['changeNums']; //接管栋数
                    $result[$owners][$a][2][1] = $xinfaChangedata[$owners][$a][1]['areas']; //接管建面
                    $result[$owners][$a][2][2] = $xinfaChangedata[$owners][$a][4]['changeNums']; //合建栋数
                    $result[$owners][$a][2][3] = $xinfaChangedata[$owners][$a][4]['areas']; //合建建面
                    $result[$owners][$a][2][4] = $zhuxiaoChangedata[$owners][$a][1]['changeNums']; //出售栋数
                    $result[$owners][$a][2][5] = $zhuxiaoChangedata[$owners][$a][1]['areas']; //出售建面
                    $result[$owners][$a][2][6] = $zhuxiaoChangedata[$owners][$a][4]['changeNums']; //灭失栋数
                    $result[$owners][$a][2][7] = $zhuxiaoChangedata[$owners][$a][4]['areas']; //灭失建面

                    $result[$owners][$a][3][0] = $xinfaChangedata[$owners][$a][2]['changeNums']; //还建栋数
                    $result[$owners][$a][3][1] = $xinfaChangedata[$owners][$a][2]['areas']; //还建建面
                    $result[$owners][$a][3][2] = $xinfaChangedata[$owners][$a][5]['changeNums']; //加改扩栋数
                    $result[$owners][$a][3][3] = $xinfaChangedata[$owners][$a][5]['areas']; //加改扩建面
                    $result[$owners][$a][3][4] = $zhuxiaoChangedata[$owners][$a][2]['changeNums']; //危改拆除栋数
                    $result[$owners][$a][3][5] = $zhuxiaoChangedata[$owners][$a][2]['areas']; //危改拆除建面
                    $result[$owners][$a][3][6] = $zhuxiaoChangedata[$owners][$a][5]['changeNums']; //房屋划转栋数
                    $result[$owners][$a][3][7] = $zhuxiaoChangedata[$owners][$a][5]['areas']; //房屋划转建面

                    $result[$owners][$a][4][0] = $xinfaChangedata[$owners][$a][3]['changeNums']; //新建栋数
                    $result[$owners][$a][4][1] = $xinfaChangedata[$owners][$a][3]['areas']; //新建建面
                    $result[$owners][$a][4][2] = $xinfaChangedata[$owners][$a][6]['changeNums']; //其他扩栋数
                    $result[$owners][$a][4][3] = $xinfaChangedata[$owners][$a][6]['areas']; //其他建面
                    $result[$owners][$a][4][4] = $zhuxiaoChangedata[$owners][$a][3]['changeNums']; //落私发还栋数
                    $result[$owners][$a][4][5] = $zhuxiaoChangedata[$owners][$a][3]['areas']; //落私发还建面
                    $result[$owners][$a][4][6] = $zhuxiaoChangedata[$owners][$a][6]['changeNums']; //其他栋数
                    $result[$owners][$a][4][7] = $zhuxiaoChangedata[$owners][$a][6]['areas']; //其他建面
                
                }

            }
        }
        
        //第一步：处理市代托，市区代托，全部下的公司，紫阳，粮道的数据（注意只有所和公司才有市代托、市区代托、全部）
        $ownertypess = [1,2,5,6,10,11]; //市、区、自、生活、市区自、全部
        foreach ($ownertypess as $own) {

            for ($d = 4; $d >0; $d--) {
                //公司和所，从1到3（1公司，2紫阳，3粮道），注意顺序公司的数据由所加和得来，所以是3、2、1的顺序
                if($own < 10 && $d ==3){ //粮道所，的市、区、自
                    $result[$own][$d] = array_merge_adds($result[$own][19],$result[$own][20],$result[$own][21],$result[$own][22],$result[$own][23],$result[$own][24],$result[$own][25],$result[$own][26],$result[$own][27],$result[$own][28],$result[$own][29],$result[$own][30],$result[$own][31],$result[$own][32],$result[$own][33],$result[$own][35]);
                }elseif($own < 10 && $d ==2){ //紫阳所，的市、区、自
                    $result[$own][$d] = array_merge_adds($result[$own][4],$result[$own][5],$result[$own][6],$result[$own][7],$result[$own][8],$result[$own][9],$result[$own][10],$result[$own][11],$result[$own][12],$result[$own][13],$result[$own][14],$result[$own][15],$result[$own][16],$result[$own][17],$result[$own][18],$result[$own][34]);
                }elseif($own < 10 && $d ==1){ //公司，的市、区、自
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 10 && $d > 1){ 
                    $result[$own][$d] = array_merge_add(array_merge_add($result[1][$d] ,$result[2][$d]),$result[5][$d]);
                }elseif($own == 10 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }elseif($own == 11 && $d > 1){ 
                    $result[$own][$d] = array_merge_add(array_merge_add(array_merge_add($result[1][$d] ,$result[2][$d]),$result[5][$d]),$result[6][$d]);
                }elseif($own == 11 && $d == 1){
                    $result[$own][$d] = array_merge_add($result[$own][2] ,$result[$own][3]);
                }
            }

        }

        foreach($result as &$res){
            foreach($res as &$re){
                foreach($re as &$r){
                    foreach($r as &$s){
                        if($s > 0){
                            $s = rtrim(rtrim($s,'0'),'.');
                        }else{
                            $s = '';
                        }
                    }
                }
            }
        }

        return $result;
    }
}