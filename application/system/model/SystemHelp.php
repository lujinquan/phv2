<?php

namespace app\system\model;

use think\Model;

/**
 * 系统公告模型
 * @package app\system\model
 */
class SystemHelp extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i',
    ];

    public function getCuidAttr($value){
        return session('systemusers')[$value]['role']['name'];
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
        // 检索文档标题
        if(isset($data['title']) && $data['title']){
            $where[] = ['title','like','%'.$data['title'].'%'];
        }
        // 检索文档类型
        if(isset($data['type']) && $data['type']){
            $where[] = ['type','eq',$data['type']];
        }
        return $where;
    }
}
