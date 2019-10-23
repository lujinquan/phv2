<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | Motto ：No pains, no gains !
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>
// +----------------------------------------------------------------------
namespace app\house\validate;

use think\Validate;
use app\deal\model\ChangeNew as ChangeNewModel;

/**
 * 租户验证器
 * @package app\admin\validate
 */
class Tenant extends Validate
{
    //定义验证规则
    protected $rule = [
        'tenant_id' => 'isAllowChange',
        'tenant_name|租户姓名' => 'require',
        'tenant_tel|联系电话' => 'require|number',
        'tenant_card|身份证号' => 'require|idCard|unique:tenant',
    ];

    //定义验证提示
    protected $message = [
        'tenant_card.idCard' => '身份证格式不正确',
        'tenant_card.unique' => '身份证号已在系统中存在',
    ];

    protected function isAllowChange($value, $rule='', $data)
    { 
        $row = ChangeNewModel::where([['tenant_id','in',$value],['change_status','>',2]])->value('id');
        return $row?'该楼栋已经在新发租异动中':true;  
    }

    //添加
    public function sceneForm()
    {
        return $this->only(['tenant_name','tenant_tel','tenant_card']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['tenant_id','tenant_name','tenant_tel','tenant_card']);
    }

    // 删除
    public function sceneDel()
    {
        return $this->only(['tenant_id']);
    }

}