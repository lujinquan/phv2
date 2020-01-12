<?php 



/**
 * @desc 封装curl的调用接口，get的请求方式
 */
function doCurlGetRequest($url, $data = array(), $timeout = 5) {
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
    // curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);
    // curl_setopt($con, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    $re = curl_exec($con);
    return $re;
    //curl_close($con);
    //var_dump(curl_error($con));
}

// function doCurlGetRequest($url,$data = '',$timeout = 10){
//  if($url == "" || $timeout <= 0){
//  return false;
//  }
//  //$url = $url.'?'.http_bulid_query($data);
//  $con = curl_init((string)$url);
//  curl_setopt($con, CURLOPT_HEADER, false);
//  curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
//  curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
//  //return curl_exec($con);
//  var_dump(curl_exec($con));
// }
//$url = 'https://procheck.ctnmit.com/admin.php/rent/api/createMonthRentOrders';
$url = 'web.phv2.com/admin.php/rent/api/createMonthRentOrders';
$res = doCurlGetRequest($url);

// 写入日志
$f = __DIR__.'/cron_log.txt';
$fp = fopen($f,'a');
fwrite($fp,'系统于'date('Y-m-d H:i:s',time())."，自动执行生成月租金订单任务，成功！\r\n");
fclose($fp);

// echo '<pre>';
// var_dump($res);
//curl_close($con);