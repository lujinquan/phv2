<?php

// +----------------------------------------------------------------------
// | 框架永久免费开源
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>
// +----------------------------------------------------------------------
// | Motto: There is only one kind of failure in the world is to give up .
// +----------------------------------------------------------------------

namespace app\wechat\home;

use think\Db;
use SendMessage\ServerCodeAPI;
use app\common\controller\Common;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;
use app\wechat\model\WeixinSignRecord;
use app\rent\model\Invoice as InvoiceModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
use app\wechat\model\Weixin as WeixinModel;
use app\system\model\SystemUser as UserModel;
use app\system\model\SystemRole as RoleModel;
use app\deal\model\Process as ProcessModel;
use app\rent\model\Recharge as RechargeModel;
use app\system\model\SystemConfig as ConfigModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\wechat\model\WeixinToken as WeixinTokenModel;
use app\wechat\model\WeixinGuide as WeixinGuideModel;
use app\wechat\model\WeixinColumn as WeixinColumnModel;
use app\wechat\model\WeixinNotice as WeixinNoticeModel;
use app\wechat\model\WeixinBanner as WeixinBannerModel;
use app\wechat\model\WeixinConfig as WeixinConfigModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinReadRecord as WeixinReadRecordModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 功能描述：用户版小程序
 * =====================================
 * @author  Lucas 
 * email:   598936602@qq.com 
 * Website  address:  www.mylucas.com.cn
 * =====================================
 * 创建时间: 2020-02-28 11:47:10
 * @example 
 * @link    文档参考地址：
 * @return  返回值  
 * @version 版本  1.0
 */
class Base extends Common
{
    protected $debug = false;

    protected $domain = '';

    protected function initialize()
    {
        parent::initialize();
        $site_domain = ConfigModel::where([['name','eq','site_domain']])->value('value');
        $this->domain = 'https://'.$site_domain;
    }

    /**
     * 验证用户token
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-23 11:23:56
     * @return  返回值  
     * @version 版本  1.0
     */
    protected function check_user_token()
    {
        $token = input('token');
        if(empty($token)){
            return ['error_code'=>10009,'error_msg'=>'令牌为空'];
        }
        // halt($token);
        // // 缓存验证token是否过期
        // $token = cache('weixin_openid_'.$token);
        // if(empty($token)){
        //     return ['error_code'=>10010,'error_msg'=>'令牌失效'];
        // }
        // $weixin_token_info = $token;
        // $member_info = WeixinMemberModel::where([['token','eq',$weixin_token_info['member_id']]])->find();
        // 数据库验证token是否过期
        $weixin_token_info = WeixinTokenModel::where([['token','eq',$token]])->field('member_id,expires_in')->find();
        if(empty($weixin_token_info)){
            return ['error_code'=>10010,'error_msg'=>'令牌失效'];
        }

        if($weixin_token_info['expires_in'] < time()){
            return ['error_code'=>10010,'error_msg'=>'令牌失效'];
        }

        $member_info = WeixinMemberModel::where([['member_id','eq',$weixin_token_info['member_id']]])->find();
        //检查用户是否允许被访问
        if($member_info['is_show'] == 2){
            return ['error_code'=>10011,'error_msg'=>'用户已被禁止访问'];
        }

        $role_type = 0; //默认是游客或未认证的租户
        $member_extra_info = '';

        $TenantModel = new TenantModel;
        $tenant_info = $TenantModel->where([['tenant_id','eq',$member_info['tenant_id']]])->field('tenant_id,tenant_name')->find();

        if($tenant_info){
            $role_type = 1; //已认证的租户
            $member_extra_info = $tenant_info;
        }

        $UserModel = new UserModel;
        $user_info = $UserModel->where([['weixin_member_id','eq',$member_info['member_id']]])->find();
        // halt($weixin_token_info);
        //检查用户是管理员，还是租户，还是其他
        if($user_info){
            $RoleModel = new RoleModel;
            $role_name = $RoleModel->where([['id','eq',$user_info['role_id']]])->value('name');
            $role_type = 2; //管理员
            $member_extra_info = $user_info;
            $member_extra_info['role_name'] = $role_name;
        }
        return ['error_code'=>0,'error_msg'=>'','role_type'=>$role_type,'member_info'=>$member_info,'member_extra_info'=>$member_extra_info];
    }
}