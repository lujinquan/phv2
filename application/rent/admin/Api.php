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

namespace app\rent\admin;

use think\Db;
use app\common\controller\Common;
use app\rent\model\Rent as RentModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\report\model\MonthReport as MonthReportModel;
use app\rent\model\Invoice as InvoiceModel;
use app\rent\model\Recharge as RechargeModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 首页的第一部分
     * @param ban_inst_id 机构id 
     * @param ctime 月份
     * @return json 10：市属、2区属、5自管、12所有
     */
    public function indexPartOne() 
    {
        if ($this->request->isAjax()) {
            $getData = $this->request->get();
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                //$startTime = str_replace('/', '', $getData['ctime']);
                //$where[] = ['date','eq',$startTime];
                $query_month = str_replace('/', '', $getData['ctime']);
            }else{
                //$where[] = ['date','eq',date('Ym')];
                $query_month = date('Ym');
            }
            
            $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$query_month.'.txt');
            if($tempData){
                $temps = json_decode($tempData,true);
            }else{
                // $MonthReportModel = new MonthReportModel;
                // $temps = $MonthReportModel->makeMonthReport($query_month);

                // cache('rent_'.$query_month,$temps,200);

                $temp_cache = cache('rent_'.$query_month);
                if($temp_cache){
                    $temps = $temp_cache;
                }else{
                    $MonthReportModel = new MonthReportModel;
                    $temps = $MonthReportModel->makeMonthReport($query_month);
                    //cache('rent_'.$query_month,$temps,200);    
                }
            }
            //halt($res);
            //$tempData = $ReportModel->where($where)->value('data');

            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:session('admin_user.inst_id');
            $ownerid = 12; //查询所有产别
            if($temps){
                // $temps = json_decode($tempData,true);
                $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            }else{
                $data['data'] = [];
            }
            
            $data = $result = [];
            $owners = [2,5,10,12];
            if($temps){
                // $temps = json_decode($tempData,true);
                foreach ($owners as $v) {
                    for ($i=1;$i<4;$i++ ) {
                        if($i == 1){
                            $j = 13;
                        }
                        if($i == 2){
                            $j = 1;
                        }
                        if($i == 3){
                            $j = 10;
                        }
                        $data['data'][$v][$i] = [
                            'rent_order_receives' => $temps[$v][$instid][17][$j], //统计应收租金的合计（住宅+机关+企业）
                            'rent_order_paids' => $temps[$v][$instid][18][$j],  //统计已缴租金的合计（住宅+机关+企业）
                        ];
                    }
                }
            }else{
                foreach ($owners as $v) {
                    for ($i=1;$i<4;$i++ ) {
                        $data['data'][$v][$i] = ['rent_order_receives' => 0,'rent_order_paids' => 0];
                    }
                }
            }
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

    /**
     * 首页的第二部分
     * @param ctime 月份
     * @return json 
     */
    public function indexPartTwo()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->get();
            $where[] = ['a.status','eq',1];
            // 检索申请时间
	        if(isset($getData['ctime']) && $getData['ctime']){
	            $startTime = strtotime($getData['ctime']);
	            $where[] = ['a.ctime','between time',[$startTime,$startTime+3600*24]];
	        }
            if(isset($getData['change_type']) && $getData['change_type']){
                $where[] = ['change_type','eq',$getData['change_type']];
            }
            // 检索楼栋机构
            $insts = config('inst_ids');
            if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
                $where[] = ['d.ban_inst_id','in',$insts[$data['ban_inst_id']]];
            }else{
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:session('admin_user.inst_id');
                $where[] = ['d.ban_inst_id','in',$insts[$instid]];
            }
            $fields = "a.id,a.change_id,a.print_times,a.change_type,a.curr_role,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%s') as ctime,b.nick,d.ban_inst_id";
            $data = $result = [];
            
            $temps = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->join('system_user b','a.cuid = b.id','left')->field($fields)->where($where)->order('a.ctime asc')->select();

            foreach($temps as $k => $v){
                // 如果业务审批角色 = 当前登录角色，且当前角色不是房管员
                if($v['curr_role'] == session('admin_user.role_id') && session('admin_user.role_id') != 4){
                	$result[] = $v;
                }
            }
            $data['data'] = array_slice($result, ($page- 1) * $limit, $limit);
            $data['count'] = count($result);
            $data['code'] = 0;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

    /**
     * 首页的第三部分
     * @param ban_inst_id 机构id 
     * @param ctime 月份
     * @return json 10：市属、2区属、5自管、12所有
     */
    public function indexPartThree()
    {
        if ($this->request->isAjax()) {
            $getData = $this->request->get();
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                //$startTime = str_replace('/', '', $getData['ctime']);
                //$where[] = ['date','eq',$startTime];
                //$where[] = ['order_date','eq',201911];
                $query_month = str_replace('/', '', $getData['ctime']);
            }else{
                //$where[] = ['date','eq',date('Ym')];
                $query_month = date('Ym');
            }

            //halt($where);
            // $tempData = Db::name('report')->where($where)->value('data');
            // $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:session('admin_user.inst_id');

            // $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$query_month.'.txt');
            $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$query_month.'.txt');
            if($tempData){
                $temps = json_decode($tempData,true);
            }else{
                $temp_cache = cache('rent_'.$query_month);
                if($temp_cache){
                    $temps = $temp_cache;
                }else{
                    $MonthReportModel = new MonthReportModel;
                    $temps = $MonthReportModel->makeMonthReport($query_month);    
                }
                
            }
            //halt($res);
            //$tempData = $ReportModel->where($where)->value('data');

            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:session('admin_user.inst_id');
            $ownerid = 12; //查询所有产别
            if($temps){
                // $temps = json_decode($tempData,true);
                $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            }else{
                $data['data'] = [];
            }

            $data = $result = [];   
            $owners = [2,5,10,12];
            if($temps){
                // $temps = json_decode($tempData,true);
                foreach ($owners as $v) {
                    $data['data'][$v] = [
                        3 => bcaddMerge([$temps[$v][$instid][12][1], $temps[$v][$instid][12][10], $temps[$v][$instid][12][13]]), //统计暂停计租的合计（住宅+机关+企业）
                        7 => bcaddMerge([$temps[$v][$instid][2][1], $temps[$v][$instid][2][10], $temps[$v][$instid][2][13]]), //统计新发租的合计（住宅+机关+企业）
                        8 => bcaddMerge([$temps[$v][$instid][4][1], $temps[$v][$instid][4][10], $temps[$v][$instid][4][13]]), //统计注销的合计（住宅+机关+企业）
                    ];
                }
            }else{
                foreach ($owners as $v) {
                    $data['data'][$v] = [3 => 0,7 => 0,8 => 0];
                }
            }
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

    

    /**
     * 首页的第四部分
     * @param ban_inst_id 机构id 
     * @param ctime 月份
     * @return json 10：市属、2区属、5自管、12所有
     */
    public function indexPartFour()
    {
        if ($this->request->isAjax()) {
            $getData = $this->request->get();
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                $startTime = str_replace('/', '', $getData['ctime']);
                $where[] = ['date','eq',$startTime];
                //$where[] = ['order_date','eq',201911];
                $query_month = str_replace('/', '', $getData['ctime']);
            }else{
                $where[] = ['date','eq',date('Ym')];
                $query_month = date('Ym');
            }
            // $tempData = Db::name('report')->where($where)->value('data');
            // $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:session('admin_user.inst_id');
            
            $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$query_month.'.txt');
            if($tempData){
                $temps = json_decode($tempData,true);
            }else{
                $temp_cache = cache('rent_'.$query_month);
                if($temp_cache){
                    $temps = $temp_cache;
                }else{
                    $MonthReportModel = new MonthReportModel;
                    $temps = $MonthReportModel->makeMonthReport($query_month);    
                }
            }
            //halt($res);
            //$tempData = $ReportModel->where($where)->value('data');

            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:session('admin_user.inst_id');
            $ownerid = 12; //查询所有产别
            if($temps){
                // $temps = json_decode($tempData,true);
                $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            }else{
                $data['data'] = [];
            }

            $data = $result = [];  
            $owners = [2,5,10,12];
            if($tempData){
                // $temps = json_decode($tempData,true);
                foreach ($owners as $v) {
                    $data['data'][$v] = [
                        'rent_unpaids' => bcaddMerge([$temps[$v][$instid][20][1], $temps[$v][$instid][20][10], $temps[$v][$instid][20][13]]), //统计暂停计租的合计（住宅+机关+企业）
                        'rent_paids' => bcaddMerge([$temps[$v][$instid][18][1], $temps[$v][$instid][18][10], $temps[$v][$instid][18][13]]),
                        'rent_before_unpaids' => bcaddMerge([$temps[$v][$instid][20][2],$temps[$v][$instid][20][3], $temps[$v][$instid][20][11],$temps[$v][$instid][20][12], $temps[$v][$instid][20][14], $temps[$v][$instid][20][15]]),
                    ];
                }
            }else{
                foreach ($owners as $v) {
                    $data['data'][$v] = ['rent_unpaids' => 0,'rent_paids' => 0,'rent_before_unpaids' => 0];
                }
            }
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        }   
    }

    /**
     * 创建每月的账单
     * @return [type] [description]
     */
    public function createMonthRentOrders(){
        // 检查当前月的租金减免异动记录是有有效
        Db::name('change_cut')->where([['is_valid','eq',1],['end_date','eq',date('Ym')]])->update(['is_valid'=>0]);
        $RentModel = new RentModel;
        // 生成每个月的账单
        $rentOrderData = json_encode($RentModel->configRentOrder($is_all_inst = 1));
        // 自动执行扣缴
        $RentModel->autopayList();
        return $rentOrderData;
    }

    /**
     * 创建每月的账单
     * @return [type] [description]
     */
    public function changeOrderToUnpaid(){
        // $RentModel = new RentModel;
        $RentModel->where([['rent_order_date','eq',date('Ym')],['rent_order_paid','<',Db::raw('rent_order_receive')]])->update(['is_deal'=>1]);
        
        // 1、把所有未处理的订单改成已处理状态
        RentModel::where([['is_deal','eq',0]])->update(['is_deal'=>1]);
    }

    /**
     * 月底把没开的发票全部开出来
     * @return [type] [description]
     */
    public function autoAllDpkj(){
        // return $this->success('执行成功');die();

        // 本月开始时间
        $month_begin_time = strtotime(date('Y-m'));

        $month_end_time = strtotime(date('Y-m',strtotime( "first day of next month" )));

        // // 开缴费发票
        // $weixin_id_undpkj = WeixinOrderModel::where([['is_need_dpkj', 'eq', 1],['order_status', 'eq', 1], ['invoice_id', 'eq', 0],['ptime','between',[$month_begin_time,$month_end_time]]])->field('order_id')->order('order_id desc')->select()->toArray();

        // $i = 0;

        // if (!empty($weixin_id_undpkj)) {
            
        //     foreach ($weixin_id_undpkj as $v) {
        //         // 每5秒执行一次
        //         // sleep(5);
        //         $InvoiceModel = new InvoiceModel;
        //         if (!$InvoiceModel->dpkj($v['order_id'])) {
        //             if ($i) {
        //                 // return $this->error($InvoiceModel->getError() . ',本次开具' . $i . '张发票！');
        //             }
        //             // return $this->error($InvoiceModel->getError());
        //         } else {
        //             $i++;
        //         }
        //     }
        // }
        // halt($i);   

        // 开充值发票
        $weixin_id_undpkj = RechargeModel::where([['is_need_dpkj', 'eq', 1],['recharge_status', 'eq', 1], ['transaction_id', '>', 0], ['invoice_id', 'eq', 0],['ptime','between',[$month_begin_time,$month_end_time]]])->field('id')->limit(200,300)->select()->toArray();
//halt($weixin_id_undpkj);
        $k = 0;
        // halt($weixin_id_undpkj);
        if (!empty($weixin_id_undpkj)) {
            //halt($weixin_id_undpkj);
            
            foreach ($weixin_id_undpkj as $v) {
                // 每5秒执行一次
                // sleep(5);
                $InvoiceModel = new InvoiceModel;
                if (!$InvoiceModel->dpkj($v['id'] ,$type = 2)) {
                    if ($k) {
                        // return $this->error($InvoiceModel->getError() . ',本次开具' . $i . '张发票！');
                    }
                    // return $this->error($InvoiceModel->getError());
                } else {
                    $k++;
                }
            }
        }
dump($k);halt($weixin_id_undpkj);
        return '缴费发票开具：'.$i.'张，充值发票开具：'.$k.'张';
        // return $this->success('执行成功');
        
    }


































    /**
     * 首页的第一部分
     * @param ban_inst_id 机构id 
     * @param ctime 月份
     * @return json 10：市属、2区属、5自管、11所有
     */
    public function indexPartOne_old() 
    {
        $getData = $this->request->get();
        // 检索楼栋机构
        $insts = config('inst_ids');
        if(isset($getData['ban_inst_id']) && $getData['ban_inst_id']){
            $where[] = ['d.ban_inst_id','in',$insts[$getData['ban_inst_id']]];
        }else{
            $instid = (isset($getData['ban_inst_id']) && $getData['ban_inst_id'])?$getData['ban_inst_id']:session('admin_user.inst_id');
            $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        }
        // 检索月份时间
        if(isset($getData['ctime']) && $getData['ctime']){
            $startTime = str_replace('/', '', $getData['ctime']);
            $where[] = ['a.rent_order_date','eq',$startTime];
        }
        $fields = 'sum(a.rent_order_receive) as rent_order_receives,sum(a.rent_order_paid) as rent_order_paids,b.house_use_id,d.ban_owner_id';
        $data = [];
        $temp = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->group('b.house_use_id,d.ban_owner_id')->where($where)->select();
        $result = [];
        foreach($temp as $t){
            $result[$t['ban_owner_id']][$t['house_use_id']] = [
                'rent_order_receives' => (float)$t['rent_order_receives'],
                'rent_order_paids' => (float)$t['rent_order_paids'],
            ];
        }
        $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                if(!isset($result[$owner][$i])){
                    $result[$owner][$i] = [
                        'rent_order_receives' => 0, 
                        'rent_order_paids' => 0, 
                    ];
                }
            }
        }
        $result[10][1]['rent_order_receives'] = $result[1][1]['rent_order_receives'] + $result[3][1]['rent_order_receives'] + $result[7][1]['rent_order_receives'];
        $result[10][2]['rent_order_receives'] = $result[1][2]['rent_order_receives'] + $result[3][2]['rent_order_receives'] + $result[7][2]['rent_order_receives'];
        $result[10][3]['rent_order_receives'] = $result[1][3]['rent_order_receives'] + $result[3][3]['rent_order_receives'] + $result[7][3]['rent_order_receives'];
        $result[10][1]['rent_order_paids'] = $result[1][1]['rent_order_paids'] + $result[3][1]['rent_order_paids'] + $result[7][1]['rent_order_paids'];
        $result[10][2]['rent_order_paids'] = $result[1][2]['rent_order_paids'] + $result[3][2]['rent_order_paids'] + $result[7][2]['rent_order_paids'];
        $result[10][3]['rent_order_paids'] = $result[1][3]['rent_order_paids'] + $result[3][3]['rent_order_paids'] + $result[7][3]['rent_order_paids'];

        $result[11][1]['rent_order_receives'] = $result[1][1]['rent_order_receives'] + $result[2][1]['rent_order_receives'] + $result[3][1]['rent_order_receives'] + $result[5][1]['rent_order_receives'] + $result[7][1]['rent_order_receives'];
        $result[11][2]['rent_order_receives'] = $result[1][2]['rent_order_receives'] + $result[2][2]['rent_order_receives'] + $result[3][2]['rent_order_receives'] + $result[5][2]['rent_order_receives'] + $result[7][2]['rent_order_receives'];
        $result[11][3]['rent_order_receives'] = $result[1][3]['rent_order_receives'] + $result[2][3]['rent_order_receives'] + $result[3][3]['rent_order_receives'] + $result[5][3]['rent_order_receives'] + $result[7][3]['rent_order_receives'];
        $result[11][1]['rent_order_paids'] = $result[1][1]['rent_order_paids'] + $result[2][1]['rent_order_paids'] + $result[3][1]['rent_order_paids'] + $result[5][1]['rent_order_paids'] + $result[7][1]['rent_order_paids'];
        $result[11][2]['rent_order_paids'] = $result[1][2]['rent_order_paids'] + $result[2][2]['rent_order_paids'] + $result[3][2]['rent_order_paids'] + $result[5][2]['rent_order_paids'] + $result[7][2]['rent_order_paids'];
        $result[11][3]['rent_order_paids'] = $result[1][3]['rent_order_paids'] + $result[2][3]['rent_order_paids'] + $result[3][3]['rent_order_paids'] + $result[5][3]['rent_order_paids'] + $result[7][3]['rent_order_paids'];
        $data['data'] = $result;
        $data['code'] = 0;
        $data['msg'] = '获取成功';
        return json($data);
    }

    /**
     * 首页的第二部分
     * @param ctime 月份
     * @return json 
     */
    public function indexPartThree_old()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->get();
            $where[] = ['change_status','eq',1];
            $types = [3,7,8];
            $where[] = ['change_type','in',$types];
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                $startTime = str_replace('/', '', $getData['ctime']);
                $where[] = ['order_date','eq',$startTime];
                //$where[] = ['order_date','eq',201911];
            }else{
                $where[] = ['order_date','eq',date('Ym')];
                //$where[] = ['order_date','eq',201911];
            }
