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
use app\common\model\SystemAnnex;
use app\common\model\SystemExport;
use app\house\model\Ban as BanModel;
use app\common\model\SystemAnnexType;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\BanTai as BanTaiModel;
use app\deal\model\Process as ProcessModel;
use app\system\model\SystemUser as UserModel;
use app\house\model\FloorPoint as FloorPointModel;

class Ban extends Admin
{

    public function index()
    { 
        //halt(get_distance('30.556853','114.307803','30.57407569885454','114.32361602783203'));
        $areas = Db::name('base_area')->where([['level','eq',3]])->column('id,area_title');
        $this->assign('areas',$areas);
        //halt($areas);
        //error_reporting(0);
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 1);
            $getData = $this->request->get();

            $group = isset($getData['group'])?$getData['group']:'y';

            switch ($group) {
                case 'y':
                    $order = 'ban_ctime desc';
                    break;
                case 'x':
                    $order = 'ban_ctime desc';
                    break;
                case 'z':
                    $order = 'ban_dtime desc';
                    break;
                default:
                    $order = 'ban_ctime desc';
                    break;
            }

            $banModel = new BanModel;
            $where = $banModel->checkWhere($getData);
            $fields = 'ban_id,ban_number,ban_area_three,ban_use_id,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,from_unixtime(ban_dtime, \'%Y-%m-%d\') as ban_dtime,(ban_civil_rent+ban_party_rent+ban_career_rent) as ban_rent,(ban_civil_area+ban_party_area+ban_career_area) as ban_area,ban_use_area,(ban_civil_num+ban_party_num+ban_career_num) as ban_num,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_property_source,ban_units,ban_floors,(ban_civil_holds+ban_party_holds+ban_career_holds) as ban_holds';
            $data = [];
            $data['data'] = $banModel->field($fields)->where($where)->page($page)->limit($limit)->order($order)->select();
            $data['count'] = $banModel->where($where)->count('ban_id');
            $totalRow = $banModel->where($where)->field('sum(ban_civil_area+ban_party_area+ban_career_area) as total_ban_area, sum(ban_civil_rent+ban_party_rent+ban_career_rent) as total_ban_rent, sum(ban_civil_holds+ban_party_holds+ban_career_holds) as total_ban_holds,sum(ban_civil_num+ban_party_num+ban_career_num) as total_ban_num, sum(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as total_ban_oprice, sum(ban_use_area) as total_ban_use_area')->find();
            if($totalRow){
                $data['total_ban_num'] = $totalRow['total_ban_num'];
                $data['total_ban_holds'] = $totalRow['total_ban_holds'];
                $data['total_ban_area'] = $totalRow['total_ban_area'];
                $data['total_ban_rent'] = $totalRow['total_ban_rent'];
                $data['total_ban_oprice'] = $totalRow['total_ban_oprice'];
                $data['total_ban_use_area'] = $totalRow['total_ban_use_area'];
            }
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

