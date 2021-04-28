<?php

namespace app\system\model;

use think\Model;

/**
 * 系统帮助中心分类模型
 * @package app\system\model
 */
class SystemHelpType extends Model
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
        if(isset($data['type_name']) && $data['type_name']){
            $where[] = ['type_name','like','%'.$data['type_name'].'%'];
        }
        return $where;
    }
}
