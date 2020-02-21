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

namespace app\weichat\model;

use think\Db;
use think\Model;


/**
 * 微信小程序接口
 */
class Weixin extends Model 
{
	// 小程序传来的code值
    protected $code = '';
    // 小程序的appid值
    protected $appid = 'wxdbbb0de34151bfc1';
    // 小程序的appSecret值
    protected $appSecret = 'b41aa54fda7fe0e7feec878456c9e012';

	protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取微信用户openid
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-02-20 11:20:40
     * @example  web.phv2.com/admin.php/weichat/weixinapi/getOpenid?code=
     * @link  文档参考地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/login/auth.code2Session.html
     * @return  返回值  Array ['openid'=>'用户唯一标识','session_key'=>'会话密钥','unionid'=> '用户在开放平台的唯一标识符'']
     * @version 版本  1.0
     */
	public function getOpenid($code = '')
	{
		if(!$code){
			$code = input('code'); //小程序传来的code值
		}
	    $wxUrl = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';
	    //把appid，appsecret，code拼接到url里
	    $getUrl = sprintf($wxUrl, $this->appid, $this->appSecret, $code);
	    //请求拼接好的url
	    $result = curl_get($getUrl);
	    $wxResult = json_decode($result, true);
	    if (empty($wxResult)) {
	        return '请求失败，微信内部错误';
	    } else {
	        $loginFail = array_key_exists('errcode', $wxResult);
	        // 如果有错误码，则请求失败
	        if ($loginFail) {//请求失败
	            return '请求失败，错误码：' . $wxResult['errcode'];
	        //请求成功
	        } else {
	        	return $wxResult;
	        }
	    }
	}

	/**
	 * 功能描述：获取小程序全局唯一后台接口调用凭据（access_token）
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-02-20 12:30:10
	 * @example web.phv2.com/admin.php/weichat/weixinapi/getAccessToken
	 * @link    文档参考地址：
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function getAccessToken()
	{
	    $wxUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
	    //把appid，appsecret，code拼接到url里
	    $getUrl = sprintf($wxUrl, $this->appid, $this->appSecret);
	    //请求拼接好的url
	    $result = curl_get($getUrl);
	    $wxResult = json_decode($result, true);
	    if (empty($wxResult)) {
	        return '请求失败，微信内部错误';
	    } else {
	        $loginFail = array_key_exists('errcode', $wxResult);
	        // 如果有错误码，则请求失败
	        if ($loginFail) {//请求失败
	            return '请求失败，错误码：' . $wxResult['errcode'];
	        //请求成功
	        } else {
	        	return $wxResult;
	        }
	    }
	}

	/**
	 * 功能描述：发送订阅消息
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-02-20 18:36:28
	 * @example 
	 * @link    文档参考地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.send.html
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function sendSubscribeTemplate($data = [])
	{
		
	    
		$postData=json_encode($data);//转化成json数组让微信可以接收
	    //请求拼接好的url
	    //获取access_token
		$resultAccessToken = $this->getAccessToken();

		$wxUrl = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=%s';
	    //把appid，appsecret，code拼接到url里
	    $postUrl = sprintf($wxUrl, $resultAccessToken['access_token']);

	    $result = http_request($postUrl , $postData);
	    $wxResult = json_decode($result, true);
	    halt($wxResult);
	    if (empty($wxResult)) {
	        return '请求失败，微信内部错误';
	    } else {
	        $loginFail = array_key_exists('errcode', $wxResult);
	        // 如果有错误码，则请求失败
	        if ($loginFail) {//请求失败
	            return '请求失败，错误码：' . $wxResult['errcode'];
	        //请求成功
	        } else {
	        	return $wxResult;
	        }
	    }
	}





}
