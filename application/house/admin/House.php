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
use app\common\model\SystemExport;
use app\common\model\SystemTcpdf;
use app\rent\model\Rent as RentModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\wechat\model\Weixin as WeixinModel;
use app\deal\model\Process as ProcessModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\house\model\FloorPoint as FloorPointModel;


class House extends Admin
{
    /*public function demo(){
        // 收欠表中，缺失rent_order_id的记录
        $data = Db::name('rent_recycle')->alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->where([['a.rent_order_id','eq',0]])->field('a.*,b.house_number,b.house_pre_rent,b.house_cou_rent,c.ban_owner_id')->select();

        $a = Db::name('rent_recycle')->alias('a')->join('house b','a.house_id = b.house_id','left')->where([['a.rent_order_id','eq',0]])->field('a.*,b.house_number')->select();

        $b = Db::name('rent_recycle')->where([['rent_order_id','eq',0]])->select();
        //halt($a);
        

        $str = '';

        foreach ($data as $k => $v) {
            $ctime = strtotime(substr_replace($v['pay_month'], '-', 4,0));
            // halt($ptime);
            $rent_order_cut = 0;
            // if($cutsArr && isset($cutsArr[$v['house_id']])){
            //    $rent_order_cut = $cutsArr[$v['house_id']]; 
            // }else{
            //     $rent_order_cut = 0;
            // }
            //$rent_order_cut = ($v['end_date'] > date('Ym'))?$v['cut_rent']:0;
            // 租金订单id
            $rent_order_number = $v['house_number'].$v['ban_owner_id'].$v['pay_month'];

            // 应收 = 规租 + 泵费 + 租差 + 协议租金 - 减免 
            //$rent_order_receive = $v['house_pre_rent'] - $rent_order_cut;
            // 待入库的数据
            $str .= "('" . $rent_order_number . "',". $v['pay_month'] . ",". $rent_order_cut ."," .$v['house_pre_rent']. ",". $v['house_cou_rent'] . ",". $v['pay_rent'] .",". $v['pay_rent'] . ",". $v['house_id'] . "," . $v['tenant_id']. ",1,1," . $v['ctime'] . "," . $ctime . "),";
            //halt($str);
        }

        // //halt($str);
        $res = Db::execute("insert into ".config('database.prefix')."rent_order (rent_order_number,rent_order_date,rent_order_cut,rent_order_pre_rent,rent_order_cou_rent,rent_order_receive,rent_order_paid,house_id,tenant_id,is_deal,pay_way,ptime,ctime) values " . rtrim($str, ','));
    }*/

