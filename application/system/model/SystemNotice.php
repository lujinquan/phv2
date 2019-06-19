<?php

namespace app\system\model;

use think\Model;

/**
 * 系统公告模型
 * @package app\system\model
 */
class SystemNotice extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i',
    ];

    public function getInstIdAttr($value){
    	switch ($value) {
    		case 1:
    			return '全部';
    			break;
    		case 2:
    			return '紫阳所';
    			break;
    		case 3:
    			return '粮道所';
    			break;
    		default:
    			# code...
    			break;
    	}
    }

    public function getCuidAttr($value){
        return session('systemusers')[$value];
    }

    public function appendReadId($id)
    {
    	$this->get($id);
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // 检索公告标题
        if(isset($data['title']) && $data['title']){
            $where[] = ['title','like','%'.$data['title'].'%'];
        }
        // 检索公告类型
        if(isset($data['type']) && $data['type']){
            $where[] = ['type','eq',$data['type']];
        }

        if(INST == 1){
            //
        }elseif(INST == 2){
            $where[] = ['inst_id','in',[1,2]];
        }elseif(INST == 3){
            $where[] = ['inst_id','in',[1,3]];
        }elseif(INST < 19 || INST == 35){
            $where[] = ['inst_id','in',[1,2]];
        }else{
            $where[] = ['inst_id','in',[1,3]];
        }

        return $where;
    }
}
