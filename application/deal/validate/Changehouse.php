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
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeHouse as ChangeHouseModel;

/**
 * 房屋调整验证器
 * @package app\system\validate
 */
class Changehouse extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'ban_id|楼栋编号' => 'require',
        'house_id|房屋编号' => 'require|isAllow',
        // 'ban_change_id|异动类别' => 'require|checkChange',
        // 'new_floors|异动后楼层' => 'checkFloor',    
        // 'ban_damage_id|异动后完损等级' => 'checkDamage',    
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！',
    ];

    // 判断当前房屋是否可以申请
    protected function isAllow($value, $rule='', $data)
  	{
        $msg = '';
        $banStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($banStatus != 1){
            $msg = '房屋状态异常！';
        }
  		$row = ChangeHouseModel::where([['house_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
      	return $msg?$msg:true;
  	}

    // 判断当前房屋是否可以申请
    // protected function checkChange($value, $rule='', $data)
    // {
    //     $msg = '';
    //     if($data['ban_change_id'] == 1){
    //         $floors = BanModel::where([['ban_id','eq',$data['ban_id']]])->value('ban_floors');
    //         if($floors == $data['new_floors']){
    //             $msg = '异动前后楼层不能相同！';
    //         }
    //     }else{
    //         $damage = BanModel::where([['ban_id','eq',$data['ban_id']]])->value('ban_damage_id');
    //         if($damage == $data['new_damage']){
    //             $msg = '异动前后完损等级不能相同！';
    //         }
    //     }
        
    //     return $msg?$msg:true;
    // }

    //添加
    public function sceneForm()
    {
        return $this->only(['ban_id','house_id']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id']);
    }

    
}