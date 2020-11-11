<?php
namespace app\report\admin;

use think\Db;
use app\system\admin\Admin;
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
            $options = $this->request->get();
            $owner = input('owner',11);
            $date = input('month');
            $group = input('group');
            //halt($group);
            $inst = isset($options['inst'])?$options['inst']:INST;

            $data = [];
            // $dataJson = Db::name('report')->where([['type','eq','PropertyReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            $dataJson = @file_get_contents(ROOT_PATH.'file/report/property/'.str_replace('-','',$date).'.txt');
            // 如果没有缓存数据
            if(!$dataJson){
                $date = date('Y-m');
                // 如果查的是当月或当年的数据，实时显示
                if($date == date('Y-m') || $date == date('Y')){
                    $MonthPropertyReportModel = new MonthPropertyReportModel;
                    $datas  = $MonthPropertyReportModel->makeMonthPropertyReport($date);
                    // halt($datas);
                // 如果查的是不是当月或当年的数据，提示暂无数据
                }else{
                    $data['code'] = 0;
                    $data['msg'] = '暂无数据！';
                    return json($data); 
                }     
            // 如果没有缓存数据         
            }else{
                $datas = json_decode($dataJson,true);
            }
            // halt($datas[$owner][$inst]);  
            $data['data'] = $datas?$datas[$owner][$inst]:array();
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

    public function index()
    {
        if ($this->request->isAjax()) {
            $data = request()->param();
            $where = [];
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
                $where[] = ['a.change_type','in',[5,7,8,9,10]];
            }
            
            // 检索楼栋注销时间
            if(isset($data['order_date']) && $data['order_date']){
                $order_date = str_replace('-', '', $data['order_date']);
               
                $where[] = ['order_date','eq',$order_date];
            }
            // // 检索楼栋创建日期
            // if(isset($data['ban_ctime']) && $data['ban_ctime']){
            //     $start = strtotime($data['ban_ctime']);
            //     $end = strtotime('+ 1 month',$start);
            //     //dump($start);halt($end);
            //     $where[] = ['ban_ctime','between',[$start,$end]];
            // }
            // 检索机构
            if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
                $insts = explode(',',$data['ban_inst_id']);
                $instid_arr = [];
                foreach ($insts as $inst) {
                    foreach (config('inst_ids')[$inst] as $instid) {
                        $instid_arr[] = $instid;
                    }
                }
                $where[] = ['b.ban_inst_id','in',array_unique($instid_arr)];
            }else{
                $where[] = ['b.ban_inst_id','in',config('inst_ids')[INST]];
            }

            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $result = [];


            // halt($where);
            $where[] = ['a.change_status','eq',1];
            $fields = 'a.change_order_number,a.change_type,a.change_rent,a.change_area,a.change_use_area,a.change_oprice,a.change_ban_num,a.tenant_id,a.house_id,a.ban_id,a.order_date,a.owner_id,a.use_id,b.ban_number,b.ban_address,b.ban_damage_id,b.ban_struct_id,b.ban_inst_id,c.tenant_name,d.house_number';
            $temp = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id')->where($where)->field($fields)->page($page)->limit($limit)->select();
            foreach ($temp as $k => &$v) {
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
            $result['count'] = Db::name('change_table')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('house d','a.house_id = d.house_id')->where($where)->count('a.change_order_number');
            $result['code'] = 0;
            $result['msg'] = '';
            return json($result);
        }
    }

    /**
     * 产权异动统计明细表
     * @return [type] [description]
     */
    public function changes_statis()
    {
        $cacheDate = 202007;

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

            $data = [];
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
            // $result = $RadixReportModel->radix($next_month_begin_time , $next_month_end_time);
            // 非基数异动统计
            $resultNoRadix = $RadixReportModel->noRadix($next_month);
            // $resultNoRadix = $RadixReportModel->noRadix($next_month_begin_time , $next_month_end_time);
            // 租金异动统计
            $resultRent = $RadixReportModel->rent($query_month);
            // $resultRent = $RadixReportModel->rent($query_month_begin_time , $query_month_end_time);
            // halt($resultRent);
            // $dataJson = Db::name('report')->where([['type','eq','PropertyReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            // $dataJson = @file_get_contents(ROOT_PATH.'file/report/property/'.str_replace('-','',$date).'.txt');
            // // 如果没有缓存数据
            // if(!$dataJson){
            //     // 如果查的是当月或当年的数据，实时显示
            //     if($date == date('Y-m') || $date == date('Y')){
            //         $MonthPropertyReportModel = new MonthPropertyReportModel;
            //         $datas  = $MonthPropertyReportModel->makeMonthPropertyReport($date);
            //     // 如果查的是不是当月或当年的数据，提示暂无数据
            //     }else{
            //         $data['code'] = 0;
            //         $data['msg'] = '暂无数据！';
            //         return json($data); 
            //     }     
            // // 如果没有缓存数据         
            // }else{
            //     $datas = json_decode($dataJson,true);
            // }   
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
}	