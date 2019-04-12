<?php
namespace app\house\admin;
use think\Db;
use app\system\admin\Admin;
use app\house\model\House as HouseModel;

class House extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $data = $this->request->get();
            $HouseModel = new HouseModel;
            $where = $HouseModel->checkWhere($data);
            $fields = 'house_id,house_number,ban_number,tenant_number,house_pre_rent,house_cou_rent,house_use_id,house_unit_id,house_floor_id,house_lease_area,house_area';
            // $data['data'] = $HouseModel->with([
            //     'ban'=> function($query){
            //         $query->where([['ban_number','like','%10101020%']]);
            //     },
            //     'tenant'=> function($query){
            //         $query->where(1);
            //     },
            //     ])->field($fields)->where($where)->page($page)->order('house_ctime desc')->limit($limit)->select();
            $data['data'] = $HouseModel->with(['ban','tenant'])->field($fields)->where($where)->page($page)->order('house_ctime desc')->limit($limit)->select();
            $data['count'] = $HouseModel->where($where)->count('house_id');
            $data['code'] = 0;
            $data['msg'] = '';
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
                'title' => '异常',
                'url' => '?group=n',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'House.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $HouseModel = new HouseModel();
            // 数据过滤
            $filData = $HouseModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$HouseModel->allowField(true)->create($filData)) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
        return $this->fetch();
    }

        public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'House.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $HouseModel = new HouseModel();
            // 入库
            if (!$HouseModel->allowField(true)->update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $id = input('param.id/d');
        $row = HouseModel::get($id);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function renttable()
    {
        return $this->fetch('renttable');
    }

    public function detail()
    {
        $id = input('param.id/d');
        $row = HouseModel::with(['ban','tenant'])->get($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function del()
    {
        $ids = $this->request->param('id/a');        
        $res = HouseModel::where([['house_id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function houseRoom()
    {
        $olds = Db::name('room_old')->column('RoomID,HouseID');
        $str = '';
        foreach($olds as $k => $v){
            $arr = explode(',',$v);
            foreach($arr as $a){
                $str .= '("' .$k .'","'.$a . '"),';
            }
        }
        // $re = Db::execute("insert into ".config('database.prefix')."house_room (room_number,house_number) values " . rtrim($str, ','));
        // halt($re);
    }
}