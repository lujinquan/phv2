<?php
namespace app\report\admin;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;

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
    	if ($this->request->isAjax()) {halt(1);
            $ReportModel = new ReportModel;
            $where = [['type','eq','RentReport']];
            $getData = $this->request->get();

            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:INST;
            $ownerid = (isset($getData['owner_id']) && $getData['owner_id'])?$getData['owner_id']:1;
            $where[] = (isset($getData['query_month']) && $getData['query_month'])?['date','eq',$getData['query_month']]:['date','eq',date('Ym')];
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
            return json($data);
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
}