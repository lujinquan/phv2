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
    	$house_id = $this->request->param('house_id');
    	$data = [];
        $ChangeCutModel = new ChangeCutModel;
        if($house_id){
        	$changecutid = ChangeCutModel::where([['house_id','eq',$house_id]])->order('ctime desc')->value('id');
            $row = $ChangeCutModel->detail($changecutid);
            $data['data'] = $row;
	        $data['msg'] = '获取成功';
	        $data['code'] = 0;
        }else{
        	$data['msg'] = '参数错误';
	        $data['code'] = -1;
        }
        return json($data);
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
            $houseOldArr = HouseModel::with('tenant')->where([['ban_id','eq',$ban_id]])->field('house_id,house_number,tenant_id,(house_pre_rent + house_diff_rent + house_diff_rent + house_pump_rent) as house_rent,house_floor_id,house_cou_rent')->select()->toArray();
            $HouseModel = new HouseModel;
            // 更新所有房屋的计算租金
            foreach($houseOldArr as $h){
                $house_rent = $HouseModel->count_house_rent($h['house_id']);
                HouseModel::where([['house_id','eq',$h['house_id']]])->update(['house_cou_rent'=>$house_rent]);
            }
            $houseNewArr = HouseModel::where([['ban_id','eq',$ban_id]])->column('house_id,house_cou_rent');
            $result = [];
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
                $result['data'][$k]['house_rent'] = $v['house_rent'];
                $result['data'][$k]['house_old_cou_rent'] = $v['house_cou_rent'];
                $result['data'][$k]['house_new_cou_rent'] = $houseNewArr[$v['house_id']];
                $result['data'][$k]['diff_cou_rent'] = bcsub($houseNewArr[$v['house_id']],$v['house_cou_rent'] ,2);
                //$v['house_cou_rent'];
            }
            $result['count'] = count($result['data']);
            $result['msg'] = '获取成功！';
            $result['code'] = 1;
            return json($result);
            //halt(json($result));
        }
    }
}