<?php

namespace app\rent\admin;

use think\Controller;
include EXTEND_PATH.'wxpay/phpqrcode.php';
include EXTEND_PATH.'wxpay/WxPayNativePay.php';
include EXTEND_PATH.'wxpay/log.php';
include EXTEND_PATH.'wxpay/notify.php';

class Weixin extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function native()
    {
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
        $input->SetBody("公房管理系统");
        $input->SetAttach("two");
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee("1"); //以1分钱为单位
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600)); //设置二维码过期时间10分钟l
        $input->SetGoods_tag("goods_tag"); //设置商品标识
        $input->SetNotify_url("web.phv2.com/admin.php/rent/Notify/orderQuery"); //设置回调地址
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

    public function nativeapi()
    {

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
        $input->SetBody("公房管理系统");
        $input->SetAttach("two");
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee("1"); //以1分钱为单位
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600)); //设置二维码过期时间10分钟
        $input->SetGoods_tag("goods_tag"); //设置商品标识
        $input->SetNotify_url("https://ph.ctnmit.com/wxpay/Notify/orderQuery"); //设置回调地址
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id("123456789");

        $result = $notify->GetPayUrl($input);



        if (isset($result["code_url"])) {
            jsons('2000','获取成功',['url'=>$result["code_url"],'out_trade_no'=>$out_trade_no]);
        }else{
            jsons('4000','获取失败');
        }

    }

    public function notify()
    {
        //Loader::import('wxpay.notify', EXTEND_PATH);
        $wxPayConfig = new \PayNotifyCallBack();
    }

    public function qrcode()
    {
        error_reporting(E_ERROR);
        //Loader::import('wxpay.phpqrcode', EXTEND_PATH);
        //require_once 'phpqrcode/phpqrcode.php';
        $url = urldecode($_GET["data"]);
        $QRcode = new \QRcode();
        $QRcode::png($url);
    }

}