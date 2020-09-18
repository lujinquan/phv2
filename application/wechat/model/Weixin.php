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
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\rent\model\Invoice as InvoiceModel;
use app\rent\model\Recharge as RechargeModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinToken as WeixinTokenModel;
use app\wechat\model\WeixinConfig as WeixinConfigModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\wechat\model\WeixinTemplate as WeixinTemplateModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;


/**
 * 微信小程序基础接口
 */
class Weixin extends Model 
{
	// 小程序传来的code值
    protected $code = '';
    // 小程序的appid值
    //protected $appid = 'wxaac82b178a3ef1d2';
    protected $appid = '';
    // 小程序的appSecret值
    //protected $appSecret = '2035d07676392ac121549f66384b04e4';
    protected $appSecret = '';

	protected function initialize()
    {

        parent::initialize();
        $configDatas = WeixinConfigModel::column('name,value');
    	$this->appid = $configDatas['app_user_appid'];
    	$this->appSecret = $configDatas['app_user_appsecret'];

        $this->config_ziyang = [
            //调用mini_login方法的时候，用下面的配置
            // 'appid'     => 'wx2cb8b9b001e3b37b',
            // 'appsecret' => '7813490da6f1265e4901ffb80afaa36f',
            // 令牌
            //'token'          => 'lucas',
            // 支付AppID
            'appid'          => $configDatas['app_user_appid'], //'wxaac82b178a3ef1d2', //公房管理小程序
            // 公众号AppSecret
            'appsecret'      =>  $configDatas['app_user_appsecret'], //'2035d07676392ac121549f66384b04e4',
            // 公众号消息加解密密钥
            'encodingaeskey' => 'VSFry92ZK486pfvv9lsITw1FpXjkBOGOXjeILzRnyFo',
            // 配置商户支付参数
            'mch_id'         => $configDatas['app_ziyang_user_pay_mchid'], //"1244050802",
            'mch_key'        => $configDatas['app_ziyang_user_pay_key'], //'XC854SKIDHXJKSID87XUSHJD87XJS9XS',
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'        => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'home'. DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'ziyang'. DIRECTORY_SEPARATOR . 'apiclient_key.pem',
            'ssl_cer'        => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'home'. DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'ziyang'. DIRECTORY_SEPARATOR. 'apiclient_cert.pem',
            // 'ssl_key'        => $configDatas['app_ziyang_apiclient_key_pem'],
            // 'ssl_cer'        => $configDatas['app_ziyang_apiclient_cert_pem'],
            // 配置缓存目录，需要拥有写权限
            //'cache_path'     => '',
        ];
        $this->config_liangdao = [
            //调用mini_login方法的时候，用下面的配置
            // 'appid'     => 'wx2cb8b9b001e3b37b',
            // 'appsecret' => '7813490da6f1265e4901ffb80afaa36f',
            // 令牌
            //'token'          => 'lucas',
            // 支付AppID
            'appid'          => $configDatas['app_user_appid'], //'wxaac82b178a3ef1d2', //公房管理小程序
            // 公众号AppSecret
            'appsecret'      =>  $configDatas['app_user_appsecret'], //'2035d07676392ac121549f66384b04e4',
            // 公众号消息加解密密钥
            'encodingaeskey' => 'VSFry92ZK486pfvv9lsITw1FpXjkBOGOXjeILzRnyFo',
            // 配置商户支付参数
            'mch_id'         => $configDatas['app_liangdao_user_pay_mchid'], //"1244050802",
            'mch_key'        => $configDatas['app_liangdao_user_pay_key'], //'XC854SKIDHXJKSID87XUSHJD87XJS9XS',
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'liangdao'. DIRECTORY_SEPARATOR  . 'apiclient_key.pem',
            'ssl_cer'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'liangdao'. DIRECTORY_SEPARATOR . 'apiclient_cert.pem',
            // 'ssl_key'        => $configDatas['app_liangdao_apiclient_key_pem'],
            // 'ssl_cer'        => $configDatas['app_liangdao_apiclient_cert_pem'],
            // 配置缓存目录，需要拥有写权限
            //'cache_path'     => '',
        ];

    }

