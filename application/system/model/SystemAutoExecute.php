<?php

namespace app\system\model;

use think\Model;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 系统消息模型
 * @package app\system\model
 */
class SystemAutoExecute extends Model
{
	protected $overdueTime = 3600*24*3; //待确认的工单系统自动确认的间隔时长

	// protected function initialize()
 //    {
 //        parent::initialize();
 //        $this->autocomplete();
 //    }

	/**
	 * [autocomplete 待确认的工单隔3天如果不确认，系统会自动确认]
	 * @return [type] [description]
	 */
	public function autocomplete()
    {
        $OporderModel = new OporderModel();
        $ops = $OporderModel->where([['dtime','>',0],['ftime','eq',0]])->field('id,dtime,ftime,jsondata,cuid')->select();
        //halt($ops);
        foreach($ops as $op){
            $data = [];
            $curTime = time();
            $overdueTime = $this->overdueTime;
            $compTime = $op['dtime'] + $overdueTime;
            if($curTime >= $compTime){
                $jsonarr = $op['jsondata'];
                $jsonarr[] = [
                    'FromUid' => $op['cuid'],
                    'Img' => '',
                    'ToUid' => '',
                    'Desc' => '',
                    'Time' => $compTime,
                    'Action' => '系统确认完结工单',
                ];
                // 【更新】序列化数据
                $data['id'] = $op['id'];
                $data['jsondata'] = json_encode($jsonarr);
                $data['ftime'] = time();
                if (!$OporderModel->allowField(true)->update($data)){
                    return $this->error('确认失败');
                }
            }
        }
    }
}