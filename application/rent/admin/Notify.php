<?php
namespace app\rent\admin;

use think\Controller;
include EXTEND_PATH.'wxpay/log.php';
include EXTEND_PATH.'wxpay/notify.php';


class Notify extends Controller
{
    public function index()
    {
        //Loader::import('wxpay.notify', EXTEND_PATH);
        $wxPayConfig = new \PayNotifyCallBack();

        return $this->fetch();
    }

    //订单查询功能
    public function orderQuery()
    {
        //Loader::import('wxpay.lib.WxPayApi', EXTEND_PATH);
        //Loader::import('wxpay.log', EXTEND_PATH);
        $WxPayApi = new \WxPayApi();
        if(isset($_REQUEST["transaction_id"]) && $_REQUEST["transaction_id"] != ""){
            $transaction_id = $_REQUEST["transaction_id"];
            $input = new \WxPayOrderQuery();
            $input->SetTransaction_id($transaction_id);
            //printf_info(WxPayApi::orderQuery($input));
            $result=$WxPayApi::orderQuery($input);
            echo $result['trade_state'];
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
            echo $result['trade_state'];
            exit();
        }
    }

    public function successCurl()
    {
        return $this->fetch();
    }
}