    /**
     * 获取微信用户openid,unionid
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
	    //echo $result;echo '<br>'; echo 1;exit;
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
	 * @link    文档参考地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/access-token/auth.getAccessToken.html
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function getAccessToken()
	{
		// $access_token = cache(($this->appid).'_access_token');
		// //cache(($this->appid).'_access_token',null);
		// if(!empty($access_token)){
		// 	return ['access_token'=>$access_token,'expires_in'=>7000];
		// }
	    $wxUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
	    //把appid，appsecret，code拼接到url里
	    $getUrl = sprintf($wxUrl, $this->appid, $this->appSecret);
	    //echo 1;exit;
	    //请求拼接好的url
	    $result = curl_get($getUrl);
	    //echo $result;exit;
	    $wxResult = json_decode($result, true);
	    //$wxResult = ['access_token'=>'34_p9IMMq6SSBu5n8iyBlN8BceqC0XwJYqjmk6mG77FS03AuP55o58rUTP2umKNHfF9uzKiAYVhGxX_HntSL2LBnBWZ6GGiewNfOg1Tbh-YJTDhemJNRlSnvCBlrKmhY09VbhKPCSbwrQYnjWOZULIeAIAEVS','expires_in'=>7200];
	    if (empty($wxResult)) {
	        return '请求失败，微信内部错误';
	    } else {
	        $loginFail = array_key_exists('errcode', $wxResult);
	        // 如果有错误码，则请求失败
	        if ($loginFail) {//请求失败
	            return '请求失败，错误码：' . $wxResult['errcode'];
	        //请求成功
	        } else {
	        	Db::name('weixin_access_token')->insert(['appid'=>$this->appid,'access_token'=>$wxResult['access_token'],'ctime'=>time(),'expires_in'=>$wxResult['expires_in'],'remark'=>'微信小程序接口请求的access_token']);
	        	cache(($this->appid).'_access_token',$wxResult['access_token'],7000);
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
	    //halt($wxResult);
	    if (empty($wxResult)) {
	        return '请求失败，微信内部错误';
	    } else {
	        // 如果有错误码，则请求失败
	        if ($wxResult['errcode']) {//请求失败
	            return '请求失败，错误码：' . $wxResult['errcode'];
	        //请求成功
	        } else {
	        	return $wxResult;
	        }
	    }

	}

    /**
     * 生成自定义path的微信二维码，用户可以扫描二维码跳转到对应的页面
     * 选用的二维码生成C方案
     * 二维码方案官方文档说明地址：https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/qr-code.html
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 官方文档地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.createQRCode.html
     * 创建时间: 生成二维码
     * @return  返回值  
     * @version 版本  1.0
     */
	public function createqrcode( $path = '' ,$width = 430)
    {
        include EXTEND_PATH.'wechat/include.php';
        $mini = \WeMini\Qrcode::instance($this->config_ziyang); //暂时取紫阳所的配置，其实只用到了appid和appsecret,所以为所谓。
        header('Content-type:image/jpeg'); //输出的类型
        $result = $mini->createMiniPath($path , $width);
        return $result;
    }

    /**
     * 生成自定义path的微信二维码，用户可以扫描二维码跳转到对应的页面
     * 选用的二维码生成B方案
     * 二维码方案官方文档说明地址：https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/qr-code.html
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 官方文档地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.createQRCode.html
     * 创建时间: 生成二维码
     * @return  返回值  
     * @version 版本  1.0
     */
	public function createMiniScene( $scene, $page, $width = 430, $auto_color = false, $line_color = ["r" => "0", "g" => "0", "b" => "0"], $is_hyaline = true, $outType = null )
    {
        include EXTEND_PATH.'wechat/include.php';
        $mini = \WeMini\Qrcode::instance($this->config_ziyang); //暂时取紫阳所的配置，其实只用到了appid和appsecret,所以为所谓。
        header('Content-type:image/jpeg'); //输出的类型
        $result = $mini->createMiniScene($scene, $page , $width, $auto_color ,$line_color,$is_hyaline,$outType);
        return $result;
    }

