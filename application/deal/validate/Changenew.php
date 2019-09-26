<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 https://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\deal\validate;

use think\Validate;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeNew as ChangeNewModel;

/**
 * 新发租验证器
 * @package app\system\validate
 */
class Changenew extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',     
        'house_id|房屋编号' => 'require',     
        'new_type|发租类型' => 'require',     
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！'
    ];

    

    //添加
    public function sceneForm()
    {
        return $this->only(['house_id','new_type']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id','new_type']);
    }

    
}