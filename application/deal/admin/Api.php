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
     * 处理异动数据【预计耗时s】
     * @return [type] [description]
     */
    public function deal_change_lease()
    {
        // 1、同步house_id，和tenant_id
        set_time_limit(0);
        Db::execute('update ph_json_data as a left join ph_house as b on a.house_number = b.house_number left join ph_tenant as c on a.tenant_number = c.tenant_number set a.house_id = b.house_id,a.tenant_id = c.tenant_id');
        // 2、处理change_lease的child_json,data_json数据
        $users = Db::name('system_user')->column('number,id');
        $steps = [1=>'提交申请',2=>'审批',3=>'审批',4=>'终审',5=>'发证',6=>'提交签字'];
        $leaseJsonChild = Db::name('change_lease')->where([['process_id','neq',1]])->field('id,child_json')->select();
        foreach ($leaseJsonChild as $lease) {
            $child = json_decode($lease['child_json'],true);
            $a = [];
            foreach ($child as $k => $v) {
                $temp = [
                    'step' => $v['Step'],
                    'action' => $steps[$v['Step']],
                    'time' => date('Y-m-d H:i:s',$v['CreateTime']),
                    'uid' => isset($users[$v['UserNumber']])?$users[$v['UserNumber']]:1,
                ];
                array_unshift($a, $temp);
            }
            Db::name('change_lease')->where([['id','eq',$lease['id']]])->update(['process_id'=> 1,'child_json'=>json_encode($a)]);
        }
    }
    /**
     * 处理数据【预计耗时s】
     * @return [type] [description]
     */
    public function deal_change_pause()
    {
        // 同步data_json和
        set_time_limit(0);
        $users = Db::name('system_user')->column('number,id');
        $houses = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->column('a.house_number,a.house_id,a.tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent,b.tenant_name,b.tenant_number,b.tenant_card,b.tenant_tel');
        $housesss = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->column('a.house_id,a.house_number,a.tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent,b.tenant_name,b.tenant_number,b.tenant_card,b.tenant_tel');
        //halt($houses);
        $steps = [1=>'提交申请',2=>'审批',3=>'审批',4=>'终审',5=>'发证',6=>'提交签字'];
        $data = Db::name('change_pause')->where([['process_id','neq',1]])->field('id,house_id,child_json')->select();
        foreach($data as $d){
            $housearr = explode(',', $d['house_id']);
            $datajson = [];
            if(count($housearr) == 1){
                $datajson[0] = $housesss[$housearr[0]];
                $implodeHouses = $housearr[0];
            }else{
                foreach ($housearr as &$v) {
                    $v = $houses[$v]['house_id'];
                    $datajson[] = $houses[$v];
                }
                $implodeHouses = implode(',', $housearr);
                //halt($housearr);
            }
            Db::name('change_pause')->where([['id','eq',$d['id']]])->update(['process_id'=>1,'house_id'=>$implodeHouses,'data_json'=>json_encode($datajson)]);
            //halt(1);
        }
    }
    /**
     * 处理数据【预计耗时s】
     * @return [type] [description]
     */
    public function deal_change_cancel()
    {
        // 1、同步house_id，和tenant_id
        set_time_limit(0);
        // 2、入库注销数据
        $jsonData = Db::name('json_data')->field('change_order_number,house_id,house_use_id,house_number,tenant_id,tenant_name,house_oprice,house_area,house_pre_rent,house_use_area,house_lease_area,house_diff_rent,house_pump_rent')->where([['changetype','eq','注销']])->select(); 
        $jsonArr = []; 
        foreach($jsonData as $d){
            $jsonArr[$d['change_order_number']][] = $d;
        }
        foreach ($jsonArr as $k => $v) {
            Db::name('change_cancel')->where([['change_order_number','eq',$k]])->update(['data_json'=>json_encode($v)]);
        }
    }

    /**
     * 数据处理
     * @param id 消息id
     * @return json
     */
    public function dealChildJson()
    {
        //halt('确认处理child_json数据？会造成导入的数据详情页无法正常打开，如果确认处理，请在程序中注释改代码！');
        set_time_limit(0);
        //将字表中的数据json化，写入到对应的异动表中
        $allChildData = Db::name('json_child')->column("change_order_number,success,step,uid,from_unixtime(time,'%Y-%m-%d %H:%i:%s') as time,action,img");
//halt($allChildData);
        $result = [];
        foreach($allChildData as $k => &$v){
            unset($v['change_order_number']);
            $result[$k][] = $v;
        }

        $where = 1;
        // 处理原暂停计租异动
        $allPauseData = Db::name('change_pause')->where($where)->column('change_order_number');
        foreach($allPauseData as $a){
            if(isset($result[$a])){
                Db::name('change_pause')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理新发租异动
        $allNewData = Db::name('change_new')->where($where)->column('change_order_number');
        foreach($allNewData as $a){
            if(isset($result[$a])){
                Db::name('change_new')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理租金减免异动
        $allCutData = Db::name('change_cut')->where($where)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_cut')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理注销+房改异动
        $allCancelData = Db::name('change_cancel')->where($where)->column('change_order_number');
        foreach($allCancelData as $a){
            if(isset($result[$a])){
                // dump($a);
                // halt($result);
                Db::name('change_cancel')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理房屋调整异动
        $allHouseData = Db::name('change_house')->where($where)->column('change_order_number');
        foreach($allHouseData as $a){
            if(isset($result[$a])){
                Db::name('change_house')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理陈欠核销异动
        $allOffsetData = Db::name('change_offset')->where($where)->column('change_order_number');
        foreach($allOffsetData as $a){
            if(isset($result[$a])){
                Db::name('change_offset')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理租金追加调整异动
        $allRentaddData = Db::name('change_rentadd')->where($where)->column('change_order_number');
        foreach($allRentaddData as $a){
            if(isset($result[$a])){
                Db::name('change_rentadd')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        halt('ok');
    }

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

    

}