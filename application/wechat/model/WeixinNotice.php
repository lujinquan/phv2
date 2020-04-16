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
 * 微信小程序Token
 */
class WeixinNotice extends Model 
{
	// 设置模型名称
    protected $name = 'weixin_notice';
	// 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function getCuidAttr($value){
        //halt(session('systemusers')[$value]);
        return session('systemusers')?session('systemusers')[$value]['nick']:$value;
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        // 检索公告标题
        if(isset($data['title']) && $data['title']){
            $where[] = ['title','like','%'.$data['title'].'%'];
        }
        // 检索认证状态
        if(isset($data['type']) && $data['type']){
            $where[] = ['type','eq',$data['type']];
        }

        // 检索是否启用
        if(isset($data['is_show'])){
            if($data['is_show'] === "1"){
                $where[] = ['is_show','eq',1];
            }
            if($data['is_show'] === "0"){
                $where[] = ['is_show','eq',0];
            } 
        }
        // 检索是否需要授权才能查看
        if(isset($data['is_auth'])){
            if($data['is_auth'] === "1"){
                $where[] = ['is_auth','eq',1];
            }
            if($data['is_auth'] === "2"){
                $where[] = ['is_auth','in',[1,2]];
            } 
        }

        return $where;
    }
}