<?php
namespace app\report\admin;

use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;

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
            $owner = $options['owner'];
            $date = $options['month'];
            $inst = $options['inst'];
            $type = $options['type'];

            $data = [];
            $dataJson = Db::name('report')->where([['type','eq','HouseReport'],['date','eq',str_replace('-','',$date)]])->value('data');
            $datas = json_decode($dataJson,true);
            $data['data'] = $datas?$datas[$type][$owner][$inst]:array();
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
            $inst = $options['inst'];

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