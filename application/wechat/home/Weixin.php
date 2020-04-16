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
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
use app\wechat\model\Weixin as WeixinModel;
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
class Weixin extends Common
{
    protected $debug = false;

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 功能描述：用户进入小程序 [前端每隔3000秒请求一次]
     * @author  Lucas 
     * 创建时间: 2020-02-26 11:39:34
     */
    public function applogin()
    {
        $code = input('code'); //小程序传来的code值
        $result = [];
        $result['action'] = 'wechat/weixin/applogin';
        if(!$code){
            $result['code'] = 1001;
            $result['msg'] = '验证码不能为空';
            $result['en_msg'] = 'Code value cannot be empty';
        }
        $WeixinModel = new WeixinModel;
        if($this->debug){
            // 下面是一个模拟返回的数组，注释上一行
            $resultOpenid = [
                'openid' => 'debug_openid',
                'session_key' => 'debug_session_key',
                //'unionid' => 'debug_unionid', // 特定情况下才会返回unionid
            ];
        }else{
            $resultOpenid = $WeixinModel->getOpenid($code);
        }
        
        if(is_array($resultOpenid)){
            if($this->debug){
                // 下面是一个模拟返回的数组，注释上一行
                $resultAccessToken = [
                    'access_token' => 'debug_access_token',
                    'expires_in' => 7200,
                ];
            }else{
                $resultAccessToken = $WeixinModel->getAccessToken();
            }

            //halt($resultAccessToken);
            
            $expires_time = time() + $resultAccessToken['expires_in']; //设置过期时间
            $token = md5($resultOpenid['openid'].time()); //设置token

            cache('weixin_openid_'.$token, $resultOpenid['openid'],7000); //存储openid
            cache('weixin_expires_time_'.$token, $expires_time,7000);  //存储过期时间
            cache('weixin_session_key_'.$token, $resultOpenid['session_key'],7000);  //存储session_key
            //cache('weixin_unionid_'.$token, $resultOpenid['unionid'],7000);  //存储unionid
            $result['code'] = 1;
            $result['data'] = [
                'openid' => $resultOpenid['openid'],
                'session_key' => $resultOpenid['session_key'],
                'token' => $token,
            ];
            $result['msg'] = '获取成功！';

            $WeixinMemberModel = new WeixinMemberModel;
            $member_info = $WeixinMemberModel->where([['openid','eq',$resultOpenid['openid']]])->find();
            // if( empty($member_info) )
            // {
            //  $member_info = $WeixinMemberModel->where([['unionid','eq',$resultOpenid['unionid']]])->find();
            // }
            // 如果在系统中能查到微信会员信息
            if(!empty($member_info) )
            {
                $member_id = $member_info['member_id'];
                // 更新授权用户的信息
                // $member_info->member_name = $data['nickName'];
                // $member_info->avatar = $data['avatarUrl'];
                $member_info->last_login_time = time();
                $member_info->last_login_ip = get_client_ip();
                $member_info->login_count = Db::raw('login_count+1');
                $member_info->save();   
                // 添加微信Token记录
                $WeixinTokenModel = new WeixinTokenModel;
                $WeixinTokenModel->token = $token;
                $WeixinTokenModel->member_id = $member_id;
                $WeixinTokenModel->session_key = $resultOpenid['session_key'];
                $WeixinTokenModel->expires_in = $expires_time;
                $WeixinTokenModel->save();
                
            // 如果在系统中查不到微信会员信息
            }else{
                $WeixinMemberModel->openid = $resultOpenid['openid'];
                $WeixinMemberModel->app_cols = ["3","4","5"]; // 默认显示缴费、报修、办事指引图标
                $WeixinMemberModel->member_name = '';
                $WeixinMemberModel->last_login_time = time();
                $WeixinMemberModel->last_login_ip = get_client_ip();
                $WeixinMemberModel->save();
                $member_id = $WeixinMemberModel->member_id;   
                // 添加微信Token记录
                $WeixinTokenModel = new WeixinTokenModel;
                $WeixinTokenModel->token = $token;
                $WeixinTokenModel->member_id = $member_id;
                $WeixinTokenModel->session_key = $resultOpenid['session_key'];
                $WeixinTokenModel->expires_in = $expires_time;
                $WeixinTokenModel->save();

            }

            // 清除该会员前所有的token失效
            $member_house_id = $WeixinTokenModel->id;
            $cache_token_dead = $WeixinTokenModel->where([['member_id','eq',$member_id],['id','neq',$member_house_id],['token_status','eq',1]])->select();
            if($cache_token_dead){
                foreach ($cache_token_dead as $k => $t) {
                    cache('weixin_openid_'.$t['token'], NULL); //清除openid缓存
                    cache('weixin_expires_time_'.$t['token'], NULL); //清除openid缓存
                    cache('weixin_session_key_'.$t['token'], NULL); //清除openid缓存
                    //cache('weixin_unionid_'.$t['token'], NULL); //清除openid缓存
                    $t->token_status = 0; //token状态设置为失效
                    $t->save();
                }
            }
            
        }else{
            $result['code'] = 0;
            $result['msg'] = $resultOpenid;
        }
        return json($result);  
    }

