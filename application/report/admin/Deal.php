<?php
namespace app\report\admin;

use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;
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
            $owner = $options['owner'];
            $date = $options['month'];
            $group = $options['group'];
            //halt($group);
            $inst = isset($options['inst'])?$options['inst']:INST;

            $data = [];
            // $dataJson = Db::name('report')->where([['type','eq','PropertyReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            $dataJson = @file_get_contents(ROOT_PATH.'file/report/property/'.str_replace('-','',$date).'.txt');
            // 如果没有缓存数据
            if(!$dataJson){
                // 如果查的是当月或当年的数据，实时显示
                if($date == date('Y-m') || $date == date('Y')){
                    $MonthPropertyReportModel = new MonthPropertyReportModel;
                    $datas  = $MonthPropertyReportModel->makeMonthPropertyReport($date);
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

    /**
     * 产权异动统计明细表
     * @return [type] [description]
     */
    public function changes_statis()
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
            $dataJson = @file_get_contents(ROOT_PATH.'file/report/property/'.str_replace('-','',$date).'.txt');
            // 如果没有缓存数据
            if(!$dataJson){
                // 如果查的是当月或当年的数据，实时显示
                if($date == date('Y-m') || $date == date('Y')){
                    $MonthPropertyReportModel = new MonthPropertyReportModel;
                    $datas  = $MonthPropertyReportModel->makeMonthPropertyReport($date);
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