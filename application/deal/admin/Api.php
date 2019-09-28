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

namespace app\deal\admin;

use think\Db;
use app\common\controller\Common;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 
     * @param id 消息id
     * @return json
     */
    public function getChangeCutRow() 
    {
    	$house_id = $this->request->param('house_id');
    	$data = [];
        $ChangeCutModel = new ChangeCutModel;
        if($house_id){
        	$changecutid = ChangeCutModel::where([['house_id','eq',$house_id]])->order('ctime desc')->value('id');
            $row = $ChangeCutModel->detail($changecutid);
            $data['data'] = $row;
	        $data['msg'] = '获取成功';
	        $data['code'] = 0;
        }else{
        	$data['msg'] = '参数错误';
	        $data['code'] = -1;
        }
        return json($data);
    }
}