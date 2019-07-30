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

namespace app\order\admin;
use app\system\admin\Admin;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 工单
 */
class Info extends Admin
{
    public function index()
    {
    	if ($this->request->isAjax()) {
	    	$OpOrderModel = new OpOrderModel;
	    	$temps = $OpOrderModel->with('SystemUser')->where([['duid','like','%,%']])->select();

	    	$liudanInstIds = explode(',',session('39inst_ids'));
	    	$zhengwanInstIds = explode(',',session('40inst_ids'));

	    	$data['data'][0] = [
	    		'name' => '刘丹',
				'orderTotal' => 0, //工单总量
				'orderEndTotal' => 0, //完结量
				'orderTurnTotal' => 0,  //转接量
				'orderPointTotal' => 0, //总流转节点
				'orderTimeTotal' => 0, //总登录时间
	    	];
	    	$data['data'][1] = [
	    		'name' => '郑湾',
				'orderTotal' => 0, //工单总量
				'orderEndTotal' => 0, //完结量
				'orderTurnTotal' => 0,  //转接量
				'orderPointTotal' => 0, //总流转节点
				'orderTimeTotal' => 0, //总登录时间
	    	];
	    	
	    	foreach($temps as $k => &$v){
	    		$uids = explode(',',$v['duid']);
	    		if($uids[1] == 81){ //刘丹
	    			$data['data'][0]['orderTotal']++;
	    			if($v['ftime']){
						$data['data'][0]['orderEndTotal']++;
						$data['data'][0]['orderTimeTotal'] += ($v['ftime'] - $v['ctime']);
	    			}
	    			if(in_array($v['inst_id'],$zhengwanInstIds)){
	    				$data['data'][1]['orderTurnTotal']++;
	    			}
	    			$data['data'][0]['orderTurnTotal'] += count($uids);
	    			
	    		}
	    		if($uids[1] == 82){ //郑湾
	    			$data['data'][1]['orderTotal']++;
	    			if($v['ftime']){
						$data['data'][0]['orderEndTotal']++;
						$data['data'][1]['orderTimeTotal'] += ($v['ftime'] - $v['ctime']);
	    			}
	    			if(in_array($v['inst_id'],$liudanInstIds)){
						$data['data'][1]['orderTurnTotal']++;
	    			}
	    			$data['data'][1]['orderTurnTotal'] += count($uids);
	    			
	    		}
	        }
	        if($data['data'][0]['orderTotal'] == 0){
	        	$data['data'][0]['orderEndPercent'] = 0; //完结率
		        $data['data'][0]['orderTurnPercent'] = 0; //平均流转节点
		        $data['data'][0]['orderTimePercent'] = 0; //平均处理时长
	        }else{
				$data['data'][0]['orderEndPercent'] = round($data['data'][0]['orderEndTotal'] / $data['data'][0]['orderTotal'],2)*100 .'%'; //完结率
		        $data['data'][0]['orderTurnPercent'] = round($data['data'][0]['orderPointTotal'] / $data['data'][0]['orderTotal']); //平均流转节点
		        $data['data'][0]['orderTimePercent'] = round($data['data'][0]['orderTimeTotal'] / $data['data'][0]['orderTotal']); //平均处理时长
	        }
	        if($data['data'][1]['orderTotal'] == 0){
				$data['data'][1]['orderEndPercent'] = 0;
				$data['data'][1]['orderTurnPercent'] = 0;
				$data['data'][1]['orderTimePercent'] = 0; //平均处理时长
	        }else{
		        $data['data'][1]['orderEndPercent'] = ($data['data'][1]['orderEndTotal'] / $data['data'][1]['orderTotal'])*100 .'%';	
		        $data['data'][1]['orderTurnPercent'] = round($data['data'][1]['orderPointTotal'] / $data['data'][1]['orderTotal']);
		        $data['data'][1]['orderTimePercent'] = round($data['data'][1]['orderTimeTotal'] / $data['data'][1]['orderTotal']); //平均处理时长
	        }
	        
	        $data['code'] = 0;
	        $data['msg'] = '';
	        
	    	//halt($data);
	    	return json($data);
	    }
    	return $this->fetch();
    }
}