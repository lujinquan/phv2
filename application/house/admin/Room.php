<?php
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
                return $this->error('添加失败');
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

            return $this->success('添加成功');
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
 // halt($filData);
            // 入库room表
            if (!$RoomModel->allowField(true)->update($filData)) {
                return $this->error('添加失败');
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
}