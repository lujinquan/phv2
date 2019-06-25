<?php

namespace app\system\model;

use think\Model;

/**
 * 系统消息模型
 * @package app\system\model
 */
class SystemAffiche extends Model
{
    // 定义时间戳字段名
    // protected $createTime = 'create_time';
    // protected $updateTime = false;

    // 自动写入时间戳
    //protected $autoWriteTimestamp = true;

    public function getAffiche()
    {
    	$allDatas = $this->where([['to_user_id','eq','*']])->whereOr([['to_user_id','like','%|'.session('admin_user.uid').'|%']])->select();
    	$affiches = [
    		'reads' => [],
    		'unreads' => [],
    	]; 
    	if($allDatas){
    		foreach($allDatas as $row){
                $row['create_time'] = tranTime($row['create_time']);
    			if(strpos($row['read_users'],'|'.session('admin_user.uid').'|') === false){
					$affiches['unreads'][] = $row;
    			}else{
    				$affiches['reads'][] = $row;
    			}
    			
    		}
    	}
    	//halt($affiches);
    	return $affiches;
    }

    public function appendReadId($id)
    {
    	$this->get($id);
    }

    /**
     * [buildAffiche description]
     * @param  [type] $title        [description]
     * @param  [type] $content      [description]
     * @param  string $to_user_id   [description]
     * @param  string $from_user_id [description]
     * @return [type]               [description]
     */
    // public function buildAffiche($title,$content,$to_user_id = '*',$from_user_id = '*')
    // {
    //     $row = [];
    //     $row['title'] = $title;
    //     $row['from_user_id'] = $from_user_id;
    //     $row['to_user_id'] = $to_user_id;
    //     $row['content'] = $content;
    //     $row['create_time'] = time();
    //     return $this->save($row);
    // }
}
