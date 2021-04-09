<?php
namespace app\report\admin;

use think\Db;
use app\system\admin\Admin;
use app\common\model\SystemExport;
use app\report\model\Report as ReportModel;
use app\report\model\RadixReport as RadixReportModel;
use app\report\model\HouseReport as HouseReportModel;
use app\report\model\MonthPropertyReport as MonthPropertyReportModel;
use app\report\model\YearPropertyReport as YearPropertyReportModel;

class Deal extends Admin
{
	/**
     * 产权异动统计明细表
     * @return [type] [description]
     */
    public function changes()
    {
        // //把所有房屋统计报表数据同步写入到文件中去
        // $ReportModel = new ReportModel;
        // $tempData = $ReportModel->where([['type','eq','PropertyReport']])->column('date,data');
        // foreach ($tempData as $k => $v) {
        //     file_put_contents(ROOT_PATH.'file/report/property/'.$k.'.txt', $v);
        // }
        $owerLst = [1 => '市属',2 => '区属',5 => '自管',6 => '生活',10 => '市区自',11 => '所有产别',];     
        if ($this->request->isAjax()) {
            $data = request()->param();
            $where = [];
            $where[] = ['a.change_status','eq',1];
            // 检索楼栋编号
            if(isset($data['ban_number']) && $data['ban_number']){
                $where[] = ['b.ban_number','like','%'.$data['ban_number'].'%'];                
            }
            // 检索楼栋地址
            if(isset($data['ban_address']) && $data['ban_address']){
                $where[] = ['b.ban_address','like','%'.$data['ban_address'].'%'];
            }
            // 检索产别
            if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
                $where[] = ['a.owner_id','in',explode(',',$data['ban_owner_id'])];
            }
            // 检索产别
            if(isset($data['ban_use_id']) && $data['ban_use_id']){
                $where[] = ['a.use_id','in',explode(',',$data['ban_use_id'])];
            }
            // 检索结构类别
            if(isset($data['ban_struct_id']) && $data['ban_struct_id']){
                $where[] = ['b.ban_struct_id','in',explode(',',$data['ban_struct_id'])];
            }
            // 检索完损等级
            if(isset($data['ban_damage_id']) && $data['ban_damage_id']){
                $where[] = ['b.ban_damage_id','in',explode(',',$data['ban_damage_id'])];
            }
            // 检索异动类型
            if(isset($data['change_type']) && $data['change_type']){
                $where[] = ['a.change_type','in',explode(',',$data['change_type'])];
            }else{
                $where[] = ['a.change_type','in',[7,8,10,12,14]];
            }
            
            // 检索异动时间
            if(isset($data['order_date'])){
                if($data['order_date']){
                    $order_date = str_replace('-', '', $data['order_date']);
               
                    $where[] = ['order_date','eq',$order_date];
                }else{

                } 
            }else{
                $where[] = ['order_date','eq',date('Ym')];
            }
            // // 检索楼栋创建日期
            // if(isset($data['ban_ctime']) && $data['ban_ctime']){
            //     $start = strtotime($data['ban_ctime']);
            //     $end = strtotime('+ 1 month',$start);
            //     //dump($start);halt($end);
            //     $where[] = ['ban_ctime','between',[$start,$end]];
            // }
            // 检索机构
            if(isset($data['inst_id']) && $data['inst_id']){
                $insts = explode(',',$data['inst_id']);
                $instid_arr = [];
                foreach ($insts as $inst) {
                    foreach (config('inst_ids')[$inst] as $instid) {
                        $instid_arr[] = $instid;
                    }
                }
                $where[] = ['a.inst_id','in',array_unique($instid_arr)];
            }else{
                $where[] = ['a.inst_id','in',config('inst_ids')[INST]];
            }

            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $result = [];


            // halt($where);
            $where[] = ['a.change_status','eq',1];
            $fields = 'a.change_order_number,a.change_type,sum(a.change_rent) as change_rent,sum(a.change_area) as change_area,sum(a.change_use_area) as change_use_area,sum(a.change_oprice) as change_oprice,sum(a.change_ban_num) as change_ban_num,a.tenant_id,a.house_id,a.ban_id,a.order_date,a.owner_id,a.use_id,sum(a.change_month_rent) as change_month_rent,a.inst_id,a.new_inst_id,sum(a.change_year_rent) as change_year_rent,b.ban_number,b.ban_address,b.ban_damage_id,b.ban_struct_id,b.ban_inst_id,c.tenant_name,d.house_number';
            $temp = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id','left')->where($where)->field($fields)->page($page)->group('a.change_order_number')->having('change_rent + change_area + change_use_area + change_oprice > 0')->limit($limit)->select();
            // halt($temp);
            foreach ($temp as $k => &$v) {
                if($v['change_type'] == 12){ // 把租金调整，都改成房屋调整
                    $v['change_type'] = 9;
                }
                
                $v['order_date'] = substr_replace($v['order_date'], '-', 4,0);
                if(empty($v['tenant_id'])){
                    $house_info = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id')->field('b.tenant_id,b.tenant_name')->where([['house_id','eq',$v['house_id']]])->find();
                    if($house_info){
                       $v['tenant_id'] = $house_info['tenant_id'];
                       $v['tenant_name'] = $house_info['tenant_name'];
                    }
                }
            }
            $result['data'] = $temp;
            $result['count'] = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id','left')->where($where)->group('a.change_order_number')->having('sum(change_rent) + sum(change_area) + sum(change_use_area) + sum(change_oprice) > 0')->count('a.change_order_number');
            $result['code'] = 0;
            $result['msg'] = '';
            return json($result);
        }
        
