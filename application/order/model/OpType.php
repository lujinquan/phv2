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

namespace app\order\model;

use app\system\model\SystemBase;
use app\system\model\SystemUser as UserModel;
use app\common\model\Cparam as ParamModel;

class OpType extends SystemBase
{
    // 设置模型名称
    protected $name = 'op_type';
    // 设置主键
    protected $pk = 'id';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i',
    ];

    /**
     * imgs 自动转化
     * @param $value
     * @return array
     */
    // public function getFiletypesAttr($value)
    // {
    //     return $value?explode(',',$value):'';
    // }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // 检索分类名称
        if(isset($data['title']) && $data['title']){
            $where[] = ['title','like','%'.$data['title'].'%'];
        }

        //$instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        $where[] = ['status','eq',1];

        return $where;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data)
    {
        $data['house_cuid'] = ADMIN_ID;
        if(isset($data['files'])){
            $data['filetypes'] = implode(',',$data['files']).',13';
        }else{
            $data['filetypes'] = '13';
        } 
        unset($data['files']);
        if(isset($data['keyids'])){
            $data['keyids'] = implode(',',$data['keyids']);
        }
        return $data; 
    }
}