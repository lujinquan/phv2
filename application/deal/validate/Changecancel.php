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
use app\deal\model\ChangeUse as ChangeUseModel;

/**
 * 注销验证器
 * @package app\system\validate
 */
class Changecancel extends Validate
{
    //定义验证规则
    protected $rule = [	
        'ban_id|楼栋编号' => 'require',     
        'cancel_ban|注销楼栋' => 'isCancelBan', 
        'cancel_type|注销类型' => 'require|number',    
    ];

    //定义验证提示
    protected $message = [
        // 'username.require' => '请输入账户名称',
        // 'role_id.require'  => '请选择角色分组',
    ];

    // 判断当前房屋是否可以申请
    protected function isCancelBan($value, $rule='', $data)
    {
        //halt($value);
        $msg = '';
        if($value == 1){ //如果勾选了注销楼栋
            //halt($data['house_id']);
            $houseids = HouseModel::where([['ban_id','eq',$data['ban_id']],['house_status','eq',1]])->column('house_id');
            foreach ($houseids as $v) {
                if(!in_array($v, $data['house_id'])){
                    $msg = '当前未选择全部房屋，请关闭是否注销按钮！';
                    break;
                }
                
            }
            //$msg = '您当前已经勾选了注销楼栋了！';
        }
        // if($row){
        //     $msg = '房屋已在该异动中，请勿重复申请！';
        // }
        return $msg?$msg:true;
    }

    //添加
    public function sceneForm()
    {
        return $this->only(['ban_id','cancel_ban','cancel_type']);
    }

    // 编辑
    public function sceneEdit()
    {
        return $this->only(['post']);
    }

    
}