    public function index()
    {
        // $HouseModel = new HouseModel;
        // $find = $HouseModel->get_unpaid_rents(8633);
        // halt($find);

        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();

            $group = isset($getData['group'])?$getData['group']:'y';

            switch ($group) {
                case 'y':
                    $order = 'house_ctime desc';
                    break;
                case 'x':
                    $order = 'house_ctime desc';
                    break;
                case 'z':
                    $order = 'house_dtime desc';
                    break;
                default:
                    $order = 'house_ctime desc';
                    break;
            }
            
            $HouseModel = new HouseModel;
            $where = $HouseModel->checkWhere($getData);
            //halt($where);
            $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_advance_rent,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,from_unixtime(a.house_ctime, \'%Y-%m-%d\') as house_ctime,from_unixtime(a.house_dtime, \'%Y-%m-%d\') as house_dtime,c.tenant_id,c.tenant_name,d.ban_units,d.ban_floors,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';
            //halt($where);
            $data = [];
            $data['data'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order($order)->select();

            foreach ($data['data'] as $k => &$v) {
                $member_id = Db::name('weixin_member_house')->where([['house_id','eq',$v['house_id']],['dtime','eq',0]])->value('member_id');
                if(empty($member_id)){
                    $v['member_id'] = '';
                }else{
                    $v['member_id'] = $member_id;
                }
                if($v['tenant_id']){ //如果当前房屋已经绑定租户
                    
                    $leaseInfo = Db::name('change_lease')->where([['house_id','eq',$v['house_id']],['change_status','eq',1],['tenant_id','eq',$v['tenant_id']]])->order('id desc')->field("from_unixtime(last_print_time, '%Y-%m-%d %H:%i:%s') as last_print_time,id as change_lease_id")->find();
                    $v['last_print_time'] = $leaseInfo['last_print_time'];
                    $v['change_lease_id'] = $leaseInfo['change_lease_id'];
                }else{
                    $v['change_lease_id'] = '';
                    $v['last_print_time'] = '';
                }  
                //halt($v);
            }
            // 统计房屋建面、计租面积、规租
            $totalRow =  Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(house_lease_area) as total_house_lease_area, sum(house_area) as total_house_area, sum(house_pre_rent) as total_house_pre_rent , sum(house_cou_rent) as total_house_cou_rent, sum(house_diff_rent) as total_house_diff_rent, sum(house_pump_rent) as total_house_pump_rent, sum(house_advance_rent) as total_house_advance_rent')->find();
            if($totalRow){
                $data['total_house_lease_area'] = $totalRow['total_house_lease_area'];
                $data['total_house_area'] = $totalRow['total_house_area'];
                $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
                $data['total_house_cou_rent'] = $totalRow['total_house_cou_rent'];
                $data['total_house_diff_rent'] = $totalRow['total_house_diff_rent'];
                $data['total_house_pump_rent'] = $totalRow['total_house_pump_rent'];
                $data['total_house_advance_rent'] = $totalRow['total_house_advance_rent'];
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

    /**
     * 检查房屋信息的异常数据
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
        
        //$BanModel = new HouseModel();
        $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,c.tenant_id,c.tenant_name,d.ban_units,d.ban_floors,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';
            //halt($where);
            $data = [];
            $data['data'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();

        //$all_data = $BanModel->limit(10)->select()->toArray();
        //$all_data = $Model->select()->toArray();
        //halt($all_data);
        $error_data = [];
        $error_data['base_error'] = [];
        foreach($all_data as $k => $v){
            // if(($v['ban_civil_num'] + $v['ban_party_num'] + $v['ban_career_num']) > 1){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，合栋数异常';
            // }
            // if(!isset($params['owners'][$v['ban_owner_id']])){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，产别异常';
            // }
            // if(!isset($params['structs'][$v['ban_struct_id']])){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，结构类别异常';
            // }
            // if(!isset($params['damages'][$v['ban_damage_id']])){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，完损等级异常';
            // }
            // if(strlen($v['ban_number']) != 10){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，长度异常';
            // }
            // if(!in_array($v['ban_inst_pid'], [2,3]) || $v['ban_inst_id'] > 35 || $v['ban_inst_id'] < 4){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，所属机构异常';
            // }
            // if($v['ban_status'] > 1 && $v['ban_dtime'] == 0){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，状态为注销，但缺失注销时间状态';
            // }
            // if(!in_array($v['ban_cuid'],$useridArr)){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，创建人在系统中无法找到';
            // }
            // if(!in_array($v['ban_use_id'], [1,2,3])){
            //     $error_data['base_error'][] = '楼栋编号：'.$v['ban_number'].'，使用性质异常';
            // }
        }
        //halt($error_data);
        $this->assign('error_data',$error_data);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            if(isset($data['house_id']) && $data['house_id']){
                // 数据验证
                $result = $this->validate($data, 'House.edit');
                if($result !== true) {
                    return $this->error($result);
                }
                $HouseModel = new HouseModel();
                // 修改
                if ($HouseModel->allowField(true)->update($data) === false) {
                    return $this->error('修改失败');
                }
                //$HouseModel = new HouseModel();
                $house_cou_rent = $HouseModel->count_house_rent($data['house_id']);
                
                $HouseModel->where([['house_id','eq',$data['house_id']]])->update(['house_cou_rent'=>$house_cou_rent,'house_pre_rent'=>$house_cou_rent]);

                $row = $HouseModel->get($data['house_id']);
                return $this->success('修改成功','',$row);
            }else{
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

                $row = $HouseModel->allowField(true)->create($filData);
                // 入库
                if (!$row) {
                    return $this->error('新增失败');
                }
                $house_cou_rent = $HouseModel->count_house_rent($row['house_id']);
                $HouseModel->where([['house_id','eq',$row['house_id']]])->update(['house_cou_rent'=>$house_cou_rent,'house_pre_rent'=>$house_cou_rent]);
                $row = $HouseModel->get($row['house_id']);
                return $this->success('新增成功','',$row);
            }
            
        }

        // $group = input('param.group');
        // $row = HouseModel::with(['ban','tenant'])->find($id);
        // $cutRent = Db::name('change_cut')->where([['house_id','eq',$id],['tenant_id','eq',$row['tenant_id']],['change_status','eq',1],['end_date','>',date('Ym')]])->value('cut_rent');
        // $row['cut_rent'] = $cutRent?$cutRent:'0.00';
        // //halt($row);
        // $row['ban_struct_point'] = Db::name('ban_struct_type')->where([['id','eq',$row['ban_struct_id']]])->value('new_point');
        // //halt($row);
        // //获取当前房屋的房间
        // $rooms = $row->house_room()->where([['house_room_status','<=',1]])->order('room_id asc')->column('room_id'); 
        // //halt($rooms);
        // //定义计租表房间数组
        // $HouseModel = new HouseModel;
        // $roomTables = $HouseModel->get_house_renttable($id);

        return $this->fetch();
    }

    public function update()
    {
        $id         = $this->request->param('id');
        $house_advance_rent      = $this->request->param('val');
        if($house_advance_rent < 0){
            return $this->success('金额不能为负数');
        }
        if(HouseModel::where([['house_id','eq',$id]])->update(['house_advance_rent'=>$house_advance_rent]) !== false){
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
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
            if ($HouseModel->allowField(true)->update($data) === false) {
                return $this->error('修改失败');
            }
            $HouseModel = new HouseModel();
            //halt($HouseModel->count_house_pre_rent($data['house_id']));
            //$house_pre_rent = $HouseModel->count_house_pre_rent($data['house_id']);
            $house_cou_rent = $HouseModel->count_house_rent($data['house_id']);

            Db::name('house')->where([['house_id','eq',$data['house_id']]])->update(['house_pre_rent'=>$house_cou_rent,'house_cou_rent'=>$house_cou_rent]);

            $HouseModel = new HouseModel();
            $row = $HouseModel->find($data['house_id']);
            return $this->success('修改成功','',$row);
        }
        $id = input('param.id/d');
        $row = HouseModel::with(['ban','tenant'])->get($id);
        $group = input('param.group');
        $this->assign('group',$group);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function edity()
    {   
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            // $result = $this->validate($data, 'House.edit');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // $HouseModel = new HouseModel();
            // // 入库
            // if ($HouseModel->allowField(true)->update($data) === false) {
            //     return $this->error('修改失败');
            // }
            $ban_info = Db::name('ban')->where([['ban_id','eq',$data['ban_id']]])->field('ban_units')->find();
            if ($ban_info['ban_units'] < $data['house_unit_id']) {
                return $this->error('居住单元号不能大于楼栋总单元数');
            }
            // $HouseModel = new HouseModel();
            //halt($HouseModel->count_house_pre_rent($data['house_id']));
            //$house_pre_rent = $HouseModel->count_house_pre_rent($data['house_id']);
            // $house_cou_rent = $HouseModel->count_house_rent($data['house_id']);

            Db::name('house')->where([['house_id','eq',$data['house_id']]])->update(['house_unit_id'=>$data['house_unit_id'],'house_floor_id'=>$data['house_floor_id']]);

            $HouseModel = new HouseModel();
            $row = $HouseModel->find($data['house_id']);
            return $this->success('修改成功','', $row);
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
        $cutRent = Db::name('change_cut')->where([['house_id','eq',$id],['tenant_id','eq',$row['tenant_id']],['change_status','eq',1],['end_date','>',date('Ym')]])->value('cut_rent');
        $row['cut_rent'] = $cutRent?$cutRent:'0.00';
        //halt($row);
        $row['ban_struct_point'] = Db::name('ban_struct_type')->where([['id','eq',$row['ban_struct_id']]])->value('new_point');
        //halt($row);
        //获取当前房屋的房间
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->order('room_id asc')->column('room_id'); 
        //halt($rooms);
        //定义计租表房间数组
        $HouseModel = new HouseModel;
        $roomTables = $HouseModel->get_house_renttable($id);

         // 统计当前租户的欠租情况
        $RentModel = new RentModel;
        $rentOrderInfo = $RentModel->where([['rent_order_status','eq',1],['house_id','eq',$id],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive - rent_order_paid) total_rent_order_unpaid')->find();
        $row['total_rent_order_unpaid'] = $rentOrderInfo['total_rent_order_unpaid'];
        
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
            $data['house_info'] = $row->toArray();
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

        // 统计当前租户的欠租情况
        $RentModel = new RentModel;
        $rentOrderInfo = $RentModel->where([['rent_order_status','eq',1],['house_id','eq',$id],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive - rent_order_paid) total_rent_order_unpaid')->find();
        $row['total_rent_order_unpaid'] = $rentOrderInfo['total_rent_order_unpaid'];
        //halt($total_rent_order_unpaid);

        //-------------- by lucas 【计租表】 Start ------------------------
        $cutRent = Db::name('change_cut')->where([['house_id','eq',$id],['tenant_id','eq',$row['tenant_id']],['change_status','eq',1],['end_date','>',date('Ym')]])->value('cut_rent');
        $row['cut_rent'] = $cutRent?$cutRent:'0.00';
        $row['ban_struct_point'] = Db::name('ban_struct_type')->where([['id','eq',$row['ban_struct_id']]])->value('new_point');
        //获取当前房屋的房间
        $rooms = $row->house_room()->where([['house_room_status','<=',1]])->order('room_id asc')->column('room_id');
        //定义计租表房间数组
        $HouseModel = new HouseModel;
        $roomTables = $HouseModel->get_house_renttable($id);
        $this->assign('room_tables',$roomTables);
        //-------------- by lucas 【计租表】 End --------------------------
        //halt($row);
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
        $res = HouseModel::where([['house_id','in',$ids],['house_status','eq',0]])->delete();
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

    /**
     * 生成自定义path的微信二维码，用户可以扫描二维码跳转到对应的页面
     * 选用的二维码生成c方案
     * 二维码方案官方文档说明地址：https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/qr-code.html
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 官方文档地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.createQRCode.html
     * 创建时间: 生成二维码
     * @return  返回值  
     * @version 版本  1.0
     */
    public function createqrcode()
    {

        set_time_limit(0);

        $houseModel = new HouseModel;
        $houseNumberArr = $houseModel->where([['house_status','>',0],['house_share_img','eq','']])->field('house_id,house_number')->limit(200)->select();

        //halt($houseNumberArr);
        $WeixinModel = new WeixinModel;
        $i = 0;
        $width = 300;
        foreach($houseNumberArr as $h){
            // C方案生成二维码，C方案生成二维码，有数量限制100000张
            /*$path = 'pages/payment/payment?houseid='.$h['house_id'];
            $filename = '/upload/wechat/qrcode/share_'.$h['house_id'].'_'.$h['house_number'].'.png';
            $result = $WeixinModel->createqrcode($path,$width);
            file_put_contents('.'.$filename,$result);
            $houseModel = new HouseModel;
            $res = $houseModel->where([['house_id','eq',$h['house_id']]])->update(['house_share_img'=>'https://procheck.ctnmit.com'.$filename]);*/

            // B方案生成二维码,无数量限制，但是每分钟最多生成5000张
            $path = 'pages/payment/payment'; //注意路径格式，这个路径不能带参数！
            $filename = '/upload/wechat/qrcode/share_'.$h['house_id'].'_'.$h['house_number'].'.png';
            $result = $WeixinModel->createMiniScene($h['house_id'] , $path,$width); //B方案生成二维码，
            //halt($result);
            file_put_contents('.'.$filename,$result);
            $houseModel = new HouseModel;
            $res = $houseModel->where([['house_id','eq',$h['house_id']]])->update(['house_share_img'=>'https://pro.ctnmit.com'.$filename]);
            if($res){
               $i++; 
            }
            //halt($res);
        } 
        halt($i);
        //return $this->success('生成成功，一共生成'.$i.'张二维码！');

    }

    /**
     * excel导出
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-05-11 16:30:36
     * @return  返回值  
     * @version 版本  1.0
     */
    public function export()
    {   
        if ($this->request->isAjax()) {
            $getData = $this->request->post();
            $houseModel = new HouseModel;
            $where = $houseModel->checkWhere($getData);
            $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,a.house_status,c.tenant_id,c.tenant_name,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';

            $tableData = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('house_ctime desc')->select();

            foreach ($tableData as $k => &$v) {
                if($v['tenant_id']){ //如果当前房屋已经绑定租户
                    $v['last_print_time'] = Db::name('change_lease')->where([['house_id','eq',$v['house_id']],['change_status','eq',1],['tenant_id','eq',$v['tenant_id']]])->order('id desc')->value("from_unixtime(last_print_time, '%Y-%m-%d %H:%i:%s') as last_print_time");
                }else{
                    $v['last_print_time'] = '';
                }  
                unset($v['house_id'],$v['tenant_id']);
            }
            // halt($tableData);
            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                    array('title' => '楼栋编号', 'field' => 'ban_number', 'width' => 12 ,'type' => 'string'),
                    array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),                    
                    array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                    array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                    array('title' => '规定租金', 'field' => 'house_pre_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '计算租金', 'field' => 'house_cou_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '租差', 'field' => 'house_diff_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '泵费', 'field' => 'house_pump_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '协议租金', 'field' => 'house_protocol_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '使用面积', 'field' => 'house_use_area', 'width' => 12,'type' => 'number'),
                    array('title' => '计租面积', 'field' => 'house_lease_area', 'width' => 12,'type' => 'number'),
                    array('title' => '房屋建面', 'field' => 'house_area', 'width' => 12,'type' => 'number'),
                    array('title' => '房屋原价', 'field' => 'house_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '是否已暂停计租', 'field' => 'house_is_pause', 'width' => 24,'type' => 'string'),
                    array('title' => '居住单元', 'field' => 'house_unit_id', 'width' => 12,'type' => 'number'),
                    array('title' => '居住层', 'field' => 'house_floor_id', 'width' => 12,'type' => 'number'),
                    array('title' => '门牌号', 'field' => 'house_door', 'width' => 12,'type' => 'string'),
                    array('title' => '结构类别', 'field' => 'ban_struct_id', 'width' => 12,'type' => 'string'),
                    array('title' => '完损等级', 'field' => 'ban_damage_id', 'width' => 12,'type' => 'string'),
                    array('title' => '出证时间', 'field' => 'last_print_time', 'width' => 24,'type' => 'string'),
                    array('title' => '状态', 'field' => 'house_status', 'width' => 12,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '房屋数据',
                    'Title' => '房屋数据',
                ];
                //halt($tableData);
                return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
            }else{
                $result = [];
                $result['code'] = 0;
                $result['msg'] = '数据为空！';
                return json($result); 
            }
            
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

    public function print_out()
    {
        $html = <<<EOF
    <style>
        .PageNext {page-break-after: always;font-family: 'Microsoft YaHei';width: 310px;}
        .j-print-title{width: 310px; font-size: 20px;padding: 0 0 10px;font-weight: bold;display: inline-block;text-align: center;}
        .j-print-table{border: 1px solid #333;border-collapse: collapse; width: 310px;font-size: 14px;font-weight: 200;box-sizing: border-box;display: inline-block;padding:6px;}
        .j-print-table td{border: 1px solid #333;border-collapse: collapse;background-color: #fff;box-sizing: border-box;height:20px;line-height: 20px;}
        .j-print-table td.j-print-90{width: 90px;}
        .j-print-table td.j-print-120{width: 103px;}
        .j-print-table td.j-print-con{border: 1px solid #333;border-collapse: collapse;background-color: #fff;box-sizing: border-box;line-height: 18px;font-size: 12px;}
        .j-print-table td.j-print-con span{line-height: 18px;display:block;}
    </style>
    <div class="PageNext">
        <div class="j-print-title">缴费单<br/></div>
        <table class="j-print-table">
            <tr>
                <td class="j-print-90" align="left">租户名</td>
                <td colspan="2"  align="left">刘道荣</td>
            </tr>
            <tr>
                <td class="j-print-90" align="left">租户地址</td>
                <td colspan="2" align="left">新生里还建楼1栋</td>
            </tr>
            <tr>
                <td class="j-print-90" align="left">历史欠租</td>
                <td class="j-print-120" align="left">1667.2</td>
                <td rowspan="3">
                    <img  style="width: 100px;box-sizing: border-box;" src="https://procheck.ctnmit.com/upload/wechat/qrcode/share_1_10020050010001.png" />
                </td>
            </tr>
            <tr>
                <td class="j-print-90" align="left">本期欠租</td>
                <td class="j-print-120" align="left">97.5</td>
            </tr>
            <tr>
                <td class="j-print-90" align="left">合计欠租</td>
                <td class="j-print-120" align="left">16672</td>
            </tr>
            <tr>
                <td class="j-print-con" colspan="3" align="left">
                    <span>尊敬的租户：</span>
                    <span>可能是您的疏忽或者其它原因未来得及处理，请务必于2020年6月25日前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>
                    <span>特此通知，谢谢合作！</span>  
                </td>
            </tr>
        </table>
    </div>
EOF;
//echo $html;exit;
        $SystemTcpdf = new SystemTcpdf;
        $SystemTcpdf->example_000($html,[95,95]);
    }
}