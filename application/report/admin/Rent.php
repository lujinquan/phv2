<?php
namespace app\report\admin;
use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;
use app\report\model\MonthReport as MonthReportModel;
use app\common\model\Cparam as ParamModel;
include EXTEND_PATH.'phpexcel/PHPExcel.php';

class Rent extends Admin
{

    public function index()
    {

        if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    /**
     * [months 月租金报表]
     * @return [type] [description]
     */
    public function months()
    {
        //把所有月租金报表数据同步写入到文件中去
        // $ReportModel = new ReportModel;
        // $tempData = $ReportModel->where([['type','eq','RentReport']])->column('date,data');
        // foreach ($tempData as $k => $v) {
        //     file_put_contents(ROOT_PATH.'file/report/rent/'.$k.'.txt', $v);
        // }
        if ($this->request->isAjax()) {
            $ReportModel = new ReportModel;
            $where = [['type','eq','RentReport']];
            $getData = $this->request->post();
            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:INST;
            $ownerid = (isset($getData['owner_id']) && $getData['owner_id'])?$getData['owner_id']:12;
            $query_month = (isset($getData['query_month']) && $getData['query_month'])?str_replace('-','',$getData['query_month']):date('Ym');
            //$query_month = '202011';
            $where[] = [['date','eq',$query_month]];

            $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$query_month.'.txt');
            //halt($res);
            //$tempData = $ReportModel->where($where)->value('data');

            if($tempData){
                $temps = json_decode($tempData,true);
                $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            }else{
                $MonthReportModel = new MonthReportModel;
                $temps = $MonthReportModel->makeMonthReport($query_month);
                //halt($tempData);
                $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
                // $data['data'] = [];
            }
            $params = ParamModel::getCparams();
            foreach($data['data'] as $k1 => &$v1){

                foreach ($v1 as $k2 => &$v2) {
                    if(in_array($k1,[4,6,9,10,11,12,14,15,16])){
                        if($v2 <> 0){
                            $v2 = '-'.abs($v2);
                        }
                        
                        // halt($v2);
                    }
                }

            }
            // halt($data['data']);
            //$temps = json_decode($tempData,true);
            //$data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            $data['msg'] = '';
            $data['code'] = 0;
            $data['instid'] = $params['insts'][$instid];
            // [1,2,3,5,7,10,11,12]; //市、区、代、自、托、市代托、市区代托、全部
            $owners = ['1'=>'市属','2'=>'区属','3'=>'代管','5'=>'自管','7'=>'托管','10'=>'市代托','11'=>'市区代托','12'=>'所有产别'];
            //$data['ownerid'] = $params['owners'][$ownerid];
            $data['ownerid'] = $owners[$ownerid];
            $data['query_month'] =  substr_replace($query_month, '-', 4 ,0);
            $data['query_date'] = date('Y-m-d');
            //$data['admin'] = $params['owners'][];
            return json_encode($data);
        }
        return $this->fetch();
    }

