<?php
namespace app\report\admin;
use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;
use app\common\model\Cparam as ParamModel;
use app\rent\model\Rent as RentModel;

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
    	if ($this->request->isAjax()) {
            $ReportModel = new ReportModel;
            $where = [['type','eq','RentReport']];
            $getData = $this->request->post();

            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:INST;
            $ownerid = (isset($getData['owner_id']) && $getData['owner_id'])?$getData['owner_id']:1;
            $where[] = (isset($getData['query_month']) && $getData['query_month'])?['date','eq',str_replace('-','',$getData['query_month'])]:['date','eq',date('Ym')];
            $tempData = $ReportModel->where($where)->value('data');
            if($tempData){
                $temps = json_decode($tempData,true);
                $data['data'] = isset($temps[$instid][$ownerid])?$temps[$instid][$ownerid]:[];
            }else{
                $data['data'] = [];
            }
            $temps = json_decode($tempData,true);
            $data['data'] = isset($temps[$instid][$ownerid])?$temps[$instid][$ownerid]:[];
            $data['msg'] = '';
            $data['code'] = 0;
            //halt(json_encode($data));
            return json_encode($data);
        }
        // $ReportModel = new ReportModel;
        // $where = [['type','eq','RentReport']];
        // $getData = $this->request->get();

        // $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:INST;
        // $ownerid = (isset($getData['owner_id']) && $getData['owner_id'])?$getData['owner_id']:1;
        // $where[] = (isset($getData['query_month']) && $getData['query_month'])?['date','eq',$getData['query_month']]:['date','eq',date('Ym')];
        // $tempData = $ReportModel->where($where)->value('data');
        // $temps = json_decode($tempData,true);
        // $data['data'] = $temps[$instid][$ownerid];
        // $data['msg'] = '';
        // $data['code'] = 0;
        // return halt($data);
        return $this->fetch();
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
        if ($this->request->isAjax()) {
            $ownerid = input('param.owner_id/d',1); //默认查询市属
            $instid = input('param.inst_id/d',INST); //默认查询当前机构
            
            $useid = input('param.use_id/d',1); //默认查询住宅
            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            $month = str_replace('-','',$curMonth);
            $params = ParamModel::getCparams();
            $separate = substr($month,0,4).'00';
            $where = [];
            $where[] = ['a.rent_order_date','<=',$month];
            if($useid != 0){
                $where[] = ['b.house_use_id','eq',$useid];
            }
            if($ownerid != 0){
                $where[] = ['d.ban_owner_id','eq',$ownerid];
            }
            //$where[] = ['rent_order_receive','eq',rent_order_paid];
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
            $fields = 'a.house_id,a.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaid,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $data = $temp = [];
            $baseData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->limit(500)->select();
            //dump($where);halt($baseData);
            //dump($month);dump($separate);
            foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);
                if($b['rent_order_unpaid'] == 0){
                    continue;
                }
                if(!isset($temp[$b['house_id']]['total'])){
                  $temp[$b['house_id']]['total'] = 0;  
                }
                if(!isset($temp[$b['house_id']]['curMonthUnpaidRent'])){
                  $temp[$b['house_id']]['curMonthUnpaidRent'] = 0;  
                }
                if(!isset($temp[$b['house_id']]['beforeMonthUnpaidRent'])){
                  $temp[$b['house_id']]['beforeMonthUnpaidRent'] = 0;  
                }
                if(!isset($temp[$b['house_id']]['beforeYearUnpaidRent'])){
                  $temp[$b['house_id']]['beforeYearUnpaidRent'] = 0;  
                }
                if($b['rent_order_date'] == $month){ // 统计本月欠租
                    $temp[$b['house_id']]['curMonthUnpaidRent'] = $b['rent_order_unpaid'];
                }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                    $temp[$b['house_id']]['beforeMonthUnpaidRent'] += $b['rent_order_unpaid'];

                }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                    $temp[$b['house_id']]['beforeYearUnpaidRent'] += $b['rent_order_unpaid'];
                }
                $temp[$b['house_id']]['number'] = $b['house_number'];
                $temp[$b['house_id']]['address'] = $b['ban_address'];
                $temp[$b['house_id']]['tenant'] = $b['tenant_name'];
                $temp[$b['house_id']]['use'] = $params['uses'][$b['house_use_id']];
                $temp[$b['house_id']]['total'] += $b['rent_order_unpaid'];
                $temp[$b['house_id']]['remark'] = '';
            }
            //dump($baseData);halt($temp);
            $data['data'] = $temp;
            $data['count'] = count($temp);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }
}