    /**
     * 功能描述：用户授权小程序
     * @author  Lucas 
     * 创建时间: 2020-02-26 11:39:34
     */
    public function applogin_do()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/applogin_do';
        if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
        }
        $token = input('token');
        
        //$data_json = file_get_contents('php://input');
        
        if($this->debug){
            $data = [
                'avatarUrl' => "https://wx.qlogo.cn/mmopen/vi_32/molOLezL8XLRnpH34rtdJrl1z0UE3PR55zv6axNbiaM6tWibmcOyLrm6ibAY4xIRLoAJ9fnOdzHcz1wl3N4fsMicYw/132",
                'city' => '',
                'country' => 'Egypt',
                'gender' => 1,
                'language' => 'zh_CN',
                'nickName' => 'Lucas',
                'province' => '',
            ];
        }else{
            //$data = json_decode($data_json, true);
            $data = [];
            $data['nickName'] = input('nickName');
            $data['avatarUrl'] = input('avatarUrl');
            //halt($data);
        }
        //$user_info = $data['userinfo']; //获取授权的用户信息
        //$share_id = $data['share_id']; //获取授权的分享id
        
        $openid = cache('weixin_openid_'.$token);
        $expires_time = cache('weixin_expires_time_'.$token);
        $session_key = cache('weixin_session_key_'.$token);
        //$unionid = cache('weixin_unionid_'.$token);
        
        // 清除用户表情符号
        //$user_info['nickName'] = \Lib\Weixin\WeChatEmoji::clear($user_info['nickName']);
        $nickName = trim(emoji_encode($data['nickName']));
        
        $WeixinMemberModel = new WeixinMemberModel;

        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        
        // if( !empty($unionid) && empty($member_info) )
        // {
        //  $member_info = $WeixinMemberModel->where([['unionid','eq',$unionid]])->find();
        // }
        // 如果在系统中能查到微信会员信息
        if(!empty($member_info) )
        {
            if($member_info['is_show'] == 2){
                $result['code'] = 10011;
                $result['msg'] = '用户已被禁止访问';
                $result['en_msg'] = 'The user has been denied access';
                return json($result);
            }
            $member_id = $member_info['member_id'];

            // 更新授权用户的信息
            $member_info->member_name = $data['nickName'];
            $member_info->avatar = $data['avatarUrl'];
            $member_info->last_login_time = time();
            $member_info->last_login_ip = get_client_ip();
            $member_info->login_count = Db::raw('login_count+1');
            $member_info->save();
            
            // 添加微信Token记录
            // $WeixinTokenModel = new WeixinTokenModel;
            // $WeixinTokenModel->token = $token;
            // $WeixinTokenModel->member_id = $member_id;
            // $WeixinTokenModel->session_key = $session_key;
            // $WeixinTokenModel->expires_in = $expires_time;
            // $WeixinTokenModel->save();

        // 如果在系统中查不到微信会员信息
        }else{

            $WeixinMemberModel->openid = $openid;
            //$WeixinMemberModel->unionid = $unionid;
            $WeixinMemberModel->member_name = $data['nickName'];
            $WeixinMemberModel->avatar = $data['avatarUrl'];
            $WeixinMemberModel->last_login_time = time();
            $WeixinMemberModel->last_login_ip = get_client_ip();
            $WeixinMemberModel->save();
            $member_id = $WeixinMemberModel->member_id;

            
            // 添加微信Token记录
            // $WeixinTokenModel = new WeixinTokenModel;
            // $WeixinTokenModel->token = $token;
            // $WeixinTokenModel->member_id = $member_id;
            // $WeixinTokenModel->session_key = $session_key;
            // $WeixinTokenModel->expires_in = $expires_time;
            // $WeixinTokenModel->save();

            // if($share_id > 0)
            // {
            //  $share_member = M('member')->field('we_openid')->where( array('member' => $share_id) )->find();
                
            //  $member_formid_info = M('member_formid')->where( array('member_id' => $share_id, 'state' => 0) )->find();
            //  //更新
            //  if(!empty($member_formid_info))
            //  {
            //      $template_data['keyword1'] = array('value' => $data['name'], 'color' => '#030303');
            //      $template_data['keyword2'] = array('value' => '普通会员', 'color' => '#030303');
            //      $template_data['keyword3'] = array('value' => date('Y-m-d H:i:s'), 'color' => '#030303');
            //      $template_data['keyword4'] = array('value' => '恭喜你，获得一位新成员', 'color' => '#030303');
                    
            //      $pay_order_msg_info =  M('config')->where( array('name' => 'wxprog_member_take_in') )->find();
            //      $template_id = $pay_order_msg_info['value'];
            //      $url =C('SITE_URL');
            //      $pagepath = 'pages/dan/me';
            //      send_wxtemplate_msg($template_data,$url,$pagepath,$share_member['we_openid'],$template_id,$member_formid_info['formid']);
            //      M('member_formid')->where( array('id' => $member_formid_info['id']) )->save( array('state' => 1) );
            //  }
                
            // }
        }
        
        
        $result['code'] = 1;
        $result['msg'] = '授权成功！';
        return json($result);
    }

    /**
     * 功能描述：获取业务图标（并标记当前用户已自定义的业务）
     * @author  Lucas 
     * 创建时间: 2020-03-12 15:48:43
     */
    public function column_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/column_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }

        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;

        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        // $appcols = array_values($member_info['app_cols']);
        //halt($member_info);

        // 获取所有业务列表
        $WeixinColumnModel = new WeixinColumnModel;
        $columns = $WeixinColumnModel->field('col_id,col_name,is_top,col_icon,app_page')->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->select()->toArray();
        //halt($columns);
        foreach ($columns as $k => &$v) {
             $file = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
             $v['file'] = 'https://procheck.ctnmit.com'.$file;
             if($member_info['app_cols']){
                if(in_array($v['col_id'], $member_info['app_cols'])){ // 标记已选择的图标
                    $v['is_choose'] = 1;
                 }else{
                    $v['is_choose'] = 0;
                 }  
             }else{
                $v['is_choose'] = 0;
             }
                
        }
   
        // 获取待缴费金额
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->column('house_id');
        $unpaid_rent = 0;
        if($houses){
            // 获取待缴费的金额（已认证和未认证的欠租合计）
            $rent_order_row = Db::name('rent_order')->where([['house_id','in',$houses]])->field('sum(rent_order_receive - rent_order_paid) as rent_order_unpaids,sum(rent_order_paid) as rent_order_paids')->find();
            if($rent_order_row){
                $unpaid_rent = $rent_order_row['rent_order_unpaids'];
            }
        }
        
        // 获取待办事项
        $undeal_event = 0;

        // 获取未读消息
        $unread_message = 0;

        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        $result['data']['column'] = $columns;
        $result['data']['unpaid_rent'] = $unpaid_rent; //待缴费的金额
        $result['data']['undeal_event'] = $undeal_event; //待办事项个数
        $result['data']['unread_message'] = $unread_message; //未读消息个数
        return json($result);
    }

    /**
     * 功能描述：修改用户自定义创建的快捷业务图标
     * @author  Lucas 
     * 创建时间: 2020-03-12 15:50:50
     */
    public function edit_my_column_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/edit_my_column_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $cols = input('app_cols');
        if(!$cols){
            $result['code'] = 10040;
            $result['msg'] = '图标设置不能为空';
            $result['en_msg'] = 'App cols is empty';
            return json($result);
        }
        // 获取所有业务列表
        $WeixinColumnModel = new WeixinColumnModel;
        $columns = $WeixinColumnModel->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->column('col_id');
        $colArr = explode(',',$cols);
        // dump($columns);
        // halt($colArr);
        foreach ($colArr as  $v) {
            if(!in_array($v,$columns)){
                $result['code'] = 10041;
                $result['msg'] = '图标超出可选范围';
                $result['en_msg'] = 'Col id is out of range';
                return json($result);
            }
        }


        $member_info->app_cols = $colArr;
        $member_info->save();
        //halt($res);
        // $appcols = array_values($member_info['app_cols']);
        // //halt($member_info);

        // // 获取所有业务列表
        // $WeixinColumnModel = new WeixinColumnModel;
        // $columns = $WeixinColumnModel->field('col_id,col_name,col_icon,app_page')->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->select()->toArray();
        // //halt($columns);
        // foreach ($columns as $k => &$v) {
        //      $file = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
        //      $v['file'] = 'https://procheck.ctnmit.com'.$file;
        //      if(in_array($v['col_id'], $appcols)){ // 标记已选择的图标
        //         $v['is_choose'] = 1;
        //      }else{
        //         $v['is_choose'] = 0;
        //      }     
        // }
        $result['code'] = 1;
        $result['msg'] = '编辑成功！';
        return json($result);
    }

    /**
     * 功能描述：获取主页的数据
     * @author  Lucas 
     * 创建时间: 2020-02-26 15:55:15
     */
    public function index_info()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/index_info';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
    
        
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $banners = WeixinBannerModel::where([['dtime','eq',0],['is_show','eq',1]])->order('sort desc')->select()->toArray();
        foreach ($banners as &$b) {
            $banner = SystemAnnex::where([['id','eq',$b['banner_img']]])->value('file');
            $b['banner_img'] = 'https://procheck.ctnmit.com'.$banner;
        }
        $result['data']['app_user_index_banner'] = $banners;
        // 获取公告列表
        $WeixinNoticeModel = new WeixinNoticeModel;
        $noticeWhere = [];
        $noticeWhere[] = ['dtime','eq',0];
        $noticeWhere[] = ['is_show','eq',1];
        $noticeWhere[] = ['type','eq',1];

        if($member_info['tenant_id']){ //如果是认证用户则可以查看所有公告
            //$noticeWhere[] = ['is_auth','eq',3];
        }
        if(!$member_info['tenant_id'] && $member_info['member_name']){ //如果是登录用户，则可以查看所有人+登录
            $noticeWhere[] = ['is_auth','in',[1,2]];
        }
        if(!$member_info['tenant_id'] && !$member_info['member_name']){ //如果是未登录的用户，则只能查看所有人
            $noticeWhere[] = ['is_auth','eq',1];
        }
        $result['data']['notice'] = $WeixinNoticeModel->field('id,title,content,ctime')->where($noticeWhere)->order('sort asc')->select()->toArray();
        // 获取业务列表
        $WeixinColumnModel = new WeixinColumnModel;
        $columns = $WeixinColumnModel->field('col_id,col_name,col_icon,app_page')->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->select()->toArray();
        foreach ($columns as $k => &$v) {
             $file = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
             $v['file'] = 'https://procheck.ctnmit.com'.$file;
             if(!in_array($v['col_id'],$member_info['app_cols'])){
                unset($columns[$k]);
             }
             //halt($v);
        }
        // 获取服务配置
        $result['data']['service'] = Db::name('weixin_service_config')->find();
        if($result['data']['service']){
            //halt($result['data']['service']['value']);
            $service = htmlspecialchars_decode($result['data']['service']['value']);
            //halt($service);
            $curr_domin = input('server.http_host');
            //halt($curr_domin);
            //if(strpos($service, 'https') === false){
                $service = str_replace('/static/js/editor/kindeditor/file/image', 'https://'.$curr_domin.'/static/js/editor/kindeditor/file/image', $service);
            //}
            //$service = str_replace('https://'.$curr_domin.'https://'.$curr_domin, 'https://'.$curr_domin, $service);

            $result['data']['service']['value'] = $service;
        }
        
        $result['data']['column'] = $columns;
        // 基础配置
        $configs = WeixinConfigModel::column('name,value');
        $result['data']['app_user_index_message'] = [$configs['app_user_index_message']]; // 主页滚动信息
        $result['data']['app_user_index_title'] = $configs['app_user_index_title']; // 主页标题
        $result['data']['app_user_version'] = $configs['app_user_version']; // 小程序版本 
        $result['data']['app_user_concat_us'] = $configs['app_user_concat_us']; // 联系我们
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
         
        return json($result);    
    }

    /**
     * 功能描述：获取公告列表
     * @author  Lucas 
     * 创建时间: 2020-02-28 11:29:57
     */
    public function notice_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/notice_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }

        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;

        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $page = input('page',1);
        $limit = 5;
        // 获取公告列表
        $noticeWhere = [];
        $noticeWhere[] = ['dtime','eq',0];
        $noticeWhere[] = ['is_show','eq',1];
        $noticeWhere[] = ['type','eq',1];
        if($member_info['tenant_id']){ //如果是认证用户则可以查看所有公告
            //$noticeWhere[] = ['is_auth','eq',3];
        }
        if(!$member_info['tenant_id'] && $member_info['member_name']){ //如果是登录用户，则可以查看所有人+登录
            $noticeWhere[] = ['is_auth','in',[1,2]];
        }
        if(!$member_info['tenant_id'] && !$member_info['member_name']){ //如果是未登录的用户，则只能查看所有人
            $noticeWhere[] = ['is_auth','eq',1];
        }
        $result['data'] = WeixinNoticeModel::field('id,title,type,content,ctime')->where($noticeWhere)->order('sort desc')->page($page)->limit($limit)->select()->toArray(); 
        $result['count'] = WeixinNoticeModel::where([['dtime','eq',0],['is_show','eq',1],['type','eq',1]])->count('id');
        $result['pages'] = ceil($result['count'] / $limit);
        $result['curr_page'] = $page;
        $result['code'] = 1;
        $result['msg'] = '获取成功！'; 
        return json($result);    
    }

    /**
     * 功能描述：获取办事指引列表
     * @author  Lucas 
     * 创建时间: 2020-03-25 14:00:09
     */
    public function guide_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/guide_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }
        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;

        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $page = input('page',1);
        $limit = 20;
        // 获取公告列表
 
        $result['data'] = WeixinGuideModel::field('id,title,remark,content,ctime')->where([['is_show','eq',1]])->order('sort asc')->page($page)->limit($limit)->select()->toArray(); 
        $result['count'] = WeixinGuideModel::where([['is_show','eq',1]])->count('id');
        $result['pages'] = ceil($result['count'] / $limit);
        $result['curr_page'] = $page;
        $result['code'] = 1;
        $result['msg'] = '获取成功！'; 
        return json($result);    
    }

    /**
     * 功能描述：获取参数配置
     * @author  Lucas 
     * 创建时间: 2020-02-28 11:29:57
     */
    public function get_params()
    {
        // 验证令牌
        $result = [];
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
        } 
        // 获取参数对照数据
        $result['data'] = ParamModel::getCparams();
        $result['code'] = 1;
        $result['msg'] = '获取成功！'; 
        return json($result);    
    }

    /**
     * 功能描述：获取主页公告的详情
     * @author  Lucas 
     * 创建时间: 2020-02-26 16:21:03
     */
    public function notice_detail()
    {
        //halt(session('systemusers'));
        
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/notice_detail';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn4624ol3tpa1JiBPQuY1toMY';
        }
        
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        // 获取
        $id = trim(input('id'));
        if(!$id){
            $result['code'] = 10005;
            $result['msg'] = '公告编号不能为空';
            $result['en_msg'] = 'Key ID is empty';
            return json($result);
        }
        $WeixinNoticeModel = new WeixinNoticeModel;
        $result['data'] = $WeixinNoticeModel->get($id);
        //halt($result['data']);
        if(!$result['data']){
            $result['code'] = 10006;
            $result['msg'] = '公告编号不存在';
            $result['en_msg'] = 'Key ID is error';
            return json($result);
        }
        $content = htmlspecialchars_decode($result['data']['content']);
        $curr_domin = input('server.http_host');
        // if(strpos($content, 'https') === false){
            $content = str_replace('/static/js/editor/kindeditor/file/image', 'https://'.$curr_domin.'/static/js/editor/kindeditor/file/image', $content);
        // }
        //$content = str_replace('https://'.$curr_domin.'https://'.$curr_domin, 'https://'.$curr_domin, $content);
        // 绑定手机号

        if($member_info['member_name']){ //如果用户已授权则记录下会员的浏览记录
            $WeixinReadRecordModel = new WeixinReadRecordModel;
            $record = $WeixinReadRecordModel->where([['notice_id','eq',$id],['member_id','eq',$member_info['member_id']]])->find();
            if(!$record){
                $WeixinReadRecordModel->save(['notice_id'=>$id,'member_id'=>$member_info['member_id'],'member_name'=>$member_info['member_name'],'avatar'=>$member_info['avatar']]);
            }
        }

        
        
        $result['data']['content'] = $content;
        $result['data']['cuid'] = Db::name('system_user')->where([['id','eq',$result['data']['cuid']]])->value('nick');
        $result['code'] = 1;
        $result['msg'] = '获取成功！';     
        return json($result);
    }

    /**
     * 功能描述：获取办事指引的详情
     * @author  Lucas 
     * 创建时间: 2020-02-26 16:21:03
     */
    public function guide_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/guide_detail';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        // 获取
        $id = trim(input('id'));
        if(!$id){
            $result['code'] = 10005;
            $result['msg'] = '办事指引编号不能为空';
            $result['en_msg'] = 'Key ID is empty';
            return json($result);
        }
        $WeixinGuideModel = new WeixinGuideModel;
        $result['data'] = $WeixinGuideModel->get($id);
        //halt($result['data']);
        if(!$result['data']){
            $result['code'] = 10006;
            $result['msg'] = '办事指引编号不存在';
            $result['en_msg'] = 'Key ID is error';
            return json($result);
        }
        $content = htmlspecialchars_decode($result['data']['content']);
        $curr_domin = input('server.http_host');
        // if(strpos($content, 'https') === false){
            $content = str_replace('/static/js/editor/kindeditor/file/image', 'https://'.$curr_domin.'/static/js/editor/kindeditor/file/image', $content);
        // }
        //$content = str_replace('https://'.$curr_domin.'https://'.$curr_domin, 'https://'.$curr_domin, $content);
        $result['data']['content'] = $content;
        $result['data']['cuid'] = Db::name('system_user')->where([['id','eq',$result['data']['cuid']]])->value('nick');
        $result['code'] = 1;
        $result['msg'] = '获取成功！';     
        return json($result);
    }

    /**
     * 功能描述： 给会员添加房屋（添加member_house关联记录）
     * @author  Lucas 
     * 创建时间: 2020-02-26 17:36:54
     */
    public function add_member_house()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/add_member_house';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }

        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        
        $house_number = trim(input('house_number'));
        if(!$house_number){
            $result['code'] = 10007;
            $result['msg'] = '房屋编号不能为空';
            $result['en_msg'] = 'House No. is empty';
            return json($result);
        }
        
        // 绑定手机号 
        $HouseModel = new HouseModel;
        $house_info = $HouseModel->where([['house_number','eq',$house_number]])->find();
        if(!$house_info){
            $result['code'] = 10008;
            $result['msg'] = '房屋编号不存在';
            $result['en_msg'] = 'House No. is error';
            return json($result);
        }
        if($house_info['house_status'] != 1){
            $result['code'] = 10009;
            $result['msg'] = '房屋已注销或未发租';
            $result['en_msg'] = 'Abnormal house status';
            return json($result);
        }
        if($house_info['house_is_pause'] == 1){
            $result['code'] = 10011;
            $result['msg'] = '房屋已被暂停计租';
            $result['en_msg'] = 'The house has been suspended';
            return json($result);
        }
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        
        //halt($houses);
        $counts = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0],['is_auth','eq',0]])->count();
        if($counts > 9){ //会员绑定的房屋数量达到>9个，提示超出数量
            $result['code'] = 10012;
            $result['msg'] = '绑定房屋数量不能超过10个';
            $result['en_msg'] = 'The number of bound houses exceeds the limit';
            return json($result);
        }
        $find = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$house_info['house_id']],['dtime','eq',0]])->find();
        if($find){
            $result['code'] = 10013;
            $result['msg'] = '请勿重复绑定该房屋';
            $result['en_msg'] = 'The house has been bound';
            return json($result);
        }
        // $member_house_find = $WeixinMemberHouseModel->where([['house_id','eq',$house_info['house_id']]])->find();
        // if($member_house_find){
        //     $result['msg'] = '当前房屋编号已被占用！';
        //     return json($result);
        // }
        $WeixinMemberHouseModel->house_id = $house_info['house_id'];
        $WeixinMemberHouseModel->member_id = $member_info['member_id'];
        $WeixinMemberHouseModel->save();
        // 如果当前会员已认证，则每次添加房屋的时候刷新认证房屋数据
        if($member_info['tenant_id']){
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            // 调试
            $auth_house_ids = $HouseModel->where([['tenant_id','eq',$member_info['tenant_id']],['house_is_pause','eq',0],['house_status','eq',1]])->column('house_id');
            $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']]])->column('house_id,is_auth');
            foreach ($auth_house_ids as $a) {
                if(isset($houses[$a]) && $houses[$a] == 0){
                    $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$a]])->update(['is_auth'=>1]);
                }
                if(!isset($houses[$a])){
                    $WeixinMemberHouseModel = new WeixinMemberHouseModel;
                    $WeixinMemberHouseModel->save(['member_id'=>$member_info['member_id'],'house_id'=>$a,'is_auth'=>1]);
                }
            }
        }
        
        $result['code'] = 1;
        $result['msg'] = '添加成功';
        return json($result);
    }

    /**
     * 功能描述： 获取我的房屋列表数据
     * @author  Lucas 
     * 创建时间: 2020-02-26 17:36:54
     */
    public function my_house_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/my_house_list';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn4624ol3tpa1JiBPQuY1toMY';
        }
        
        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        $result['is_auth'] = 0;
        if($member_info){

            if($member_info['is_show'] == 2){
                $result['code'] = 10011;
                $result['msg'] = '用户已被禁止访问';
                $result['en_msg'] = 'The user has been denied access';
                return json($result);
            }
            if($member_info['tenant_id']){
                $result['is_auth'] = 1;
            }
            // 从member_house关联表中查询会员绑定的房屋
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $member_houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info->member_id],['dtime','eq',0]])->select()->toArray();
            // 如果有绑定的房屋
            if($member_houses){
                $houses = [];
                foreach ($member_houses as $k => $v) {
                    $HouseModel = new HouseModel;
                    $row = $HouseModel->with(['ban','tenant'])->where([['house_id','eq',$v['house_id']]])->find();
                    $row['is_auth'] = $v['is_auth'];
                    // unset($systemHouseArr[$v['house_id']]);
                    $houses[] = $row;
                }
                $result['data'] = $houses;
                $result['code'] = 1;
                $result['msg'] = '获取成功';
                return json($result);
            }
            
        }
        $result['data'] = [];
        $result['code'] = 1;
        $result['msg'] = '获取成功';
        return json($result);
        
    }

    /**
     * 功能描述：租户详情
     * @author  Lucas 
     * 创建时间: 2020-02-28 15:06:57 
     */
    public function member_info() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/member_info';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token);
        }else{
            $openid = 'oRqsn4624ol3tpa1JiBPQuY1toMY';
        }

        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $result['data']['member'] = $member_info;

        // 查找绑定的房屋
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        // 获取关联的所有房屋
        $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]
])->column('house_id');
        // 获取关联的所有自己已认证的房屋
        $is_auth_houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['is_auth','eq',1],['dtime','eq',0]
])->column('house_id');

        $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        // 去掉暂停计租的房子+已注销的房子
        $result['data']['house'] = HouseModel::with('ban')->where([['house_id','in',$houses],['house_is_pause','eq',0],['house_status','eq',1]])->field('house_id,house_balance,ban_id,tenant_id,house_unit_id,house_is_pause,house_pre_rent,house_status,house_floor_id,house_balance')->select()->toArray();
        $yue = 0;
        foreach ($result['data']['house'] as $k => &$v) {
            //halt($v);
            $row = Db::name('rent_order')->where([['house_id','eq',$v['house_id']],['tenant_id','eq',$v['tenant_id']]])->field('sum(rent_order_receive - rent_order_paid) as rent_order_unpaids,sum(rent_order_paid) as rent_order_paids')->find();
            $v['is_auth'] = 0;
            if(in_array($v['house_id'], $is_auth_houses)){
                $yue += $v['house_balance'];
                $v['is_auth'] = 1;
            }
            
            $v['rent_order_unpaids'] = $row['rent_order_unpaids']?$row['rent_order_unpaids']:0;
            $v['rent_order_paids'] = $row['rent_order_paids']?$row['rent_order_paids']:0;
            //$value['id'] = $key + 1;
        }
        $result['data']['yue'] = $yue;
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        return json($result); 
    }

    /**
     * 功能描述： 获取我的订单列表数据
     * 小程序使用页面：【 我的 -> 历史账单 】
     * @author  Lucas 
     * 创建时间: 2020-02-28 10:13:33
     */
    public function my_order_list() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/my_order_list';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token);
        }else{
            $openid = 'oRqsn47Ar-NOdj2pRjLp2P2Ela4g';
        }

        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $houseID = input('get.house_id');
        // 验证验证码
        $datasel = input('get.data_sel');

        // 查找绑定的房屋
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        
        $houseArr = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]
])->column('house_id,is_auth');
        //halt($houseArr);
        if(!$houseArr){
            $result['code'] = 10050;
            $result['msg'] = '当前会员未绑定任何房屋';
            $result['en_msg'] = 'The current user is not bound to any house';
            return json($result);
        }
        //$WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']]])->column('house_id');
        $houseIds = array_keys($houseArr);

        $where = [];
        $where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
        
        if($houseID){
            $houseArr = $WeixinMemberHouseModel->where([['house_id','eq',$houseID],['dtime','eq',0]
])->column('house_id,is_auth');
            $where[] = ['a.house_id','eq',$houseID];
        }else{
            
            // halt($houseIds);
            // $houseAuths = array_values($houseArr);
            $where[] = ['a.house_id','in',$houseIds];
        }
        if($datasel){
            $startDate = substr($datasel,0,4);
            $endDate = substr($datasel,5,2);
            $where[] = ['a.rent_order_date','eq',$startDate.$endDate];
        }
        $fields = "a.rent_order_id,a.house_id,from_unixtime(a.ptime, '%Y-%m-%d %H:%i:%s') as ptime,a.pay_way,a.tenant_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_floor_id,b.house_door,b.house_unit_id,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $rents = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.rent_order_id desc')->select();
        $params = ParamModel::getCparams();
        $result['data']['rent'] = [];
        foreach ($rents as $key => $value) {
            // 如果当前房子不是自己认证的房屋，则只显示当前会员支付的订单
            if($houseArr[$value['house_id']] == 0 && $value['pay_way'] == 4){
                $find = WeixinOrderTradeModel::alias('a')->join('weixin_order b','a.out_trade_no = b.out_trade_no','inner')->where([['rent_order_id','eq',$value['rent_order_id']]])->field('b.member_id')->order('a.ctime desc')->find();
                if(!$find){
                    continue;
                }
                //halt($find);
            }
           
            $value['pay_way_name'] = $params['pay_way'][$value['pay_way']];
            $result['data']['rent'][] = $value;
        }
        //halt($result['data']['rent']);
        //$result['data']['rent'] = $rents;
        $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        $result['data']['house'] = HouseModel::with('ban')->where([['house_id','in',$houseIds]])->field('house_balance,house_id,house_pre_rent,ban_id,house_unit_id,house_floor_id')->select();
        $result['code'] = 1;
        
        $result['msg'] = '获取成功！';
      
        return json($result); 
    }

     /**
     * 功能描述： 获取某个订单的历史详情
     * @author  Lucas 
     * 创建时间: 2020-02-28 10:13:33
     */
    public function order_hisitory() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/order_hisitory';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token);
        }else{
            $openid = 'oRqsn47Ar-NOdj2pRjLp2P2Ela4g';
        }
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $rentOrderID = input('get.rent_order_id');
        if(!$rentOrderID){
            $result['code'] = 10051;
            $result['msg'] = '订单编号不能为空';
            $result['en_msg'] = 'Rent Order Id is empty';
            return json($result);
        }
        $params = ParamModel::getCparams();
        //alias('a')->join('order b')
        $trades = WeixinOrderTradeModel::where([['rent_order_id','eq',$rentOrderID]])->order('ctime desc')->select()->toArray();
        $data = [];
        foreach ($trades as $k => $v) {
            $orderInfo = WeixinOrderModel::where([['out_trade_no','eq',$v['out_trade_no']]])->find();
            if($orderInfo['order_status'] != 3){ //如果是预支付状态则跳过当前记录
                $data[] = [
                    'time' => $orderInfo['ptime'],
                    'msg' => $params['order_status'][$orderInfo['order_status']],
                ];
            } 
        }
        
        $result['data'] = $data;
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        
        return json($result); 
    }

    public function house_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/house_detail';
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token);
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
        // if(!$this->check_token()){
        //     $result['msg'] = '令牌已失效！';
        //     return json($result);
        // }
        
        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $id = trim(input('get.house_id'));
        if(!$id){
            $result['code'] = 10018;
            $result['msg'] = '房屋编号不能为空';
            $result['en_msg'] = 'House ID is empty';
            return json($result);
        }
        //if($member_info['tenant_id']){
        $HouseModel = new HouseModel;
        $temp = HouseModel::with(['ban','tenant'])->get($id);
        $cutRent = Db::name('change_cut')->where([['house_id','eq',$id],['tenant_id','eq',$temp['tenant_id']],['change_status','eq',1],['end_date','>',date('Ym')]])->value('cut_rent');
        $temp['cut_rent'] = $cutRent?$cutRent:'0.00';
        
        $params = ParamModel::getCparams();

        $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
        $temp['house_use_id'] = $params['uses'][$temp['house_use_id']];
        $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
        $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
        $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
        // $temp['ban_imgs'] = SystemAnnex::changeFormat($temp['ban_imgs'],$complete = true);
        // $temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');
        $rooms = $HouseModel->get_house_renttable($id);
        foreach($rooms as &$t){
            $t['baseinfo']['room_type'] = $params['roomtypes'][$t['baseinfo']['room_type']];
            $t['baseinfo']['room_status'] = $params['status'][$t['baseinfo']['room_status']];
            $t['baseinfo']['ban_owner_id'] = $params['owners'][$t['baseinfo']['ban_owner_id']];
            $t['baseinfo']['ban_inst_id'] = $params['insts'][$t['baseinfo']['ban_inst_id']];
            $t['baseinfo']['ban_struct_id'] = $params['structs'][$t['baseinfo']['ban_struct_id']];
        }
        $temp['rooms'] = $rooms;
        $result['data'] = $temp;
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        
        return json($result);  
    }


    /**
     * 获取某个房屋的租金订单信息
     * @param id 消息id
     * @return json
     */
    public function rent_order_info() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token);
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
        
        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $houseID = trim(input('house_id')); //获取房屋id
        if(!$houseID){
            $result['code'] = 10018;
            $result['msg'] = '房屋编号不能为空';
            $result['en_msg'] = 'House ID is empty';
            return json($result);
        }
 
        $result['data']['rent'] = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')],['house_id','eq',$houseID]])->order('rent_order_id desc')->select();
        foreach ($result['data']['rent'] as $key => &$value) {
            $value['id'] = $key + 1;
        }
        $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        $result['data']['house'] = HouseModel::with('ban')->where([['house_id','eq',$houseID]])->field('house_balance,ban_id,house_id,house_pre_rent,house_unit_id,house_floor_id')->select();
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        $result['action'] = 'wechat/weixin/rent_order_info';
        return json($result); 
    }

    /**
     * 功能描述： 给openid绑定手机号
     * @author  Lucas 
     * 创建时间: 2020-02-26 16:26:08
     */
    public function binding_tel()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token);
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $tel = input('tel');
        $code = input('code');
        // 验证验证码
        if(!$code){
            $result['code'] = 10022;
            $result['msg'] = '验证码不能为空';
            $result['en_msg'] = 'Code is empty';
            return json($result);
        }
        // 验证手机号
        if(!$tel){
            $result['code'] = 10020;
            $result['msg'] = '手机号不能为空';
            $result['en_msg'] = 'Phone number is empty';
            return json($result);

            //$result['msg'] = '请输入手机号！';
        }
        $TenantModel = new TenantModel;
        $tenant_info = $TenantModel->where([['tenant_tel','eq',$tel]])->field('tenant_id,tenant_name')->find();
        if(!$tenant_info){
            $result['code'] = 10021;
            $result['msg'] = '手机号未在系统中绑定租户';
            $result['en_msg'] = 'Phone number is not bound to tenant in the system';
            return json($result);
        }
        
        $auth = new ServerCodeAPI();    
        $res = $auth->CheckSmsYzm($tel , $code);
        $res = json_decode($res);
        // 验证短信码是否正确
        if($res->code == '200'){
            $WeixinMemberModel = new WeixinMemberModel;
            $openid = cache('weixin_openid_'.$token);
            // 绑定手机号
            $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
            if($member_info['tenant_id']){
                $result['code'] = 10051;
                $result['msg'] = '请勿重复认证';
                $result['en_msg'] = 'Current user authenticated';
                return json($result);
            }

            $member_info->tenant_id = $tenant_info['tenant_id'];
            $member_info->real_name = $tenant_info['tenant_name'];
            $member_info->tel = $tel;
            $member_info->auth_time = time();
            $member_info->save();
            // 将认证的房屋加到member_house表中(去除掉暂停计租和注销的房子)
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $houses = HouseModel::where([['tenant_id','eq',$tenant_info['tenant_id']],['house_is_pause','eq',0],['house_status','eq',1]])->column('house_id');
            $houseSaveData = [];
            foreach ($houses as $h) {
                $row = $WeixinMemberHouseModel->where([['house_id','eq',$h],['member_id','eq',$member_info['member_id']],['dtime','eq',0]
])->find();
                // 如果已添加认证的房屋，直接修改认证状态
                if($row){
                    $row->is_auth = 1;
                    $row->save();
                // 没添加过，就直接添加进来并标识已认证
                }else{
                    $WeixinMemberHouseModel->house_id = $h;
                    $WeixinMemberHouseModel->member_id = $member_info['member_id'];
                    $WeixinMemberHouseModel->is_auth = 1;
                    $WeixinMemberHouseModel->save();
                }      
            }
            $result['code'] = 1;
            $result['msg'] = '绑定成功！';
        }else if($res->code == '413'){
            $result['code'] = 10023;
            $result['msg'] = '验证失败';
            $result['en_msg'] = 'Validation failed';
            return json($result);
        } else {
            $result['code'] = 10024;
            $result['msg'] = '请重新验证';
            $result['en_msg'] = 'Please get it again';
            return json($result);
        }
        return json($result);
    }

    /**
     * 功能描述： 验证用户token
     * @author  Lucas 
     * 创建时间: 2020-02-26 16:47:53
     */
    protected function check_token()
    {
        $token = input('token');
        $openid = cache('weixin_openid_'.$token);

        $expires_time = cache('weixin_expires_time_'.$token);
        //halt($expires_time);  
        if(!$openid){
        //if(!$openid || $expires_time < time()){
            return false;
        }
        return true;
    }

    /**
     * 功能描述： 发送短信
     * @author  Lucas 
     * 创建时间: 2020-02-26 16:27:20
     */
    public function send_message()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
                return json($result);
            }
        }
        $tel = input('tel');
        // 验证手机号
        if(!$tel){
            $result['code'] = 10020;
            $result['msg'] = '手机号不能为空';
            $result['en_msg'] = 'Phone number is empty';
            return json($result);
        }
        $TenantModel = new TenantModel;
        $tenant_id = $TenantModel->where([['tenant_tel','eq',$tel]])->value('tenant_id');
        if(!$tenant_id){
            $result['code'] = 10021;
            $result['msg'] = 'Phone number is not bound to tenant in the system';
            return json($result);
        }
        // 发送短信
        $auth = new ServerCodeAPI();
        $res = json_decode($auth->SendSmsCode($tel));
        if($res->code == '416'){
           $result['code'] = 10024;
            $result['msg'] = '验证次数超出限制';
            $result['en_msg'] = 'Validation times out of limit';
            return json($result);
        }else{
           $result['code'] = 1; 
           $result['msg'] = '发送成功！';
        }
        return json($result);
        
    }

    

    /**
     * 功能描述：
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-02-26 11:13:17 
     * @example 
     * @link    文档参考地址：
     * @return  返回值  
     * @version 版本  1.0
     */
    public function me()
    {
        /*$token = I('get.token');
        
        $weprogram_token = M('weprogram_token')->field('member_id')->where( array('token' =>$token) )->find();
        if(empty($weprogram_token))
        {
            $data = array('code' =>1);
        } else{
            $member_info =  M('member')->field('name,avatar')->where( array('member_id' => $weprogram_token['member_id']) )->find();
        
            $user_info = array();
            $user_info['headimgurl'] = $member_info['avatar'];
            $user_info['nickname'] = $member_info['name'];
            
            $data = array('code' =>0, 'user_info' => $user_info);
        }
        
        echo json_encode($data);
        die();*/
    }

    public function addhistory_community()
    {
        /*$gpc = I('request.');
        
        $token =  $gpc['token'];
        $head_id = $gpc['community_id'];
        
        
        $weprogram_token = M('lionfish_comshop_weprogram_token')->field('member_id')->where( array('token' => $token) )->find();
        
        if(  empty($weprogram_token) ||  empty($weprogram_token['member_id']) )
        {
            echo json_encode( array('code' => 2) );
            die();
        }
        $member_id = $weprogram_token['member_id'];
        
        D('Seller/Community')->in_community_history($member_id,$head_id);
        
        echo json_encode( array('code' => 0) );
        die();*/
    }

    public function in_community_history($member_id,$head_id)
    {
    
        /*if( !empty($head_id) && $head_id > 0 )
        {
            $history_info = M('lionfish_community_history')->where( array('head_id' => $head_id,'member_id' =>$member_id ) )->find();
        
            if( empty($history_info) )
            {
                $data = array();
                $data['member_id'] = $member_id;
                $data['head_id'] = $head_id;
                $data['addtime'] = time();
                
                M('lionfish_community_history')->add($data);
                
                
                $this->upgrade_head_level($head_id);
                
            } else {
                
                $sql = 'UPDATE '.C('DB_PREFIX'). 'lionfish_community_history SET addtime = '.time().' where id = '.$history_info['id'].'  order by id desc limit 1';
                M()->execute($sql);
            }
        }*/
        
    }



}