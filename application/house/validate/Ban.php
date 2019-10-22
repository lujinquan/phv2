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
 * 楼栋验证器
 * @package app\admin\validate
 */
class Ban extends Validate
{
    //定义验证规则
    protected $rule = [
        'ban_id' => 'isAllowChange',
        'ban_address|楼栋地址' => 'require', 
        'ban_inst_id|管段' => 'require',
        'ban_owner_id|产别' => 'require|number',
        'ban_struct_id|结构类别' => 'require|number',
        'ban_damage_id|完损等级' => 'require|number',   
        'ban_ratio|栋系数' => 'float',
        'ban_build_year|建成年份' => 'require|date',
        'ban_door|栋号' => 'require|number',
        'ban_units|单元数' => 'require|number|gt:0',
        'ban_floors|楼层数' => 'require|number|gt:0',
        'ban_career_num|企业栋数' => 'require|in:0,1', 
        'ban_party_num|机关栋数' => 'require|in:0,1', 
        'ban_civil_num|民用栋数' => 'require|in:0,1', 
        'ban_gpsx|经度' => 'require', 
        'ban_gpsy|纬度' => 'require', 
        'ban_property_id|产权证号' => 'require',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    protected function isAllowChange($value, $rule='', $data)
    { 
        $row = ChangeNewModel::where([['ban_id','in',$value],['change_status','>',2]])->value('id');
        return $row?'该楼栋已经在新发租异动中':true;  
    }

    // 添加
    public function sceneForm()
    {
        return $this->only(['ban_address','ban_inst_id','ban_owner_id','ban_struct_id','ban_damage_id','ban_units','ban_floors','ban_ratio','ban_build_year','ban_door','ban_career_num','ban_party_num','ban_civil_num','ban_gpsx','ban_gpsy','ban_property_id']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['ban_id','ban_address','ban_inst_id','ban_owner_id','ban_struct_id','ban_damage_id','ban_units','ban_floors','ban_ratio','ban_build_year','ban_door','ban_career_num','ban_party_num','ban_civil_num','ban_gpsx','ban_gpsy','ban_property_id']);
    }

    // 编辑
    public function sceneDel()
    {
        return $this->only(['ban_id']);
    }


}