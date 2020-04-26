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
 * 微信小程序基础配置
 */
class WeixinTemplate extends Model 
{
	// 设置模型名称
    protected $name = 'weixin_template';	
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // // 检索公告标题
        // if(isset($data['banner_title']) && $data['banner_title']){
        //     $where[] = ['banner_title','like','%'.$data['banner_title'].'%'];
        // }
        // // 检索是否启用
        // if(isset($data['is_show'])){
        //     if($data['is_show'] === "1"){
        //         $where[] = ['is_show','eq',1];
        //     }
        //     if($data['is_show'] === "0"){
        //         $where[] = ['is_show','eq',0];
        //     } 
        // }
        

        return $where;
    }
}