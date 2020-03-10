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
use app\house\model\House as HouseModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;


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
     
    public function house_list($member_id)
    {
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->find($member_id);
//halt($member_id);
        // 从member_house关联表中查询会员绑定的房屋
            //$this = new this;
            $member_houses = self::where([['member_id','eq',$member_id]])->select()->toArray();

            // 查询当前绑定的房屋
            $systemHouseArr = [];
            if($member_info['tenant_id']){
                $houseArr = HouseModel::with(['ban','tenant'])->where([['tenant_id','eq',$member_info['tenant_id']]])->select()->toArray();
                if($houseArr){
                    foreach ($houseArr as $h) {
                        $h['is_auth'] = 1;
                        $systemHouseArr[$h['house_id']] = $h; 
                    }
                }
            }

            if($member_houses){
                $houses = [];
                foreach ($member_houses as $k => $v) {
                    $HouseModel = new HouseModel;
                    $row = $HouseModel->with(['ban','tenant'])->where([['house_id','eq',$v['house_id']]])->find()->toArray();
                    $row['is_auth'] = $v['is_auth'];
                    unset($systemHouseArr[$v['house_id']]);
                    $houses[] = $row;
                }
                return array_merge($houses,$systemHouseArr);
                // $result['code'] = 1;
                // $result['msg'] = '获取成功';
                // return json($result);
            }
    }
    // public function house()
    // {
    //     return $this->hasMany('app\house\model\house', 'house_id', 'house_id');
    // }
	
}