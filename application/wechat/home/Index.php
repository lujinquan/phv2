<?php

namespace app\wechat\home;

use think\Db;
use app\common\controller\Common;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\rent\model\Invoice as InvoiceModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\Recharge as RechargeModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinToken as WeixinTokenModel;
use app\wechat\model\WeixinConfig as WeixinConfigModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinTemplate as WeixinTemplateModel;
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
    private $config_ziyang;
    private $config_liangdao;
    protected $debug = false;
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $configDatas = WeixinConfigModel::column('name,value');
        //halt($configDatas);
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
            'ssl_key'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'ziyang'. DIRECTORY_SEPARATOR . 'apiclient_key.pem',
            'ssl_cer'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'ziyang'. DIRECTORY_SEPARATOR. 'apiclient_cert.pem',
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
        $curr_domin = input('server.http_host');
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
        $input->SetNotify_url("https://".$curr_domin."/wechat/index/orderquery"); //设置回调地址
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
                $result['msg'] = '令牌失效';
                $result['en_msg'] = 'Invalid token';
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
            $result['msg'] = '订单编号不能为空';
            $result['en_msg'] = 'Order ID is empty';
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

            $ban_row = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','inner')->where([['a.house_id','eq',$rent_order_info['house_id']]])->field('b.ban_inst_pid')->find();
            // 检查订单是否存在
            if(!$rent_order_info){
                $result['code'] = 10031;
                $result['msg'] = '订单编号错误';
                $result['en_msg'] = 'Order ID is error';
                return json($result);
            }
            // 检查订单是否已经完成支付
            if($rent_order_info['ptime']){
                $result['code'] = 10032;
                $result['msg'] = '订单已支付，请勿重复支付';
                $result['en_msg'] = 'Order has been paid, please do not pay repeatedly';
                return json($result);
            }
            // 检查订单绑定的房屋是否以被当前会员绑定
            // if(!in_array($rent_order_info['house_id'],$member_houses)){
            //     $result['code'] = 10033;
            //     $result['msg'] = '当前房屋未绑定';
            //     $result['en_msg'] = 'The house is not bound by the current member';
            //     return json($result);
            // }
            $pay_money += $rent_order_info['rent_order_receive']*100;
        }

        $inst_pid = $ban_row['ban_inst_pid'];

        // 调起支付
        include EXTEND_PATH.'wechat/include.php';
        if($inst_pid == 2){
            $wechat = \WeChat\Pay::instance($this->config_ziyang);
        }else if($inst_pid == 3){
            $wechat = \WeChat\Pay::instance($this->config_liangdao);
        }else{
            $result['code'] = 10038;
            $result['msg'] = '房屋所属机构异常';
            return json($result); 
        }

        //商户订单号的规则，年月日时分秒+6数字随机码
        $out_trade_no = date('YmdHis') . random(6);

        //$out_trade_no = $rent_order_info['rent_order_number'];
        $attach = md5($out_trade_no);

        $curr_domin = input('server.http_host');
        // 下面的参数注意要换成动态的
        $options = [
            'body'             => '租金账单',
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $pay_money,
            'openid'           => $openid, //用世念的openid
            'trade_type'       => 'JSAPI',
            'receipt'          => 'Y', //传入Y时，支付成功消息和支付详情页将出现开票入口。需要在微信支付商户平台或微信公众平台开通电子发票功能，传此字段才可生效
            //'notify_url'       => 'https://'.$curr_domin.'/wechat/index/payordernotify',
            'attach'           => $attach,
            'spbill_create_ip' => '127.0.0.1',
        ];
        if($inst_pid == 2){
            //回调函数不能带参数
            $options['notify_url'] = 'https://'.$curr_domin.'/wechat/index/payOrderNotifyZiyang';
        }else if($inst_pid == 3){
            $options['notify_url'] = 'https://'.$curr_domin.'/wechat/index/payOrderNotifyLiangdao';
            //$wechat = \WeChat\Pay::instance($this->config_liangdao);
        }
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

        $options['order_id'] = $WeixinOrderModel->order_id; //需要支付的支付订单号
        $WeixinTemplateModel = new WeixinTemplateModel;
        $template_info = $WeixinTemplateModel->where([['name','eq','app_user_payment_remind']])->find();
        $options['template_id'] = $template_info['value']; // 模板id
        $result['code'] = 1;
        $result['msg'] = '获取成功';
        $result['data'] = $options;
        return json($result);

    }

    /**
     * 生成自定义path的微信二维码，用户可以扫描二维码跳转到对应的页面
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
    public function createqrcode()
    {

    }

    public function recharge()
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
            $openid = cache('weixin_openid_'.$token); //存储openid
        }else{
            $openid = 'oRqsn49gtDoiVPFcZ6luFjGwqT1g';
        }
        // 检查房屋id是否为空
        $house_id = trim(input('house_id'));
        if(!$house_id){
            $result['code'] = 10071;
            $result['msg'] = '房屋编号不能为空';
            $result['en_msg'] = 'House ID is empty';
            return json($result);
        }
        $house_info = HouseModel::where([['house_id','eq',$house_id]])->find();
        if(!$house_info){
            $result['code'] = 10072;
            $result['msg'] = '房屋编号错误';
            $result['en_msg'] = 'House ID is error';
            return json($result);
        }
        if($house_info['house_status'] != 1){
            $result['code'] = 10073;
            $result['msg'] = '房屋已注销或未发租';
            $result['en_msg'] = 'Housing status error';
            return json($result);
        }
        // 检查支付金额是否为空
        $money = input('money');
        if(!$money){
            $result['code'] = 10074;
            $result['msg'] = '充值金额错误';
            $result['en_msg'] = 'Money is empty or zero';
            return json($result);
        }
        // 检查是否允许充值（会员与房屋是否绑定，房屋是否未注销）
        
        $WeixinMemberModel = new WeixinMemberModel;
        $member_info = $WeixinMemberModel->where([['openid','eq',$openid]])->find();
        $member_houses = WeixinMemberHouseModel::where([['member_id','eq',$member_info->member_id]])->column('house_id');
        if(!in_array($house_id, $member_houses)){
            $result['code'] = 10075;
            $result['msg'] = '房屋未被绑定';
            $result['en_msg'] = 'Member not bound to current house';
            return json($result);
        }

        
        // $RentModel = new RentModel;
        // $rentOrderIDS = explode(',',$rent_order_id);
        // //halt($rentOrderIDS);
        // $pay_money = 0;
        // foreach($rentOrderIDS as $rid){
        //     $rent_order_info = $RentModel->find($rid);
        //     // 检查订单是否存在
        //     if(!$rent_order_info){
        //         $result['code'] = 10031;
        //         $result['msg'] = 'Order ID is error';
        //         return json($result);
        //     }
        //     // 检查订单是否已经完成支付
        //     if($rent_order_info['ptime']){
        //         $result['code'] = 10032;
        //         $result['msg'] = 'Order has been paid, please do not pay repeatedly';
        //         return json($result);
        //     }
        //     // 检查订单绑定的房屋是否以被当前会员绑定
        //     if(!in_array($rent_order_info['house_id'],$member_houses)){
        //         $result['code'] = 10033;
        //         $result['msg'] = 'The house is not bound by the current member';
        //         return json($result);
        //     }
        //     $pay_money += $rent_order_info['rent_order_receive']*100;
        // }
        //halt($pay_money);
        

        // if($member_info->tenant_id){
        //     $houses = HouseModel::where([['tenant_id','eq',$member_info->tenant_id]])->column('house_id');
        //     $member_houses = array_merge($member_houses,$houses);
        // }
        
        //halt($member_houses);
        
        
        // 调起支付
        include EXTEND_PATH.'wechat/include.php';

        $ban_row = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id','inner')->where([['a.house_id','eq',$house_id]])->field('b.ban_inst_pid')->find();
        $inst_pid = $ban_row['ban_inst_pid'];
        if($inst_pid == 2){
            $wechat = \WeChat\Pay::instance($this->config_ziyang);
        }else if($inst_pid == 3){
            $wechat = \WeChat\Pay::instance($this->config_liangdao);
        }else{
            $result['code'] = 10038;
            $result['msg'] = '房屋所属机构异常';
            return json($result); 
        }
        //$wechat = \WeChat\Pay::instance($this->config_ziyang);
        //商户订单号的规则，年月日时分秒+6数字随机码
        $out_trade_no = date('YmdHis') . random(6);

        $curr_domin = input('server.http_host');
        //$out_trade_no = $rent_order_info['rent_order_number'];
        $attach = md5($out_trade_no);
        // 下面的参数注意要换成动态的
        $options = [
            'body'             => '房屋余额充值',
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $money * 100,
            'openid'           => $openid, //用世念的openid
            'trade_type'       => 'JSAPI',
            'receipt'          => 'Y', //传入Y时，支付成功消息和支付详情页将出现开票入口。需要在微信支付商户平台或微信公众平台开通电子发票功能，传此字段才可生效
            //'notify_url'       => 'https://'.$curr_domin.'/wechat/index/rechargenotify',
            'attach'           => $attach,
            'spbill_create_ip' => '127.0.0.1',
        ];
        if($inst_pid == 2){
            $options['notify_url'] = 'https://'.$curr_domin.'/wechat/index/rechargenotify?inst_id=2';
        }else if($inst_pid == 3){
            $options['notify_url'] = 'https://'.$curr_domin.'/wechat/index/rechargenotify?inst_id=3';
            //$wechat = \WeChat\Pay::instance($this->config_liangdao);
        }
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
        // $WeixinOrderModel = new WeixinOrderModel;
        // $WeixinOrderModel->perpay_id = $res['prepay_id'];
        // $WeixinOrderModel->attach = $attach;
        // $WeixinOrderModel->out_trade_no = $out_trade_no;
        // $WeixinOrderModel->member_id = $member_info->member_id;
        // //$WeixinOrderModel->rent_order_id = $rent_order_id;
        // $WeixinOrderModel->agent = $_SERVER['HTTP_USER_AGENT'];
        // $WeixinOrderModel->save();


        // 生成后台预充值订单
        $RechargeModel = new RechargeModel;
        $RechargeModel->pay_number = $out_trade_no;
        $RechargeModel->house_id = $house_id;
        $RechargeModel->member_id = $member_info['member_id'];
        $RechargeModel->tenant_id = $member_info['tenant_id'];
        $RechargeModel->pay_rent = $money;
        $RechargeModel->pay_way = 4; //微信支付
        $RechargeModel->prepay_id = $res['prepay_id'];
        $RechargeModel->out_trade_no = $out_trade_no;
        //$RechargeModel->pay_remark = $rent_order_info['rent_order_receive'];
        $RechargeModel->save();
        

        $result['code'] = 1;
        $result['msg'] = '获取成功';
        $result['data'] = $options;
        return json($result);

    }

    /**
     * 功能描述：充值支付结果通知（native或jsapi支付成功后微信根据支付提交的地址回调）
     * @author  Lucas 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7&index=8
     * 创建时间: 2020-03-12 21:53:40
     */
    public function rechargenotify()
    {

        include EXTEND_PATH.'wechat/include.php';
        $inst_id = input('inst_id');
        if($inst_id == 2){
            $wechat = \WeChat\Pay::instance($this->config_ziyang);
        }else if($inst_id == 3){
            $wechat = \WeChat\Pay::instance($this->config_liangdao);
        }
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
            $RechargeModel = new RechargeModel;
            $row = $RechargeModel->where([['out_trade_no','eq',$data['out_trade_no']]])->find();
            // 更新预付订单
            if($row){

                if($row['recharge_status'] == 0){
                    $pay_rent = $data['total_fee'] / 100;

                    // 充值的钱，如果有欠缴的订单，先处理订单，由于功能要改版，所以充值的钱，暂时不去抵扣欠钱
                    /*$RentModel = new RentModel;
                    $rent_info = $RentModel->where([['house_id','eq',$row['house_id']],['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->field('sum(rent_order_receive-rent_order_paid) as rent_order_unpaid')->find();
                    if($rent_info && $pay_rent > $rent_info['rent_order_unpaid']){
                        $pay_rent = bcsub($pay_rent, $rent_info['rent_order_unpaid'],2);

                        $RentModel = new RentModel;
                        $RentModel->where([['house_id','eq',$row['house_id']],['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->update(['rent_order_paid'=>Db::raw('rent_order_receive'),'is_deal'=>1,'pay_way'=>4,'ptime'=>time()]);

                        //缴费后，更新order_child表
                        $rent_info = $RentModel->where([['house_id','eq',$row['house_id']],['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->select();
                        $RentOrderChildModel = new RentOrderChildModel;
                        $RentOrderChildModel->house_id = $row['house_id'];
                        $RentOrderChildModel->tenant_id = $row['tenant_id'];
                        $RentOrderChildModel->rent_order_id = $row['rent_order_id'];
                        $RentOrderChildModel->pay_rent = $pay_rent;
                        $RentOrderChildModel->pay_year = substr($row['rent_order_date'],0,4);
                        $RentOrderChildModel->pay_month = $row['rent_order_date'];
                        $RentOrderChildModel->cdate = date('Ym',$ctime);
                        $RentOrderChildModel->ctime = $ctime;
                        $RentOrderChildModel->save();

                        //$RentOrderChildModel->where([['house_id','eq',$row['house_id']],['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->update(['rent_order_paid'=>Db::raw('rent_order_receive'),'is_deal'=>1,'pay_way'=>4,'ptime'=>time()]);
                    }*/

                    // 更新房屋余额
                    $HouseModel = new HouseModel;
                    $house_info = $HouseModel->where([['house_id','eq',$row['house_id']]])->find();
                    $yue = bcaddMerge([$house_info['house_balance'],$pay_rent]);
                    $house_info->house_balance = $yue;
                    $house_info->save();

                    
                    // 更新预付订单
                    $row->transaction_id = $data['transaction_id'];
                    $row->ptime = strtotime($data['time_end']); //支付时间
                    $row->pay_rent = $pay_rent; //支付金额，单位：分
                    $row->yue = $yue;
                    $row->trade_type = $data['trade_type']; //支付类型，如：JSAPI
                    $row->recharge_status = 1; //充值状态，1充值成功
                    $row->save();
                    
                    
                    // 添加房屋台账【待测】
                    $HouseTaiModel = new HouseTaiModel;
                    $HouseTaiModel->house_id = $house_info['house_id'];
                    $HouseTaiModel->tenant_id = $house_info['tenant_id'];
                    //$HouseTaiModel->cuid = ADMIN_ID;
                    $HouseTaiModel->house_tai_type = 2;
                    $HouseTaiModel->house_tai_remark = '微信充值：'. $pay_rent .'元，剩余余额：'.$yue.'元。';
                    $HouseTaiModel->data_json = [];
                    $HouseTaiModel->change_type = '';
                    $HouseTaiModel->change_id = '';
                    $HouseTaiModel->save();

                    
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
     * 功能描述：紫阳所订单支付完成，结果通知（native或jsapi支付成功后微信根据支付提交的地址回调）
     * @author  Lucas 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7&index=8
     * 创建时间: 2020-03-12 21:53:40
     */
    public function payOrderNotifyZiyang()
    {
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config_ziyang);
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

                $house_id = '';

                $RentModel = new RentModel;
                foreach ($rent_order_ids as $rid) {

                    // 缴纳欠租订单order
                    $rent_order_info = $RentModel->where([['rent_order_id','eq',$rid]])->find();
                    $rent_order_info->rent_order_paid = Db::raw('rent_order_receive'); 
                    //$rent_order_info->ptime = strtotime($data['time_end']);
                    //$rent_order_info->pay_way = 4; 
                    $rent_order_info->is_deal = 1; 
                    $rent_order_info->save();

                    $house_id = $rent_order_info['house_id'];
                    
                    // 缴纳欠租订单order_child
                    $RentOrderChildModel = new RentOrderChildModel;
                    $RentOrderChildModel->house_id = $rent_order_info['house_id'];
                    $RentOrderChildModel->tenant_id = $rent_order_info['tenant_id'];
                    $RentOrderChildModel->rent_order_id = $rent_order_info['rent_order_id'];
                    $RentOrderChildModel->rent_order_number = $rent_order_info['rent_order_number'];
                    $RentOrderChildModel->rent_order_receive = $rent_order_info['rent_order_receive'];
                    $RentOrderChildModel->rent_order_pre_rent = $rent_order_info['rent_order_pre_rent'];
                    $RentOrderChildModel->rent_order_cou_rent = $rent_order_info['rent_order_cou_rent']; 
                    $RentOrderChildModel->rent_order_cut = $rent_order_info['rent_order_cut'];
                    $RentOrderChildModel->rent_order_diff = $rent_order_info['rent_order_diff'];
                    $RentOrderChildModel->rent_order_pump = $rent_order_info['rent_order_pump'];
                    $RentOrderChildModel->rent_order_date = $rent_order_info['rent_order_date'];
                    $RentOrderChildModel->rent_order_paid = $data['total_fee'] / 100;
                    $RentOrderChildModel->pay_way = 4; // 4是微信支付
                    $RentOrderChildModel->save();


                    // 添加房屋台账，记录缴费状况
                    $HouseTaiModel = new HouseTaiModel;
                    $HouseTaiModel->house_id = $rent_order_info['house_id'];
                    $HouseTaiModel->tenant_id = $rent_order_info['tenant_id'];
                    $HouseTaiModel->cuid = 0;
                    $HouseTaiModel->house_tai_type = 2;
                    $HouseTaiModel->house_tai_remark = '微信缴费：'.$rent_order_info['rent_order_receive'].'元';
                    $HouseTaiModel->data_json = [];
                    $HouseTaiModel->change_type = '';
                    $HouseTaiModel->change_id = '';
                    $HouseTaiModel->save();
                }

                // 如果支付的人，并没有绑定当前房屋，则自动绑定当前房屋(非认证状态)
                $member_house_info = WeixinMemberHouseModel::where([['member_id','eq',$row->member_id],['house_id','eq',$house_id],['dtime','eq',0]])->find();
                if(!$member_house_info){
                    WeixinMemberHouseModel::create([
                        'member_id' => $row->member_id,
                        'house_id' => $house_id,
                    ]);
                }
               
                // 开具电子发票
                $InvoiceModel = new InvoiceModel;
                $InvoiceModel->dpkj($row['order_id']);

            // 如果通过out_trae_no无法找到预付订单，则抛出错误
            }else{
                
            }

            // 返回接收成功的回复
            ob_clean();
            echo $wechat->getNotifySuccessReply();
        }
    }

    /**
     * 功能描述：支付结果通知（native或jsapi支付成功后微信根据支付提交的地址回调）
     * @author  Lucas 
     * @link    文档参考地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7&index=8
     * 创建时间: 2020-03-12 21:53:40
     */
    public function payOrderNotifyLiangdao()
    {
        include EXTEND_PATH.'wechat/include.php';
        $wechat = \WeChat\Pay::instance($this->config_liangdao);
        // 获取通知参数
        $data = $wechat->getNotify();
        
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

                $house_id = '';

                $RentModel = new RentModel;
                foreach ($rent_order_ids as $rid) {
                    $rent_order_info = $RentModel->where([['rent_order_id','eq',$rid]])->find();
                    $rent_order_info->rent_order_paid = Db::raw('rent_order_receive'); 
                    // $rent_order_info->ptime = strtotime($data['time_end']);
                    // $rent_order_info->pay_way = 4; //4是微信支付
                    $rent_order_info->is_deal = 1; 
                    $rent_order_info->save();

                    $house_id = $rent_order_info['house_id'];

                    // 缴纳欠租订单order_child
                    $RentOrderChildModel = new RentOrderChildModel;
                    $RentOrderChildModel->house_id = $rent_order_info['house_id'];
                    $RentOrderChildModel->tenant_id = $rent_order_info['tenant_id'];
                    $RentOrderChildModel->rent_order_id = $rent_order_info['rent_order_id'];
                    $RentOrderChildModel->rent_order_number = $rent_order_info['rent_order_number'];
                    $RentOrderChildModel->rent_order_receive = $rent_order_info['rent_order_receive'];
                    $RentOrderChildModel->rent_order_pre_rent = $rent_order_info['rent_order_pre_rent'];
                    $RentOrderChildModel->rent_order_cou_rent = $rent_order_info['rent_order_cou_rent']; 
                    $RentOrderChildModel->rent_order_cut = $rent_order_info['rent_order_cut'];
                    $RentOrderChildModel->rent_order_diff = $rent_order_info['rent_order_diff'];
                    $RentOrderChildModel->rent_order_pump = $rent_order_info['rent_order_pump'];
                    $RentOrderChildModel->rent_order_date = $rent_order_info['rent_order_date'];
                    $RentOrderChildModel->rent_order_paid = $data['total_fee'] / 100;
                    $RentOrderChildModel->pay_way = 4; // 4是微信支付
                    $RentOrderChildModel->save();

                    // 添加房屋台账，记录缴费状况
                    $HouseTaiModel = new HouseTaiModel;
                    $HouseTaiModel->house_id = $rent_order_info['house_id'];
                    $HouseTaiModel->tenant_id = $rent_order_info['tenant_id'];
                    $HouseTaiModel->cuid = 0;
                    $HouseTaiModel->house_tai_type = 2;
                    $HouseTaiModel->house_tai_remark = '微信缴费：'.$rent_order_info['rent_order_receive'].'元';
                    $HouseTaiModel->data_json = [];
                    $HouseTaiModel->change_type = '';
                    $HouseTaiModel->change_id = '';
                    $HouseTaiModel->save();
                }

                // 如果支付的人，并没有绑定当前房屋，则自动绑定当前房屋(非认证状态)
                $member_house_info = WeixinMemberHouseModel::where([['member_id','eq',$row->member_id],['house_id','eq',$house_id],['dtime','eq',0]])->find();
                if(!$member_house_info){
                    WeixinMemberHouseModel::create([
                        'member_id' => $row->member_id,
                        'house_id' => $house_id,
                    ]);
                }

                // 开具电子发票
                // $InvoiceModel = new InvoiceModel;
                // $InvoiceModel->dpkj($row['order_id']);
                
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
                    $result['msg'] = '令牌失效';
                    $result['en_msg'] = 'Invalid token';
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

            $mini = \WeMini\Crypt::instance($this->config_ziyang);

            //print_r($mini->session($code));
            $data = $mini->decode($iv, $sessionKey, $encryptedData);
            if(!$data){
                $result['code'] = 10060;
                $result['msg'] = '微信内部错误';
                $result['en_msg'] = 'Wechat internal error';
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
            $result['msg'] = '请求方式错误';
            $result['en_msg'] = 'Please request by post';
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
        $mini = \WeMini\Template::instance($this->config_ziyang);
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

        $WeixinOrderTradeModel = new WeixinOrderTradeModel;
        $order_trade_info = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->find();

        $ban_row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->where([['rent_order_id','eq',$order_trade_info['rent_order_id']]])->field('c.ban_inst_pid')->find();
        $inst_pid = $ban_row['ban_inst_pid'];
        include EXTEND_PATH.'wechat/include.php';
        if($inst_pid == 2){
            $wechat = \WeChat\Pay::instance($this->config_ziyang);
        }else if($inst_pid == 3){
            $wechat = \WeChat\Pay::instance($this->config_liangdao);
        }else{
            return  $this->error('房屋所属机构异常');
        }

        
        //$wechat = \WeChat\Pay::instance($this->config_ziyang);
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
        $WeixinOrderRefundModel->ptime = $order_info->getData('ptime');
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

            // 缴纳欠租订单order_child
            // $RentOrderChildModel = new RentOrderChildModel;
            // $RentOrderChildModel->house_id = $rent_order_info['house_id'];
            // $RentOrderChildModel->tenant_id = $rent_order_info['tenant_id'];
            // $RentOrderChildModel->rent_order_id = $rent_order_info['rent_order_id'];
            // $RentOrderChildModel->rent_order_number = $rent_order_info['rent_order_number'];
            // $RentOrderChildModel->rent_order_receive = $rent_order_info['rent_order_receive'];
            // $RentOrderChildModel->rent_order_pre_rent = $rent_order_info['rent_order_pre_rent'];
            // $RentOrderChildModel->rent_order_cou_rent = $rent_order_info['rent_order_cou_rent']; 
            // $RentOrderChildModel->rent_order_cut = $rent_order_info['rent_order_cut'];
            // $RentOrderChildModel->rent_order_diff = $rent_order_info['rent_order_diff'];
            // $RentOrderChildModel->rent_order_pump = $rent_order_info['rent_order_pump'];
            // $RentOrderChildModel->rent_order_date = $rent_order_info['rent_order_date'];
            // $RentOrderChildModel->rent_order_paid = $data['total_fee'] / 100;
            // $RentOrderChildModel->pay_way = 4; // 4是微信支付
            // $RentOrderChildModel->save();
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
        $wechat = \WeChat\Pay::instance($this->config_ziyang);
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
        $wechat = \WeChat\Pay::instance($this->config_ziyang);
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
        $wechat = \WeChat\Pay::instance($this->config_ziyang);
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