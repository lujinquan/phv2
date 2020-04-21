<?php
namespace app\report\admin;

use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;
use app\report\model\HouseReport as HouseReportModel;
use app\report\model\MonthPropertyReport as MonthPropertyReportModel;
use app\report\model\YearPropertyReport as YearPropertyReportModel;

class House extends Admin
{

	/**
	 * 房屋统计报表
	 * @return [type] [description]
	 */
    public function archives()
    {
        /*//把所有房屋统计报表数据同步写入到文件中去
        $ReportModel = new ReportModel;
        $tempData = $ReportModel->where([['type','eq','HouseReport']])->column('date,data');
        foreach ($tempData as $k => $v) {
            file_put_contents(ROOT_PATH.'file/report/house/'.$k.'.txt', $v);
        }*/
    	$owerLst = [1 => '市属',2 => '区属',3 => '代管',5 => '自管', 6 => '生活', 7 => '托管', 10 => '市代托',11 => '市区代托', 12 => '所有产别'];     
        if ($this->request->isAjax()) {
            $options = $this->request->post();
            //halt($options);
            $owner = $options['owner'];
            $date = $options['month']?$options['month']:date('Y-m');
            $inst = isset($options['inst'])?$options['inst']:INST;
            $type = $options['type'];

            $data = [];
            $data['data'] = [];

            //$dataJson = Db::name('report')->where([['type','eq','HouseReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            $dataJson = @file_get_contents(ROOT_PATH.'file/report/house/'.str_replace('-','',$date).'.txt');
            if($dataJson){
                $datas = json_decode($dataJson,true);
                $data['data'] = $datas[$type][$owner][$inst];
            }
            if(!$dataJson && $date == date('Y-m')){
                $HouseReportModel = new HouseReportModel;
                //dump($type);dump($owner);halt($inst);
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
            //$where = [['type','eq','HouseReport'],['date','eq',$date]];

            //$ReportModel = new ReportModel;
            //$res = $ReportModel->where($where)->find();
            file_put_contents(ROOT_PATH.'file/report/house/'.$date.'.txt', json_encode($HouseReportdata));
            // if($res){
            //     $re = $ReportModel->where($where)->update(['data'=>json_encode($HouseReportdata)]);

            // }else{
            //     $re = $ReportModel->create([
            //         'data'=>json_encode($HouseReportdata),
            //         'type'=>'HouseReport',
            //         'date'=>$date,
            //     ]);
            // }
            
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
        // //把所有房屋统计报表数据同步写入到文件中去
        // $ReportModel = new ReportModel;
        // $tempData = $ReportModel->where([['type','eq','PropertyReport']])->column('date,data');
        // foreach ($tempData as $k => $v) {
        //     file_put_contents(ROOT_PATH.'file/report/property/'.$k.'.txt', $v);
        // }
        $owerLst = [1 => '市属',2 => '区属',5 => '自管',6 => '生活',10 => '市区自',11 => '所有产别',];     
    	if ($this->request->isAjax()) {
            $options = $this->request->get();
            $owner = $options['owner'];
            $date = $options['month'];
            $group = $options['group'];
            //halt($group);
            $inst = isset($options['inst'])?$options['inst']:INST;

            $data = [];
            // $dataJson = Db::name('report')->where([['type','eq','PropertyReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            $dataJson = file_get_contents(ROOT_PATH.'file/report/property/'.str_replace('-','',$date).'.txt');

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
        $group = input('group','m');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '月报',
                'url' => '?group=m',
            ],
            [
                'title' => '年报',
                'url' => '?group=y',
            ]
        ];
        $tabData['current'] = url('?group='.$group);
        //$this->assign('ban_number',input('param.ban_number',''));
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        $this->assign('owerLst',$owerLst);
        return $this->fetch('propertys_'.$group);
    }

    /**
     * [months 生成月产权统计报表]
     * @return [type] [description]
     */
    public function makeMonthPropertysReport()
    {
        if ($this->request->isAjax()) {
            //set_time_limit(0);

            $date = date('Ym');

            //Debug::remark('begin');
            $MonthPropertyReportModel = new MonthPropertyReportModel;
            $HouseReportdata = $MonthPropertyReportModel->makeMonthPropertyReport($date);
            //Debug::remark('end');
            
            file_put_contents(ROOT_PATH.'file/report/property/'.$date.'.txt', json_encode($HouseReportdata));

            // $where = [['type','eq','PropertyReport'],['date','eq',$date]];

            // $ReportModel = new ReportModel;
            // $res = $ReportModel->where($where)->find();

            // if($res){
            //     $re = $ReportModel->where($where)->update(['data'=>json_encode($HouseReportdata)]);
            // }else{
            //     $re = $ReportModel->create([
            //         'data'=>json_encode($HouseReportdata),
            //         'type'=>'PropertyReport',
            //         'date'=>$date,
            //     ]);
            // }
            
            $data = [];
            $data['msg'] = $date.'月报，保存成功！';
            $data['code'] = 1;
            return json($data);
        }
    }

    /**
     * [months 生成月产权统计报表]
     * @return [type] [description]
     */
    public function makeYearPropertysReport()
    {
        if ($this->request->isAjax()) {
            //set_time_limit(0);
            $date = date('Y');

            //Debug::remark('begin');
            $YearPropertyReportModel = new YearPropertyReportModel;
            $HouseReportdata = $YearPropertyReportModel->makeYearPropertyReport($date);

            file_put_contents(ROOT_PATH.'file/report/property/'.$date.'.txt', json_encode($HouseReportdata));

            // //Debug::remark('end');
            // $where = [['type','eq','PropertyReport'],['date','eq',$date]];

            // $ReportModel = new ReportModel;
            // $res = $ReportModel->where($where)->find();

            // if($res){
            //     $re = $ReportModel->where($where)->update(['data'=>json_encode($HouseReportdata)]);
            // }else{
            //     $re = $ReportModel->create([
            //         'data'=>json_encode($HouseReportdata),
            //         'type'=>'PropertyReport',
            //         'date'=>$date,
            //     ]);
            // }
            
            $data = [];
            $data['msg'] = $date.'年报，保存成功！';
            $data['code'] = 1;
            return json($data);
        }
    }

}