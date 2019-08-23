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

use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\HouseRoom as HouseRoomModel;
use app\house\model\Room as RoomModel;
use app\common\model\Cparam as ParamModel;

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
            
            $data = [];
            $data['data'] = $RoomModel->withJoin(['ban'=> function($query)use($where){ //注意闭包传参的方式
                     $query->where($where['ban']);
                 },])->where($where['room'])->page($page)->order('room_ctime desc')->limit($limit)->select();
            $data['count'] = $RoomModel->withJoin(['ban'=> function($query)use($where){ //注意闭包传参的方式
                     $query->where($where['ban']);
                 },])->where($where['room'])->count('room_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function add()
    {   
        $id = input('param.id/d');
        $row = HouseModel::get($id);
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
 
            // 入库room表
            if (!$RoomModel->allowField(true)->save($filData)) {
                return $this->error('新增失败');
            }

            // 补充房间计算租金，入库room表
            $room_cou_rent = $RoomModel->count_room_rent($RoomModel->room_id);
            $RoomModel->where([['room_id','eq',$RoomModel->room_id]])->setField('room_cou_rent',$room_cou_rent);
            
            //入库house_room表
            $HouseModel = new HouseModel;
            foreach($filData['house_room'] as &$f){
                $f['room_id'] = $RoomModel->room_id;
                // 更新house表的计算租金
                $house_cou_rent = $HouseModel->count_house_rent($f['house_id']);
                $HouseModel->where([['house_id','eq',$f['house_id']]])->setField('house_cou_rent',$house_cou_rent);
            }
            $HouseRoomModel = new HouseRoomModel;
            $HouseRoomModel->saveAll($filData['house_room']);

            return $this->success('新增成功');
        }
        $this->assign('data_info',$row);
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
            if (!$RoomModel->allowField(true)->update($filData)) {
                return $this->error('修改失败');
            }

            // 补充房间计算租金，=更新room表
            $room_cou_rent = $RoomModel->count_room_rent($filData['room_id']);
            $RoomModel->where([['room_id','eq',$data['room_id']]])->setField('room_cou_rent',$room_cou_rent);
            
            //更新house_room表
            $HouseModel = new HouseModel;

            foreach($filData['house_room'] as &$f){
                $f['room_id'] = $filData['room_id'];
                // 更新house表的计算租金
                $house_cou_rent = $HouseModel->count_house_rent($f['house_id']);
                $HouseModel->where([['house_id','eq',$f['house_id']]])->setField('house_cou_rent',$house_cou_rent);
            }
            $HouseRoomModel = new HouseRoomModel;

            HouseRoomModel::where([['room_id','eq',$filData['room_id']]])->delete();
            $HouseRoomModel->saveAll($filData['house_room']);

            return $this->success('修改成功');
        }
        $id = input('param.id/d');
        $row = RoomModel::get($id);
        $row['room_rent_point'] = 100 - $row['room_rent_point']*100;
        $houseArrs = HouseRoomModel::where([['room_id','eq',$id]])->column('house_number');
        $this->assign('data_info',$row);
        $this->assign('houseArrs',$houseArrs);
        return $this->fetch('form');
    }

    public function del(){
        $id = input('param.id/d');
        $RoomModel = new RoomModel;
        $re = $RoomModel->where([['room_id','eq',$id]])->setField('room_status',10);
        $HouseRoomModel = new HouseRoomModel;
        $HouseRoomModel->where([['room_id','eq',$id]])->setField('house_room_status',10);
        if($re){
            return $this->success('删除成功');
        }
            return $this->error('删除失败');

    }

    public function detail()
    {
        $id = input('param.id/d');
        $row = RoomModel::with(['ban'])->find($id);
        $row['room_rent_point'] = (100 - $row['room_rent_point']*100).'%';
        $houses = $row->house_room()->where([['house_room_status','<=',1]])->column('house_number');
        //halt($houses);
        $this->assign('houses',$houses);
        $this->assign('data_info',$row);
        return $this->fetch();
    }
}