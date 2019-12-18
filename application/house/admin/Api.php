<?php

// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\house\admin;

use think\Db;
use think\Debug;
use app\common\model\SystemAnnex;
use app\common\controller\Common;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 处理异动数据【预计耗时s】
     * @return [type] [description]
     */
    public function check_data()
    {
        set_time_limit(0);
        debug('begin');
        switch (input('type')) { 
            case 1:
                $msg = $this->check_ban_data();
                break;
            case 2:
                $msg = $this->check_house_data();
                break;
            case 3:
                $msg = $this->check_room_data();
                break;
            case 4:
                $msg = $this->check_tenant_data();
                break;
            case 5:
                $msg = $this->check_rent_data();
                break;
            case 6:
                $msg = $this->check_change_data();
                break;
            case 7:
                $msg = $this->check_admin_data();
                break;
            case 8:
                $msg = $this->check_log_data();
                break;
            case 9:
                $msg = $this->check_config_data();
                break;
            case 10:
                $msg = $this->check_order_data();
                break;
            case 11:
                $msg = $this->check_report_data();
                break;
            case 12:
                $msg = $this->check_msg_data();
                break;
            default:
                return $this->error('暂未开发！');
                break;
        }
        debug('end');
        $time = floor(debug('begin','end')).'s';
        return $this->success($msg.'，耗时：'.$time);
    }

    public function check_msg_data()
    {
        //$query = Db::connect('mysql://root:ctnm@127.0.0.1:3306/hk#utf8')->query('select table_schema,table_name,table_type,table_rows from information_schema.tables where table_schema="hk" and table_rows > 0 and table_name in (select hk_field from test);');
        //$hk_db = 'mysql://root:ctnm@127.0.0.1:3306/hk#utf8';
        //$shop_db = 'mysql://root:ctnm@127.0.0.1:3306/shop#utf8';
        $query = Db::query('select * from hk.test');
        $apply_tables = [];
        foreach($query as $v){ 
            $counts = Db::query("select count(*) as counts FROM hk.".$v['hk_field']);
            //halt($counts);
            if(!$counts[0]['counts']){
                continue;
            }
            //halt($counts);
            $hk_field = Db::query("SHOW FULL FIELDS FROM hk.".$v['hk_field']);
            $shop_field = Db::query("SHOW FULL FIELDS FROM shop.".$v['shop_field']);
            $hk_field_arr = [];
            $shop_field_arr = [];
            $apply_fields = [];
            foreach ($hk_field as $v1) {
                $hk_field_arr[] = $v1['Field'];
            }
            foreach ($shop_field as $v2) {
                $shop_field_arr[] = $v2['Field'];
                //dump($hk_field_arr);halt($v2['Field']);
                if(in_array($v2['Field'],$hk_field_arr)){
                    $apply_fields[] = $v2['Field'];
                }
            }
            $appply_fields_str = implode(',',$apply_fields);
            $executeSql = "insert into shop.".$v['shop_field']."(". $appply_fields_str.") select ". $appply_fields_str ." from hk.".$v['hk_field'];
            //halt($executeSql);
            Db::execute('truncate shop.'.$v['shop_field']);
            $res = Db::execute($executeSql);
            if($res){
                $apply_tables[] = $v['shop_field'];
            }
            //dump($v);halt($res);
            //dump($v);dump($apply_fields);dump($shop_field_arr);halt($hk_field_arr);
        }
        return $msg = count($apply_tables).'张表同步完成！';
        //halt($query);
    	//$id = 
    }
    
    public function tenant_info()
    {
        $id = input('param.id/d');
        $result = [];
        $result['code'] = 0;
        $row = TenantModel::with(['system_user'])->find($id);
        if(!$row){
            $result['msg'] = '当前租户不存在！';
            return $result;
        }
        $row['tenant_imgs'] = SystemAnnex::changeFormat($row['tenant_imgs']);
        // 获取租户的余额
        $row['tenant_balance'] = HouseModel::where([['tenant_id','eq',$row['tenant_id']]])->sum('house_balance');
        //获取租户的合计欠租情况
        $rentRow = RentModel::where([['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive) as rent_order_receives,sum(rent_order_paid) as rent_order_paid')->find(); //欠租
        $row['rent_order_unpaid'] = $rentRow['rent_order_receives']-$rentRow['rent_order_paid'];
        $result['code'] = 1;
        $result['data'] = $row;
        return $result;
    }
    
    public function check_house_data()
    {
        $owners = ['1'=>'市','2'=>'区','3'=>'代','5'=>'自','6'=>'','7'=>''];
        $houses = Db::name('house')->alias('a')->where([['house_szno','eq','']])->join('ban d','a.ban_id = d.ban_id','left')->field('house_id,ban_inst_pid,ban_owner_id')->order('house_id asc')->select();
        foreach ($houses as $v) {
            if(isset($v['ban_owner_id'])){
                $str = '租直昌'.$owners[$v['ban_owner_id']].'0'.$v['ban_inst_pid'].'-';
                Db::name('house')->where([['house_id','eq',$v['house_id']]])->update(['house_szno'=>$str]);
            }   
        }
        return $msg = '检测完毕，无误！';
        
    } 

    public function check_report_data()
    {
        $datas = Db::name('report')->where([['type','eq','RentReport']])->field('date,data')->select();
        foreach ($datas as $key => $data) {
            $dataArr = json_decode($data['data'],true);
            if(!isset($dataArr[10][4])){
                foreach($dataArr as $k1 => $v1){
                    // $k1是产别，1市、2区、3代、5自、7托、10市代托、11市区代托、12全部
                    for ($d = 33; $d >3; $d--) {
                        // $k2是机构，1~33
                        if(!isset($v1[$d])){
                            if($k1 == 10){
                                $dataArr[$k1][$d] =  array_merge_add(array_merge_add($dataArr[1][$d] ,$dataArr[3][$d]),$dataArr[7][$d]);
                            }
                            if($k1 == 11){
                                $dataArr[$k1][$d] =  array_merge_add(array_merge_add(array_merge_add($dataArr[1][$d] ,$dataArr[3][$d]),$dataArr[7][$d]),$dataArr[2][$d]);
                            }
                            if($k1 == 12){
                                $dataArr[$k1][$d] =  array_merge_add(array_merge_add(array_merge_add(array_merge_add($dataArr[1][$d] ,$dataArr[3][$d]),$dataArr[7][$d]),$dataArr[2][$d]),$dataArr[5][$d]);
                            }
                            
                        }
                    }
                }
                //halt($dataArr[10][4]);
                Db::name('report')->where([['type','eq','RentReport'],['date','eq',$data['date']]])->update(['data'=>json_encode($dataArr)]);
            }
        }
        
        
        return $msg = '检测完毕，无误！';
        
    } 
    

    







}