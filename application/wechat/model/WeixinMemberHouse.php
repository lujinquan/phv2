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

namespace app\wechat\model;

use think\Db;
use think\Model;


/**
 * 微信小程序用户房屋关联
 */
class WeixinMemberHouse extends Model 
{
	// 设置模型名称
    protected $name = 'weixin_member_house';
    // 设置主键
    protected $pk = 'id';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->hasOne('app\house\model\house', 'house_id', 'house_id')->bind('house_number,ban_id');
    }
     
    // public function house()
    // {
    //     return $this->hasMany('app\house\model\house', 'house_id', 'house_id');
    // }
	
}