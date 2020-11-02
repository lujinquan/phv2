<?php 

/**
 * @desc 封装curl的调用接口，get的请求方式
 */
function doCurlGetRequest($url, $data = array(), $timeout = 10) {
    if($url == "" || $timeout <= 0){
        return false;
    }
    if($data != array()) {
        $url = $url . '?' . http_build_query($data);
    }
    //Log::write("发送URL[".$url."]");
    $con = curl_init((string)$url);
    curl_setopt($con, CURLOPT_HEADER, false);
    curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
    curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($con, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    // curl_setopt($con, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    $re = curl_exec($con);
    curl_close($con);
    return $re;
    //curl_close($con);
    //var_dump(curl_error($con));
}

$url = 'https://procheck.ctnmit.com/admin.php/rent/api/changeOrderToUnpaid';
//$url = 'web.phv2.com/admin.php/rent/api/createMonthRentOrders';
$res = doCurlGetRequest($url);
//var_dump($res);exit;
if($res){
    $result = json_decode($res,true);
    //var_dump($result);exit;
    $msg = $result['msg'];
}else{
    $msg = '程序执行成功！';
}
// 写入日志
$f = __DIR__.'/cron_log.txt';
$fp = fopen($f,'a');
fwrite($fp,'系统于'.date('Y-m-d H:i:s',time())."，自动执行本月订单转为欠缴状态任务，成功！返回信息：".$msg."。\r\n");
fclose($fp);