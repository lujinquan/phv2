<?php

namespace app\system\model;

use think\Model;

/**
 * 系统公告模型
 * @package app\system\model
 */
class SystemAffiche extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'mtime';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getAffiche()
    {
    	$allDatas = $this->where([['to_user_id','eq','*']])->whereOr([['to_user_id','like','%|'.session('admin_user.uid').'|%']])->select();
    	$affiches = [
    		'reads' => [],
    		'unreads' => [],
    	]; 
    	if($allDatas){
    		foreach($allDatas as $row){
    			if(strpos($row['read_users'],'|'.session('admin_user.uid').'|') === false){
					$affiches['unreads'][] = $row;
    			}else{
    				$affiches['reads'][] = $row;
    			}
    			
    		}
    	}
    	//$affiches['total'] = count($affiches['unreads']);
    	return $affiches;
    }

    public function appendReadId($id)
    {
    	$this->get($id);
    }
}