	/**
	 * 功能描述：会员分享给后台发送模板
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-02-26 15:21:30
	 * @example 
	 * @link    文档参考地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.send.html
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function sendWxtemplateMsg($template_data,$url,$pagepath,$to_openid,$template_id,$form_id='1')
	{

	}

	/**
     * 功能描述：查询支付订单
     * @author  Lucas 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_2
     * @param  transaction_id 交易单号
     * @param  out_trade_no 订单号
     * @param  type 查询类型 queryOrder（查询订单是否支付） 或者 queryRefund（查询是否退款）
     * 创建时间: 2020-03-10 11:50:03
     */
    public function queryOrder($transaction_id , $out_trade_no ,$type = 'queryOrder')
    {
    	// 初始化数据
    	$options = [];
    	// 查询交易单号
        if ($transaction_id) {
            $order_info = WeixinOrderModel::with('weixinMember')->where([['transaction_id','eq',$transaction_id]])->find();
            $options = ['transaction_id' => $transaction_id, ];
        // 查询订单号
        }elseif ($out_trade_no) {
            $order_info = WeixinOrderModel::with('weixinMember')->where([['out_trade_no','eq',$out_trade_no]])->find();
            $options = ['out_trade_no' => $out_trade_no,];
        }
        
        // 查询属于紫阳或粮道
        $order_trade_info = WeixinOrderTradeModel::where([['out_trade_no','eq',$order_info['out_trade_no']]])->field('rent_order_id')->find();
        $ban_row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->where([['rent_order_id','eq',$order_trade_info['rent_order_id']]])->field('c.ban_inst_pid')->find();
        $inst_pid = $ban_row['ban_inst_pid'];

        // 调用订单查询接口
        include EXTEND_PATH.'wechat/include.php';
        if($inst_pid == 2){
            $wechat = \WeChat\Pay::instance($this->config_ziyang);
        }else if($inst_pid == 3){
            $wechat = \WeChat\Pay::instance($this->config_liangdao);
        }
        $result = $wechat->queryOrder($options);
        // 返回数据示例
		/*array(21) {
		  ["return_code"] => string(7) "SUCCESS"
		  ["return_msg"] => string(2) "OK"
		  ["appid"] => string(18) "wxaac82b178a3ef1d2"
		  ["mch_id"] => string(10) "1600300531"
		  ["nonce_str"] => string(16) "kXHGBCuWKVfSDyzD"
		  ["sign"] => string(64) "19C95C47A2C78F3B3CBDD4531B693A314196778B1B0CBB6A9A7B75C37D9B43D1"
		  ["result_code"] => string(7) "SUCCESS"
		  ["openid"] => string(28) "oRqsn4624ol3tpa1JiBPQuY1toMY"
		  ["is_subscribe"] => string(1) "N"
		  ["trade_type"] => string(5) "JSAPI"
		  ["bank_type"] => string(6) "OTHERS"
		  ["total_fee"] => string(1) "1"
		  ["fee_type"] => string(3) "CNY"
		  ["transaction_id"] => string(28) "4200000773202009173931052445"
		  ["out_trade_no"] => string(20) "20200917091232742529"
		  ["attach"] => string(32) "451ae098bc00f669e70c9d7e97587285"
		  ["time_end"] => string(14) "20200917091309"
		  ["trade_state"] => string(7) "SUCCESS"
		  ["cash_fee"] => string(1) "1"
		  ["trade_state_desc"] => string(12) "支付成功"
		  ["cash_fee_type"] => string(3) "CNY"
		}*/
        
        //halt($result);
        return $result;
    }

    /**
     * 功能描述：查询缴费订单退款接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间:  2020-03-10 16:28:29
     * @example 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_5
     * @return  返回值  
     * @version 版本  1.0
     */
    public function refundQuery($transaction_id , $out_trade_no ,$type = 'queryRefund')
    {
        // 初始化数据
        $options = [];
        // 查询交易单号
        if ($transaction_id) {
            $order_info = WeixinOrderModel::with('weixinMember')->where([['transaction_id','eq',$transaction_id]])->find();
            $options = ['transaction_id' => $transaction_id, ];
        // 查询订单号
        }elseif ($out_trade_no) {
            $order_info = WeixinOrderModel::with('weixinMember')->where([['out_trade_no','eq',$out_trade_no]])->find();
            $options = ['out_trade_no' => $out_trade_no,];
        }

        // 查询属于紫阳或粮道
        $order_trade_info = WeixinOrderTradeModel::where([['out_trade_no','eq',$order_info['out_trade_no']]])->field('rent_order_id')->find();
        $ban_row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->where([['rent_order_id','eq',$order_trade_info['rent_order_id']]])->field('c.ban_inst_pid')->find();
        $inst_pid = $ban_row['ban_inst_pid'];

        // 调用订单查询接口
        include EXTEND_PATH.'wechat/include.php';
        if($inst_pid == 2){
            $wechat = \WeChat\Pay::instance($this->config_ziyang);
        }else if($inst_pid == 3){
            $wechat = \WeChat\Pay::instance($this->config_liangdao);
        }else{
            return $this->error('房屋所属机构异常');
        }
        $result = $wechat->queryRefund($options);
        // 返回数据示例
        /*array(21) {
          ["appid"] => string(18) "wxaac82b178a3ef1d2"
          ["cash_fee"] => string(1) "2"
          ["mch_id"] => string(10) "1600300531"
          ["nonce_str"] => string(16) "Y2131tqmiDodJ5DP"
          ["out_refund_no_0"] => string(20) "20200827112643864884"
          ["out_trade_no"] => string(20) "20200827112643864884"
          ["refund_account_0"] => string(29) "REFUND_SOURCE_UNSETTLED_FUNDS"
          ["refund_channel_0"] => string(8) "ORIGINAL"
          ["refund_count"] => string(1) "1"
          ["refund_fee"] => string(1) "2"
          ["refund_fee_0"] => string(1) "2"
          ["refund_id_0"] => string(29) "50300405742020090702549281988"
          ["refund_recv_accout_0"] => string(21) "支付用户的零钱"
          ["refund_status_0"] => string(7) "SUCCESS"
          ["refund_success_time_0"] => string(19) "2020-09-07 11:25:46"
          ["result_code"] => string(7) "SUCCESS"
          ["return_code"] => string(7) "SUCCESS"
          ["return_msg"] => string(2) "OK"
          ["sign"] => string(64) "22D70B280B3961DDC8ED62F3673EB6769844972073B2C2E8351202C1FC3536EB"
          ["total_fee"] => string(1) "2"
          ["transaction_id"] => string(28) "4200000709202008272832156780"
        }*/
        //halt($result);
        return $result;
    }

}
