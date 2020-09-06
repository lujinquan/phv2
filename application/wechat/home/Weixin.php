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
class Weixin extends Common
{
    protected $debug = false;

    protected $domain = '';

    protected function initialize()
    {
        parent::initialize();
        $site_domain = ConfigModel::where([['name','eq','site_domain']])->value('value');
        $this->domain = 'https://'.$site_domain;
    }

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
            //halt($code);
            $resultOpenid = $WeixinModel->getOpenid($code);
            //halt($resultOpenid);
        }
        
        if(is_array($resultOpenid)){
            if($this->debug){
                // 下面是一个模拟返回的数组，注释上一行
                $resultAccessToken = [
                    'access_token' => 'debug_access_token',
                    'expires_in' => 7200,
                ];
            }else{
                //echo 2;exit;
                $resultAccessToken = $WeixinModel->getAccessToken();
            }

            $expires_time = time() + $resultAccessToken['expires_in']; //设置过期时间
            $token = md5($resultOpenid['openid'].time()); //设置token

            cache('weixin_openid_'.$token, $resultOpenid['openid'],7000); //存储openid
            cache('weixin_expires_time_'.$token, $expires_time,7000);  //存储过期时间
            cache('weixin_session_key_'.$token, $resultOpenid['session_key'],7000);  //存储session_key
            //cache('weixin_unionid_'.$token, $resultOpenid['unionid'],7000);  //存储unionid
            

            
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
     * 用户授权小程序
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 10:10:39
     * @return  返回值  
     * @version 版本  1.0
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

         // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }

        //halt($checkData['role_type']);
        // 获取所有业务列表
        $WeixinColumnModel = new WeixinColumnModel;
        $columns = $WeixinColumnModel->field('col_id,col_name,auth_roles,is_top,col_icon,app_page')->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->select()->toArray();
        //halt($columns);
        $all_process_columns = [];
        foreach ($columns as $k => &$v) {
             $file = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
             $v['file'] = $this->domain.$file;

             if($checkData['role_type'] == 2){ // 2代表是后台管理员
                if($v['auth_roles'] && !in_array($member_extra_info['role_id'],$v['auth_roles'])){
                    continue;
                }
             }
             if($checkData['role_type'] == 0){
                if($v['auth_roles'] && !in_array(100,$v['auth_roles'])){ // 100这个角色代表未认证的用户
                    continue;
                }
             }
             if($checkData['role_type'] == 1){
                if($v['auth_roles'] && !in_array(101,$v['auth_roles'])){ // 101这个角色代表已认证的用户（就是租户本人）
                    continue;
                }
             }

             if($member_info['app_cols']){
                if(in_array($v['col_id'], $member_info['app_cols'])){ // 标记已选择的图标
                    $v['is_choose'] = 1;
                 }else{
                    $v['is_choose'] = 0;
                 }  
             }else{
                $v['is_choose'] = 0;
             }
             $all_process_columns[] = $v;  
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
        $result['data']['column'] = $all_process_columns;
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
     * 高管获取审批列表
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-23 10:56:21
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_process_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_process_list';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $row = $checkData['member_extra_info'];
        }

        if($row){
            $params = ParamModel::getCparams();        
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $type = input('type',1);
            $changetype = input('change_type');
            $inst = input('ban_inst_id');

            $ProcessModel = new ProcessModel;
            if($changetype){
                $where[] = ['change_type','eq',$changetype];
            }else{
                $where[] = ['change_type','in',[1,3,4,7,8,9,10,11,13,14,17]];
            }
            $insts = config('inst_ids');
            if($inst){
                $where[] = ['d.ban_inst_id','in',$insts[$inst]];
            }else{
                $instid = $inst?$data['ban_inst_id']:$row['inst_id'];
                $where[] = ['d.ban_inst_id','in',$insts[$instid]];
            }
            $where[] = ['a.status','eq',1];

            $fields = "a.id,a.change_id,a.change_type,a.print_times,a.change_order_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_desc,a.curr_role,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $result = [];
            $result['data'] = $dataTemps = [];
            $temps = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ctime asc')->select();
            foreach($temps as $k => $v){
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['change_type_name'] = $params['changes'][$v['change_type']];
                if($type == 1){ // 
                    if($v['curr_role'] == $row['role_id']){
                        array_push($dataTemps,$v);
                    }
                }
                // else{
                //     if($v['curr_role'] != $row['role_id']){
                //         array_unshift($dataTemps,$v);
                //     }
                // }
            }

            $result['data'] = array_slice($dataTemps, ($page - 1) * $limit, $limit);
            $result['count'] = count($dataTemps);   

        
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    /**
     * 审批接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-23 10:57:05
     * @return  返回值  
     * @version 版本  1.0
     */
    public function process()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/process';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $row = $checkData['member_extra_info'];
        }
        $id = input('param.id/d');
        $change_type = input('param.change_type/d');

        define('ADMIN_ID', $row['id']);
        define('ADMIN_ROLE', $row['role_id']);

        //检查当前页面或当前表单，是否允许被请求？
        $PorcessModel = new ProcessModel;
        $rowProcess = $PorcessModel->where([['change_id','eq',$id],['change_type','eq',$change_type]])->find();
        if($rowProcess['curr_role'] != ADMIN_ROLE){
            $result['msg'] = '审批状态错误!';
            return json($result);
        }
        if($rowProcess['ftime'] > 0){
            $result['msg'] = '异动已经完成，请刷新重试！';
            return json($result);
        }

        $data = $this->request->get();
        //halt($data);
        if($change_type == 18 && ADMIN_ROLE == 6){
            $ChangeModel = new ChangeLeaseModel;
            $changeRow = $ChangeModel->where([['id','eq',$id]])->find();
            if(!$changeRow['print_times']){
                $result['msg'] = '请先打印租约后再审批！';
                return json($result);
                //return $this->error('请先打印租约后再审批！');
            }
        }

        // 如果审批失败，数据回滚
        Db::transaction(function () {
            $model = new ProcessModel;
            $change_type = input('param.change_type/d');
            $data = $this->request->get();
            $model->process($change_type,$data); //$data必须包含子表的id
        });

        $result['msg'] = '审批成功！';
        $result['code'] = 1;
        return json($result);
    }

    /**
     * 某个异动审批的详情
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-23 11:02:01
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_process_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_process_detail';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $row = $checkData['member_extra_info'];
        }
        $id = input('get.id/d');
        $change_type = input('param.change_type/d');
        $userRoles = UserModel::alias('a')->join('system_role b','a.role_id = b.id','left')->column('a.id,a.nick,a.role_id,b.name as role_name');

        if($row){
            $params = ParamModel::getCparams();
            // 显示对应的审批页面
            $id = input('param.id/d');
            
            if(!$change_type || !$id){
                return $this->error('参数错误！');
            }
            $PorcessModel = new ProcessModel;
            $result = [];
            $temps = $PorcessModel->detail($change_type,$id);
            $temps['row'] = $temps['row']->toArray();
            switch ($change_type) {
                case 1: // 租金减免
                    $temps['row']['cut_type'] = $params['cuttypes'][$temps['row']['cut_type']];
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];
                    $temps['row']['house_use_id'] = $params['uses'][$temps['row']['house_use_id']];
                    break;
                case 3: // 暂停计租
                    if($temps['row']['data_json']){
                        foreach ($temps['row']['data_json'] as $a => $b) {
                            $temps['row']['data_json'][$a]['house_use_id'] = $params['uses'][$b['house_use_id']];
                        }
                    }
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];
                    $temps['row']['ban_info']['ban_struct_id'] = $params['structs'][$temps['row']['ban_info']['ban_struct_id']];
                    $temps['row']['ban_info']['ban_damage_id'] = $params['damages'][$temps['row']['ban_info']['ban_damage_id']];
                    break;
                case 4: // 陈欠核销
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];

                    $temps['row']['ban_info']['ban_inst_id'] = $params['insts'][$temps['row']['ban_info']['ban_inst_id']];
                    if($temps['row']['data_json']){
                        foreach ($temps['row']['data_json'] as $a => $b) {
                            $temps['row']['data_json'][$a]['house_use_id'] = $params['uses'][$b['house_use_id']];
                            $temps['row']['data_json'][$a]['ban_owner_id'] = $params['owners'][$b['ban_owner_id']]; 
                        }
                    }
                    break;
                case 7: // 新发租
                    $temps['row']['new_type'] = $params['news'][$temps['row']['new_type']];
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];
                    $temps['row']['house_info']['house_use_id'] = $params['uses'][$temps['row']['house_info']['house_use_id']];
                    break;
                case 8: // 注销
                    $temps['row']['cancel_type'] = $params['cancels'][$temps['row']['cancel_type']];
                    break;
                case 9: // 房屋调整
                    
                    break;
                case 10: // 管段调整
                    $temps['row']['old_inst_id'] = $params['insts'][$temps['row']['old_inst_id']];
                    $temps['row']['new_inst_id'] = $params['insts'][$temps['row']['new_inst_id']];
                    if($temps['row']['data_json']){
                        foreach ($temps['row']['data_json'] as $a => $b) {
                            $temps['row']['data_json'][$a]['ban_inst_id'] = $params['insts'][$b['ban_inst_id']];
                        }
                    }
                    break;
                case 11: // 租金追加调整
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];

                    $temps['row']['ban_info']['ban_inst_id'] = $params['insts'][$temps['row']['ban_info']['ban_inst_id']];
                    break;
                case 13: // 使用权变更
                    $temps['row']['change_use_type'] = $params['usetypes'][$temps['row']['change_use_type']];
                    break;
                case 14: // 楼栋调整
                    $temps['row']['ban_change_id_name'] = $params['ban_change_ids'][$temps['row']['ban_change_id']];
                    if($temps['row']['old_damage']){
                        $temps['row']['old_damage'] = $params['damages'][$temps['row']['old_damage']];
                    }
                    if($temps['row']['new_damage']){
                        $temps['row']['new_damage'] = $params['damages'][$temps['row']['new_damage']];
                    }
                    if($temps['row']['old_struct']){
                        $temps['row']['old_struct'] = $params['structs'][$temps['row']['old_struct']];
                    }
                    if($temps['row']['new_struct']){
                        $temps['row']['new_struct'] = $params['structs'][$temps['row']['new_struct']];
                    }
                    $temps['row']['ban_info']['ban_inst_id'] = $params['insts'][$temps['row']['ban_info']['ban_inst_id']];
                    break;
                case 17: // 别字更正
                    
                    break;
                default:
                    break;
            }
            
            
            if($temps['row']['change_imgs']){
                foreach ($temps['row']['change_imgs'] as $k => $v) {
                    $temps['row']['change_imgs'][$k]['file'] = get_domain().$v['file'];
                }
            }        
            if($temps['row']['child_json']){
                foreach ($temps['row']['child_json'] as $a => $b) {
                    $temps['row']['child_json'][$a]['role_name'] = $userRoles[$b['uid']]['role_name'];
                    $temps['row']['child_json'][$a]['nick'] = $userRoles[$b['uid']]['nick'];
                }
            }

            $result['data'] = $temps;      
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }

    /**
     * 获取租金列表
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-23 11:18:20
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_rent_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_rent_list';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $row = $checkData['member_extra_info'];
        }

        if($row){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $type = input('type');
            $use = input('house_use_id');
            $owner = input('ban_owner_id');
            $tenant = input('tenant_name');
            $status = input('house_status');
            $address = input('ban_address');
            $date = input('rent_order_date');
            $ptime = input('ptime');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $gpsx = input('param.gpsx', '114.307803');
            $gpsy = input('param.gpsy', '30.556853');
//dump('gpsx：'.$gpsx);halt($gpsy);
            $insts = config('inst_ids');
            
            $keywords = input('keywords');
            $keywordsWhere = '';
            if($keywords){
                $keywordsWhere = " c.tenant_name like '%".$keywords."%' or d.ban_address like '%".$keywords."%'";
            }

            $where = [];
            $where[] = ['d.ban_inst_id','in',$insts[$row['inst_id']]];
            
            
            if($use){
                $where[] = ['a.house_use_id','eq',$use];
            }
            if($owner){
                $where[] = ['d.ban_owner_id','eq',$owner];
            }
            if($address){
                $where[] = ['d.ban_address','like','%'.$address.'%'];
            }
            if($tenant){
                $where[] = ['c.tenant_name','like','%'.$tenant.'%'];
            }
            if($date){
                $tempDate = str_replace('-', '', $date);
                $where[] = ['rent_order_date','eq',$tempDate];
            }
            if($ptime){
                $nowDate = $ptime;
                $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));
                $where[] = ['ptime','between time',[$nowDate,$nextDate]];
            }

            if($type){ //如果是已缴，按照订单来排列
                $where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
                $where[] = ['a.ptime','>',0];

                $fields = 'a.id,a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.pay_way,a.ptime,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_id,b.house_number,b.house_use_id,b.house_unit_id,b.house_floor_id,b.house_share_img,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
                $data = [];
                $temps = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->where($keywordsWhere)->page($page)->limit($limit)->order('a.rent_order_date desc')->select();
               

                $result['data'] = [];
                foreach ($temps as $v) { 
                    //$v['id'] = $v['id'];
                    $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                    $v['house_use_id'] = $params['uses'][$v['house_use_id']];
                    $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                    $v['rent_order_date'] = substr($v['rent_order_date'],0,4).'-'.substr($v['rent_order_date'],4,2).'-01';
                    if($v['ptime']){
                        $v['ptime'] = date('Y-m-d H:i:s',$v['ptime']);
                    }
                    if($v['pay_way']){
                        $v['pay_way'] = $params['pay_way'][$v['pay_way']];
                    }
                    $result['data'][] = $v;
                }
                $result['count'] = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->where($keywordsWhere)->count('a.rent_order_id');

            }else{ //如果是待缴，按照房屋来排列
                $where[] = ['rent_order_paid','exp',Db::raw('<rent_order_receive')]; 
                
                // 按照距离来排序
                $is_distance_sort = false;
                if ($is_distance_sort) {
                    $fields = 'a.rent_order_receive,a.rent_order_paid,sum(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.pay_way,a.ptime,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_id,b.house_number,b.house_use_id,b.house_unit_id,b.house_floor_id,b.house_share_img,c.tenant_name,d.ban_address,d.ban_id,d.ban_gpsx,d.ban_gpsy,d.ban_owner_id,d.ban_inst_id';
                    $temps = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->where($keywordsWhere)->group('a.house_id')->select();
                    $ban_arr = Db::name('ban')->where([['ban_status','eq',1],['ban_inst_id','in',$insts[$row['inst_id']]]])->column('ban_id,ban_gpsx,ban_gpsy');

                    $distances = array();

                    foreach ($ban_arr as &$ban) {
                        // 计算距离
                        $ban['distance'] = get_distance($gpsy,$gpsx,$ban['ban_gpsy'],$ban['ban_gpsx']);
                    }

                    
                    // foreach ($users as $user) {
                    //   $distances[] = $user['age'];
                    // }
                    // array_multisort($ages, SORT_ASC, $users);

                    $result['data'] = [];
                    foreach ($temps as &$v) { 
                        $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                        $v['house_use_id'] = $params['uses'][$v['house_use_id']];
                        $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                        $v['distance'] = $ban_arr[$v['ban_id']]['distance'];
                        //$result['data'][] = $v;
                    }

                    sort($temps);

                    //二维数组冒泡排序
                    $a = [];
                    foreach($temps as $key=>$val){
                        $a[] = $val['distance']; // $a是$sort的其中一个字段
                    }
                    $temps = bubble_sort($temps,$a,'asc'); // 正序

                    $result['data']  = array_slice($temps, ($page- 1) * $limit, $limit);
                    $result['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->where($keywordsWhere)->count('a.house_id');
                }else{
                    $fields = 'a.rent_order_receive,a.rent_order_paid,sum(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.pay_way,a.ptime,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_id,b.house_number,b.house_use_id,b.house_unit_id,b.house_floor_id,b.house_share_img,c.tenant_name,d.ban_address,d.ban_id,d.ban_gpsx,d.ban_gpsy,d.ban_owner_id,d.ban_inst_id';
                    $temps = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->page($page)->limit($limit)->where($where)->where($keywordsWhere)->group('a.house_id')->select();

                    foreach ($temps as &$v) { 
                        $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                        $v['house_use_id'] = $params['uses'][$v['house_use_id']];
                        $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                        //$v['distance'] = $ban_arr[$v['ban_id']]['distance'];
                        //$result['data'][] = $v;
                    }
                    $result['data'] = $temps;
                    $result['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->where($keywordsWhere)->count('a.house_id');
                }
                
            }
            //halt($result);
            $result['pages'] = ceil($result['count'] / $limit);
            $result['cur_page'] = $page;
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    /**
     * 获取某个租金订单的详情
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-23 11:19:31
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_rent_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_rent_detail';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $row = $checkData['member_extra_info'];
        }

        $id = input('get.id');
        //$id = 1;

        $RentOrderChildModel = new RentOrderChildModel;
        $row = $RentOrderChildModel->detail($id);

        $row['pdfurl'] = ''; 

        // 如果是微信支付则显示微信支付的相关数据
        if ($row['pay_way'] == 4) {
            $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            $rent_order_trade_info = $WeixinOrderTradeModel->where([['rent_order_id','eq',$row['rent_order_id']]])->field('out_trade_no,pay_dan_money')->find();

            $WeixinOrderModel = new WeixinOrderModel;
            $order_info = $WeixinOrderModel->with('weixinMember')->where([['out_trade_no','eq',$rent_order_trade_info['out_trade_no']]])->find()->toArray();
            if($order_info['order_status'] == 2){ //如果状态是已退款
                $WeixinOrderRefundModel = new WeixinOrderRefundModel;
                $order_refund_info = $WeixinOrderRefundModel->where([['order_id','eq',$id]])->find();
                $result['order_refund_info'] = $order_refund_info;
            }
            if($order_info['invoice_id']){
              $InvoiceModel = new InvoiceModel;
              $invoice_info = $InvoiceModel->find($order_info['invoice_id'])->toArray();
              $invoice_info['fplx'] = ($invoice_info['fplx'] == '026')?'增值税电子发票':'区块链发票';
              $invoice_info['zsfs'] = ($invoice_info['zsfs'] == 2)?'差额征税':'普通征税';
              $invoice_info['kplx'] = ($invoice_info['kplx'])?'红字发票':'蓝字发票';
              $result['invoice_info'] = $invoice_info;
              $order_info['invoice_id'] = '是';
              $row['pdfurl'] = ($this->domain).$invoice_info['local_pdfurl']; 
            }
        }
        

        $params = ParamModel::getCparams();

        $row['ban_inst_id'] = $params['insts'][$row['ban_inst_id']];
        $row['house_use_id'] = $params['uses'][$row['house_use_id']];
        $row['ban_owner_id'] = $params['owners'][$row['ban_owner_id']];
        $row['pay_way'] = $params['pay_way'][$row['pay_way']];
        $row['rent_order_date'] = substr($row['rent_order_date'],0,4).'年'.substr($row['rent_order_date'],4,2).'月01日';
        // if($row['ptime']){
        //     $row['ptime'] = date('Y年m月d日',$row['ptime']);
        // }
        $result['data'] = $row;
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
//halt($id);
        // if($row){
        //     $BanModel = new BanModel;

        //     $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.ptime,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id';
        //     $temp = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['id','eq',$id]])->find();

        //     $params = ParamModel::getCparams();

        //     $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
        //     $temp['house_use_id'] = $params['uses'][$temp['house_use_id']];
        //     $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
        //     $temp['rent_order_date'] = substr($temp['rent_order_date'],0,4).'年'.substr($temp['rent_order_date'],4,2).'月01日';
        //     if($temp['ptime']){
        //         $temp['ptime'] = date('Y年m月d日',$temp['ptime']);
        //     }
        //     $result['data'] = $temp;            
        //     $result['code'] = 1;
        //     $result['msg'] = '获取成功！';
        // }else{
        //     $result['msg'] = '参数错误！';
        // }
        return json($result);  
    }

    /**
     * 获取主页的数据（场景：用户访问小程序首页调用）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 10:13:15
     * @return  返回值  
     * @version 版本  1.0
     */
    public function index_info()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/index_info';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }

        $banners = WeixinBannerModel::where([['dtime','eq',0],['is_show','eq',1]])->order('sort desc')->select()->toArray();
        foreach ($banners as &$b) {
            $banner = SystemAnnex::where([['id','eq',$b['banner_img']]])->value('file');
            $b['banner_img'] = $this->domain.$banner;
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
        $columns = $WeixinColumnModel->field('col_id,col_name,auth_roles,col_icon,app_page')->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->select()->toArray();
        foreach ($columns as $k => &$v) {
             $file = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
             $v['file'] = $this->domain.$file;
             if(!in_array($v['col_id'],$member_info['app_cols'])){
                unset($columns[$k]);
             }
             //待优化，下面这一大段
             if($checkData['role_type'] == 2){ // 2代表是后台管理员
                if($v['auth_roles'] && !in_array($member_extra_info['role_id'],$v['auth_roles'])){
                    unset($columns[$k]);
                }
             }
             if($checkData['role_type'] == 0){
                if($v['auth_roles'] && !in_array(100,$v['auth_roles'])){ // 100这个角色代表未认证的用户
                    unset($columns[$k]);
                }
             }
             if($checkData['role_type'] == 1){
                if($v['auth_roles'] && !in_array(101,$v['auth_roles'])){ // 101这个角色代表已认证的用户（就是租户本人）
                    unset($columns[$k]);
                }
             }
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
     * 楼栋列表（场景：管理员点击业务的“房屋档案”）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 11:07:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_ban_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_ban_list';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            if($checkData['role_type'] != 2){ //如果当前用户不是管理员
                $result['code'] = '权限不足';
                $result['msg'] = '20000';
                return json($result);
            }
            $row = $checkData['member_extra_info'];
        }

        if($row){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $damage = input('ban_damage_id');
            $owner = input('ban_owner_id');
            $struct = input('ban_struct_id');
            $status = input('ban_status');
            $address = input('ban_address');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $BanModel = new BanModel;
            $where = [];
            
            $where[] = ['ban_inst_id','in',config('inst_ids')[$row['inst_id']]];
            
            if($damage){
                $where[] = ['ban_damage_id','eq',$damage];
            }
            if($owner){
                $where[] = ['ban_owner_id','eq',$owner];
            }
            if($address){
                $where[] = ['ban_address','like','%'.$address.'%'];
            }
            if($struct){
                $where[] = ['ban_struct_id','eq',$struct];
            }
            if($status !== null){
                $where[] = ['ban_status','eq',$status];
            }else{
                $where[] = ['ban_status','eq',1];
            }
            //halt($where);
            $temps = $BanModel->field('ban_id,ban_number,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,(ban_civil_rent+ban_party_rent+ban_career_rent) as ban_rent,(ban_civil_area+ban_party_area+ban_career_area) as ban_area,ban_use_area,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_property_source,ban_units,ban_floors,(ban_civil_holds+ban_party_holds+ban_career_holds) as ban_holds,ban_status')->where($where)->page($page)->limit($limit)->order('ban_ctime desc')->select()->toArray();
            $result['data'] = [];
            foreach ($temps as $v) {
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['ban_status'] = $params['status'][$v['ban_status']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                $v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = $BanModel->where($where)->order('ban_ctime desc')->count('ban_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    /**
     * 楼栋详情（场景：管理员点击楼栋详情）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 11:07:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_ban_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_ban_detail';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            if($checkData['role_type'] != 2){ //如果当前用户不是管理员
                $result['code'] = '权限不足';
                $result['msg'] = '20000';
                return json($result);
            }
            $row = $checkData['member_extra_info'];
        }
        $id = input('get.ban_id');
        if($row){
            $BanModel = new BanModel;
            $temp = $BanModel->get($id);
            $params = ParamModel::getCparams();
            $temp['ban_rent'] = bcaddMerge([$temp['ban_civil_rent'],$temp['ban_party_rent'],$temp['ban_career_rent']]);
            $temp['ban_area'] = bcaddMerge([$temp['ban_civil_area'],$temp['ban_party_area'],$temp['ban_career_area']]);
            $temp['ban_oprice'] = bcaddMerge([$temp['ban_civil_oprice'],$temp['ban_party_oprice'],$temp['ban_career_oprice']]);
            $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
            $temp['ban_status'] = $params['status'][$temp['ban_status']];
            $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            $temp['ban_imgs'] = SystemAnnex::changeFormat($temp['ban_imgs'],$complete = true);
            $temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');

            $result['data'] = $temp;
                      
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }

    /**
     * 房管员版
     * 房屋列表（场景：管理员点击业务的“房屋档案”）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 11:07:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_house_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_house_list';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            if($checkData['role_type'] != 2){ //如果当前用户不是管理员
                $result['code'] = '权限不足';
                $result['msg'] = '20000';
                return json($result);
            }
            $row = $checkData['member_extra_info'];
        }

        if($row){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $use = input('house_use_id');
            $owner = input('ban_owner_id');
            $tenant = input('tenant_name');
            $status = input('house_status');
            $address = input('ban_address');
            $house_is_pause = input('house_is_pause');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            
            $where = [];
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$row['inst_id']]];
            
            if($house_is_pause !== null){
                $where[] = ['a.house_is_pause','eq',$house_is_pause];
            }
            if($use){
                $where[] = ['a.house_use_id','eq',$use];
            }
            if($owner){
                $where[] = ['d.ban_owner_id','eq',$owner];
            }
            if($address){
                $where[] = ['d.ban_address','like','%'.$address.'%'];
            }
            if($tenant){
                $where[] = ['c.tenant_name','like','%'.$tenant.'%'];
            }
            if($status !== null){
                $where[] = ['a.house_status','eq',$status];
            }else{
                $where[] = ['d.ban_status','eq',1]; 
            }
            //halt($where);
            $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,a.house_status,c.tenant_id,c.tenant_name,d.ban_units,d.ban_floors,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';
            //halt($where);
            $data = [];
            $temps = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();

            $result['data'] = [];
            foreach ($temps as $v) {
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['house_use_id'] = $params['uses'][$v['house_use_id']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['house_status'] = $params['status'][$v['house_status']];
                //$v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                //$v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.house_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    /**
     * 房屋详情（场景：管理员点击房屋详情）【借口已经废弃，因为用户版已经有房屋详情接口了】
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 11:07:43
     * @return  返回值  
     * @version 版本  1.0
     */
    /*public function admin_house_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/index_info';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            if($checkData['role_type'] != 2){ //如果当前用户不是管理员
                $result['code'] = '权限不足';
                $result['msg'] = '20000';
                return json($result);
            }
            $row = $checkData['member_extra_info'];
        }
        $id = input('get.house_id');

        if($row){
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
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }*/


    /**
     * 租户列表（场景：管理员点击业务的“租户档案”）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 11:07:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_tenant_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_tenant_list';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            if($checkData['role_type'] != 2){ //如果当前用户不是管理员
                $result['code'] = '权限不足';
                $result['msg'] = '20000';
                return json($result);
            }
            $row = $checkData['member_extra_info'];
        }
// $UserModel = new UserModel;
// $row = $UserModel->where([['weixin_member_id','eq',42]])->find();
        if($row){
            $params = ParamModel::getCparams();
            //$result['data']['params'] = $params;
            $status = input('tenant_status');
            $tenant = input('tenant_name');
            $tenant_tel = input('tenant_tel');
            $tenant_card = input('tenant_card');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $where = [];
            $where[] = ['tenant_inst_id','in',config('inst_ids')[$row['inst_id']]];
            if($tenant){
                $where[] = ['tenant_name','like','%'.$tenant.'%'];
            }
            if($tenant_tel){
                $where[] = ['tenant_tel','like','%'.$tenant_tel.'%'];
            }
            if($tenant_card){
                $where[] = ['tenant_card','like','%'.$tenant_card.'%'];
            }
            if($status !== null){
                $where[] = ['tenant_status','eq',$status];
            }else{
                $where[] = ['tenant_status','eq',1];   
            }
            //,sum(house_balance) as tenant_balance
            $fields = 'tenant_id,tenant_inst_id,tenant_inst_pid,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_status';
            $result = [];
            //halt($where);
            $temps = Db::name('tenant')->field($fields)->where($where)->page($page)->order('tenant_ctime desc')->limit($limit)->select();
            
            $result['data'] = [];
            foreach ($temps as $v) {
                // $v['tenant_inst_id'] = $params['insts'][$v['tenant_inst_id']];
                $v['tenant_status'] = $params['status'][$v['tenant_status']];
                $v['tenant_balance'] = Db::name('house')->where([['tenant_id','eq',$v['tenant_id']]])->sum('house_balance');
                //halt($v);
                // $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                // $v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                // $v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = Db::name('tenant')->where($where)->count('tenant_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    /**
     * 缴费，可以缴纳部分（迭代2.0.3）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-02 14:10:46
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_pay()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_pay';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            if($checkData['role_type'] != 2){ //如果当前用户不是管理员
                $result['code'] = '权限不足';
                $result['msg'] = '20000';
                return json($result);
            }
            $row = $checkData['member_extra_info'];
        }



        $rent_order_ids = trim(input('rent_order_ids')); //缴费的ids
        $pay_rent = trim(input('pay_rent')); //缴费的金额
        $house_id = trim(input('house_id')); //缴费的房屋

        if($rent_order_ids){ // 方式：收欠，以订单为单位
            $RentModel = new RentModel;
            $RentModel->whole_orders_to_pay(explode(',',$rent_order_ids),$row['id']); 
        }else{ // 方式：缴费，缴费金额
            $RentModel = new RentModel; 
            $RentModel->pay_for_rent($house_id,$pay_rent,$row['id']);
        }
        
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        return json($result);    
    }

    /**
     * 租户详情（场景：管理员点击租户详情）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-06-22 11:21:16
     * @return  返回值  
     * @version 版本  1.0
     */
    public function admin_tenant_detail()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/admin_tenant_detail';
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            // if($checkData['role_type'] != 2){ //如果当前用户不是管理员
            //     $result['code'] = '权限不足';
            //     $result['msg'] = '20000';
            //     return json($result);
            // }
            $row = $checkData['member_extra_info'];
        }
        $id = input('get.tenant_id');

        if($row){
            $TenantModel = new TenantModel;
            $temp = $TenantModel->get($id);
            $params = ParamModel::getCparams();

            $temp['tenant_inst_id'] = $params['insts'][$temp['tenant_inst_id']];
            // $temp['ban_status'] = $params['status'][$temp['ban_status']];
            // $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            // $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            // $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            $temp['tenant_imgs'] = SystemAnnex::changeFormat($temp['tenant_imgs'],$complete = true);
            //$temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');

            $result['data'] = $temp;

                      
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
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
        $limit = 20;
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
        // 初始化数据
        $result = ['code'=>0,'action'=>'wechat/weixin/notice_detail'];
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{
            $member_info = $checkData['member_info']; //微信用户基础数据
            // if($checkData['role_type'] != 2){ //如果当前用户不是管理员
            //     $result['code'] = '权限不足';
            //     return json($result);
            // }
        }
        // 获取办事指引详情
        $WeixinNoticeModel = new WeixinNoticeModel;
        $res = $WeixinNoticeModel->detail(trim(input('id')));
        if(!$res){
            return json(['msg'=>$res->getError()]);
        }
        // 绑定手机号
        // if($member_info['member_name']){ //如果用户已授权则记录下会员的浏览记录
        //     $WeixinReadRecordModel = new WeixinReadRecordModel;
        //     $record = $WeixinReadRecordModel->where([['notice_id','eq',$id],['member_id','eq',$member_info['member_id']]])->find();
        //     if(!$record){
        //         $WeixinReadRecordModel->save(['notice_id'=>$id,'member_id'=>$member_info['member_id'],'member_name'=>$member_info['member_name'],'avatar'=>$member_info['avatar']]);
        //     }
        // }

        $result['data'] = $res;
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
        // 初始化数据
        $result = ['code'=>0,'action'=>'wechat/weixin/guide_detail'];
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{
            $member_info = $checkData['member_info']; //微信用户基础数据
            // if($checkData['role_type'] != 2){ //如果当前用户不是管理员
            //     $result['code'] = '权限不足';
            //     return json($result);
            // }
        } 
        // 获取办事指引详情
        $WeixinGuideModel = new WeixinGuideModel;
        $res = $WeixinGuideModel->detail(trim(input('id')));
        if(!$res){
            return json(['msg'=>$res->getError()]);
        }else{
            $result['data'] = $res;
            $result['code'] = 1;
            $result['msg'] = '获取成功！'; 
            return json($result);
        }
    }

        /**
     * 功能描述： 给会员添加房屋（添加member_house关联记录）
     * @author  Lucas 
     * 创建时间: 2020-02-26 17:36:54
     */
    public function add_member_house_check()
    {
        // 初始化数据
        $result = ['code'=>0,'action'=>'wechat/weixin/add_member_house_check'];
        // 验证用户
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{
            $member_info = $checkData['member_info']; //微信用户基础数据
            // if($checkData['role_type'] != 2){ //如果当前用户不是管理员
            //     $result['code'] = '权限不足';
            //     return json($result);
            // }
        } 
        $type = trim(input('type',''));  //查询的类型，1，手机号 2，身份证号 3，房屋编号
        $keywords = trim(input('keywords'));
        if(!$keywords){
            $result['code'] = 10007;
            $result['msg'] = '关键词不能为空';
            $result['en_msg'] = 'keywords is empty';
            return json($result);
        }
        if(!in_array($type,[1,2,3])){
            $result['code'] = 100022;
            $result['msg'] = '查询类型错误';
            $result['en_msg'] = 'query type error';
            return json($result);
        }
        $houseArr = [];
        if($type == 1){ //1，手机号
            $TenantModel = new TenantModel;
            $tenant_ids = $TenantModel->where([['tenant_tel','eq', $keywords]])->column('tenant_id');
            if(!$tenant_ids){
                $result['msg'] = '手机号查询为空';
                return json($result);
            }
            $HouseModel = new HouseModel;
            $houseArr = $HouseModel->with(['ban','tenant'])->where([['house_status','eq',1],['house_is_pause','eq',0],['tenant_id','in',$tenant_ids]])->select()->toArray();

        }else if($type == 2){ //2，身份证号
            $TenantModel = new TenantModel;
            $tenant_ids = $TenantModel->where([['tenant_card','eq', strtolower($keywords)]])->column('tenant_id');
            if(!$tenant_ids){
                $result['msg'] = '身份证号查询为空';
                return json($result);
            }
            $HouseModel = new HouseModel;
            $houseArr = $HouseModel->with(['ban','tenant'])->where([['house_status','eq',1],['house_is_pause','eq',0],['tenant_id','in',$tenant_ids]])->select()->toArray();
        }else{ //3，房屋编号
            $HouseModel = new HouseModel;
            $houseArr = $HouseModel->with(['ban','tenant'])->where([['house_status','eq',1],['house_is_pause','eq',0],['house_number','eq',$keywords]])->select()->toArray();
        }
        if(!$houseArr){
            $result['msg'] = '房屋查询为空';
            return json($result);
        }
        $ar = [];
        foreach ($houseArr as $k => $v) {
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $find = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$v['house_id']],['dtime','eq',0]])->find();
            if(!$find){
                $ar[] = $v;
            }
        }
        if(!$ar){
            $result['msg'] = '请勿重复绑定该房屋';
            return json($result);
        }
        
        $result['code'] = 1;
        $result['data'] = $ar;
        $result['msg'] = '查询成功';
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
        $house_ids = trim(input('house_ids'));
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
 
        $HouseModel = new HouseModel;
        $houseArr = $HouseModel->where([['house_status','eq',1],['house_is_pause','eq',0],['house_id','in',explode(',',$house_ids)]])->select()->toArray();

        foreach ($houseArr as $k => $v) {
            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            $find = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$v['house_id']],['dtime','eq',0]])->find();
            if(!$find){
                $WeixinMemberHouseModel->house_id = $v['house_id'];
                $WeixinMemberHouseModel->member_id = $member_info['member_id'];
                $WeixinMemberHouseModel->save();
            }
                 
        } 
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
     * 功能描述： 给会员删除房屋（添加member_house关联记录）
     * @author  Lucas 
     * 创建时间: 2020-02-26 17:36:54
     */
    public function del_member_house()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/del_member_house';
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            // if($checkData['role_type'] != 2){ //如果当前用户不是管理员
            //     $result['code'] = '权限不足';
            //     $result['msg'] = '20000';
            //     return json($result);
            // }
        }
        $house_id = trim(input('house_id'));
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        $WeixinMemberHouseModel->where([['house_id','eq',$house_id],['member_id','eq',$member_info['member_id']]])->update(['dtime'=>time()]);
        $result['code'] = 1;
        $result['msg'] = '删除成功';
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
            $is_mine = input('is_mine');
            if($is_mine == 1){ //如果是认证场景调用

                $TenantModel = new TenantModel;
                $tenant_info = $TenantModel->where([['tenant_tel','eq',$member_info['tel']]])->find();
                $HouseModel = new HouseModel;
                //认证的时候，显示暂停计租和注销状态的房子,['house_status','eq',1],['house_is_pause','eq',0]
                $result['data'] = $HouseModel->with(['ban','tenant'])->where([['tenant_id','eq',$tenant_info['tenant_id']]])->select()->toArray();
                $result['code'] = 1;
                $result['msg'] = '获取成功';
                //halt($result['data']);
                return json($result);
            }
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
                    
                    $rent_order_unpaids = Db::name('rent_order')->where([['house_id','eq',$v['house_id']]])->value('sum(rent_order_receive - rent_order_paid) as rent_order_unpaids');

                    $row['rent_order_unpaids'] = $rent_order_unpaids?$rent_order_unpaids:0;
                    
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
        // if($this->debug === false){ 
        //     if(!$this->check_token()){
        //         $result['code'] = 10010;
        //         $result['msg'] = '令牌失效';
        //         $result['en_msg'] = 'Invalid token';
        //         return json($result);
        //     }
        //     $token = input('token');
        //     $openid = cache('weixin_openid_'.$token);
        // }else{
        //     $openid = 'oRqsn4624ol3tpa1JiBPQuY1toMY';
        // }
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            // if($checkData['role_type'] != 2){ //如果当前用户不是管理员
            //     $result['code'] = '权限不足';
            //     $result['msg'] = '20000';
            //     return json($result);
            // }
            $member_extra_info = $checkData['member_extra_info'];
        }
        // 绑定手机号
        //$WeixinMemberModel = new WeixinMemberModel;
        //$member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $result['data']['member'] = $member_info;
        $result['data']['role_type'] = $checkData['role_type'];
        $result['data']['member_extra_info'] = $member_extra_info;

        // 查找绑定的房屋
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        // 获取关联的所有房屋
        $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->column('house_id');
        // 获取关联的所有自己已认证的房屋
        $is_auth_houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['is_auth','eq',1],['dtime','eq',0]])->column('house_id');

        $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        // 去掉暂停计租的房子+已注销的房子
        $result['data']['house'] = HouseModel::with('ban,tenant')->where([['house_id','in',$houses],['house_is_pause','eq',0],['house_status','eq',1]])->field('house_id,house_use_id,house_balance,ban_id,tenant_id,house_unit_id,house_is_pause,house_pre_rent,house_status,house_floor_id,house_balance')->select()->toArray();
        $yue = 0;
        foreach ($result['data']['house'] as $k => &$v) {
            $row = Db::name('rent_order')->where([['house_id','eq',$v['house_id']]])->field('sum(rent_order_receive - rent_order_paid) as rent_order_unpaids,sum(rent_order_paid) as rent_order_paids')->find();
            if($row['rent_order_unpaids'] == 0){
                unset($result['data']['house'][$k]);
                continue;
            }
            $v['is_auth'] = 0;
            $v['house_use_id'] = $v['house_use_id'];
            if(in_array($v['house_id'], $is_auth_houses)){
                $yue += $v['house_balance'];
                $v['is_auth'] = 1;
            }
            $v['rent_order_unpaids'] = $row['rent_order_unpaids']?$row['rent_order_unpaids']:0;
            $v['rent_order_paids'] = $row['rent_order_paids']?$row['rent_order_paids']:0;
        }
        $result['data']['house'] = array_values($result['data']['house']);
        $result['data']['yue'] = $yue;
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        return json($result); 
    }

    /**
     * 功能描述： 获取我的订单列表数据【原接口，由于调整成了按照支付记录来，所以暂时弃用】
     * 小程序使用页面：【 我的 -> 历史账单 】
     * @author  Lucas 
     * 创建时间: 2020-02-28 10:13:33
     */
    public function my_order_list_old() 
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
        
        $houseArr = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->column('house_id,is_auth');
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
        // 只显示微信支付的记录
        $where[] = ['a.pay_way','eq',4];
        $where[] = ['a.rent_order_status','eq',1];
        if($houseID){
            $houseArr = $WeixinMemberHouseModel->where([['house_id','eq',$houseID],['dtime','eq',0]])->column('house_id,is_auth');
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
        $rents = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ptime desc')->select();
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
            //$value['pay_way_name'] = $value['pay_way'];
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
     * 功能描述： 获取我的订单列表数据 【待优化：两个搜索条件同时搜索有问题】
     * 小程序使用页面：【 我的 -> 历史账单 】
     * @author  Lucas 
     * 创建时间: 2020-02-28 10:13:33
     */
    public function my_order_list() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 1;
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

        // 初始化参数
        $where = $data = [];
        $params = ParamModel::getCparams();
        $where[] = ['ptime','>',0];
        $where[] = ['member_id','eq',$member_info['member_id']];

        // 支付时间搜索
        $datasel = input('get.data_sel');

        // 房屋id
        $house_id = input('get.house_id');
        if($datasel){
            //halt($datasel);
            $startDate = $datasel;
            $endDate = date( "Y-m", strtotime( "first day of next month",strtotime($datasel) ) );
            $where[] = ['ptime','between time',[$startDate,$endDate]];
        }
        // if($house_id){
        //     $where[] = ['house_id','eq',$house_id];
        // }
        // 查找绑定的房屋
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        
        $houseArr = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->column('house_id,is_auth');
        if(!$houseArr){
            $result['code'] = 10050;
            $result['msg'] = '当前会员未绑定任何房屋';
            $result['en_msg'] = 'The current user is not bound to any house';
            return json($result);
        }
        $houseIds = array_keys($houseArr);

        // 查询微信支付订单
        $temp = WeixinOrderModel::with('weixinMember')->where($where)->order('ctime desc')->select()->toArray();
        if(empty($temp)){
            $result['code'] = 10051;
            $result['msg'] = '暂无支付订单记录';
            return json($result);
        }
        foreach ($temp as $k => &$v) {
            // 每一个支付订单有可能对应多个月租金订单，但是由于都是一个房屋，所以只取一个即可
            $rent_order_id = WeixinOrderTradeModel::where([['out_trade_no','eq',$v['out_trade_no']]])->value('rent_order_id');
            $info = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('a.rent_order_date,a.house_id,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_address')->where([['a.rent_order_id','eq',$rent_order_id]])->find();
            if($house_id){
                if($info['house_id'] != $house_id){
                    unset($temp[$k]);
                    continue;
                }
            }
            if($info){
                $v['house_number'] = $info['house_number'];
                $v['tenant_name'] = $info['tenant_name'];
                $v['rent_order_date'] = $info['rent_order_date'];
                $v['ban_address'] = $info['ban_address'];
                $v['pay_way_name'] = $params['pay_way'][4]; // 只有微信支付，所以是4
            }
        }

        $result['data']['rent'] = $temp;
        if(empty($temp)){
            $result['code'] = 10051;
        }
        $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        $result['data']['house'] = HouseModel::with('ban')->where([['house_id','in',$houseIds]])->field('house_balance,house_id,house_pre_rent,ban_id,house_unit_id,house_floor_id')->select();
        
        $result['msg'] = '获取成功！';
        return json($result); 
    }

    /**
     * 功能描述： 获取我的订单列表的详情
     * 小程序使用页面：【 我的 -> 历史账单 】
     * @author  Lucas 
     * 创建时间: 2020-02-28 10:13:33
     */
    public function my_order_detail() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/my_order_detail';
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
        // 接收微信支付订单的id（即weixin_order的主键order_id）
        $id = input('get.order_id');
        
        
        $WeixinOrderModel = new WeixinOrderModel;
        $order_info = $WeixinOrderModel->with('weixinMember')->find($id)->toArray();
        if($order_info['order_status'] == 2){ //如果状态是已退款
            $WeixinOrderRefundModel = new WeixinOrderRefundModel;
            $order_refund_info = $WeixinOrderRefundModel->where([['order_id','eq',$id]])->find();
            $result['order_refund_info'] = $order_refund_info;
        }else{
            $result['order_refund_info'] = '';
        }
        if($order_info['invoice_id']){
          $InvoiceModel = new InvoiceModel;
          $invoice_info = $InvoiceModel->find($order_info['invoice_id'])->toArray();
          $invoice_info['fplx'] = ($invoice_info['fplx'] == '026')?'增值税电子发票':'区块链发票';
          $invoice_info['zsfs'] = ($invoice_info['zsfs'] == 2)?'差额征税':'普通征税';
          $invoice_info['kplx'] = ($invoice_info['kplx'])?'红字发票':'蓝字发票';
          $result['invoice_info'] = $invoice_info;
          $order_info['invoice_id'] = '是';
        }else{
           $result['invoice_info'] = ''; 
        }
        $WeixinOrderTradeModel = new WeixinOrderTradeModel;
        $rent_orders = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
        $rent_order_ids = array_keys($rent_orders);
        $houses = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->where([['a.rent_order_id','in',$rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
        foreach ($houses as $k => &$v) {
            $v['rent_order_date'] = substr($v['rent_order_date'], 0,4).'-'.substr($v['rent_order_date'], 4,2);
            $v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
        }
        $result['houses'] = $houses;
        
        $result['order_info'] = $order_info;

        $result['code'] = 1;
        
        $result['msg'] = '获取成功！';
      
        return json($result); 
    }

     /**
     * 功能描述： 获取某个订单的历史详情
     * @author  Lucas 
     * 创建时间: 2020-02-28 10:13:33
     */
    public function order_history() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/order_history';
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
            $orderInfo = WeixinOrderModel::with('weixin_member')->where([['out_trade_no','eq',$v['out_trade_no']]])->find();
           
            if($orderInfo['order_status'] == 3){ //如果是预支付状态则跳过当前记录
                continue;
            }
            if($orderInfo['order_status'] == 2){ //如果是退款的，则先显示支付的，再显示退款的
                $refundInfo = WeixinOrderRefundModel::where([['order_id','eq',$orderInfo['order_id']]])->find();
                $data[] = [
                    'time' => $refundInfo['ctime'],
                    'member_name' => $orderInfo['member_name'],
                    'msg' => '已退款（'.$orderInfo['member_name'].'）',
                    //'msg' => $params['order_status'][$orderInfo['order_status']],
                ];
                $data[] = [
                    'time' => $refundInfo['ptime'],
                    'member_name' => $orderInfo['member_name'],
                    'msg' => '支付成功（'.$orderInfo['member_name'].'）',
                    //'msg' => $params['order_status'][1],
                ];
            }
            if($orderInfo['order_status'] == 1){ //如果是支付成功
                $data[] = [
                    'time' => $orderInfo['ptime'],
                    'member_name' => $orderInfo['member_name'],
                    'msg' => '支付成功（'.$orderInfo['member_name'].'）',
                    //'msg' => $params['order_status'][$orderInfo['order_status']],
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

    public function sendSubscribeTemplate()
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

        $template_id = input('template_id','2kL0FTh48uEpTgBcLAwp2siR7eTrKOgNiHZSdXA_r_k'); //接收template_id
        $order_id = input('order_id',39); //接收openid

        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();

        $WeixinOrderModel = new WeixinOrderModel;
        $order_info = $WeixinOrderModel->where([['order_id','eq',$order_id]])->find();
        //$openid = input('openid','oxgVt5RZHUzam9oAHlJRGRlpDwFY'); //接收openid
        
        // $action = input('action'); //接收action
        // $scene = input('scene'); //接收scene
       //halt($openid);
        $data = [
            'touser' => $openid, //要发送给用户的openId
            //改成自己的模板id，在微信接口权限里一次性订阅消息的查看模板id
            'template_id' => $template_id,
            //'url' => "自己网站链接url ", //自己网站链接url 
            //'scene'=>"$scene",
            //'title'=>"title", //标题
            //下面的data格式必须与小程序后台设置的模板详情参数一致！
            // 'data'=>array(
            //     'character_string1'=>array(
            //         'value'=>"202002200000",
            //     ),
            //     'amount2'=>array(
            //         'value'=>"￥".$order_info['pay_money'],
            //     ),
            //     'date3'=>array(
            //         'value'=>"2020-02-21",
            //     ),
            //     'phrase6'=>array(
            //         'value'=>"微信",
            //     ),
            //     'phrase9'=>array(
            //         'value'=>"支付成功",
            //     ),
            // )
            'data'=>array(
                'character_string1'=>array(
                    'value'=>$order_info['out_trade_no'],
                ),
                'amount4'=>array(
                    'value'=>"￥".$order_info['pay_money'],
                ),
                'phrase2'=>array(
                    'value'=>"支付成功",
                ),
            )
        ];
        //halt($data);
        $WeixinModel = new WeixinModel;
        $res = $WeixinModel->sendSubscribeTemplate($data);
        $result = [];
        if(is_array($res)){
            if($res['errcode'] == 0){
                $result['code'] = 1;
                $result['msg'] = '发送成功！';
            }else{
                $result['code'] = 0;
                $result['msg'] = '发送失败！';
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = $res;
        }
        
        //halt($res);
        return json($result);  
    }

    /**
     * 管理员巡检打卡
     * @param id 消息id
     * @return json
     */
    public function sign_add()
    {
        $data = [
            'member_id'=>1,
            'sign_content'=>'这里是签到的内容哦',
            'sign_imgs'=> '12,13',
            'sign_gpsx'=>'114.334228',
            'sign_gpsy'=>'30.560372'
        ];
        $data['change_imgs'] = trim(implode(',',$data['file']),',');
        
        $res = WeixinSignRecord::create($data);
        halt($res);
    }

    /**
     * 获取某个房屋的租金订单信息
     * @param id 消息id
     * @return json
     */
    public function rent_order_info() 
    {
        $type = input('type','');
        if($type != 'share'){
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
            $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        }
        
        
        
        $houseID = trim(input('house_id')); //获取房屋id
        // $houseID = 22;
        // $scence = trim(input('scence'));
        if(!$houseID){
            $result['code'] = 10018;
            $result['msg'] = '房屋编号不能为空';
            $result['en_msg'] = 'House ID is empty';
            return json($result);
        }
 
        $result['data']['rent'] = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')],['house_id','eq',$houseID]])->order('rent_order_id desc')->select();
        foreach ($result['data']['rent'] as $key => &$value) {
            $value['id'] = $key + 1;
            // $value['rent_order_date'] = $scence;
        }

        
        $houseRow = HouseModel::with('ban,tenant')->where([['house_id','eq',$houseID]])->field('house_balance,ban_id,house_use_id,house_id,house_number,tenant_id,house_pre_rent,house_unit_id,house_share_img,house_floor_id')->find();

        $domain = get_domain();

        $findFile = str_replace($this->domain,$_SERVER['DOCUMENT_ROOT'],$houseRow['house_share_img']);
        if($houseRow['house_share_img'] && is_file($findFile)){

        }else{
            $width = 300;
            $WeixinModel = new WeixinModel;
            $path = 'pages/payment/payment'; //注意路径格式，这个路径不能带参数！
            $filename = '/upload/wechat/qrcode/share_'.$houseRow['house_id'].'_'.$houseRow['house_number'].'.png';
            $createMiniSceneData = $WeixinModel->createMiniScene($houseRow['house_id'] , $path,$width); //B方案生成二维码，
            file_put_contents('.'.$filename,$createMiniSceneData);
            $houseModel = new HouseModel;
            $res = $houseModel->where([['house_id','eq',$houseID]])->update(['house_share_img'=>$this->domain.$filename]);
            $houseRow['house_share_img'] = $this->domain.$filename;


        }
        // 判断是否允许支付，只开放部分管段（目前仅紫阳01、02），和部分使用性质（目前仅住宅）
        if (in_array($houseRow['ban_inst_id'], [7,12,19,25]) && $houseRow['house_use_id'] == 1) {
            $houseRow['is_allow_to_pay'] = 1;
        }else{
            $houseRow['is_allow_to_pay'] = 0;
        }
        //halt($houseRow);
        //$houseRow['is_allow_to_pay'] = 0;
        $result['data']['house'] = $houseRow;
        
        // 判断是否认证过当前房屋
        // $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        // $is_auth_houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['is_auth','eq',1],['dtime','eq',0]])->column('house_id');
        // $result['data']['house']['is_auth'] = 0;
        // if($is_auth_houses && in_array($result['data']['house']['house_id'], $is_auth_houses)){
        //     $result['data']['house']['is_auth'] = 1;
        // }

        //$result['data']['house']['house_share_img'] = 'https://procheck.ctnmit.com/static/wechat/image/share/20200616180247.jpg';
         // 统计当前租户的欠租情况
        $RentModel = new RentModel;
        $rentOrderInfo = $RentModel->where([['house_id','eq',$houseID]])->field('sum(rent_order_receive - rent_order_paid) total_rent_order_unpaid')->find();
        $result['data']['house']['rent_order_unpaids'] = $rentOrderInfo['total_rent_order_unpaid']?$rentOrderInfo['total_rent_order_unpaid']:'0.00';

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
            // 将认证的房屋加到member_house表中(不去除掉暂停计租和注销的房子,['house_is_pause','eq',0],['house_status','eq',1])
            
            $houses = HouseModel::where([['tenant_id','eq',$tenant_info['tenant_id']]])->column('house_id');
            $houseSaveData = [];
            foreach ($houses as $h) {
                $WeixinMemberHouseModel = new WeixinMemberHouseModel;
                $row = $WeixinMemberHouseModel->where([['house_id','eq',$h],['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->find();
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
        if(empty(cache('weixin_openid_'.$token))){
            return ['error_code'=>10010,'error_msg'=>'令牌失效'];
        }

        $openid = cache('weixin_openid_'.$token); //存储openid
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
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
        //halt($user_info);
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

    /**
     * 功能描述： 验证用户token
     * @author   Lucas 
     * 创建时间:  2020-02-26 16:47:53
     */
    protected function check_token()
    {
        $token = input('token');
        $openid = cache('weixin_openid_'.$token);
        if(!$openid){
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
            $result['msg'] = '手机号未在系统中绑定租户';
            $result['en_msg'] = 'Phone number is not bound to tenant in the system';
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

    





}