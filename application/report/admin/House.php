<?php
namespace app\report\admin;

use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;
use app\report\model\HouseReport as HouseReportModel;

class House extends Admin
{

	/**
	 * 房屋统计报表
	 * @return [type] [description]
	 */
    public function archives()
    {
    	$owerLst = [1 => '市属',2 => '区属',3 => '代管',5 => '自管', 6 => '生活', 7 => '托管', 10 => '市代托',11 => '市区代托', 12 => '所有产别'];     
        if ($this->request->isAjax()) {
            $options = $this->request->post();
            //halt($options);
            $owner = $options['owner'];
            $date = $options['month'];
            $inst = isset($options['inst'])?$options['inst']:INST;
            $type = $options['type'];

            $data = [];
            $data['data'] = [];
            $dataJson = Db::name('report')->where([['type','eq','HouseReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            if($dataJson){
                $datas = json_decode($dataJson,true);
                $data['data'] = $datas[$type][$owner][$inst];
            }
            if(!$dataJson && $date == date('Y-m')){
                $HouseReportModel = new HouseReportModel;
                $data['data'] = $HouseReportModel->index($type,$owner,$inst);
            }

            $data['msg'] = '';
            if($data['data']){
                $data['code'] = 1;
                $data['msg'] = '获取成功！';
            }else{
                $data['code'] = 0;
                $data['msg'] = '暂无数据！';               
            }
            //halt($data);
            return json($data);
        }
        $this->assign('owerLst',$owerLst);
        return $this->fetch();
    }

    /**
     * [months 生成房屋统计报表]
     * @return [type] [description]
     */
    public function makeArchivesReport()
    {
        //if ($this->request->isAjax()) {
            set_time_limit(0);
            $date = date('Ym');
            //$date = 201909;
            //Debug::remark('begin');
            $HouseReportModel = new HouseReportModel;
            $HouseReportdata = $HouseReportModel->makeHouseReport($date);
            //Debug::remark('end');
            $where = [['type','eq','HouseReport'],['date','eq',$date]];

            $ReportModel = new ReportModel;
            $res = $ReportModel->where($where)->find();

            if($res){
                $re = $ReportModel->where($where)->update(['data'=>json_encode($HouseReportdata)]);
            }else{
                $re = $ReportModel->create([
                    'data'=>json_encode($HouseReportdata),
                    'type'=>'HouseReport',
                    'date'=>$date,
                ]);
            }
            
            $data = [];
            $data['msg'] = $date.'月报，保存成功！';
            $data['code'] = 1;
            return json($data);
        //}
    }

    /**
	 * 房屋统计报表
	 * @return [type] [description]
	 */
    public function propertys()
    {
        $owerLst = [1 => '市属',2 => '区属',5 => '自管',6 => '生活',10 => '市区自',11 => '所有产别',];     
    	if ($this->request->isAjax()) {
            $options = $this->request->get();
            $owner = $options['owner'];
            $date = $options['month'];
            $inst = isset($options['inst'])?$options['inst']:INST;

            $data = [];
            $dataJson = Db::name('report')->where([['type','eq','PropertyReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            $datas = json_decode($dataJson,true);
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
}