// 暂停计租，新发组，注销，
            // if(isset($getData['change_type']) && $getData['change_type']){
            //     
            // }
            // 检索楼栋机构
            $insts = config('inst_ids');
            if(isset($data['inst_id']) && $data['inst_id']){
                $where[] = ['inst_id','in',$insts[$data['inst_id']]];
            }else{
                $instid = (isset($data['inst_id']) && $data['inst_id'])?$data['inst_id']:session('admin_user.inst_id');
                $where[] = ['inst_id','in',$insts[$instid]];
            }
            
            $data = $result = [];
            
            $temp = Db::name('change_table')->group('owner_id,change_type')->where($where)->field('owner_id,change_type,sum(change_rent) as change_rents')->select();

            foreach($temp as $t){
                $result[$t['owner_id']][$t['change_type']] = (float)$t['change_rents'];
            }
            //halt($result);
            $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
            foreach ($ownertypes as $owner) {
                foreach ($types as $i) {
                    if(!isset($result[$owner][$i])){
                        $result[$owner][$i] = 0;
                    }
                }
            }

            $result[10][3] = $result[1][3] + $result[3][3] + $result[7][3];
            $result[10][7] = $result[1][7] + $result[3][7] + $result[7][7];
            $result[10][8] = $result[1][8] + $result[3][8] + $result[7][8];
           
            $result[11][3] = $result[1][3] + $result[2][3]+ $result[3][3] + $result[5][3] + $result[7][3];
            $result[11][7] = $result[1][7] + $result[2][7]+ $result[3][7] + $result[5][7] + $result[7][7];
            $result[11][8] = $result[1][8] + $result[2][8]+ $result[3][8] + $result[5][8] + $result[7][8];
           
            $data['data'] = $result;
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

    /**
     * 首页的第二部分
     * @param ctime 月份
     * @return json 
     */
    public function indexPartFour_old()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->get();
            $where = $where1 = $where2 =[];
            $where[] = ['is_deal','eq',1];
            // $where[] = ['change_type','in',[3,7,8]];
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                $startTime = str_replace('/', '', $getData['ctime']);
                $where1[] = ['rent_order_date','eq',$startTime];
                $where2[] = ['rent_order_date','<',$startTime];
            }else{
                $where1[] = ['rent_order_date','eq',date('Ym')];
                $where2[] = ['rent_order_date','<',date('Ym')];
            }
            // 检索楼栋机构
            $insts = config('inst_ids');
            if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
                $where[] = ['ban_inst_id','in',$insts[$data['ban_inst_id']]];
            }else{
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:session('admin_user.inst_id');
                $where[] = ['ban_inst_id','in',$insts[$instid]];
            }
            
            $data = $result = [];
            
            $temp1 = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->where($where1)->field('ban_owner_id,sum(rent_order_receive-rent_order_paid) as rent_unpaids,sum(rent_order_paid) as rent_paids')->group('d.ban_owner_id')->select();

            $temp2 = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->where($where2)->field('ban_owner_id,sum(rent_order_receive-rent_order_paid) as rent_unpaids')->group('d.ban_owner_id')->select();

            foreach($temp1 as $t){
                $result[$t['ban_owner_id']] = [
                    'rent_unpaids' => (float)$t['rent_unpaids'],
                    'rent_paids' => (float)$t['rent_paids'],
                ];
            }
            foreach($temp2 as $p){
                $result[$p['ban_owner_id']]['rent_before_unpaids'] = (float)$t['rent_unpaids'];
            }

            $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
            foreach ($ownertypes as $owner) {
                if(!isset($result[$owner])){
                    $result[$owner] = [ 
                        'rent_unpaids' => 0, 
                        'rent_paids' => 0, 
                        'rent_before_unpaids' => 0, 
                    ];
                } 
            }
            $result[10]['rent_unpaids'] = $result[1]['rent_unpaids'] + $result[3]['rent_unpaids'] + $result[7]['rent_unpaids'];
            $result[10]['rent_paids'] = $result[1]['rent_paids'] + $result[3]['rent_paids'] + $result[7]['rent_paids'];
            $result[10]['rent_before_unpaids'] = $result[1]['rent_before_unpaids'] + $result[3]['rent_before_unpaids'] + $result[7]['rent_before_unpaids'];
           
            $result[11]['rent_unpaids'] = $result[1]['rent_unpaids'] + $result[2]['rent_unpaids']+ $result[3]['rent_unpaids'] + $result[5]['rent_unpaids'] + $result[7]['rent_unpaids'];
            $result[11]['rent_paids'] = $result[1]['rent_paids'] + $result[2]['rent_paids']+ $result[3]['rent_paids'] + $result[5]['rent_paids'] + $result[7]['rent_paids'];
            $result[11]['rent_before_unpaids'] = $result[1]['rent_before_unpaids'] + $result[2]['rent_before_unpaids']+ $result[3]['rent_before_unpaids'] + $result[5]['rent_before_unpaids'] + $result[7]['rent_before_unpaids'];
            $data['data'] = $result;
            //$data['count'] = count($result);
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

   
}