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

namespace app\deal\admin;

use think\Db;
use app\common\controller\Common;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 
     * @param id 消息id
     * @return json
     */
    public function getChangeCutRow() 
    {
        if ($this->request->isAjax()) {
        	$house_id = $this->request->param('house_id');
        	$data = [];
            $ChangeCutModel = new ChangeCutModel;
            if($house_id){
            	$changecutid = ChangeCutModel::where([['house_id','eq',$house_id]])->order('ctime desc')->value('id');
                $row = $ChangeCutModel->detail($changecutid);
                $data['data'] = $row->toArray();
    	        $data['msg'] = '获取成功';
    	        $data['code'] = 1;
            }else{
            	$data['msg'] = '参数错误';
    	        $data['code'] = 0;
            }
            return json($data);
        }
    }

    /**
     * @param ban_number 楼栋编号
     * @param ban_floors 楼栋总楼层数
     * @return json
     */
    public function getChangeBan()
    {
        if ($this->request->isAjax()) {
            $ban_id = input('param.ban_id/s');
            $ban_floors = input('param.ban_floors/d');
            $oldFloors = BanModel::where([['ban_id','eq',$ban_id]])->value('ban_floors');

            // 获取该楼栋下所有房间
            $RoomModel = new RoomModel;
            $roomids = $RoomModel->where([['ban_id','eq',$ban_id]])->column('room_id');
            // 更新所有房间的计算租金
            foreach($roomids as $roomid){
                $room_rent = $RoomModel->count_room_rent($roomid);
                RoomModel::where([['room_id','eq',$roomid]])->update(['room_cou_rent'=>$room_rent]);
            }
            // 获取该楼栋下所有房屋
            $houseOldArr = HouseModel::with('tenant')->where([['ban_id','eq',$ban_id]])->field('house_id,house_number,tenant_id,house_pre_rent,house_floor_id,house_cou_rent')->select()->toArray();
            $HouseModel = new HouseModel;
            $result = [];
            $result['data'] = [];
            // 更新所有房屋的计算租金
            if($houseOldArr){
                foreach($houseOldArr as $h){
                    $house_rent = $HouseModel->count_house_rent($h['house_id']);
                    HouseModel::where([['house_id','eq',$h['house_id']]])->update(['house_cou_rent'=>$house_rent]);
                }
                $houseNewArr = HouseModel::where([['ban_id','eq',$ban_id]])->column('house_id,house_cou_rent');
                
                $oldCouRents = $newCouRents = 0;
                foreach ($houseOldArr as $k => $v) {
                    if($ban_floors < $v['house_floor_id']){
                        return $this->error('总楼层不能小于居住层！');
                        break;
                    }
                    $oldCouRents = bcadd($oldCouRents,$v['house_cou_rent'],2); //统计所有房屋原计算租金之和
                    $newCouRents = bcadd($newCouRents,$houseNewArr[$v['house_id']],2); //统计所有房屋现计算租金之和
                    $result['data'][$k]['house_number'] = $v['house_number'];
                    $result['data'][$k]['tenant_name'] = $v['tenant_name'];
                    $result['data'][$k]['old_floor'] = $v['house_floor_id'].'/'.$oldFloors;
                    $result['data'][$k]['new_floor'] = $v['house_floor_id'].'/'.$ban_floors;
                    $result['data'][$k]['house_pre_rent'] = $v['house_pre_rent'];
                    $result['data'][$k]['house_old_cou_rent'] = $v['house_cou_rent'];
                    $result['data'][$k]['house_new_cou_rent'] = $houseNewArr[$v['house_id']];
                    $result['data'][$k]['diff_cou_rent'] = bcsub($houseNewArr[$v['house_id']],$v['house_cou_rent'] ,2);
                    //$v['house_cou_rent'];
                }               
            }

            $result['count'] = count($result['data']);
            $result['msg'] = '获取成功！';
            $result['code'] = 1;
            return json($result);
            //halt(json($result));
        }
    }

    /**
     * 处理异动数据
     * @return [type] [description]
     */
    public function dealData()
    {
        // 1、同步house_id，和tenant_id
        // set_time_limit(0);
        // Db::execute('update ph_json_data as a left join ph_house as b on a.house_number = b.house_number left join ph_tenant as c on a.tenant_number = c.tenant_number set a.house_id = b.house_id,a.tenant_id = c.tenant_id');
        
        // 2、入库注销数据
        $jsonData = Db::name('json_data')->field('change_order_number,house_id,house_use_id,house_number,tenant_id,tenant_name,house_oprice,house_area,house_pre_rent,house_lease_area,house_diff_rent,house_pump_rent')->where([['changetype','eq','注销']])->select(); 

        //halt(count($jsonData));
        $jsonArr = []; 
        foreach($jsonData as $d){
            $jsonArr[$d['change_order_number']][] = $d;
        }
        
        foreach ($jsonArr as $k => $v) {
            //halt(json_encode($v));
            Db::name('change_cancel')->where([['change_order_number','eq',$k]])->update(['data_json'=>json_encode($v)]);
        }
    }

}