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
use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\HouseRoom as HouseRoomModel;

class Room extends Admin
{

    public function index()
    {   
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RoomModel = new RoomModel;
            $where = $RoomModel->checkWhere($getData);
            //halt($where);
            $data = [];
            $data['data'] = $RoomModel->withJoin(['ban'=> function($query)use($where){ //注意闭包传参的方式
                     $query->where($where['ban']);
                 },])->where($where['room'])->page($page)->order('room_ctime desc')->limit($limit)->select();
            $data['count'] = $RoomModel->withJoin(['ban'=> function($query)use($where){ //注意闭包传参的方式
                     $query->where($where['ban']);
                 },])->where($where['room'])->count('room_id');

            // 统计房间使面、计租面积
            $totalRow = $RoomModel->withJoin(['ban'=> function($query)use($where){ //注意闭包传参的方式
                     $query->where($where['ban']);
                 },])->where($where['room'])->field('sum(room_use_area) as total_room_use_area, sum(room_lease_area) as total_room_lease_area')->find();
            if($totalRow){
                $data['total_room_use_area'] = $totalRow['total_room_use_area'];
                $data['total_room_lease_area'] = $totalRow['total_room_lease_area'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            //halt($data);
            return json($data);
        }
        $group = input('group','y');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '正常',
                'url' => '?group=y',
            ],
            [
                'title' => '新发',
                'url' => '?group=x',
            ],
            [
                'title' => '注销',
                'url' => '?group=z',
            ]
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    public function add()
    {   
        $id = input('param.id/d');
        $row = HouseModel::with(['ban','tenant'])->get($id);
    	if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Room.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $RoomModel = new RoomModel();
            // 数据过滤
            $filData = $RoomModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 1、入库room表
            if (!$RoomModel->allowField(true)->save($filData)) {
                return $this->error('新增失败');
            }
            // 2、补充房间计算租金，入库room表
            $room_cou_rent = $RoomModel->count_room_rent($RoomModel->room_id);
            $RoomModel->where([['room_id','eq',$RoomModel->room_id]])->setField('room_cou_rent',$room_cou_rent);
            // 3、入库house_room表
            $HouseModel = new HouseModel;
            $HouseRoomModel = new HouseRoomModel;
            foreach($filData['house_room'] as &$v){
                $v['room_id'] = $RoomModel->room_id;
            }
            $HouseRoomModel->saveAll($filData['house_room']);
            // 4、更新房屋信息
            // halt($filData['house_id']);
            $HouseModel->update_house_info($filData['house_id']);
            return $this->success('新增成功');
        }
        $this->assign('data_info',$row);
        $this->assign('flag','normal');
    	return $this->fetch('add');
    }

    public function edit()
    {   

        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Room.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $RoomModel = new RoomModel();
            // 数据过滤
            $filData = $RoomModel->dataFilter($data,'edit');
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库room表
            if ($RoomModel->allowField(true)->update($filData) === false) {
                return $this->error('修改失败');
            }
            // 补充房间计算租金，更新room表
            $room_cou_rent = $RoomModel->count_room_rent($filData['room_id']);
            $RoomModel->where([['room_id','eq',$data['room_id']]])->setField('room_cou_rent',$room_cou_rent);      
            //更新house_room表
            $HouseModel = new HouseModel;
            foreach($filData['house_room'] as &$v){
                $f['room_id'] = $filData['room_id'];
            }
            $HouseRoomModel = new HouseRoomModel;
            HouseRoomModel::where([['room_id','eq',$filData['room_id']]])->delete();
            $houseRoomArr = [];
            foreach ($filData['house_id'] as $k => $v) {
                if($v){
                    $houseRoomArr[$k] = [
                        'room_id' => $data['room_id'],
                        'house_id' => $v,
                    ];
                }
            }
            $HouseRoomModel->saveAll($houseRoomArr);
            $HouseModel->update_house_info($filData['house_id']);

            return $this->success('修改成功');
        }

        $id = input('param.id/d'); //从房屋列表页点进来的
        $house_id = input('param.house_id/s');
        $row = RoomModel::with('ban')->find($id);
        $row['room_rent_point'] = 100 - $row['room_rent_point']*100;
        $houseidArrs = HouseRoomModel::where([['room_id','eq',$id]])->column('house_id');
        //dump($row);halt($houseidArrs);
        $houseArrsTemp = HouseModel::with('tenant')->where([['house_id','in',$houseidArrs]])->field('house_id,tenant_id')->select();
        $houseArrs = [];
        foreach($houseArrsTemp as $h){
            if($h['house_id'] == $house_id){
                array_unshift($houseArrs, $h);
            }else{
                array_push($houseArrs, $h);
            }
        }
        //dump($house_number);
        //halt($houseArrs);
        $this->assign('data_info',$row);
        $this->assign('houseArrs',$houseArrs);
        $this->assign('flag','normal');
        return $this->fetch('form');
    }

    public function del(){
        $id = input('param.id/d');
        // 1、先获取要删除的房间绑定的房屋
        $HouseRoomModel = new HouseRoomModel; 
        $houseArr = $HouseRoomModel->where([['room_id','eq',$id]])->column('house_id');
        if(!$houseArr){
            return $this->error('参数错误！');
        }
        // 2、删除房间数据
        $RoomModel = new RoomModel;
        $re = $RoomModel->where([['room_id','eq',$id]])->delete();
        // 3、删除房屋房间关联数据
        if($re){
            $HouseRoomModel->where([['room_id','eq',$id]])->delete();
        }
        // 4、更新第一步获取的房屋的计算租金、计租面积、使用面积
        $HouseModel = new HouseModel;
        $HouseModel->update_house_info($houseArr);

        return $this->success('删除成功');
    }

    // public function del(){
    //     $id = input('param.id/d');
    //     $RoomModel = new RoomModel;
    //     $HouseModel = new HouseModel;
    //     $re = $RoomModel->where([['room_id','eq',$id]])->setField('room_status',10);
    //     $HouseRoomModel = new HouseRoomModel;
    //     $HouseRoomModel->where([['room_id','eq',$id]])->setField('house_room_status',10);

    //     if($re){
    //         $HouseRoomModel = new HouseRoomModel;
    //         $houseArr = $HouseRoomModel->where([['room_id','eq',$id]])->column('house_id');
    //         foreach ($houseArr as $f) {
    //             // 更新house表的计算租金
    //             $house_cou_rent = $HouseModel->count_house_rent($f);
    //             $roomids = $HouseRoomModel->where([['house_id','eq',$f]])->column('room_id');
    //             $roomRow = $RoomModel->where([['room_id','in',$roomids]])->field('sum(room_use_area) as room_use_area,sum(room_lease_area) as room_lease_area')->find();
    //             $HouseModel->where([['house_id','eq',$f]])->update(['house_cou_rent'=>$house_cou_rent,'house_use_area'=>$roomRow['room_use_area'],'house_lease_area'=>$roomRow['room_lease_area']]);
    //         }
    //         return $this->success('删除成功');
    //     }
    //     return $this->error('删除失败');

    // }

    public function detail()
    {
        $id = input('param.id/d');
        $row = RoomModel::with(['ban'])->find($id);
        $row['room_rent_point'] = (100 - $row['room_rent_point']*100).'%';
        $room_ids = $row->house_room()->where([['house_room_status','<=',1]])->column('house_id');
        $houses = HouseModel::with('tenant')->where([['house_id','in',$room_ids]])->field('house_number,tenant_id')->select();
        //dump($row);halt($houses);
        $this->assign('houses',$houses);
        $this->assign('data_info',$row);
        return $this->fetch();
    }
}