        $this->assign('owerLst',$owerLst);
        return $this->fetch();
    }

    

    /**
     * 产权异动明细表导出
     * @return [type] [description]
     */
    public function export()
    {
        if ($this->request->isAjax()) {
            $data = request()->param();
            $where = [];
            $where[] = ['a.change_status','eq',1];
            // 检索楼栋编号
            if(isset($data['ban_number']) && $data['ban_number']){
                $where[] = ['b.ban_number','like','%'.$data['ban_number'].'%'];                
            }
            // 检索楼栋地址
            if(isset($data['ban_address']) && $data['ban_address']){
                $where[] = ['b.ban_address','like','%'.$data['ban_address'].'%'];
            }
            // 检索产别
            if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
                $where[] = ['a.owner_id','in',explode(',',$data['ban_owner_id'])];
            }
            // 检索产别
            if(isset($data['ban_use_id']) && $data['ban_use_id']){
                $where[] = ['a.use_id','in',explode(',',$data['ban_use_id'])];
            }
            // 检索结构类别
            if(isset($data['ban_struct_id']) && $data['ban_struct_id']){
                $where[] = ['b.ban_struct_id','in',explode(',',$data['ban_struct_id'])];
            }
            // 检索完损等级
            if(isset($data['ban_damage_id']) && $data['ban_damage_id']){
                $where[] = ['b.ban_damage_id','in',explode(',',$data['ban_damage_id'])];
            }
            // 检索异动类型
            if(isset($data['change_type']) && $data['change_type']){
                $where[] = ['a.change_type','in',explode(',',$data['change_type'])];
            }else{
                $where[] = ['a.change_type','in',[7,8,10,12,14]];
            }
            
            // 检索异动时间
            if(isset($data['order_date'])){
                if($data['order_date']){
                    $order_date = str_replace('-', '', $data['order_date']);
               
                    $where[] = ['order_date','eq',$order_date];
                }else{

                } 
            }else{
                $where[] = ['order_date','eq',date('Ym')];
            }
            // // 检索楼栋创建日期
            // if(isset($data['ban_ctime']) && $data['ban_ctime']){
            //     $start = strtotime($data['ban_ctime']);
            //     $end = strtotime('+ 1 month',$start);
            //     //dump($start);halt($end);
            //     $where[] = ['ban_ctime','between',[$start,$end]];
            // }
            // 检索机构
            if(isset($data['inst_id']) && $data['inst_id']){
                $insts = explode(',',$data['inst_id']);
                $instid_arr = [];
                foreach ($insts as $inst) {
                    foreach (config('inst_ids')[$inst] as $instid) {
                        $instid_arr[] = $instid;
                    }
                }
                $where[] = ['a.inst_id','in',array_unique($instid_arr)];
            }else{
                $where[] = ['a.inst_id','in',config('inst_ids')[INST]];
            }

            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $result = [];


            // halt($where);
            $where[] = ['a.change_status','eq',1];
            $fields = 'a.change_order_number,a.change_type,sum(a.change_rent) as change_rent,sum(a.change_area) as change_area,sum(a.change_use_area) as change_use_area,sum(a.change_oprice) as change_oprice,sum(a.change_ban_num) as change_ban_num,a.tenant_id,a.house_id,a.ban_id,a.order_date,a.owner_id,a.use_id,sum(a.change_month_rent) as change_month_rent,a.inst_id,a.new_inst_id,sum(a.change_year_rent) as change_year_rent,b.ban_number,b.ban_address,b.ban_damage_id,b.ban_struct_id,b.ban_inst_id,c.tenant_name,d.house_number';
            $tableData = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id','left')->group('a.change_order_number')->having('change_rent + change_area + change_use_area + change_oprice > 0')->where($where)->field($fields)->select();
            foreach ($tableData as $k => &$v) {
                if($v['change_type'] == 12){ // 把租金调整，都改成房屋调整
                    $v['change_type'] = 9;
                }
                $v['ban_holds'] = 1;
                $v['remark'] = '';
                $v['order_date'] = substr_replace($v['order_date'], '-', 4,0);
                if(empty($v['tenant_id'])){
                    $house_info = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id')->field('b.tenant_id,b.tenant_name')->where([['house_id','eq',$v['house_id']]])->find();
                    if($house_info){
                       $v['tenant_id'] = $house_info['tenant_id'];
                       $v['tenant_name'] = $house_info['tenant_name'];
                    }
                }
            }
            // halt($tableData);
            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '异动类型', 'field' => 'change_type', 'width' => 12,'type' => 'string'),
                    array('title' => '异动编号', 'field' => 'change_order_number', 'width' => 24,'type' => 'string'),
                    array('title' => '管段', 'field' => 'inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '新管段', 'field' => 'new_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '楼栋编号', 'field' => 'ban_number', 'width' => 12 ,'type' => 'string'),                 
                    array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                    array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                    array('title' => '户数', 'field' => 'ban_holds', 'width' => 12,'type' => 'number'),
                    array('title' => '规定租金', 'field' => 'change_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '计租面积', 'field' => 'change_use_area', 'width' => 12,'type' => 'number'),
                    array('title' => '建筑面积', 'field' => 'change_area', 'width' => 12,'type' => 'number'),
                    array('title' => '原价', 'field' => 'change_oprice', 'width' => 12,'type' => 'number'),
                    array('title' => '栋数', 'field' => 'change_ban_num', 'width' => 12,'type' => 'number'),
                    array('title' => '生效时间', 'field' => 'order_date', 'width' => 24,'type' => 'string'),
                    array('title' => '产别', 'field' => 'owner_id', 'width' => 12,'type' => 'number'),                    
                    array('title' => '使用性质', 'field' => 'use_id', 'width' => 12,'type' => 'string'),
                    array('title' => '完损等级', 'field' => 'ban_damage_id', 'width' => 12,'type' => 'string'),
                    array('title' => '结构类别', 'field' => 'ban_struct_id', 'width' => 12,'type' => 'string'),
                    array('title' => '备注', 'field' => 'remark', 'width' => 12,'type' => 'string'),

                    // array('title' => '计算租金', 'field' => 'house_cou_rent', 'width' => 12,'type' => 'number'),
                    // array('title' => '租差', 'field' => 'house_diff_rent', 'width' => 12,'type' => 'number'),
                    // array('title' => '泵费', 'field' => 'house_pump_rent', 'width' => 12,'type' => 'number'),
                    // array('title' => '协议租金', 'field' => 'house_protocol_rent', 'width' => 12,'type' => 'number'),
                    // array('title' => '使用面积', 'field' => 'house_use_area', 'width' => 12,'type' => 'number'),
                    
                    
                   
                    // array('title' => '是否已暂停计租', 'field' => 'house_is_pause', 'width' => 24,'type' => 'string'),
                    // array('title' => '是否绑定微信', 'field' => 'is_bind_weixin', 'width' => 24,'type' => 'string'),
                    // array('title' => '居住单元', 'field' => 'house_unit_id', 'width' => 12,'type' => 'number'),
                    // array('title' => '居住层', 'field' => 'house_floor_id', 'width' => 12,'type' => 'number'),
                    // array('title' => '门牌号', 'field' => 'house_door', 'width' => 12,'type' => 'string'),
                    
                    
                    // array('title' => '出证时间', 'field' => 'last_print_time', 'width' => 24,'type' => 'string'),
                    // array('title' => '状态', 'field' => 'house_status', 'width' => 12,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '产权异动明细数据',
                    'Title' => '产权异动明细数据',
                ];
                //halt($tableData);
                return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
            }else{
                $result = [];
                $result['code'] = 0;
                $result['msg'] = '数据为空！';
                return json($result); 
            }
            // $result['data'] = $temp;
            // $result['count'] = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id')->where($where)->count('a.change_order_number');
            // $result['code'] = 0;
            // $result['msg'] = '';
            // return json($result);
        }
    }

    // public function index()
    // {
    //     if ($this->request->isAjax()) {
    //         $data = request()->param();
    //         $where = [];
    //         // 检索楼栋编号
    //         if(isset($data['ban_number']) && $data['ban_number']){
    //             $where[] = ['b.ban_number','like','%'.$data['ban_number'].'%'];                
    //         }
    //         // 检索楼栋地址
    //         if(isset($data['ban_address']) && $data['ban_address']){
    //             $where[] = ['b.ban_address','like','%'.$data['ban_address'].'%'];
    //         }
    //         // 检索产别
    //         if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
    //             $where[] = ['a.owner_id','in',explode(',',$data['ban_owner_id'])];
    //         }
    //         // 检索产别
    //         if(isset($data['ban_use_id']) && $data['ban_use_id']){
    //             $where[] = ['a.use_id','in',explode(',',$data['ban_use_id'])];
    //         }
    //         // 检索结构类别
    //         if(isset($data['ban_struct_id']) && $data['ban_struct_id']){
    //             $where[] = ['b.ban_struct_id','in',explode(',',$data['ban_struct_id'])];
    //         }
    //         // 检索完损等级
    //         if(isset($data['ban_damage_id']) && $data['ban_damage_id']){
    //             $where[] = ['b.ban_damage_id','in',explode(',',$data['ban_damage_id'])];
    //         }
    //         // 检索异动类型
    //         if(isset($data['change_type']) && $data['change_type']){
    //             $where[] = ['a.change_type','in',explode(',',$data['change_type'])];
    //         }else{
    //             $where[] = ['a.change_type','in',[7,8,9,10]];
    //         }
            
    //         // 检索异动时间
    //         if(isset($data['order_date']) && $data['order_date']){
    //             $order_date = str_replace('-', '', $data['order_date']);
               
    //             $where[] = ['order_date','eq',$order_date];
    //         }else{
    //             $where[] = ['order_date','eq',date('Ym')];
    //         }
    //         // // 检索楼栋创建日期
    //         // if(isset($data['ban_ctime']) && $data['ban_ctime']){
    //         //     $start = strtotime($data['ban_ctime']);
    //         //     $end = strtotime('+ 1 month',$start);
    //         //     //dump($start);halt($end);
    //         //     $where[] = ['ban_ctime','between',[$start,$end]];
    //         // }
    //         // 检索机构
    //         if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
    //             $insts = explode(',',$data['ban_inst_id']);
    //             $instid_arr = [];
    //             foreach ($insts as $inst) {
    //                 foreach (config('inst_ids')[$inst] as $instid) {
    //                     $instid_arr[] = $instid;
    //                 }
    //             }
    //             $where[] = ['b.ban_inst_id','in',array_unique($instid_arr)];
    //         }else{
    //             $where[] = ['b.ban_inst_id','in',config('inst_ids')[INST]];
    //         }

    //         $page = input('param.page/d', 1);
    //         $limit = input('param.limit/d', 10);

    //         $result = [];


    //         // halt($where);
    //         $where[] = ['a.change_status','eq',1];
    //         $fields = 'a.change_order_number,a.change_type,a.change_rent,a.change_area,a.change_use_area,a.change_oprice,a.change_ban_num,a.tenant_id,a.house_id,a.ban_id,a.order_date,a.owner_id,a.use_id,b.ban_number,b.ban_address,b.ban_damage_id,b.ban_struct_id,b.ban_inst_id,c.tenant_name,d.house_number';
    //         $temp = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id')->where($where)->field($fields)->page($page)->limit($limit)->select();
    //         foreach ($temp as $k => &$v) {
    //             $v['order_date'] = substr_replace($v['order_date'], '-', 4,0);
    //             if(empty($v['tenant_id'])){
    //                 $house_info = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id')->field('b.tenant_id,b.tenant_name')->where([['house_id','eq',$v['house_id']]])->find();
    //                 if($house_info){
    //                    $v['tenant_id'] = $house_info['tenant_id'];
    //                    $v['tenant_name'] = $house_info['tenant_name'];
    //                 }
    //             }
    //         }
    //         $result['data'] = $temp;
    //         $result['count'] = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id')->where($where)->count('a.change_order_number');
    //         $result['code'] = 0;
    //         $result['msg'] = '';
    //         return json($result);
    //     }
    // }

    /**
     * 产权异动统计明细表
     * @return [type] [description]
     */
    public function changes_statis()
    {
        // $cacheDate = 202007;

        //halt($result);
        //$this->assign('data',$result[12][1]);

        //halt(1);
        // //把所有房屋统计报表数据同步写入到文件中去
        // $ReportModel = new ReportModel;
        // $tempData = $ReportModel->where([['type','eq','PropertyReport']])->column('date,data');
        // foreach ($tempData as $k => $v) {
        //     file_put_contents(ROOT_PATH.'file/report/property/'.$k.'.txt', $v);
        // }
        $owerLst = [1 => '市属',2 => '区属',3 => '代管',5 => '自管', 6 => '生活', 7 => '托管', 10 => '市代托',11 => '市区代托', 12 => '所有产别'];      
        if ($this->request->isAjax()) {
            $owner_id = input('owner_id');
            $query_month = input('query_month');
            $next_month_time = strtotime('first day of next month',strtotime($query_month));
            $next_month = date('Y-m',$next_month_time);
            // halt($next_month);
            $inst_id = input('inst_id');

            $date = str_replace('-','',$query_month);
            $dataJson = @file_get_contents(ROOT_PATH.'file/report/change/'.str_replace('-','',$date).'.txt');
            $data = [];
            if($dataJson){
                $datas = json_decode($dataJson,true);
//                halt($datas);
                $result = $datas['radix'];
                $resultNoRadix = $datas['noRadix'];
                $resultRent = $datas['rent'];
                $data['query_month_simple'] = $query_month;
                $data['query_month'] = date('Y年m月',strtotime($query_month));
                $data['next_month'] = date('Y年m月',$next_month_time);
                $data['inst_id'] = $inst_id;
                $data['owner_id'] = $owner_id;
            }else{
                $data['query_month_simple'] = $query_month;
                $data['query_month'] = date('Y年m月',strtotime($query_month));
                $data['next_month'] = date('Y年m月',$next_month_time);
                $data['inst_id'] = $inst_id;
                $data['owner_id'] = $owner_id;

                $RadixReportModel = new RadixReportModel;

                // 查询当月时间1日零时
                $query_month_begin_time = $query_month.'-01';
                // 查询当月时间28日零时
                $query_month_end_time = $query_month.'-28';
                // 查询的下月时间1日零时
                $next_month_begin_time = $next_month.'-01';
                // 查询的下月时间28日零时
                $next_month_end_time = $next_month.'-28';

                // dump($query_month_begin_time);dump($query_month_end_time);dump($next_month_begin_time);halt($next_month_end_time);
                // 基数异动统计
                $result = $RadixReportModel->radix($next_month);
                // halt($result);
                // $result = $RadixReportModel->radix($next_month_begin_time , $next_month_end_time);
                // 非基数异动统计
                $resultNoRadix = $RadixReportModel->noRadix($query_month,$next_month);
                // $resultNoRadix = $RadixReportModel->noRadix($next_month_begin_time , $next_month_end_time);
                // 租金异动统计
                $resultRent = $RadixReportModel->rent($query_month);
            }



            $data['data'] = $result[$owner_id][$inst_id];
            $data['no_radix_data'] = $resultNoRadix[$owner_id][$inst_id];
            $data['rent_data'] = $resultRent[$owner_id][$inst_id];
            $data['msg'] = '';
            if($data['data']){
                $data['code'] = 1;
                $data['msg'] = '获取成功！';
            }else{
                $data['code'] = 0;
                $data['msg'] = '暂无数据！';               
            }
            return json($data);
        }
        
        $this->assign('owerLst',$owerLst);
        return $this->fetch();
    }

    /**
     * 产权异动统计明细表
     * @return [type] [description]
     */
    public function make_changes_statis_report()
    {
        set_time_limit(0);
        $query_month = date('Y-m');
        $date = str_replace('-','',$query_month);
        $next_month_time = strtotime('first day of next month',strtotime($query_month));
        $next_month = date('Y-m',$next_month_time);

        $RadixReportModel = new RadixReportModel;
        $result = [];
        $result['radix'] = $RadixReportModel->radix($next_month);
        $result['noRadix'] = $RadixReportModel->noRadix($query_month,$next_month);
        $result['rent'] = $RadixReportModel->rent($query_month);

//        $HouseReportdata = $HouseReportModel->makeHouseReport($date);
        file_put_contents(ROOT_PATH.'file/report/change/'.$date.'.txt', json_encode($result));
        $data = [];
        $data['msg'] = substr($date,0,4).'-'.substr($date,4,2).'月报，保存成功！';
        $data['code'] = 1;
        return json($data);

    }
}	