<?php

namespace app\wechat\home;

use think\Db;
use app\common\controller\Common;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinToken as WeixinTokenModel;
use app\wechat\model\WeixinConfig as WeixinConfigModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;


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
        $configDatas = WeixinConfigModel::column('name,value');
        //halt($configDatas);
        $this->config = [
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
            'mch_id'         => $configDatas['app_user_pay_mchid'], //"1244050802",
            'mch_key'        => $configDatas['app_user_pay_key'], //'XC854SKIDHXJKSID87XUSHJD87XJS9XS',
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
        //halt($_SERVER['HTTP_USER_AGENT']);
        // 生成后台订单
        // $WeixinOrderModel = new WeixinOrderModel;
        // $WeixinOrderModel->perpay_id = 'perpay_id';
        // $WeixinOrderModel->out_trade_no = 'out_trade_no';
        // $WeixinOrderModel->member_id = 'member_id';
        // $WeixinOrderModel->rent_order_id = 1;
        // $WeixinOrderModel->agent = $_SERVER['HTTP_USER_AGENT'];
        // $res = $WeixinOrderModel->save();
        // if($res){
        //     $result['code'] = 1;
        //     $result['msg'] = '添加成功';
        //     return json($result);
        // }
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        if($this->debug === false){ 
            if(!$this->check_token()){
                $result['code'] = 10010;
                $result['msg'] = 'Invalid token';
                return json($result);
            }
            $token = input('token');
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
        $member_houses = WeixinMemberHouseModel::where([['member_id','eq',$member_info->member_id]])->column('house_id');

        $RentModel = new RentModel;
        $rentOrderIDS = explode(',',$rent_order_id);
        //halt($rentOrderIDS);
        $pay_money = 0;
        foreach($rentOrderIDS as $rid){
            $rent_order_info = $RentModel->find($rid);
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
            // 检查订单绑定的房屋是否以被当前会员绑定
            if(!in_array($rent_order_info['house_id'],$member_houses)){
                $result['code'] = 10033;
                $result['msg'] = 'The house is not bound by the current member';
                return json($result);
            }
            $pay_money += $rent_order_info['rent_order_receive']*100;
        }
        //halt($pay_money);
        

        // if($member_info->tenant_id){
        //     $houses = HouseModel::where([['tenant_id','eq',$member_info->tenant_id]])->column('house_id');
        //     $member_houses = array_merge($member_houses,$houses);
        // }
        
        //halt($member_houses);
        
        
        // 调起支付
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        //商户订单号的规则，年月日时分秒+6数字随机码
        $out_trade_no = date('YmdHis') . random(6);

        //$out_trade_no = $rent_order_info['rent_order_number'];
        $attach = md5($out_trade_no);
        // 下面的参数注意要换成动态的
        $options = [
            'body'             => '测试商品',
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $pay_money,
            'openid'           => $openid, //用世念的openid
            'trade_type'       => 'JSAPI',
            'notify_url'       => 'https://procheck.ctnmit.com/wechat/index/payordernotify',
            'attach'           => $attach,
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
        
        // 生成后台订单
        $WeixinOrderModel = new WeixinOrderModel;
        $WeixinOrderModel->perpay_id = $res['prepay_id'];
        $WeixinOrderModel->attach = $attach;
        $WeixinOrderModel->out_trade_no = $out_trade_no;
        $WeixinOrderModel->member_id = $member_info->member_id;
        //$WeixinOrderModel->rent_order_id = $rent_order_id;
        $WeixinOrderModel->agent = $_SERVER['HTTP_USER_AGENT'];
        $WeixinOrderModel->save();

        // 生成后台订单与out_trade_no关联数据
         

        foreach($rentOrderIDS as $reid){
            $rent_order_info = $RentModel->find($reid);
            $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            $WeixinOrderTradeModel->out_trade_no = $out_trade_no;
            $WeixinOrderTradeModel->rent_order_id = $reid;
            $WeixinOrderTradeModel->pay_dan_money = $rent_order_info['rent_order_receive'];
            $WeixinOrderTradeModel->save();
        }

        $result['code'] = 1;
        $result['msg'] = '获取成功';
        $result['data'] = $options;
        return json($result);

    }

    /**
     * 功能描述：支付结果通知（native或jsapi支付成功后微信根据支付提交的地址回调）
     * @author  Lucas 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7&index=8
     * 创建时间: 2020-03-12 21:53:40
     */
    public function payOrderNotify()
    {

        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        // 获取通知参数
        $data = $wechat->getNotify();
        // 下面是一个返回data的例子
        // $data = [
        //     'appid' => 'wx2421b1c4370ec43b',
        //     'attach' => '支付测试',
        //     'bank_type' => 'CFT',
        //     'fee_type' => 'CNY',
        //     'is_subscribe' => 'Y',
        //     'mch_id' => '10000100',
        //     'nonce_str' => '5d2b6c2a8db53831f7eda20af46e531c',
        //     'openid' => 'oUpF8uMEb4qRXf22hE3X68TekukE',
        //     'out_trade_no' => '1409811653',
        //     'result_code' => 'SUCCESS',
        //     'return_code' => 'SUCCESS',
        //     'sign' => 'B552ED6B279343CB493C5DD0D78AB241',
        //     'time_end' => '20140903131540',
        //     'total_fee' => '1',
        //     'coupon_fee' => '10',
        //     'coupon_count' => '1',
        //     'coupon_type' => 'CASH',
        //     'coupon_id' => '10000',
        //     'coupon_fee' => '100',
        //     'trade_type' => 'JSAPI',
        //     'transaction_id' => '1004400740201409030005092168',
        // ];
        if ($data['return_code'] === 'SUCCESS' && $data['result_code'] === 'SUCCESS') {
            // @todo 去更新下原订单的支付状态
            //$order_no = $data['out_trade_no'];

            // 生成后台订单
            $WeixinOrderModel = new WeixinOrderModel;
            $row = $WeixinOrderModel->where([['out_trade_no','eq',$data['out_trade_no']]])->find();
            // 更新预付订单
            if($row){
                // 更新预付订单
                $row->transaction_id = $data['transaction_id'];
                $row->ptime = strtotime($data['time_end']); //支付时间
                $row->pay_money = $data['total_fee'] / 100; //支付金额，单位：分
                $row->trade_type = $data['trade_type']; //支付类型，如：JSAPI
                $row->order_status = 1; //支付状态1，支付完成
                $row->save();
                // 更新租金订单表
                $WeixinOrderTradeModel = new WeixinOrderTradeModel; 
                $rent_order_ids = $WeixinOrderTradeModel->where([['out_trade_no','eq',$data['out_trade_no']]])->column('rent_order_id');


                $RentModel = new RentModel;
                foreach ($rent_order_ids as $rid) {
                    $rent_order_info = $RentModel->where([['rent_order_id','eq',$rid]])->find();
                    $rent_order_info->rent_order_paid = Db::raw('rent_order_receive'); 
                    $rent_order_info->ptime = strtotime($data['time_end']);
                    $rent_order_info->pay_way = 4; //4是微信支付
                    $rent_order_info->is_deal = 1; 
                    $rent_order_info->save();
                }

                
            // 如果通过out_trae_no无法找到预付订单，则抛出错误
            }else{
                
            }

            // 返回接收成功的回复
            ob_clean();
            echo $wechat->getNotifySuccessReply();
        }
    }

    /**
     * 功能描述：微信手机号授权
     * @author  Lucas 
     * 创建时间: 2020-03-19 16:56:06
     */
    public function mini_login(){
        if($this->request->isPost()){
            $requestData = $this->request->post();
            if($this->debug === false){ 
                if(!$this->check_token()){
                    $result['code'] = 10010;
                    $result['msg'] = 'Invalid token';
                    return json($result);
                }
                $token = $requestData['token'];
                $openid = cache('weixin_openid_'.$token); //存储openid
            }else{
                $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
            }
            // $WeixinMemberModel = new WeixinMemberModel;
            // $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
            // 解码数据
            //$iv = 'nzxhaKK93YdWH49nQGc78A==';
            //$code = '013LyiTR0TwjC92QjJRR0mEsTR0LyiT3';
            //$encryptedData = 'ArpF8jFz6/vBoFF1TNb0YdU7LCX5l7hLlZGX4TL8Y6hfHaKScC4F26XI7GjBUbL1xAp57ivXV9xFrV3XJuLyDqKI1UZ1nMa4Ru2wdWZ8ZyjzbU9YaXO+I/+5g4utOq5Ksvbvg8fgzdS20/HqaXMkdIO48xsYqvMlRY1+8pSi4TbrtV8R22wSTGI281QzntSw2oBXaG+oevvwmv1tivtNsuafmUlcBVHKP2BEYAcvI8M2vV8VHKF6XPxiLwv1g07cxhPaLtngEx+DKYg7Z1pdSScHsUfAmN5qyXQKtfB3zpmintEDu/SfLsZxEX2E3O3nUlzYCh/haH+IL6BgZP/gDKml5gUW6j3wz43+V/965JdG6JeLk7Qdo4I+Aly+FaVP1YxSqV9DxqA67CWgUchY7JBrCPgc0CvhtnV3vAVWLyReeiu3g4TX/8ZQk79++6Vf';
            //$sessionKey = 'gOlNjSO0cTWquWOxSuvGSg==';
            $iv = $requestData['iv'];
            $encryptedData = $requestData['encryptedData'];
         
            include EXTEND_PATH.'wechat/include.php';
            $sessionKey = WeixinTokenModel::where([['token','eq',$token]])->value('session_key');

            $mini = \WeMini\Crypt::instance($this->config);

            //print_r($mini->session($code));
            $data = $mini->decode($iv, $sessionKey, $encryptedData);
            if(!$data){
                $result['code'] = 10060;
                $result['msg'] = 'Wechat internal error';
                return json($result);
            }
            $WeixinMemberModel = new WeixinMemberModel;
            $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
            $member_info->weixin_tel = $data['phoneNumber'];
            $member_info->save();
            $result['code'] = 1;
            $result['data'] = $data;
            return json($result);
        }else{
            $result['code'] = 10061;
            $result['msg'] = 'Please request by post';
            return json($result);
        }
        
    }

    /**
     * 功能描述：帐号下已存在的订阅消息模板列表
     * @author  Lucas 
     * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.getTemplateList.html
     * 创建时间:  2020-03-09 14:21:15
     */
    public function mini_template_list(){
        include EXTEND_PATH.'wechat/include.php';
        $mini = \WeMini\Template::instance($this->config);
        $res = $mini->getSubscribeTemplateList();
        $result = [];
        $result['data'] = $res['data'];
        $result['msg'] = '获取成功！';
        $result['code'] = 1;
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

        $id = input('id');
        $ref_description = input('ref_description');
        if(!$ref_description){
            return  $this->error('退款备注不能为空');
        }
        //exit;  
        $WeixinOrderModel = new WeixinOrderModel;
        $order_info = $WeixinOrderModel->with('weixinMember')->find($id);

        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        // 下面的参数注意要换成动态的
        $options = [
            'transaction_id' => $order_info['transaction_id'], //微信订单号 transaction_id
            'out_refund_no'  => $order_info['out_trade_no'], //
            'total_fee'      => $order_info['pay_money'] * 100,
            'refund_fee'     => $order_info['pay_money'] * 100,
        ];

        //halt($options);
        $result = $wechat->createRefund($options);
        if($result['result_code'] == 'FAIL'){
            return  $this->error($result['err_code_des']);
        }
        //halt($result);
        // $result = [
        //     'return_code' => "SUCCESS",
        //     'return_msg' => "OK",
        //     'appid' => "wxaac82b178a3ef1d2",
        //     'mch_id' => "1244050802",
        //     'nonce_str' => "LqJP9iBVC9ESsNgM",
        //     'sign' => "BAAE65616FE0AD3701AC2B199A3585DD0D9CD621E0D076E14797B4B66F91FC35",
        //     'result_code' => "SUCCESS",
        //     'transaction_id' => "4200000511202003139890551158",
        //     'out_trade_no' => "10600316847101202001",
        //     'out_refund_no' => "10600316847101202001",
        //     'refund_id' => "50300603632020031615181917068",
        //     'refund_channel' => [],
        //     'refund_fee' => "2",
        //     'coupon_refund_fee' => "0",
        //     'total_fee' => "2",
        //     'cash_fee' => "2",
        //     'coupon_refund_count' => "0",
        //     'cash_refund_fee' => "2",
        // ];
        $WeixinOrderRefundModel = new WeixinOrderRefundModel;
        $WeixinOrderRefundModel->order_id = $order_info['order_id'];
        $WeixinOrderRefundModel->out_trade_no = $order_info['out_trade_no'];
        $WeixinOrderRefundModel->ref_money = $result['refund_fee'] / 100;
        $WeixinOrderRefundModel->member_id = $order_info['member_id'];
        $WeixinOrderRefundModel->refund_id = $result['refund_id'];
        $WeixinOrderRefundModel->out_refund_no = $result['out_refund_no'];
        $WeixinOrderRefundModel->ref_description = $ref_description;
        $WeixinOrderRefundModel->save();

        // 更新租金订单表,将缴费记录回退
        $WeixinOrderTradeModel = new WeixinOrderTradeModel; 
        $rent_order_ids = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id');

        $RentModel = new RentModel;
        foreach ($rent_order_ids as $rid) {
            $rent_order_info = $RentModel->where([['rent_order_id','eq',$rid]])->find();
            $rent_order_info->rent_order_paid = 0; 
            $rent_order_info->ptime = 0;
            $rent_order_info->pay_way = 0; 
            $rent_order_info->is_deal = 0; 
            $rent_order_info->save();
        }

        $order_info->order_status = 2;
        $order_info->save();

        
        return  $this->success('退款成功，已退还至'.$order_info['member_name'].'，'. ($result['refund_fee']/100) .'元钱！');
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
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_2
     * 创建时间: 2020-03-10 11:50:03
     */
    public function payOrderQuery()
    {
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config);
        $options = [
            'transaction_id' => '1008450740201411110005820873',
        //        'out_trade_no'   => '商户订单号',
        ];
        $result = $wechat->queryOrder($options);

        var_export($result);
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