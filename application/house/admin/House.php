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
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\house\model\FloorPoint as FloorPointModel;
use app\deal\model\Process as ProcessModel;


class House extends Admin
{

    public function index()
    {

    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
           
            $HouseModel = new HouseModel;
            $where = $HouseModel->checkWhere($getData);
            //halt($where);
            $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,c.tenant_id,c.tenant_name,d.ban_units,d.ban_floors,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';
            //halt($where);
            $data = [];
            $data['data'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();

            foreach ($data['data'] as $k => &$v) {
                if($v['tenant_id']){ //如果当前房屋已经绑定租户
                    $v['last_print_time'] = Db::name('change_lease')->where([['house_id','eq',$v['house_id']],['change_status','eq',1],['tenant_id','eq',$v['tenant_id']]])->order('id desc')->value("from_unixtime(last_print_time, '%Y-%m-%d %H:%i:%s') as last_print_time");
                }else{
                    $v['last_print_time'] = '';
                }  
            }
            
            

            $data['count'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->count('a.house_id');

            // //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
            // $data['data'] = $HouseModel->withJoin([
            //      'ban'=> function($query)use($where){ //注意闭包传参的方式
            //          $query->where($where['ban']);
            //      },
            //      'tenant'=> function($query)use($where){
            //          $query->where($where['tenant']);
            //      },
            //      // 'change_lease'=> function($query)use($where){ //注意闭包传参的方式
            //      //     $query->where($where['lease']);
            //      // },
            //      ],'left')->field($fields)->where($where['house'])->page($page)->order('house_ctime desc')->limit($limit)->select();
            // $data['count'] = $HouseModel->withJoin([
            //      'ban'=> function($query)use($where){
            //          $query->where($where['ban']);
            //      },
            //      'tenant'=> function($query)use($where){
            //          $query->where($where['tenant']);
            //      },
            //      // 'change_lease'=> function($query)use($where){ //注意闭包传参的方式
            //      //     $query->where($where['lease']);
            //      // },
            //      ],'left')->where($where['house'])->count('house_id');
               
            //二、这种可以实现关联模型查询，但是不能将无法关联的数据剔除掉会出现undifined数据）
            // $data['data'] = $HouseModel->with([
            //      'ban'=> function($query){
            //          $query->where([['ban_address','like','%康平小区%']]);
            //      },
            //      'tenant'=> function($query){
            //          $query->where(1);
            //      },
            //      ],'inner')->field($fields)->where($where)->page($page)->order('house_ctime desc')->limit($limit)->select();
             

            //四、直接用数据库连接，都能满足但是不标准
            // $data['data'] = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->field('a.house_id,a.house_number,a.house_pre_rent,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.ban_number,b.ban_address,b.ban_owner_id,b.ban_inst_id,b.ban_units,b.ban_floors,c.tenant_name')->where($where)->page($page)->limit($limit)->select();
            // $data['count'] = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->where($where)->count('a.house_id');
            
            //三、无法实现关联查询，速度快   
            // $data['data'] = $HouseModel->with(['ban','tenant'])->field($fields)->where($where)->page($page)->order('house_ctime desc')->limit($limit)->select();
            // $data['count'] = $HouseModel->with(['ban','tenant'])->where($where)->count('house_id');
//halt($data);
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
                'title' => '新发',
                'url' => '?group=x',
            ],
            [
                'title' => '注销',
                'url' => '?group=z',
            ]
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('ban_number',input('param.ban_number',''));
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
            $result = $this->validate($data, 'House.form');
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
                return $this->error('新增失败');
            }
            return $this->success('新增成功');
        }
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'House.edit');
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
        $row = HouseModel::with(['ban','tenant'])->get($id);
        $group = input('param.group');
        $this->assign('group',$group);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function renttable()
    {
        $id = input('param.id/d');
        $group = input('param.group');
        $row = HouseModel::with(['ban','tenant'])->find($id);
        $cutRent = Db::name('change_cut')->where([['house_id','eq',$id],['tenant_id','eq',$row['tenant_id']],['change_status','eq',1],['end_date','<',date('Ym')]])->value('cut_rent');
        $row['cut_rent'] = $cutRent?$cutRent:'0.00';
        $row['ban_struct_point'] = Db::name('ban_struct_type')->where([['id','eq',$row['ban_struct_id']]])->value('new_point');
        //halt($row);
        //获取当前房屋的房间
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->order('room_id asc')->column('room_id'); 
        //halt($rooms);
        //定义计租表房间数组
        $HouseModel = new HouseModel;
        $roomTables = $HouseModel->get_house_renttable($id);
        // $roomTables = [];
        // if($rooms){
        //     $FloorPointModel =new FloorPointModel;
        //     //halt($rooms);
        //     foreach($rooms as $roo){
        //          $roomtype = RoomModel::where([['room_id','eq',$roo]])->find();
        //          $sort = $roomtype->room_type_point()->value('sort');
        //          $roomsSort[$sort][] = $roo;
        //     }
        //     //halt($roomsSort);
        //     ksort($roomsSort);
        //     //halt($roomsSort);
        //     foreach($roomsSort as $ro){
        //         foreach($ro as $r){
        //             $roomRow = RoomModel::with('ban')->where([['room_id','eq',$r]])->find();
        //             //动态获取层次调解率
        //             $flor_point = $FloorPointModel->get_floor_point($roomRow['room_floor_id'], $roomRow['ban_floors']);
        //             $roomRow['floor_point'] = ($flor_point * 100).'%';
        //             $roomRow['room_rent_point'] = 100*(1 - $roomRow['room_rent_point']).'%';
        //             $room_houses = $roomRow->house_room()->column('house_id');
        //             //dump($row);
        //             //halt($room_houses);
        //             $houses = HouseModel::with('tenant')->where([['house_id','in',$room_houses]])->field('house_id,house_number,tenant_id')->select();

        //             // 将被查询的房屋编号排在最前面
        //             $temp = [];
        //             foreach ($houses as $key => $value) {
        //                 if($value['house_id'] == $id){
        //                     array_unshift($temp, $value);
        //                 }else{
        //                     array_push($temp, $value);
        //                 }
        //             }
        //             //halt($houses);
        //             $roomTables[] = [
        //                 'baseinfo' => $roomRow,
        //                 'houseinfo' => $temp,
        //             ];
        //         }
        //     }
        // }
        //halt($roomTables);
        if ($this->request->isAjax()) {
            $data = [];
            $data['data'] = $roomTables;
            $data['msg'] = '获取计租表数据成功！';
            $data['code'] = 1;
            $flag = input('param.flag');
            if($flag === 'syn'){ //如果是房屋调整异动中调用，则执行临时表同步更新操作
                Db::execute('call syn_temp_table');
            }
            return json($data);
        }

        //halt($roomTables);
        $this->assign('room_tables',$roomTables);
        $this->assign('group',$group);
        $this->assign('data_info',$row);
        return $this->fetch('renttable');
    }

    public function detail()
    {
        $id = input('param.id/d');
        $row = HouseModel::with(['ban','tenant'])->get($id);
        $group = input('group','y');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '详情',
                'url' => '?id='.$id.'&group=y',
            ],
            [
                'title' => '台账',
                'url' => '?id='.$id.'&group=t',
            ]
        ];
        $tabData['current'] = url("detail?id=$id&group=$group");

        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->param();
            $HouseTaiModel = new HouseTaiModel;
            $where = $HouseTaiModel->checkWhere($getData);
            $data = [];
            $data['data'] = $HouseTaiModel->with(['tenant','SystemUser'])->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            $data['count'] = $HouseTaiModel->where($where)->count('house_tai_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function taiDetail()
    {
        $HouseTaiModel = new HouseTaiModel;
        $id = input('param.id/d');
        $row = $HouseTaiModel->get($id);
        $temps = $row['data_json'];
        
        if($temps){
            $tableBanData = Db::query("SHOW FULL FIELDS FROM ".config('database.prefix')."ban");
            $tableHouseData = Db::query("SHOW FULL FIELDS FROM ".config('database.prefix')."house");
            $tableTenantData = Db::query("SHOW FULL FIELDS FROM ".config('database.prefix')."tenant");
            $tableData = array_merge($tableBanData,$tableHouseData,$tableTenantData);
            $colNameArr = [];
            foreach ($tableData as $v) {
                $colNameArr[$v['Field']] = $v['Comment'];
            }
            foreach ($temps as $key => $value) {
                $datas[] = [
                    $colNameArr[$key] , $value['old'],$value['new']
                ];
            }
            $this->assign('datas',$datas);
            return $this->fetch();
        // 如果是异动形成的台账，则调取异动记录信息
        }elseif($row['change_type'] && $row['change_id']){  
            $PorcessModel = new ProcessModel;
            $result = $PorcessModel->detail($row['change_type'],$row['change_id']);
            if(isset($result['old_data_info'])){
                $this->assign('old_data_info',$result['old_data_info']);
            }
            $this->assign('data_info',$result['row']);
            return $this->fetch($result['template']);
        }else{
            return $this->error('数据为空！');  
        }
               
    }

    public function del()
    {
        $ids = $this->request->param('id/a');
        $data = [];   
        $data['house_id'] = $ids;
        // 数据验证
        $result = $this->validate($data, 'House.del');
        if($result !== true) {
            return $this->error($result);
        }     
        $res = HouseModel::where([['house_id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function detailOne()
    {
        if ($this->request->isAjax()) {
            $id = input('param.id/d');
            $row = HouseModel::get($id);
            if($row){
                $data = [
                    'code' => 0,
                    'msg' => '获取成功',
                    'data' => $row,
                ];
            }else{
                $data = [
                    'code' => 1,
                    'msg' => '获取失败',
                    'data' => $row,
                ];
            } 
            return json($data);
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
    }
}