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
use app\system\model\SystemUser as UserModel;
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
use app\wechat\model\WeixinLeadMember as WeixinLeadMemberModel;
use app\wechat\model\WeixinReadRecord as WeixinReadRecordModel;
use app\house\model\Ban as BanModel;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeBan as ChangeBanModel;
use app\deal\model\ChangeHouse as ChangeHouseModel;
use app\deal\model\ChangeCancel as ChangeCancelModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;
use app\deal\model\ChangeName as ChangeNameModel;
use app\deal\model\ChangeNew as ChangeNewModel;
use app\deal\model\ChangeOffset as ChangeOffsetModel;
use app\deal\model\ChangePause as ChangePauseModel;
use app\deal\model\ChangeRentAdd as ChangeRentAddModel;
use app\deal\model\ChangeUse as ChangeUseModel;
use app\deal\model\ChangeInst as ChangeInstModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\deal\model\ChangeCutYear as ChangeCutYearModel;

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
class Leader extends Common
{
    protected $debug = true;

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 功能描述：高管版，用户进入小程序 [前端每隔3000秒请求一次]
     * @author  Lucas 
     * 创建时间: 2020-02-26 11:39:34
     */
    public function applogin()
    {
        $code = input('code'); //小程序传来的code值
        $result = [];
        $result['action'] = 'wechat/leader/applogin';
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

            $WeixinLeadMemberModel = new WeixinLeadMemberModel;
            $member_info = $WeixinLeadMemberModel->where([['openid','eq',$resultOpenid['openid']]])->find();
            // if( empty($member_info) )
            // {
            //  $member_info = $WeixinMemberModel->where([['unionid','eq',$resultOpenid['unionid']]])->find();
            // }
            // 如果在系统中能查到微信会员信息
            if(!empty($member_info) )
            {
                $member_id = $member_info['lead_member_id'];
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
                $result['code'] = '10012';
                $result['msg'] = '用户无权限进入小程序';
                return json($result);
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
     * 功能描述：获取主页的数据
     * @author  Lucas 
     * 创建时间: 2020-02-26 15:55:15
     */
    public function index_info()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/leader/index_info';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'xxx';
        }
    
        
        $WeixinLeadMemberModel = new WeixinLeadMemberModel;
        $member_info = $WeixinLeadMemberModel->where([['openid','eq',$openid]])->find();
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
        $noticeWhere[] = ['type','eq',3];

        // if($member_info['tenant_id']){ //如果是认证用户则可以查看所有公告
        //     //$noticeWhere[] = ['is_auth','eq',3];
        // }
        // if(!$member_info['tenant_id'] && $member_info['member_name']){ //如果是登录用户，则可以查看所有人+登录
        //     $noticeWhere[] = ['is_auth','in',[1,2]];
        // }
        // if(!$member_info['tenant_id'] && !$member_info['member_name']){ //如果是未登录的用户，则只能查看所有人
        //     $noticeWhere[] = ['is_auth','eq',1];
        // }
        $result['data']['notice'] = $WeixinNoticeModel->field('id,title,content,ctime')->where($noticeWhere)->order('sort asc')->select()->toArray();
        // 获取业务列表
        // $WeixinColumnModel = new WeixinColumnModel;
        // $columns = $WeixinColumnModel->field('col_id,col_name,col_icon,app_page')->where([['is_show','eq',1],['dtime','eq',0]])->order('is_top desc,sort asc')->select()->toArray();
        // foreach ($columns as $k => &$v) {
        //      $file = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
        //      $v['file'] = 'https://procheck.ctnmit.com'.$file;
        //      if(!in_array($v['col_id'],$member_info['app_cols'])){
        //         unset($columns[$k]);
        //      }
        //      //halt($v);
        // }
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
        
        // $result['data']['column'] = $columns;
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

    public function ban_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/leader/ban_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'xxx';
        }

        $WeixinLeadMemberModel = new WeixinLeadMemberModel;
        $lead_member_info = $WeixinLeadMemberModel->where([['openid','eq',$openid]])->find();

        //halt(get_domain());
        // $key = input('get.key');
        // $result = [];
        // $result['code'] = 0;
        // if(!$key){
        //     $result['msg'] = '参数错误！';
        //     return json($result);
        // }
        // $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['id','eq',$lead_member_info['user_id']]])->field('id,inst_id,nick,mobile')->find();
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

    public function house_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/leader/house_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'xxx';
        }

        $WeixinLeadMemberModel = new WeixinLeadMemberModel;
        $lead_member_info = $WeixinLeadMemberModel->where([['openid','eq',$openid]])->find();

        //halt(get_domain());
        // $key = input('get.key');
        // $result = [];
        // $result['code'] = 0;
        // if(!$key){
        //     $result['msg'] = '参数错误！';
        //     return json($result);
        // }
        // $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['id','eq',$lead_member_info['user_id']]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $use = input('house_use_id');
            $owner = input('ban_owner_id');
            $tenant = input('tenant_name');
            $status = input('house_status');
            $address = input('ban_address');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            
            $where = [];
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$row['inst_id']]];
            
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

    public function tenant_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/leader/house_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'xxx';
        }

        $WeixinLeadMemberModel = new WeixinLeadMemberModel;
        $lead_member_info = $WeixinLeadMemberModel->where([['openid','eq',$openid]])->find();

        $row = UserModel::where([['id','eq',$lead_member_info['user_id']]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $params = ParamModel::getCparams();
            //$result['data']['params'] = $params;
            $status = input('tenant_status');
            $tenant = input('tenant_name');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $where = [];
            $where[] = ['tenant_inst_id','in',config('inst_ids')[$row['inst_id']]];
            if($tenant){
                $where[] = ['a.tenant_name','like','%'.$tenant.'%'];
            }
            if($status !== null){
                $where[] = ['a.tenant_status','eq',$status];
            }else{
                $where[] = ['a.tenant_status','eq',1];   
            }
            $fields = 'a.tenant_id,tenant_inst_id,tenant_inst_pid,tenant_number,tenant_name,tenant_tel,tenant_card,sum(house_balance) as tenant_balance,a.tenant_status';
            $result = [];
            //halt($where);
            $temps = Db::name('tenant')->alias('a')->join('house b','a.tenant_id = b.tenant_id','left')->field($fields)->where($where)->page($page)->group('a.tenant_id')->order('tenant_ctime desc')->limit($limit)->select();
            //halt($temps);
            $result['data'] = [];
            foreach ($temps as $v) {
                // $v['tenant_inst_id'] = $params['insts'][$v['tenant_inst_id']];
                $v['tenant_status'] = $params['status'][$v['tenant_status']];
                // $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                // $v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                // $v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = Db::name('tenant')->alias('a')->join('house b','a.tenant_id = b.tenant_id','left')->where($where)->count('a.tenant_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    public function process_list()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/leader/house_list';
        if($this->debug === false){
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = '令牌失效';
                return json($result);
            }
            $token = input('token');
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'xxx';
        }

        $WeixinLeadMemberModel = new WeixinLeadMemberModel;
        $lead_member_info = $WeixinLeadMemberModel->where([['openid','eq',$openid]])->find();

        $row = UserModel::where([['id','eq',$lead_member_info['user_id']]])->field('id,inst_id,nick,mobile')->find();

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
     * 功能描述： 验证用户token
     * @author   Lucas 
     * 创建时间:  2020-02-26 16:47:53
     */
    protected function check_token()
    {
        $token = input('token');
        $openid = cache('weixin_openid_'.$token);
        $expires_time = cache('weixin_expires_time_'.$token); 
        if(!$openid){
            return false;
        }
        return true;
    }

}