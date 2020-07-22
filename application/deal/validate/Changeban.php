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
use app\deal\model\ChangeBan as ChangeBanModel;

/**
 * 楼栋调整验证器
 * @package app\system\validate
 */
class Changeban extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'ban_id|楼栋编号' => 'require|isAllow',
        'ban_change_id|异动类别' => 'require|checkChange',
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
        $banStatus = BanModel::where([['ban_id','eq',$value]])->value('ban_status');
        if($banStatus != 1){
            $msg = '楼栋状态异常！';
        }
  		$row = ChangeBanModel::where([['ban_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '楼栋已在该异动中，请勿重复申请！';
        }
      	return $msg?$msg:true;
  	}

    // 判断当前房屋是否可以申请
    protected function checkChange($value, $rule='', $data)
    {
        $msg = '';
        if($data['ban_change_id'] == 1){
            if(!$data['new_floors']){
                $msg = '异动后的楼层不能为空！';
            }else{
                $floors = BanModel::where([['ban_id','eq',$data['ban_id']]])->value('ban_floors');
                if($floors == $data['new_floors']){
                    $msg = '异动前后楼层不能相同！';
                } 
            }
        }elseif($data['ban_change_id'] == 2){
            if(!$data['new_damage']){
                $msg = '异动后的完损等级不能为空！';
            }else{
                $damage = BanModel::where([['ban_id','eq',$data['ban_id']]])->value('ban_damage_id');
                if($damage == $data['new_damage']){
                    $msg = '异动前后完损等级不能相同！';
                }
            }
        }elseif($data['ban_change_id'] == 3){
            if(!$data['new_address']){
                $msg = '异动后的楼栋地址不能为空！';
            }else{
                $address = BanModel::where([['ban_id','eq',$data['ban_id']]])->value('ban_address');
                if($address == $data['new_address']){
                    $msg = '异动前后楼栋地址不能相同！';
                }
            }
        }else{
            if(!$data['new_struct']){
                $msg = '异动后的结构类别不能为空！';
            }else{
                $struct = BanModel::where([['ban_id','eq',$data['ban_id']]])->value('ban_struct_id');
                if($struct == $data['new_struct']){
                    $msg = '异动前后结构类别不能相同！';
                }
            }
        }
        
        return $msg?$msg:true;
    }

    //添加
    public function sceneForm()
    {
        return $this->only(['ban_id','ban_change_id','new_floors']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id']);
    }

    
}