    /**
     * [months 生成月租金报表]
     * @return [type] [description]
     */
    public function makeMonthReport()
    {
        if ($this->request->isAjax()) {
            
            $data = [];
            // $data['msg'] = '报表正在核对中，请勿生成本月报表！';
            // $data['code'] = 0;
            // return json($data);exit;
            
            $date = date('Ym'); // 生成的报表日期，默认当前月，【如果要手动修改日期，只需要改当前值，例如 $date = 202008; 表示当前操作会生成报表】
            // $date = '202012';
            $full_date = substr_replace($date,'-',4,0);

            //检查上月的报表是否生成
            $last_month = date('Ym',strtotime('- 1 month',strtotime($full_date)));

            $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$last_month.'.txt');
            if(!$tempData){
                return $this->error('未生成'.substr_replace($last_month,'-',4,0).'月报表');
            }
            //$date = 201909;
            //Debug::remark('begin');
            $MonthReportModel = new MonthReportModel;
            $HouseReportdata = $MonthReportModel->makeMonthReport($date);
            //Debug::remark('end');
            //$where = [['type','eq','RentReport'],['date','eq',$date]];

            file_put_contents(ROOT_PATH.'file/report/rent/'.$date.'.txt', json_encode($HouseReportdata));

            /*$ReportModel = new ReportModel;
            $res = $ReportModel->where($where)->find();

            if($res){
                $re = $ReportModel->where($where)->update(['data'=>json_encode($HouseReportdata)]);
            }else{
                $re = $ReportModel->create([
                    'data'=>json_encode($HouseReportdata),
                    'type'=>'RentReport',
                    'date'=>$date,
                ]);
            }*/
            
            $data = [];
            $data['msg'] = substr($date,0,4).'-'.substr($date,4,2).'月报，保存成功！';
            $data['code'] = 1;
            return json($data);
        }
    }

    /**
     * [months 月租金分析报表]
     * @return [type] [description]
     */
    public function analyze()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * [months 欠租明细报表]
     * @return [type] [description]
     */
    public function unpaidRent()
    {
        $params = ParamModel::getCparams();
        if ($this->request->isAjax()) {

            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            $query_month = str_replace('-','',$curMonth);
            $tempData = @file_get_contents(ROOT_PATH.'file/report/unpaid/'.$query_month.'.txt');//halt($tempData);
            if($tempData){ // 有缓存就读取缓存数据
                $temps = json_decode($tempData,true);
                $ownerid = input('param.owner_id'); //默认查询所有产别
                $instid = input('param.inst_id',INST); //默认查询当前机构
                $useid = input('param.use_id'); //默认查询所有使用性质
            
                $data = $result = [];
                $total_cur_month_unpaid_rent = 0;
                $total_before_month_unpaid_rent = 0;
                $total_before_year_unpaid_rent = 0;

                if($ownerid){
                    $owners = explode(',',$ownerid);
                }else{
                    $owners = [1,2,3,5,6,7];
                }

                if($useid){
                    $uses = explode(',',$useid);
                }else{
                    $uses = [1,2,3];
                }
                //halt($uses);
                foreach ($temps as $k => $v) {
                    //halt($v);
                    //halt(in_array($v['inst'],config('inst_ids')[$instid]));
                    if(in_array($v['owner'], $owners) && in_array($v['use'], $uses) && in_array($v['inst'],config('inst_ids')[$instid])){
                        
                        $v['use'] = $params['uses'][$v['use']];
                        if($v['curMonthUnpaidRent'] > 0){
                            $total_cur_month_unpaid_rent = bcadd($total_cur_month_unpaid_rent,$v['curMonthUnpaidRent'],2);
                        }
                        if($v['beforeMonthUnpaidRent'] > 0){
                            $total_before_month_unpaid_rent = bcadd($total_before_month_unpaid_rent,$v['beforeMonthUnpaidRent'],2);
                        }
                        if($v['beforeYearUnpaidRent'] > 0){
                            $total_before_year_unpaid_rent = bcadd($total_before_year_unpaid_rent,$v['beforeYearUnpaidRent'],2);
                        } 
                        $result[$k] = $v;
                    }
                    
                }

                $data['data'] = $result;
                $data['total_cur_month_unpaid_rent'] = $total_cur_month_unpaid_rent;
                $data['total_before_month_unpaid_rent'] = $total_before_month_unpaid_rent;
                $data['total_before_year_unpaid_rent'] = $total_before_year_unpaid_rent;
                $data['total_unpaid_rent'] = bcaddMerge([$data['total_cur_month_unpaid_rent'],$data['total_before_month_unpaid_rent'],$data['total_before_year_unpaid_rent']]);
            }else{
                $ReportModel = new ReportModel;
                $result = $ReportModel->getUnpaidRent();
                $data = [];
                $data['data'] = $result['data'];
                $data['total_cur_month_unpaid_rent'] = $result['total_cur_month_unpaid_rent'];
                $data['total_before_month_unpaid_rent'] = $result['total_before_month_unpaid_rent'];
                $data['total_before_year_unpaid_rent'] = $result['total_before_year_unpaid_rent'];
                $data['total_unpaid_rent'] = bcaddMerge([$data['total_cur_month_unpaid_rent'],$data['total_before_month_unpaid_rent'],$data['total_before_year_unpaid_rent']]);
            }

            $data['count'] = count($data['data']);
            $data['code'] = 0;
            $data['msg'] = '获取成功';
            return json($data);
            
        }
        return $this->fetch();
    }

    /**
     * [months 生成欠租明细报表]
     * @return [type] [description]
     */
    public function makeUnpaidReport()
    {
        set_time_limit(0);
        if ($this->request->isAjax()) {
            
            $date = date('Ym');

            // $date = 202012;

            $full_date = substr_replace($date,'-',4,0);

            //Debug::remark('end');
            $where = [['a.rent_order_paid','<',Db::raw('a.rent_order_receive')]];
            $where = [['a.rent_order_status','eq',1]];
            // $where = [['a.rent_order_date','<=','202012']];
            $separate = substr($date,0,4).'00';
            $params = ParamModel::getCparams();
            $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaid,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
            $result = $data = [];
            $baseData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();

            $total_cur_month_unpaid_rent = 0;
            $total_before_month_unpaid_rent = 0;
            $total_before_year_unpaid_rent = 0;
            foreach($baseData as $b){
                // if($b['rent_order_unpaid'] == 0){
                //     halt($b);
                //     continue;
                // }
                $data[$b['house_id']]['number'] = $b['house_number'];
                $data[$b['house_id']]['address'] = $b['ban_address'];
                $data[$b['house_id']]['tenant'] = $b['tenant_name'];
                $data[$b['house_id']]['use'] = $b['house_use_id'];
                $data[$b['house_id']]['owner'] = $b['ban_owner_id'];
                $data[$b['house_id']]['inst'] = $b['ban_inst_id'];
                // $data[$b['house_id']]['use'] = $params['uses'][$b['house_use_id']];
                // $data[$b['house_id']]['owner'] = $params['owners'][$b['ban_owner_id']];
                // $data[$b['house_id']]['inst'] = $params['insts'][$b['ban_inst_id']];
                if(!isset($data[$b['house_id']]['total'])){
                  $data[$b['house_id']]['total'] = 0;  
                }
                if(!isset($data[$b['house_id']]['curMonthUnpaidRent'])){
                  $data[$b['house_id']]['curMonthUnpaidRent'] = 0;  
                }
                if(!isset($data[$b['house_id']]['beforeMonthUnpaidRent'])){
                  $data[$b['house_id']]['beforeMonthUnpaidRent'] = 0;  
                }
                if(!isset($data[$b['house_id']]['beforeYearUnpaidRent'])){
                  $data[$b['house_id']]['beforeYearUnpaidRent'] = 0;  
                }

                //dump($month);dump($separate);dump($b['rent_order_date']);exit;
                if($b['rent_order_date'] == $date){ // 统计本月欠租
                    $data[$b['house_id']]['curMonthUnpaidRent'] = $b['rent_order_unpaid'];
                    $total_cur_month_unpaid_rent = bcaddMerge([$total_cur_month_unpaid_rent,$b['rent_order_unpaid']]);
                }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $date){ // 统计以前月欠租 
                    //$data[$b['house_id']]['beforeMonthUnpaidRent'] += $b['rent_order_unpaid'];
                    $total_before_month_unpaid_rent = bcaddMerge([$total_before_month_unpaid_rent,$b['rent_order_unpaid']]);
                    $data[$b['house_id']]['beforeMonthUnpaidRent'] = bcaddMerge([$data[$b['house_id']]['beforeMonthUnpaidRent'],$b['rent_order_unpaid']]);  
                }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                    $data[$b['house_id']]['beforeYearUnpaidRent'] = bcaddMerge([$data[$b['house_id']]['beforeYearUnpaidRent'],$b['rent_order_unpaid']]);
                    $total_before_year_unpaid_rent = bcaddMerge([$total_before_year_unpaid_rent,$b['rent_order_unpaid']]);
                }else{
                    // 如果时间不是本月，以前月，以前年，数据就过滤掉
                    continue;
                }
                
                //halt($data[$b['house_id']]);
                //$data[$b['house_id']]['total'] += $b['rent_order_unpaid'];
                $data[$b['house_id']]['total'] = bcaddMerge([$data[$b['house_id']]['total'],$b['rent_order_unpaid']]);
                $data[$b['house_id']]['remark'] = '';
            }

            //json_encode($data);
            // halt($data);
            foreach ($data as $key => $value) {
                if($value['total'] + $value['curMonthUnpaidRent'] + $value['beforeMonthUnpaidRent'] + $value['beforeYearUnpaidRent'] == 0){
                    unset($data[$key]);
                }
            }
            file_put_contents(ROOT_PATH.'file/report/unpaid/'.$date.'.txt', json_encode($data));
            
            $data = [];
            $data['msg'] = substr($date,0,4).'-'.substr($date,4,2).'月欠租明细报表，保存成功！';
            $data['code'] = 1;
            return json($data);
        }
    }

     /**
     * [months 缴费明细报表]
     * @return [type] [description]
     */
    public function paidRent()
    {
        if ($this->request->isAjax()) {

            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月

            $query_month = str_replace('-','',$curMonth);

            // halt($query_month);  
            $tempData = @file_get_contents(ROOT_PATH.'file/report/paid/'.$query_month.'.txt');

            $params = ParamModel::getCparams();
            if($tempData){ // 有缓存就读取缓存数据
               $temps = json_decode($tempData,true);
               // halt($temps['total_cur_month_paid_rent']);
               $ownerid = input('param.owner_id'); //默认查询市属
               $instid = input('param.inst_id',INST); //默认查询当前机构
               $useid = input('param.use_id'); //默认查询住宅

               if($ownerid){
                   $owners = explode(',',$ownerid);
               }else{
                   $owners = [1,2,3,5,6,7];
               }

               if($useid){
                   $uses = explode(',',$useid);
               }else{
                   $uses = [1,2,3];
               }

               // dump($owners);dump($instid);halt($uses);
               $data = $result = [];
               $total_cur_month_paid_rent = 0;
               $total_before_month_paid_rent = 0;
               $total_before_year_paid_rent = 0;

               foreach ($temps['data'] as $k => $v) {

                   if(in_array($v['owner_id'], $owners) && in_array($v['use_id'], $uses) && in_array($v['inst_id'],config('inst_ids')[$instid])){
                       // $v['use'] = $params['uses'][$v['use_id']];
                       if($v['curMonthPaidRent'] > 0){
                           $total_cur_month_paid_rent = bcadd($total_cur_month_paid_rent,$v['curMonthPaidRent'],2);
                       }
                       if($v['beforeMonthPaidRent'] > 0){
                           $total_before_month_paid_rent = bcadd($total_before_month_paid_rent,$v['beforeMonthPaidRent'],2);
                       }
                       if($v['beforeYearPaidRent'] > 0){
                           $total_before_year_paid_rent = bcadd($total_before_year_paid_rent,$v['beforeYearPaidRent'],2);
                       }
                       $result[$k] = $v;
                   }

               }

               $data['data'] = $result;
               $data['total_cur_month_paid_rent'] = $total_cur_month_paid_rent;
               $data['total_before_month_paid_rent'] = $total_before_month_paid_rent;
               $data['total_before_year_paid_rent'] = $total_before_year_paid_rent;
               $data['total_paid_rent'] = bcaddMerge([$data['total_cur_month_paid_rent'],$data['total_before_month_paid_rent'],$data['total_before_year_paid_rent']]);
           }else{
                $ReportModel = new ReportModel;
                $result = $ReportModel->getPaidRent();//halt($result);
                $data = [];
                $data['data'] = $result['data'];
                $data['total_cur_month_paid_rent'] = $result['total_cur_month_paid_rent'];
                $data['total_before_month_paid_rent'] = $result['total_before_month_paid_rent'];
                $data['total_before_year_paid_rent'] = $result['total_before_year_paid_rent'];
                $data['total_paid_rent'] = bcaddMerge([$data['total_cur_month_paid_rent'],$data['total_before_month_paid_rent'],$data['total_before_year_paid_rent']],2);
           }

            $data['count'] = count($data['data']);
            $data['code'] = 0;
            $data['msg'] = '获取成功';
            return json($data);
            
        }
        return $this->fetch();
    }

    /**
     * [months 生成实收明细报表]
     * @return [type] [description]
     */
    public function makePaidReport()
    {
        if ($this->request->isAjax()) {

            $curMonth = date('Y-m');
            // $curMonth = '2020-12';
            $month = date('Ym');
            // $month = '202012';
            $ReportModel = new ReportModel;
            $data = $ReportModel->getPaidRent();//halt($result);

            file_put_contents(ROOT_PATH.'file/report/paid/'.$month.'.txt', json_encode($data));

            $result = [];
            $result['msg'] = $curMonth.'月实收明细报表，保存成功！';
            $result['code'] = 1;
            return json($result);
        }
    }

    /**
     * [months 生成实收明细报表]
     * @return [type] [description]
     */
    public function makePaidReport_old()
    {
        if ($this->request->isAjax()) {
            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            
            // $curMonth = '2020-09'; // 自定义报表生成的月份
            
            $nextMonth = date('Y-m',strtotime('1 month'));
            // $nextMonth = '2020-10';
            $month = str_replace('-','',$curMonth);
            $params = ParamModel::getCparams();
            $separate = substr($month,0,4).'00';
            $where = [];
            
            $where[] = ['ptime','between',[strtotime($curMonth),strtotime($nextMonth)]];

            $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,a.ptime,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
            $result = $data = [];
            $baseData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();

            $total_cur_month_paid_rent = 0;
            $total_before_month_paid_rent = 0;
            $total_before_year_paid_rent = 0;
            foreach($baseData as $b){ 

                if($b['rent_order_paid'] == 0){
                    continue;
                }
                $data[$b['house_id']]['number'] = $b['house_number'];
                $data[$b['house_id']]['address'] = $b['ban_address'];
                $data[$b['house_id']]['tenant'] = $b['tenant_name'];
                $data[$b['house_id']]['use'] = $b['house_use_id'];
                $data[$b['house_id']]['owner'] = $b['ban_owner_id'];
                $data[$b['house_id']]['inst'] = $b['ban_inst_id'];
                if(!isset($data[$b['house_id']]['total'])){
                  $data[$b['house_id']]['total'] = 0;  
                }
                if(!isset($data[$b['house_id']]['curMonthPaidRent'])){
                  $data[$b['house_id']]['curMonthPaidRent'] = 0;  
                }
                if(!isset($data[$b['house_id']]['beforeMonthPaidRent'])){
                  $data[$b['house_id']]['beforeMonthPaidRent'] = 0;  
                }
                if(!isset($data[$b['house_id']]['beforeYearPaidRent'])){
                  $data[$b['house_id']]['beforeYearPaidRent'] = 0;  
                }

                //dump($month);dump($separate);dump($b['rent_order_date']);exit;
                if($b['rent_order_date'] == $month){ // 统计本月欠租
                    $data[$b['house_id']]['curMonthPaidRent'] = $b['rent_order_paid'];
                    $total_cur_month_paid_rent += $b['rent_order_paid'];
                }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                    
                    $data[$b['house_id']]['beforeMonthPaidRent'] += $b['rent_order_paid'];
                    $total_before_month_paid_rent += $b['rent_order_paid'];
                }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                    $data[$b['house_id']]['beforeYearPaidRent'] += $b['rent_order_paid'];
                    $total_before_year_paid_rent += $b['rent_order_paid'];
                }
                //halt($data[$b['house_id']]);
                $data[$b['house_id']]['total'] += $b['rent_order_paid'];
                $data[$b['house_id']]['remark'] = '';
            }

            file_put_contents(ROOT_PATH.'file/report/paid/'.$month.'.txt', json_encode($data));
            
            $result = [];
            $result['msg'] = $curMonth.'月实收明细报表，保存成功！';
            $result['code'] = 1;
            return json($result);
        }
    }

     /**
     * [months 预收明细报表]
     * @return [type] [description]
     */
    public function prePaidRent()
    {
        if ($this->request->isAjax()) {
            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            $query_month = str_replace('-','',$curMonth);
            $tempData = @file_get_contents(ROOT_PATH.'file/report/prepaid/'.$query_month.'.txt');
            //halt($tempData);
            // $ins = Db::name('base_inst')->column('inst_name,inst_id');
            // $uses = ['住宅'=>1,'机关'=>3,'企事'=>2];
            // $ows = ['市属'=>1,'区属'=>2,'代管'=>3,'自管'=>5,'生活'=>6,'托管'=>7];
            // $tempData = json_decode($tempData,true);
            // foreach ($tempData as $key => &$value) {
            //     $value['owner_id'] = $ows[$value['owner']];
            //     $value['use_id'] = $uses[$value['use']];
            //     $value['inst_id'] = $ins[$value['inst']];
            // }
            // file_put_contents(ROOT_PATH.'file/report/paid/202010.txt', json_encode($tempData));exit;
            
            // 查询是否有缓存数据
            if($tempData){ 
                $temps = json_decode($tempData,true);   
            }else{
                $ReportModel = new ReportModel;
                // 实时获取预收明细数据
                $temps = $ReportModel->getPrePaidRent($curMonth);         
            }
            // 查询产别，默认查询所有产别，传过来如果是多产别用逗号隔开
            $ownerids = input('param.owner_id'); //
            if($ownerids){
                $owners = explode(',',$ownerids);
            }else{
                $owners = [1,2,3,5,7];
            }
            // 查询使用性质，默认查询所有使用性质，传过来如果是多使用性质用逗号隔开
            $useids = input('param.use_id'); //
            if($useids){
                $uses = explode(',',$useids);
            }else{
                $uses = [1,2,3];
            }
            // 查询机构，默认查询所当前机构
            $instid = input('param.inst_id',INST); //默认查询当前机构
            $insts =  config('inst_ids')[$instid];

            // 将不满足当前查询条件的数据剔除   
            foreach ($temps as $k => &$v) {//halt($v);
                if(in_array($v['owner_id'], $owners) && in_array($v['use_id'], $uses) && in_array($v['inst_id'],$insts)){
                    continue;
                    // $v['use'] = $params['uses'][$v['use']];
                    // $total_last_yue = bcaddMerge([$total_last_yue,$v['last_yue']]);
                    // $total_pay_rent = bcaddMerge([$total_pay_rent,$v['pay_rent']]);
                    // $total_kou_rent = bcaddMerge([$total_kou_rent,$v['kou_rent']]);
                    // $total_yue = bcaddMerge([$total_yue,$v['house_balance']]);
                }else{
                    unset($temps[$k]);
                }
            }
            
            // 初始化数据
            $params = ParamModel::getCparams();
            $total_last_yue = 0; // 合计上期结转余额
            $total_pay_rent = 0; // 合计本期预缴
            $total_kou_rent = 0; // 合计本月扣缴
            $total_yue = 0; // 合计本月余额
            
            // 将所得列表数据做统计
            foreach ($temps as $k => &$v) {
                $total_last_yue = bcaddMerge([$total_last_yue,$v['last_yue']]);
                $total_pay_rent = bcaddMerge([$total_pay_rent,$v['pay_rent']]);
                $total_kou_rent = bcaddMerge([$total_kou_rent,$v['kou_rent']]);
                $total_yue = bcaddMerge([$total_yue,$v['house_balance']]);  
            }
            $data = [];
            $data['data'] = $temps;
            $data['total_last_yue'] = $total_last_yue;
            $data['total_pay_rent'] = $total_pay_rent;
            $data['total_kou_rent'] = $total_kou_rent;
            $data['total_yue'] = $total_yue;
            $data['count'] = count($data['data']);
            $data['code'] = 0;
            $data['msg'] = '获取成功';
            return json($data);
            
        }
        return $this->fetch();
    }

    /**
     * [months 生成预收明细报表]
     * @return [type] [description]
     */
    public function makePrePaidReport()
    {
        if ($this->request->isAjax()) {
            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            // $curMonth = '2020-09';
            $month = str_replace('-','',$curMonth);
            $ReportModel = new ReportModel;
            $data = $ReportModel->getPrePaidRent($curMonth);

            file_put_contents(ROOT_PATH.'file/report/prepaid/'.$month.'.txt', json_encode($data));
            $result = [];
            $result['msg'] = $curMonth.'月预收明细报表，保存成功！';
            $result['code'] = 1;
            return json($result);
        }
    }

    /**
     * [months 欠租明细报表]
     * @return [type] [description]
     */
    public function export()
    {
        if ($this->request->isAjax()) {
            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            $type = input('type','unpaid');

            // 导出欠缴明细表
            if($type == 'unpaid'){
                $query_month = str_replace('-','',$curMonth);
                $tempData = @file_get_contents(ROOT_PATH.'file/report/unpaid/'.$query_month.'.txt');
                if(empty($tempData) && $curMonth == date('Y-m')){
                    $this->error('未保存'.$curMonth.'月，欠租明细报表，数据为空');
                }
                // 查询是否有缓存数据
                if($tempData){ 
                    $tableTemp = json_decode($tempData,true);   
                    
                    // 查询产别，默认查询所有产别，传过来如果是多产别用逗号隔开
                    $ownerids = input('param.owner_id'); //
                    if($ownerids){
                        $owners = explode(',',$ownerids);
                    }else{
                        $owners = [1,2,3,5,6,7];
                    }
                    // 查询使用性质，默认查询所有使用性质，传过来如果是多使用性质用逗号隔开
                    $useids = input('param.use_id'); //
                    if($useids){
                        $uses = explode(',',$useids);
                    }else{
                        $uses = [1,2,3];
                    }
                    // 查询机构，默认查询所当前机构
                    $instid = input('param.inst_id',INST); //默认查询当前机构
                    $insts =  config('inst_ids')[$instid];

                    // 将不满足当前查询条件的数据剔除   
                    foreach ($tableTemp as $k => &$v) {
                    // halt($v);
                        if(in_array($v['owner'], $owners) && in_array($v['use'], $uses) && in_array($v['inst'],$insts)){
                            continue;
                        }else{
                            unset($tableTemp[$k]);
                        }
                    }
                    $table = $tableTemp;
                }else{

                    $ReportModel = new ReportModel; 
                    $tableTemp =  $ReportModel->getUnpaidRent();  
                    $table = $tableTemp['data'];       
                }
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','curMonthUnpaidRent','beforeMonthUnpaidRent','beforeYearUnpaidRent','total','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','本月欠租','以前月欠租','以前年欠租','合计欠租','备注'];
                $title = '欠租明细';
                
            // 导出实收明细表
            }else if($type == 'paid'){
                $query_month = str_replace('-','',$curMonth);

                // halt($query_month);  
                $tempData = @file_get_contents(ROOT_PATH.'file/report/paid/'.$query_month.'.txt');

                $params = ParamModel::getCparams();
                if($tempData){ // 有缓存就读取缓存数据
                   $temps = json_decode($tempData,true);
                   // halt($temps['total_cur_month_paid_rent']);
                   $ownerid = input('param.owner_id'); //默认查询市属
                   $instid = input('param.inst_id',INST); //默认查询当前机构
                   $useid = input('param.use_id'); //默认查询住宅

                   if($ownerid){
                       $owners = explode(',',$ownerid);
                   }else{
                       $owners = [1,2,3,5,6,7];
                   }

                   if($useid){
                       $uses = explode(',',$useid);
                   }else{
                       $uses = [1,2,3];
                   }

                   // dump($owners);dump($instid);halt($uses);
                   $data = $result = [];
                   $total_cur_month_paid_rent = 0;
                   $total_before_month_paid_rent = 0;
                   $total_before_year_paid_rent = 0;

                   foreach ($temps['data'] as $k => $v) {

                       if(in_array($v['owner_id'], $owners) && in_array($v['use_id'], $uses) && in_array($v['inst_id'],config('inst_ids')[$instid])){
                           // $v['use'] = $params['uses'][$v['use_id']];
                           if($v['curMonthPaidRent'] > 0){
                               $total_cur_month_paid_rent = bcadd($total_cur_month_paid_rent,$v['curMonthPaidRent'],2);
                           }
                           if($v['beforeMonthPaidRent'] > 0){
                               $total_before_month_paid_rent = bcadd($total_before_month_paid_rent,$v['beforeMonthPaidRent'],2);
                           }
                           if($v['beforeYearPaidRent'] > 0){
                               $total_before_year_paid_rent = bcadd($total_before_year_paid_rent,$v['beforeYearPaidRent'],2);
                           }
                           $result[$k] = $v;
                       }

                   }

                 
                    $table = $result;
                   // $data['total_cur_month_paid_rent'] = $total_cur_month_paid_rent;
                   // $data['total_before_month_paid_rent'] = $total_before_month_paid_rent;
                   // $data['total_before_year_paid_rent'] = $total_before_year_paid_rent;
                   // $data['total_paid_rent'] = bcaddMerge([$data['total_cur_month_paid_rent'],$data['total_before_month_paid_rent'],$data['total_before_year_paid_rent']]);
                   // $table = $tableTemp;
                }else{
                    $ReportModel = new ReportModel; 
                    $tableTemp =  $ReportModel->getPaidRent();
                    $table = $tableTemp['data'];
                }
                
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','curMonthPaidRent','beforeMonthPaidRent','beforeYearPaidRent','total','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','本月份','以前月份','以前年份','合计','备注'];
                $title = '实收明细';
                // $table = $tableTemp['data'];
            // 导出预收明细表
            }else if($type == 'prepaid'){

                $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
                $query_month = str_replace('-','',$curMonth);
                $tempData = @file_get_contents(ROOT_PATH.'file/report/prepaid/'.$query_month.'.txt');
                // 查询是否有缓存数据
                if($tempData){ 
                    $tableTemp = json_decode($tempData,true);   
                }else{
                    $ReportModel = new ReportModel;
                    // 实时获取预收明细数据
                    $tableTemp = $ReportModel->getPrePaidRent($curMonth);         
                }
                // 查询产别，默认查询所有产别，传过来如果是多产别用逗号隔开
                $ownerids = input('param.owner_id'); //
                if($ownerids){
                    $owners = explode(',',$ownerids);
                }else{
                    $owners = [1,2,3,5,6,7];
                }
                // 查询使用性质，默认查询所有使用性质，传过来如果是多使用性质用逗号隔开
                $useids = input('param.use_id'); //
                if($useids){
                    $uses = explode(',',$useids);
                }else{
                    $uses = [1,2,3];
                }
                // 查询机构，默认查询所当前机构
                $instid = input('param.inst_id',INST); //默认查询当前机构
                $insts =  config('inst_ids')[$instid];

                // 将不满足当前查询条件的数据剔除   
                foreach ($tableTemp as $k => &$v) {//halt($v);
                    if(in_array($v['owner_id'], $owners) && in_array($v['use_id'], $uses) && in_array($v['inst_id'],$insts)){
                        continue;
                    }else{
                        unset($tableTemp[$k]);
                    }
                }
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','house_pre_rent','last_yue','pay_rent','kou_rent','house_balance','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','规定租金','上期结转余额','本月预缴','本月扣缴','本月余额','备注'];
                $title = '预收明细';
                $table = $tableTemp;
            }
            
           
            

            if(!$table){
                return $this->error('暂无数据导出！');
            }

            $tableData = [];
            
            $sortFlip = array_flip($sort);
            //将数组重新按一定顺序组装成数值型键值对数组
            $y = 0;//halt($table);
            foreach($table as $s){
                foreach($s as $u => $o){
                    if(isset($sortFlip[$u])){
                        $tableData[$y][$sortFlip[$u]] = $o;
                    }
                } 
                ksort($tableData[$y]);
                $y++;
            }
            //halt($tableData);
            $objPHPExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel); //保存excel—2007格式

            //设置文档基本属性
            $objProps = $objPHPExcel->getProperties();
            $objProps->setCreator("Lucas");
            $objProps->setLastModifiedBy("Lucas");
            $objProps->setTitle("Office XLS");
            $objProps->setSubject("Office XLS");
            $objProps->setDescription("Test document, generated by PHPExcel");
            $objProps->setKeywords("system data");
            $objProps->setCategory("data report");
            
            /*----------------创建sheet-----------------*/
            
            $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

            /*----------------创建sheet-----------------*/

            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();

            //设置当前活动sheet的名称
            $objActSheet->setTitle($title);

            // ak 是行
            foreach($tableData as $ak => $a){ 
                $objActSheet->getRowDimension($ak+1)->setRowHeight(18);//设置行高度
                // bk 是列
                foreach($a as $bk => $b){
    
                    if($ak === 0){ //如果是第一行
                        $objActSheet->getColumnDimension($letter[$bk])->setWidth(20); //设置列宽度                  
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFont()->setBold(true); //设置是否加粗
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);//设置填充颜色
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->getStartColor()->setRGB('E6E6E6'); //设置填充颜色

                        $objActSheet->setCellValue($letter[$bk] . ($ak+1), $values[$bk]);  //写入标题
                    }
                    if($bk == 'A'){ //将第一列的格式改成文本，其他列不变
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), ' ' . $b . ' ');
                    }else{
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), $b);  
                    }
                    
                    
     
                }
            }
            //生成excel表格，自定义名
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

            /*------------这种是保存到浏览器下载位置（客户端）-------------------*/
            $queryOption = (isset($tableTemp['op']) && $tableTemp['op'])?$tableTemp['op']:'';

            $filename = $queryOption. $title .'_' . date('YmdHis', time()) . '.xlsx';    //定义文件名

            /*
            
            // 方案一：直接在浏览器上下载
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename=' . $filename);
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');

            */
           //echo strtoupper(substr(PHP_OS,0,3))==='WIN'?'windows 服务器':'不是 widnows 服务器';

           // 方案二：先保存在服务器，然后返回文件路径【注意windows默认使用GBK编码，linux默认使用UTF-8编码】
           if(strtoupper(substr(PHP_OS,0,3))==='WIN'){ //如果是windows服务器，则保存成GBK编码格式
                $filePath = './upload/excel/'.convertGBK($filename);
           }else{ //如果不是，则保存成UTF-8格式
                $filePath = './upload/excel/'.convertUTF8($filename);
           }
           $objWriter->save($filePath);
           $returnJson = [];
           $returnJson['code'] = 1;
           $returnJson['msg'] = '导出成功！';
           $returnJson['data'] = '/upload/excel/'.$filename;
           return json($returnJson); // 返回的文件名需要是以UTF-8编码
        }
    }

    /**
     * [months 实收明细报表导出]
     * @return [type] [description]
     */
    public function export_paid()
    {
        if ($this->request->isAjax()) {

            $type = input('type','unpaid');

            if($type == 'unpaid'){
                $ReportModel = new ReportModel; 
                $tableTemp =  $ReportModel->getUnpaidRent();
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','curMonthUnpaidRent','beforeMonthUnpaidRent','beforeYearUnpaidRent','total','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','本月欠租','以前月欠租','以前年欠租','合计欠租','备注'];
                $title = '欠租明细';
            }else if($type == 'paid'){
                $ReportModel = new ReportModel; 
                $tableTemp =  $ReportModel->getPaidRent();
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','curMonthPaidRent','beforeMonthPaidRent','beforeYearPaidRent','total','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','本月份','以前月份','以前年份','合计','备注'];
                $title = '实收明细';
            }
            
           
            $table = $tableTemp['data'];

            if(!$table){
                return $this->error('暂无数据导出！');
            }

            $tableData = [];
            
            $sortFlip = array_flip($sort);
            //将数组重新按一定顺序组装成数值型键值对数组
            $y = 0;//halt($table);
            foreach($table as $s){
                foreach($s as $u => $o){
                    $tableData[$y][$sortFlip[$u]] = $o;
                } 
                ksort($tableData[$y]);
                $y++;
            }
            //halt($tableData);
            $objPHPExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel); //保存excel—2007格式

            //设置文档基本属性
            $objProps = $objPHPExcel->getProperties();
            $objProps->setCreator("Lucas");
            $objProps->setLastModifiedBy("Lucas");
            $objProps->setTitle("Office XLS");
            $objProps->setSubject("Office XLS");
            $objProps->setDescription("Test document, generated by PHPExcel");
            $objProps->setKeywords("system data");
            $objProps->setCategory("data report");
            
            /*----------------创建sheet-----------------*/
            
            $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

            /*----------------创建sheet-----------------*/

            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();

            //设置当前活动sheet的名称
            $objActSheet->setTitle($title);

            // ak 是行
            foreach($tableData as $ak => $a){ 
                $objActSheet->getRowDimension($ak+1)->setRowHeight(18);//设置行高度
                // bk 是列
                foreach($a as $bk => $b){
    
                    if($ak === 0){ //如果是第一行
                        $objActSheet->getColumnDimension($letter[$bk])->setWidth(20); //设置列宽度                  
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFont()->setBold(true); //设置是否加粗
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);//设置填充颜色
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->getStartColor()->setRGB('E6E6E6'); //设置填充颜色

                        $objActSheet->setCellValue($letter[$bk] . ($ak+1), $values[$bk]);  //写入标题
                    }
                    if($bk == 'A'){ //将第一列的格式改成文本，其他列不变
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), ' ' . $b . ' ');
                    }else{
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), $b);  
                    }
                    
                    
     
                }
            }
            //生成excel表格，自定义名
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

            /*------------这种是保存到浏览器下载位置（客户端）-------------------*/

            $filename = $tableTemp['op']. $title .'_' . date('YmdHis', time()) . '.xlsx';    //定义文件名

            /*
            
            // 方案一：直接在浏览器上下载
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename=' . $filename);
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');

            */
           //echo strtoupper(substr(PHP_OS,0,3))==='WIN'?'windows 服务器':'不是 widnows 服务器';

           // 方案二：先保存在服务器，然后返回文件路径【注意windows默认使用GBK编码，linux默认使用UTF-8编码】
           if(strtoupper(substr(PHP_OS,0,3))==='WIN'){ //如果是windows服务器，则保存成GBK编码格式
                $filePath = './upload/excel/'.convertGBK($filename);
           }else{ //如果不是，则保存成UTF-8格式
                $filePath = './upload/excel/'.convertUTF8($filename);
           }
           $objWriter->save($filePath);
           $returnJson = [];
           $returnJson['code'] = 1;
           $returnJson['msg'] = '导出成功！';
           $returnJson['data'] = '/upload/excel/'.$filename;
           return json($returnJson); // 返回的文件名需要是以UTF-8编码
        }
    }

    /**
     * [months 预收明细报表导出]
     * @return [type] [description]
     */
    public function export_prepaid()
    {
        if ($this->request->isAjax()) {

            $type = input('type','unpaid');

            if($type == 'unpaid'){
                $ReportModel = new ReportModel; 
                $tableTemp =  $ReportModel->getUnpaidRent();
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','curMonthUnpaidRent','beforeMonthUnpaidRent','beforeYearUnpaidRent','total','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','上期结转余额','本月预缴','本月扣缴','本月余额','备注'];
                $title = '欠租明细';
            }else if($type == 'paid'){
                $ReportModel = new ReportModel; 
                $tableTemp =  $ReportModel->getPaidRent();
                //设置字段的排序
                $sort = ['number','address','tenant','inst','owner','use','curMonthPaidRent','beforeMonthPaidRent','beforeYearPaidRent','total','remark'];
                //标题
                $values = ['房屋编号','地址','户名','管段','产别','使用性质','本月份','以前月份','以前年份','合计','备注'];
                $title = '实收明细';
            }
            
           
            $table = $tableTemp['data'];

            if(!$table){
                return $this->error('暂无数据导出！');
            }

            $tableData = [];
            
            $sortFlip = array_flip($sort);
            //将数组重新按一定顺序组装成数值型键值对数组
            $y = 0;//halt($table);
            foreach($table as $s){
                foreach($s as $u => $o){
                    $tableData[$y][$sortFlip[$u]] = $o;
                } 
                ksort($tableData[$y]);
                $y++;
            }
            //halt($tableData);
            $objPHPExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel); //保存excel—2007格式

            //设置文档基本属性
            $objProps = $objPHPExcel->getProperties();
            $objProps->setCreator("Lucas");
            $objProps->setLastModifiedBy("Lucas");
            $objProps->setTitle("Office XLS");
            $objProps->setSubject("Office XLS");
            $objProps->setDescription("Test document, generated by PHPExcel");
            $objProps->setKeywords("system data");
            $objProps->setCategory("data report");
            
            /*----------------创建sheet-----------------*/
            
            $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

            /*----------------创建sheet-----------------*/

            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();

            //设置当前活动sheet的名称
            $objActSheet->setTitle($title);

            // ak 是行
            foreach($tableData as $ak => $a){ 
                $objActSheet->getRowDimension($ak+1)->setRowHeight(18);//设置行高度
                // bk 是列
                foreach($a as $bk => $b){
    
                    if($ak === 0){ //如果是第一行
                        $objActSheet->getColumnDimension($letter[$bk])->setWidth(20); //设置列宽度                  
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFont()->setBold(true); //设置是否加粗
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);//设置填充颜色
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->getStartColor()->setRGB('E6E6E6'); //设置填充颜色

                        $objActSheet->setCellValue($letter[$bk] . ($ak+1), $values[$bk]);  //写入标题
                    }
                    if($bk == 'A'){ //将第一列的格式改成文本，其他列不变
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), ' ' . $b . ' ');
                    }else{
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), $b);  
                    }
                    
                    
     
                }
            }
            //生成excel表格，自定义名
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

            /*------------这种是保存到浏览器下载位置（客户端）-------------------*/

            $filename = $tableTemp['op']. $title .'_' . date('YmdHis', time()) . '.xlsx';    //定义文件名

            /*
            
            // 方案一：直接在浏览器上下载
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename=' . $filename);
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');

            */
           //echo strtoupper(substr(PHP_OS,0,3))==='WIN'?'windows 服务器':'不是 widnows 服务器';

           // 方案二：先保存在服务器，然后返回文件路径【注意windows默认使用GBK编码，linux默认使用UTF-8编码】
           if(strtoupper(substr(PHP_OS,0,3))==='WIN'){ //如果是windows服务器，则保存成GBK编码格式
                $filePath = './upload/excel/'.convertGBK($filename);
           }else{ //如果不是，则保存成UTF-8格式
                $filePath = './upload/excel/'.convertUTF8($filename);
           }
           $objWriter->save($filePath);
           $returnJson = [];
           $returnJson['code'] = 1;
           $returnJson['msg'] = '导出成功！';
           $returnJson['data'] = '/upload/excel/'.$filename;
           return json($returnJson); // 返回的文件名需要是以UTF-8编码
        }
    }
}