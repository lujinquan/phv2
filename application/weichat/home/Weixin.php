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

namespace app\weichat\home;

use think\Db;
use SendMessage\ServerCodeAPI;
use app\common\controller\Common;
use app\system\model\SystemNotice;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\weichat\model\Weixin as WeixinModel;
use app\weichat\model\WeixinToken as WeixinTokenModel;
use app\weichat\model\WeixinMember as WeixinMemberModel;
use app\weichat\model\WeixinMemberHouse as WeixinMemberHouseModel;

class Weixin extends Common
{
	protected $debug = true;

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 功能描述：用户进入小程序
     * @author  Lucas 
     * 创建时间: 2020-02-26 11:39:34
     */
    public function applogin()
	{

	    $code = input('code'); //小程序传来的code值
	    $WeixinModel = new WeixinModel;
	    if($this->debug){
	    	// 下面是一个模拟返回的数组，注释上一行
		    $resultOpenid = [
		    	'openid' => 'debug_openid',
		    	'session_key' => 'debug_session_key',
		    	'unionid' => 'debug_unionid',
		    ];
	    }else{
	    	$resultOpenid = $WeixinModel->getOpenid($code);
	    }
	     
        $result = [];
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
        	
        	$expires_time = time() + $resultAccessToken['expires_in']; //设置过期时间
			$token = md5($resultOpenid['openid'].time()); //设置token
			session('weixin_openid_'.$token, $resultOpenid['openid']); //存储openid
			session('weixin_expires_time_'.$token, $expires_time);  //存储过期时间
			session('weixin_session_key_'.$token, $resultOpenid['session_key']);  //存储session_key
			session('weixin_unionid_'.$token, $resultOpenid['unionid']);  //存储unionid
            $result['code'] = 1;
            $result['data'] = [
            	'openid' => $resultOpenid['openid'],
            	'token' => $token,
            ];
            $result['msg'] = '获取成功！';
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
    	if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
    	}
        // 获取参数对照数据
    	$params = ParamModel::getCparams();
    	$result['data']['params'] = $params;
    	// 获取公告列表
        $systemNotice = new SystemNotice;
        $result['data']['notice'] = $systemNotice->field('id,title,type,content,cuid,reads,create_time')->where([['delete_time','eq',0]])->order('sort asc')->select()->toArray();
        // 获取滚动信息
        $result['data']['message'] = [
            '欢迎使用公房用户版小程序！！！','小程序由智慧公房系统提供数据服务支持，更多功能敬请期待……'
        ];
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
    	// 验证令牌
    	$result = [];
    	$result['code'] = 0;
    	if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
    	}
    	// 获取
    	$id = input('id');
    	$systemNotice = new SystemNotice;
        $result['data'] = $systemNotice->get($id);
        $result['data']['content'] = htmlspecialchars_decode($result['data']['content']);
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
    	if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
    	}
    	$token = input('token');
    	$house_number = input('house_number');
    	$openid = session('weixin_openid_'.$token); //存储openid
        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
		$HouseModel = new HouseModel;
    	$house_info = $HouseModel->where([['house_number','eq',$house_number]])->find();
    	if(!$house_info){
    		$result['msg'] = '房屋编号错误！';
            return json($result);
    	}
    	$WeixinMemberHouseModel = new WeixinMemberHouseModel;
    	$member_house_find = $WeixinMemberHouseModel->where([['house_id','eq',$house_info['house_id']]])->find();
    	if($member_house_find){
    		$result['msg'] = '当前房屋编号已被占用！';
            return json($result);
    	}
    	$WeixinMemberHouseModel->house_id = $house_info['house_id'];
    	$WeixinMemberHouseModel->member_id = $member_info['member_id'];
    	$WeixinMemberHouseModel->save();
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
    	if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
    	}
    	$token = input('token');
    	$openid = session('weixin_openid_'.$token); //存储openid
        // 绑定手机号
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        if($member_info){
        	// 从member_house关联表中查询会员绑定的房屋
        	$WeixinMemberHouseModel = new WeixinMemberHouseModel;
    		$member_houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info->member_id]])->select()->toArray();

    		if($member_houses){
    			$houses = [];
    			foreach ($member_houses as $k => $v) {
    				$HouseModel = new HouseModel;
    				$row = $HouseModel->with(['ban','tenant'])->where([['house_id','eq',$v['house_id']]])->find();
    				$row['is_auth'] = $v['is_auth'];
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
     * 功能描述： 给openid绑定手机号
     * @author  Lucas 
     * 创建时间: 2020-02-26 16:26:08
     */
    public function binding_tel()
    {
    	// 验证令牌
    	$result = [];
    	$result['code'] = 0;
    	if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
    	}
    	$tel = input('tel');
    	$code = input('code');
    	$token = input('token');
    	// 验证手机号
        if(!$tel){
            $result['msg'] = '请输入手机号！';
        }
        // 验证验证码
        if(!$code){
            $result['msg'] = '请输入验证码！';
        }
        $auth = new ServerCodeAPI();    
        $res = $auth->CheckSmsYzm($tel , $code);
        $res = json_decode($res);
        // 验证短信码是否正确
        if($res->code == '200'){
        	$WeixinMemberModel = new WeixinMemberModel;
	        $openid = session('weixin_openid_'.$token); //存储openid
	        // 绑定手机号
	        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
	        $member_info->tel = $tel;
	        $member_info->save();
	        $result['code'] = 1;
        	$result['msg'] = '绑定成功！';
        }else if($res->code == '413'){
            $result['msg'] = '验证失败！';
        } else {
            $result['msg'] = '请重新获取！';
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
        $openid = session('weixin_openid_'.$token);
        $expires_time = session('weixin_expires_time_'.$token);  
        if(!$openid || $expires_time > time()){
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
    	if(!$this->check_token()){
            $result['msg'] = '令牌已失效！';
            return json($result);
    	}
    	// 验证手机号
    	if(!$tel){
    		$result['msg'] = '请输入手机号！';
    		return json($result);
    	}
    	// 发送短信
        $auth = new ServerCodeAPI();
        $res = json_decode($auth->SendSmsCode($tel));
        if($res->code == '416'){
           $result['msg'] = '验证次数过多，请更换登录方式！';
        }else{
           $result['code'] = 1; 
           $result['msg'] = '发送成功！';
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
		$token = input('token');
		$data_json = file_get_contents('php://input');
		
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
			$data = json_decode($data_json, true);
			//halt($data);
		}
		//$user_info = $data['userinfo']; //获取授权的用户信息
		//$share_id = $data['share_id']; //获取授权的分享id
		
		$openid = session('weixin_openid_'.$token);
		$expires_time = session('weixin_expires_time_'.$token);
		$session_key = session('weixin_session_key_'.$token);
		$unionid = session('weixin_unionid_'.$token);
		
		// 清除用户表情符号
		//$user_info['nickName'] = \Lib\Weixin\WeChatEmoji::clear($user_info['nickName']);
		$nickName = trim(emoji_encode($data['nickName']));
		
		$WeixinMemberModel = new WeixinMemberModel;

		$member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
		
		if( !empty($unionid) && empty($member_info) )
		{
			$member_info = $WeixinMemberModel->where([['unionid','eq',$unionid]])->find();
		}
		// 如果在系统中能查到微信会员信息
		if(!empty($member_info) )
		{
			$member_id = $member_info['member_id'];

			// 更新授权用户的信息
			$member_info->member_name = $data['nickName'];
			$member_info->avatar = $data['avatarUrl'];
			$member_info->last_login_time = time();
			$member_info->last_login_ip = get_client_ip();
			$member_info->login_count = Db::raw('login_count+1');
	        $member_info->save();
			
			// 添加微信Token记录
			$WeixinTokenModel = new WeixinTokenModel;
			$WeixinTokenModel->token = $token;
			$WeixinTokenModel->member_id = $member_id;
			$WeixinTokenModel->session_key = $session_key;
			$WeixinTokenModel->expires_in = $expires_time;
			$WeixinTokenModel->save();

		// 如果在系统中查不到微信会员信息
		}else{

			$member_info->openid = $openid;
			$member_info->member_name = $data['nickName'];
			$member_info->avatar = $data['avatarUrl'];
			$member_info->last_login_time = time();
			$member_info->last_login_ip = get_client_ip();
	        $member_info->save();

	        $member_id = $member_info->member_id;
	        
	        // 添加微信Token记录
			$WeixinTokenModel = new WeixinTokenModel;
			$WeixinTokenModel->token = $token;
			$WeixinTokenModel->member_id = $member_id;
			$WeixinTokenModel->session_key = $session_key;
			$WeixinTokenModel->expires_in = $expires_time;
			$WeixinTokenModel->save();

			// if($share_id > 0)
			// {
			// 	$share_member = M('member')->field('we_openid')->where( array('member' => $share_id) )->find();
				
			// 	$member_formid_info = M('member_formid')->where( array('member_id' => $share_id, 'state' => 0) )->find();
			// 	//更新
			// 	if(!empty($member_formid_info))
			// 	{
			// 		$template_data['keyword1'] = array('value' => $data['name'], 'color' => '#030303');
			// 		$template_data['keyword2'] = array('value' => '普通会员', 'color' => '#030303');
			// 		$template_data['keyword3'] = array('value' => date('Y-m-d H:i:s'), 'color' => '#030303');
			// 		$template_data['keyword4'] = array('value' => '恭喜你，获得一位新成员', 'color' => '#030303');
					
			// 		$pay_order_msg_info =  M('config')->where( array('name' => 'wxprog_member_take_in') )->find();
			// 		$template_id = $pay_order_msg_info['value'];
			// 		$url =C('SITE_URL');
			// 		$pagepath = 'pages/dan/me';
			// 		send_wxtemplate_msg($template_data,$url,$pagepath,$share_member['we_openid'],$template_id,$member_formid_info['formid']);
			// 		M('member_formid')->where( array('id' => $member_formid_info['id']) )->save( array('state' => 1) );
			// 	}
				
			// }
		}
		$result = [];
		$result['code'] = 1;
		$result['msg'] = '授权成功！';
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