    /**
     * 检查楼栋信息的异常数据
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-16 16:02:30
     * @return  返回值  
     * @version 版本  1.0
     */
    public function check_data()
    {
        $params = ParamModel::getCparams();
        $useridArr = UserModel::column('id');
        $BanModel = new BanModel();

        //$all_data = $BanModel->limit(10)->select()->toArray();
        $all_data = $BanModel->select()->toArray();
        //halt($all_data);
        $error_data = [];
        $error_data['base_error'] = [];
        foreach($all_data as $k => $v){
            if(($v['ban_civil_num'] + $v['ban_party_num'] + $v['ban_career_num']) > 1){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，合栋数异常';
            }
            if(!isset($params['owners'][$v['ban_owner_id']])){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，产别异常';
            }
            if(!isset($params['structs'][$v['ban_struct_id']])){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，结构类别异常';
            }
            if(!isset($params['damages'][$v['ban_damage_id']])){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，完损等级异常';
            }
            if(strlen($v['ban_number']) != 10){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，长度异常';
            }
            if(!in_array($v['ban_inst_pid'], [2,3]) || $v['ban_inst_id'] > 35 || $v['ban_inst_id'] < 4){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，所属机构异常';
            }
            if($v['ban_status'] > 1 && $v['ban_dtime'] == 0){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，状态为注销，但缺失注销时间状态';
            }
            if(!in_array($v['ban_cuid'],$useridArr)){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，创建人在系统中无法找到';
            }
            if(!in_array($v['ban_use_id'], [1,2,3])){
                $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，使用性质异常';
            }
        }
        //halt($error_data);
        $this->assign('error_data',$error_data);
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
            return $this->success('新增成功','index?group=x');
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
            }else{
                $data['ban_imgs'] = '';
            }
            $BanModel = new BanModel();
            //halt($data);
            // 入库
            if ($BanModel->allowField(true)->update($data) === false) {
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
            $room_houses = $roomRow->house_room()->column('house_id');
            //halt($roomArr);
            $houses = HouseModel::with('tenant')->where([['house_id','in',$room_houses]])->field('house_number,tenant_id')->select();
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

    public function export()
    {   
        if ($this->request->isAjax()) {
            $getData = $this->request->post();
            $banModel = new BanModel;
            $where = $banModel->checkWhere($getData);
            $fields = 'ban_number,ban_ratio,ban_inst_id,ban_owner_id,ban_use_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,ban_civil_rent,ban_party_rent,ban_career_rent,(ban_civil_rent+ban_party_rent+ban_career_rent) as ban_rent,ban_civil_area,ban_party_area,ban_career_area,(ban_civil_area+ban_party_area+ban_career_area) as ban_area,ban_use_area,ban_civil_oprice,ban_party_oprice,ban_career_oprice,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_property_source,ban_units,ban_floors,ban_civil_holds,ban_party_holds,ban_career_holds,(ban_civil_holds+ban_party_holds+ban_career_holds) as ban_holds,ban_gpsx,ban_gpsy,ban_status';
            
            $tableData = $banModel->field($fields)->where($where)->order('ban_ctime desc')->select()->toArray(); //表数据
            
            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '楼栋编号', 'field' => 'ban_number', 'width' => 12 ,'type' => 'string'),
                    array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                    array('title' => '使用性质', 'field' => 'ban_use_id', 'width' => 12,'type' => 'number'),
                    array('title' => '完损等级', 'field' => 'ban_damage_id', 'width' => 12,'type' => 'number'),
                    array('title' => '结构类别', 'field' => 'ban_struct_id', 'width' => 12,'type' => 'number'),
                    array('title' => '栋系数', 'field' => 'ban_ratio', 'width' => 12,'type' => 'string'),
                    array('title' => '单元数量', 'field' => 'ban_units', 'width' => 12,'type' => 'number'),
                    array('title' => '楼层数量', 'field' => 'ban_floors', 'width' => 12,'type' => 'number'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                    array('title' => '经度', 'field' => 'ban_gpsx', 'width' => 12,'type' => 'string'),
                    array('title' => '纬度', 'field' => 'ban_gpsy', 'width' => 12,'type' => 'string'),
                    array('title' => '产权证号', 'field' => 'ban_property_id', 'width' => 12,'type' => 'string'),
                    array('title' => '建成年份', 'field' => 'ban_build_year', 'width' => 12,'type' => 'string'),
                    array('title' => '民规租', 'field' => 'ban_civil_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '机规租', 'field' => 'ban_party_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '企规租', 'field' => 'ban_career_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '合规租', 'field' => 'ban_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '民建面', 'field' => 'ban_civil_area', 'width' => 12,'type' => 'number'),
                    array('title' => '机建面', 'field' => 'ban_party_area', 'width' => 12,'type' => 'number'),
                    array('title' => '企建面', 'field' => 'ban_career_area', 'width' => 12,'type' => 'number'),
                    array('title' => '合建面', 'field' => 'ban_area', 'width' => 12,'type' => 'number'),
                    array('title' => '使用面积', 'field' => 'ban_use_area', 'width' => 12,'type' => 'number'),
                    array('title' => '民原价', 'field' => 'ban_civil_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '机原价', 'field' => 'ban_party_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '企原价', 'field' => 'ban_career_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '合原价', 'field' => 'ban_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '民户数', 'field' => 'ban_civil_holds', 'width' => 12,'type' => 'number'),
                    array('title' => '机户数', 'field' => 'ban_party_holds', 'width' => 12,'type' => 'number'),
                    array('title' => '企户数', 'field' => 'ban_career_holds', 'width' => 12,'type' => 'number'),
                    array('title' => '合户数', 'field' => 'ban_holds', 'width' => 12,'type' => 'number'),
                    array('title' => '产权来源', 'field' => 'ban_property_source', 'width' => 24,'type' => 'string'),
                    array('title' => '状态', 'field' => 'ban_status', 'width' => 12,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '楼栋数据',
                    'Title' => '楼栋数据',
                ];
                
                return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
            }else{
                $result = [];
                $result['code'] = 0;
                $result['msg'] = '数据为空！';
                return json($result); 
            }
            
        }
        
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
        // 如果是异动形成的台账，则调取异动记录信息
        }elseif($row['change_type'] && $row['change_id']){  
            $PorcessModel = new ProcessModel;
            //dump($row['change_type']);halt($row['change_id']);
            $result = $PorcessModel->detail($row['change_type'],$row['change_id']);
            if(isset($result['old_data_info'])){
                $this->assign('old_data_info',$result['old_data_info']);
            }
            //halt($result['template']);
            $this->assign('data_info',$result['row']);
            return $this->fetch($result['template']);
        }else{
            return $this->error('数据为空！');  
        }         
    }
}