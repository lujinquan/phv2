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
use app\house\model\Room as RoomModel;
use app\house\model\BanTai as BanTaiModel;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\FloorPoint as FloorPointModel;

class Ban extends Admin
{

    public function index()
    {   
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $banModel = new BanModel;
            $where = $banModel->checkWhere($getData);
            $fields = 'ban_id,ban_number,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,(ban_civil_rent+ban_party_rent+ban_career_rent) as ban_rent,(ban_civil_area+ban_party_area+ban_career_area) as ban_area,ban_use_area,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_property_source,ban_units,ban_floors,ban_holds';
            $data = [];
            $data['data'] = $banModel->field($fields)->where($where)->page($page)->order('ban_ctime desc')->limit($limit)->select();
            $data['count'] = $banModel->where($where)->count('ban_id');
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
            $result = $this->validate($data, 'Ban.form');
            if($result !== true) {
                return $this->error($result);
            }
            $BanModel = new BanModel();
            // 数据过滤
            $filData = $BanModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$BanModel->allowField(true)->create($filData)) {
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
            $result = $this->validate($data, 'Ban.edit');
            if($result !== true) {
                return $this->error($result);
            }
            if(isset($data['file']) && $data['file']){
                $data['ban_imgs'] = implode(',',$data['file']);
            }
            $BanModel = new BanModel();
            //halt($data);
            // 入库
            if (!$BanModel->allowField(true)->update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $id = input('param.id/d');
        $group = input('param.group');
        $this->assign('group',$group);
        $row = BanModel::get($id);
        $row['ban_imgs'] = SystemAnnex::changeFormat($row['ban_imgs']);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function detail()
    {
        $id = input('param.id/d');
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
            $TaiModel = new BanTaiModel;
            $where = $TaiModel->checkWhere($getData);
            $data = [];
            $data['data'] = $TaiModel->with(['SystemUser'])->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            $data['count'] = $TaiModel->where($where)->count('ban_tai_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $row = BanModel::get($id);
        $row['ban_imgs'] = SystemAnnex::changeFormat($row['ban_imgs']);

        // 获取该楼栋下共用房间的数据
        $RoomModel = new RoomModel;
        $roomArr = $RoomModel->with('ban')->where([['ban_id','eq',$id],['room_pub_num','>',2]])->select();
        $FloorPointModel = new FloorPointModel;
        $roomTables = [];
        foreach ($roomArr as $k => $roomRow) {
            $flor_point = $FloorPointModel->get_floor_point($roomRow['room_floor_id'], $row['ban_floors']);
            $roomRow['floor_point'] = ($flor_point * 100).'%';
            $roomRow['room_rent_point'] = 100*(1 - $roomRow['room_rent_point']).'%';
            $room_houses = $roomRow->house_room()->column('house_number');
            $houses = HouseModel::with('tenant')->where([['house_number','in',$room_houses]])->field('house_number,tenant_id')->select();
            $roomTables[] = [
                'baseinfo' => $roomRow,
                'houseinfo' => $houses,
            ];
        }
//halt($roomTables);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        //halt($row);
        // $group = input('param.group');
        $this->assign('room_tables',$roomTables);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function del()
    {
        $ids = $this->request->param('id/a'); 
        $data = [];   
        $data['ban_id'] = $ids;
        // 数据验证
        $result = $this->validate($data, 'Ban.del');
        if($result !== true) {
            return $this->error($result);
        }    
        $res = BanModel::where([['ban_id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function struct()
    {
        $id = input('param.id/d');
        $row = BanModel::get($id);
        if ($this->request->isAjax()) {
            $id = input('param.id/d');
            $unitID = input('param.unit_id/d',1);
            $row = BanModel::get($id);
            $houseArr = HouseModel::with(['tenant'])->where([['ban_id','eq',$id],['house_unit_id','eq',$unitID]])->field('house_unit_id,house_floor_id,house_id,tenant_id,house_door,house_pre_rent,house_oprice,house_area,house_use_area,house_pump_rent,house_diff_rent')->order('house_floor_id asc')->select();
            $tempHouseArr = [];
            //dump($row['ban_floors']);halt($houseArr);
            
            for($j=1;$j<=$row['ban_floors'];$j++){
                foreach($houseArr as $h){
                    if($h['house_floor_id'] == $j){
                        $tempHouseArr[$j][] = [
                            'house_id' => $h['house_id'],
                            'tenant_name' => $h['tenant_name'],
                            'house_unit_id' => $h['house_unit_id'],
                            'house_floor_id' => $h['house_floor_id'],
                            'house_door' => $h['house_door'],
                            'house_pre_rent' => $h['house_pre_rent'],
                            'house_oprice' => $h['house_oprice'],
                            'house_area' => $h['house_area'],
                            'house_use_area' => $h['house_use_area'],
                            'house_pump_rent' => $h['house_pump_rent'],
                            'house_diff_rent' => $h['house_diff_rent'],
                            //'tenant_name' => $h['tenant_name'],
                        ];
                    } 
                }
                if(!isset($tempHouseArr[$j])){
                    $tempHouseArr[$j] = [];
                }
            }
            //halt($tempHouseArr);
            $data = [];
            $data['data'] = $tempHouseArr;
            $data['code'] = 0;
            $data['msg'] = '获取成功';
            // halt($data);
            return json($data);
        }
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function taiDetail()
    {
        $TaiModelModel = new BanTaiModel;
        $id = input('param.id/d');
        $row = $TaiModelModel->get($id);
        $temps = $row['data_json'];
       
        if($temps){
            $tableData = Db::query("SHOW FULL FIELDS FROM ".config('database.prefix')."ban");
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
        }else{
            return $this->error('数据为空！');
        }         
    }
}