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
    public function getFiletypesAttr($value)
    {
        //halt($value);
        return $value?explode(',',$value):'';
    }


}