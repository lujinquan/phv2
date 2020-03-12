<?php

namespace app\wechat\home;
use app\common\controller\Common;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 功能描述：H5完整支付
 * =====================================
 * @author  Lucas 
 * email:   598936602@qq.com 
 * Website  address:  www.mylucas.com.cn
 * =====================================
 * 创建时间: 2020-03-10 11:41:31
 * @example 
 * @link    文档参考地址：
 * @return  返回值  
 * @version 版本  1.0
 */
class Index extends Common
{
    private $config;
    protected $debug = false;
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $this->config = [
            //调用mini_login方法的时候，用下面的配置
            // 'appid'     => 'wx2cb8b9b001e3b37b',
            // 'appsecret' => '7813490da6f1265e4901ffb80afaa36f',
            // 令牌
            //'token'          => 'lucas',
            // 支付AppID
            'appid'          => 'wxaac82b178a3ef1d2', //公房管理小程序
            // 公众号AppSecret
            'appsecret'      => '2035d07676392ac121549f66384b04e4',
            // 公众号消息加解密密钥
            'encodingaeskey' => 'VSFry92ZK486pfvv9lsITw1FpXjkBOGOXjeILzRnyFo',
            // 配置商户支付参数
            'mch_id'         => "1244050802",
            'mch_key'        => 'XC854SKIDHXJKSID87XUSHJD87XJS9XS',
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_key.pem',
            'ssl_cer'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem',
            // 配置缓存目录，需要拥有写权限
            //'cache_path'     => '',
        ];
    }
    /**
     * 功能描述：功能主页展示
     * @author  Lucas 
     * 创建时间: 2020-03-10 11:42:04
     */
    public function index()
    {
        //halt($this->config);
        return $this->fetch();
    }

    /**
     * 功能描述：生成待支付的二维码url及回调地址url
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-03-10 11:44:22
     * @example 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
     * @link    微信接口在线调试地址：https://pay.weixin.qq.com/wiki/tools/signverify/
     * @return  返回值  
     * @version 版本  1.0
     */
    public function native()
    {
        include EXTEND_PATH.'wxpay/WxPayNativePay.php';
        include EXTEND_PATH.'wxpay/log.php';
        //Loader::import('wxpay.WxPayNativePay', EXTEND_PATH);
        //Loader::import('wxpay.lib.WxPayApi', EXTEND_PATH);
        //Loader::import('wxpay.log', EXTEND_PATH);
        //require_once "../lib/WxPay.Api.php";
        //require_once "WxPay.NativePay.php";
        //require_once 'log.php';
        //模式一
        /**
         * 流程：
         * 1、组装包含支付信息的url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
         * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
         * 5、支付完成之后，微信服务器会通知支付成功
         * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        $notify = new \NativePay();
        //$notify
        //halt($notify->appid);
        $url1 = $notify->GetPrePayUrl("123456789");
        //模式二
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        $wxPayConfig = new \WxPayConfig();
        $out_trade_no = $wxPayConfig::MCHID.date("YmdHis");
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("公房系统2.0");
        $input->SetAttach("two");
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee("1"); //以1分钱为单位
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600)); //设置二维码过期时间10分钟
        $input->SetGoods_tag("goods_tag"); //设置商品标识
        $input->SetNotify_url("https://procheck.ctnmit.com/wechat/index/orderquery"); //设置回调地址
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id("123456789");
        $result = $notify->GetPayUrl($input);
        //halt($result);
        $url2 = $result["code_url"];
        $this->assign([
            'out_trade_no'=> $out_trade_no,
            'url1' => $url1,
            'url2' => $url2,
        ]);
        return $this->fetch();
    }

    /**
     * 功能描述：用支付组件，生成小程序jsapi调用需要的支付参数
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-03-10 16:08:05
     * @example 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=7_7&index=5
     * @return  返回值  
     * {
            "code": 1,
            "msg": "获取成功",
            "data": {
                "appId": "wxaac82b178a3ef1d2",
                "timeStamp": "1583826944",
                "nonceStr": "a29mifot6j2s38uegh0dhbh9by6801gi",
                "package": "prepay_id=wx10155543979228ce278e42501925432000",
                "signType": "MD5",
                "paySign": "6EEB2AD050814818D01415B45AAB60AA",
                "timestamp": "1583826944"
            }
        }
     * @version 版本  1.0
     */
    public function jsapi()
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = 'Invalid token';
                return json($result);
            }
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
        // 检查订单id是否为空
        $rent_order_id = input('rent_order_id');
        if(!$rent_order_id){
            $result['code'] = 10030;
            $result['msg'] = 'Order ID is empty';
            return json($result);
        }

        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();

        $RentModel = new RentModel;
        $rent_order_info = $RentModel->find($rent_order_id);
        // 检查订单是否存在
        if(!$rent_order_info){
            $result['code'] = 10031;
            $result['msg'] = 'Order ID is error';
            return json($result);
        }
        // 检查订单是否已经完成支付
        if($rent_order_info['ptime']){
            $result['code'] = 10032;
            $result['msg'] = 'Order has been paid, please do not pay repeatedly';
            return json($result);
        }
        $member_houses = WeixinMemberHouseModel::where([['member_id','eq',$member_info->member_id]])->column('house_id');
        if($member_info->tenant_id){
            $houses = HouseModel::where([['tenant_id','eq',$member_info->tenant_id]])->column('house_id');
            $member_houses = array_merge($member_houses,$houses);
        }
        
        //halt($member_houses);
        // 检查订单绑定的房屋是否以被当前会员绑定
        if(!in_array($rent_order_info['house_id'],$member_houses)){
            $result['code'] = 10033;
            $result['msg'] = 'The house is not bound by the current member';
            return json($result);
        }
        
        // 调起支付
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        // 下面的参数注意要换成动态的
        $options = [
            'body'             => '测试商品',
            'out_trade_no'     => time(),
            'total_fee'        => $rent_order_info['rent_order_receive'] * 100,
            'openid'           => $openid, //用世念的openid
            'trade_type'       => 'JSAPI',
            'notify_url'       => 'https://procheck.ctnmit.com/wechat/index/orderquery',
            'spbill_create_ip' => '127.0.0.1',
        ];
        if ($this->debug === true) {
            $result['code'] = 1;
            $result['msg'] = '获取成功，这是测试数据';
            $result['data'] = $options;
            return json($result);
        }
        // 生成预支付码
        $res = $wechat->createOrder($options);
        //halt($result);
        // 创建JSAPI参数签名
        $options = $wechat->createParamsForJsApi($res['prepay_id']);
        //dump($result );halt($options);
        // echo '<pre>';
        // echo "\n--- 创建预支付码 ---\n";
        // var_export($result);
        // echo "\n\n--- JSAPI 及 H5 参数 ---\n";
        // var_export($options);
       
        $result['code'] = 1;
        $result['msg'] = '获取成功';
        $result['data'] = $options;
        return json($result);

    }

    /**
     * 功能描述：申请退款接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间:  2020-03-10 16:28:29
     * @example 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_4
     * @return  返回值  
     * @version 版本  1.0
     */
    public function refundCreate()
    {
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        // 下面的参数注意要换成动态的
        $options = [
            'transaction_id' => '4200000505202003108122210096', //微信订单号 transaction_id
            'out_refund_no'  => '124405080220200310163324', //
            'total_fee'      => '1',
            'refund_fee'     => '1',
        ];
        $result = $wechat->createRefund($options);
        halt($result);
    }

    /**
     * 功能描述：查询退款接口
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
    public function refundQuery()
    {
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        // 下面的参数注意要换成动态的
        $options = [
            'transaction_id' => '4200000505202003108122210096',
            // 'out_trade_no'   => '商户订单号',
            // 'out_refund_no' => '商户退款单号'
            // 'refund_id' => '微信退款单号',
        ];
        $result = $wechat->queryRefund($options);
        halt($result);
    }

    /**
     * 功能描述：下载交易账单（待验证）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间:  2020-03-10 16:28:29
     * @example 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_6
     * @return  返回值  
     * @version 版本  1.0
     */
    public function downloadBill()
    {
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        // 下面的参数注意要换成动态的
        $options = [
            'bill_date' => '20200305',
            'bill_type' => 'ALL',
        ];
        $result = $wechat->billDownload($options);
        var_export($result);
        //halt($result);
    }

    public function notify()
    {
        include EXTEND_PATH.'wxpay/notify.php';
        //Loader::import('wxpay.notify', EXTEND_PATH);
        $wxPayConfig = new \PayNotifyCallBack();
    }

    /**
     * 功能描述：生成待支付的二维码
     * @author  Lucas 
     * 创建时间: 2020-03-10 11:49:36
     */
    public function qrcode()
    {
        include EXTEND_PATH.'wxpay/phpqrcode.php';
        $url = urldecode($_GET["data"]);
        $QRcode = new \QRcode();
        $QRcode::png($url);
    }

    
    /**
     * 功能描述：订单查询功能(支付页面轮询，后台查询并将查询结果告知前台)
     * @author  Lucas 
     * 创建时间: 2020-03-10 11:50:03
     */
    public function orderQuery()
    {
        include EXTEND_PATH.'wxpay/log.php';
        include EXTEND_PATH.'wxpay/lib/WxPayApi.php';
        // Loader::import('wxpay.lib.WxPayApi', EXTEND_PATH);
        // Loader::import('wxpay.log', EXTEND_PATH);
        $WxPayApi = new \WxPayApi();
        if(isset($_REQUEST["transaction_id"]) && $_REQUEST["transaction_id"] != ""){
            $transaction_id = $_REQUEST["transaction_id"];
            $input = new \WxPayOrderQuery();
            $input->SetTransaction_id($transaction_id);
            //printf_info(WxPayApi::orderQuery($input));
            $result=$WxPayApi::orderQuery($input);
            echo json_encode($result);
            //echo $result['trade_state'];
            exit();
        }
        if(isset($_REQUEST["out_trade_no"]) && $_REQUEST["out_trade_no"] != ""){
            $out_trade_no = $_REQUEST["out_trade_no"];
            $input = new \WxPayOrderQuery();
            $input->SetOut_trade_no($out_trade_no);
            //printf_info(WxPayApi::orderQuery($input));
            $result=$WxPayApi::orderQuery($input);
            // if($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS' && $result['trade_state'] == 'SUCCESS'){
            //     halt($result);
            // }
            echo json_encode($result);
            //echo $result['trade_state'];
            exit();
        }
    }

    /**
     * 功能描述：支付成功弹出成功提示
     * @author  Lucas 
     * 创建时间: 2020-03-10 11:51:23
     */
    public function successcurl()
    {
        return $this->fetch();
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

}