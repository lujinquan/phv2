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

	    	$data = [];

	    	$operateAdmins = UserModel::where([['role_id','eq',11],['status','eq',1]])->field('id,nick')->select();
	    	foreach ($operateAdmins as $k => $v) {
	    		$data['data'][$k] = [
	    			'id' => $v['id'],
		    		'name' => $v['nick'],
					'orderTotal' => 0, //工单总量
					'orderEndTotal' => 0, //完结量
					// 'orderTurnTotal' => 0,  //转接量
					'orderPointTotal' => 0, //总流转节点
					'orderTimeTotal' => 0, //总登录时间
		    	];
	    	}
	   		// halt($temps); 		
	    	foreach($temps as $k => &$v){
	    		$uids = explode(',',$v['duid']);
	    		foreach ($data['data'] as $k1 => $v1) {
	    			// $data['data'][$k1]['orderTotal']++;
	    			if($v['ftime']){
	    				if(in_array($v1['id'],$uids)){
		    				$data['data'][$k1]['orderEndTotal']++;
		    				$data['data'][$k1]['orderTimeTotal'] += $v['dtime'] - strtotime($v['ctime']);
		    			}
						// $data['data'][$k1]['orderEndTotal']++;
						
	    			}
	    			if(in_array($v1['id'],$uids)){
	    				$data['data'][$k1]['orderTotal']++;
	    				$data['data'][$k1]['orderPointTotal'] += count($uids);
	    			}
	    			
	    		}
	    	
	        }
	        // halt($data['data']);
	        
	        foreach ($data['data'] as $k2 => $v2) {
	        	if($data['data'][$k2]['orderTotal'] == 0){
		        	$data['data'][$k2]['orderEndPercent'] = 0; //完结率
			        $data['data'][$k2]['orderTurnPercent'] = 0; //平均流转节点
			        $data['data'][$k2]['orderTimePercent'] = 0; //平均处理时长
		        }else{
					$data['data'][$k2]['orderEndPercent'] = round($data['data'][$k2]['orderEndTotal'] / $data['data'][$k2]['orderTotal'],2)*100 .'%'; //完结率
			        $data['data'][$k2]['orderTurnPercent'] = round($data['data'][$k2]['orderPointTotal'] / $data['data'][$k2]['orderTotal']); //平均流转节点
			        $data['data'][$k2]['orderTimePercent'] = round($data['data'][$k2]['orderTimeTotal'] / $data['data'][$k2]['orderTotal'] / 3600); //平均处理时长
			        $data['data'][$k2]['orderTimeTotal'] = round($data['data'][$k2]['orderTimeTotal'] / 3600); //总处理时长
		        }
	        }	

	        $data['code'] = 0;
	        $data['msg'] = '';

	    	return json($data);
	    }
    	return $this->fetch();
    }
}