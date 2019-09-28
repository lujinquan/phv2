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
use app\deal\model\ChangeName as ChangeNameModel;

/**
 * 管段调整验证器
 * @package app\system\validate
 */
class Changeinst extends Validate
{
    //定义验证规则
    protected $rule = [	
        'id|异动单号' => 'require',
        'new_inst_id|新管段' => 'require|diffInst',
        'ban_ids|楼栋列表' => 'require',   
    ];

    //定义验证提示
    protected $message = [
        'id.require' => '异动单号未知错误！',
        //'new_tenant_name.different' => '新租户姓名不能与原租户姓名相同！',
    ];

    // 判断当前房屋是否可以申请使用权变更
    protected function isAllow($value, $rule='', $data)
  	{
        $msg = '';
        $houseStatus = HouseModel::where([['house_id','eq',$value]])->value('house_status');
        if($houseStatus != 1){
            $msg = '房屋状态异常！';
        }
  		$row = ChangeNameModel::where([['house_id','eq',$value],['change_status','>',1]])->find();
        if($row){
            $msg = '房屋已在该异动中，请勿重复申请！';
        }
      	return $msg?$msg:true;
  	}

    // 判断当前房屋是否可以申请使用权变更
    protected function diffInst($value, $rule='', $data)
    {
        $msg = '';
        if($value == INST){
            $msg = '调整后的管段不能与原管段相同';
        }
        return $msg?$msg:true;
    }

    //添加
    public function sceneForm()
    {
        return $this->only(['ban_ids','new_inst_id']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['id','new_inst_id']);
    }

    
}