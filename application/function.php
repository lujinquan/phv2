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

// 为方便系统升级，二次开发中用到的公共函数请写在此文件，禁止修改common.php文件
// ===== 系统升级时此文件永远不会被覆盖 =====
if (!function_exists('tranTime')) { 

	function tranTime($time) { 

		$rtime = date("m-d H:i",$time); 
		$htime = date("H:i",$time); 

		$time = time() - $time; 

		if ($time < 60) { 
			$str = '刚刚'; 
		} elseif ($time < 60 * 60) { 
			$min = floor($time/60); 
			$str = $min.'分钟前'; 
		} elseif ($time < 60 * 60 * 24) { 
			$h = floor($time/(60*60)); 
			$str = $h.'小时前 '.$htime; 
		} elseif ($time < 60 * 60 * 24 * 3) { 
			$d = floor($time/(60*60*24)); 
			if ($d==1) {
				$str = '昨天 '.$rtime; 
			} else { 
				$str = '前天 '.$rtime; 
			} 
	    } else { 
			$str = $rtime; 
		} 
		
		return $str; 

	}

}

if (!function_exists('client_os')) {
    /**
     * 用户设备类型
     * @return string
     */
    function client_os() {

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if(strpos($agent, 'windows nt')) {
            $platform = 'windows';
        } elseif(strpos($agent, 'macintosh')) {
            $platform = 'mac';
        } elseif(strpos($agent, 'ipod')) {
            $platform = 'ipod';
        } elseif(strpos($agent, 'ipad')) {
            $platform = 'ipad';
        } elseif(strpos($agent, 'iphone')) {
            $platform = 'iphone';
        } elseif (strpos($agent, 'android')) {
            $platform = 'android';
        } elseif(strpos($agent, 'unix')) {
            $platform = 'unix';
        } elseif(strpos($agent, 'linux')) {
            $platform = 'linux';
        } else {
            $platform = 'other';
        }

        return $platform;
    }
}

if (!function_exists('http_request')) {

	function http_request($url,$data = null,$headers=array())
	{
		$curl = curl_init();
		if( count($headers) >= 1 ){
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($curl, CURLOPT_URL, $url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
}

if (!function_exists('curl_get')) {
	
	function curl_get($url, &$httpCode = 0) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	    //不做证书校验,部署在linux环境下请改为true
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	    $file_contents = curl_exec($ch);
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);
	    return $file_contents;
	}

}

if (!function_exists('bubble_sort')) {
	
	function bubble_sort(&$sort,&$a,$type='asc'){//默认为正序排列
		$len = count($a);
		if($type=='desc'){
			//从大到小，倒序排列
			for($i=1;$i<$len;$i++)
			{
				for($j=$len-1;$j>=$i;$j--)
				{
					
					if($a[$j]>$a[$j-1])
					{
						$x=$a[$j];
						$a[$j]=$a[$j-1];
						$a[$j-1]=$x;
						
						$y = $sort[$j];
						$sort[$j] = $sort[$j-1];
						$sort[$j-1] = $y;
					}
				}
			}
		}else{
			//从小到大，正序排列
			for($i=1;$i<$len;$i++)
			{
				for($j=$len-1;$j>=$i;$j--)
				{
					if($a[$j]<$a[$j-1])
					{
						$x=$a[$j];
						$a[$j]=$a[$j-1];
						$a[$j-1]=$x;
						
						$y = $sort[$j];
						$sort[$j] = $sort[$j-1];
						$sort[$j-1] = $y;
					}
				}
			}
		}
		return $sort;
	}
}


if (!function_exists('convertUTF8')) {

	function convertUTF8($str){
	    if (empty($str)) {
	        return $str;
	    }
	    $code = mb_detect_encoding($str);     //$code为当前字符的字符编码

	    if ($code == 'UFT-8') {
	        return $str;
	    } else {
	        return iconv($code, 'utf-8', $str);
	    }
	}

}

if (!function_exists('convertGBK')) {

	function convertGBK($str){
	    if (empty($str)) {
	        return $str;
	    }
	    $code = mb_detect_encoding($str);     //$code为当前字符的字符编码

	    if ($code == 'GBK') {
	        return $str;
	    } else {
	        return iconv($code, 'GBK', $str);
	    }
	}

}

/**
 * 多个数精度相加，保留x位
 * @param  $[str] [<description>]
 */
if (!function_exists('bcaddMerge')) {

	function bcaddMerge($arr,$x = 2){
		if(!is_array($arr) || !is_numeric($x)){
			return '参数错误';
		}
		$count = count($arr);
	    if ($count == 0) {
	        return 0;
	    }elseif($count == 1){
	    	return $arr[0];
	    }else{
	    	for($i=1;$i<$count;$i++){
	    		$arr[$i] = bcadd($arr[$i-1],$arr[$i],$x);
	    	}
	    	return array_pop($arr);
	    }  
	}

}


/**
 * 获取传入日期的上一个月份的日期：例如：传入201702，输出201701
 * @param  $[str] [<description>]
 */
if (!function_exists('getlastMonthDays')) {

	function getlastMonthDays($date){
	    if(strpos($date,'-') === false){    //将时间日期格式转化成2017-xx的格式即可
	        $str = substr($date,0,4).'-'.substr($date,4,2);
	    }
	    $firstday = date('Y-m',strtotime($str));

	    $date =date('Ym',strtotime($firstday)-3600*24*7);
	    return $date;
	}

}

if (!function_exists('array_merge_adds')) {

	function array_merge_adds($arr1,$arr2,$arr3,$arr4,$arr5,$arr6,$arr7,$arr8,$arr9,$arr10,$arr11,$arr12,$arr13,$arr14,$arr15,$arr16){
	    foreach ($arr1 as $k1 => $ar) {
	        foreach ($ar as $k2 => $ar) {
	            $add1 = bcadd($ar , $arr2[$k1][$k2] , 2);
	            $add2 = bcadd($arr3[$k1][$k2] , $arr4[$k1][$k2] , 2);
	            $add3 = bcadd($arr5[$k1][$k2] , $arr6[$k1][$k2] , 2);
	            $add4 = bcadd($arr7[$k1][$k2] , $arr8[$k1][$k2] , 2);
	            $add5 = bcadd($arr9[$k1][$k2] , $arr10[$k1][$k2] , 2);
	            $add6 = bcadd($arr11[$k1][$k2] , $arr12[$k1][$k2] , 2);
	            $add7 = bcadd($arr13[$k1][$k2] , $arr14[$k1][$k2] , 2);
	            $adds8 = bcadd($add1 , $add2 , 2);
	            $adds9 = bcadd($add3 , $add4 , 2);
	            $adds10 = bcadd($add5 , $add6 , 2);
	            $adds11 = bcadd($arr15[$k1][$k2] , $add7 , 2);
	            $adds12 = bcadd($adds8 , $adds9 , 2);
	            $adds13 = bcadd($adds10 , $adds11 , 2);
	            $adds14 = bcadd($adds13 , $arr16[$k1][$k2] , 2);
	            $re[$k1][$k2] = bcadd($adds12 , $adds14 , 2);
	        }
	    }
	    return $re;
	}
}

if (!function_exists('array_merge_addss')) {

	function array_merge_addss($arr1,$arr2,$arr3,$arr4,$arr5,$arr6,$arr7,$arr8,$arr9,$arr10,$arr11,$arr12,$arr13,$arr14,$arr15){
	    foreach ($arr1 as $k1 => $ar) {
	        foreach ($ar as $k2 => $ar) {
	        	$i = 2;
	        	if($k2 > 3 && $k2 < 10){
	        		$i = 3;
	        	}
	            $add1 = bcadd($ar , $arr2[$k1][$k2] , $i);
	            $add2 = bcadd($arr3[$k1][$k2] , $arr4[$k1][$k2] , $i);
	            $add3 = bcadd($arr5[$k1][$k2] , $arr6[$k1][$k2] , $i);
	            $add4 = bcadd($arr7[$k1][$k2] , $arr8[$k1][$k2] , $i);
	            $add5 = bcadd($arr9[$k1][$k2] , $arr10[$k1][$k2] , $i);
	            $add6 = bcadd($arr11[$k1][$k2] , $arr12[$k1][$k2] , $i);
	            $add7 = bcadd($arr13[$k1][$k2] , $arr14[$k1][$k2] , $i);
	            $adds8 = bcadd($add1 , $add2 , $i);
	            $adds9 = bcadd($add3 , $add4 , $i);
	            $adds10 = bcadd($add5 , $add6 , $i);
	            $adds11 = bcadd($arr15[$k1][$k2] , $add7 , $i);
	            $adds12 = bcadd($adds8 , $adds9 , $i);
	            $adds13 = bcadd($adds10 , $adds11 , $i);
	            $re[$k1][$k2] = bcadd($adds12 , $adds13 , $i);
	        }
	    }
	    return $re;
	}
}

if (!function_exists('array_merge_add')) {

	function array_merge_add($arr1,$arr2){
	    foreach ($arr1 as $k1 => $ar) {
	        foreach ($ar as $k2 => $ar) {
	        	$i = 2;
	        	if($k2 > 3 && $k2 < 10){
	        		$i = 3;
	        	}
	            $re[$k1][$k2] = bcadd($ar , $arr2[$k1][$k2] , $i);
	        }
	    }
	    return $re;
	}
}

/**
 * 功能描述：微信小程序用户emoji表情转义
 * @author  Lucas 
 * 创建时间: 2020-02-26 11:53:23
 */
if (!function_exists('emoji_encode')) {
	function emoji_encode($nickname)
	{
	    $strEncode = '';
	    $length = mb_strlen($nickname, 'utf-8');
	    for ($i = 0; $i < $length; $i++) {
	        $_tmpStr = mb_substr($nickname, $i, 1, 'utf-8');
	        if (strlen($_tmpStr) >= 4) {
	            $strEncode .= '[[EMOJI:' . rawurlencode($_tmpStr) . ']]';
	        } else {
	            $strEncode .= $_tmpStr;
	        }
	    }
	    return $strEncode;
	}
}

/**
 * 功能描述：微信小程序用户emoji表情反转义
 * @author  Lucas 
 * 创建时间: 2020-02-26 11:53:23
 */
if (!function_exists('emoji_decode')) {
	function emoji_decode($str)
	{
	    $strDecode = preg_replace_callback('|\[\[EMOJI:(.*?)\]\]|', function ($matches) {
	        return rawurldecode($matches[1]);
	    }, $str);

	    return $strDecode;
	}
}

/**
 * 功能描述：判断是否是微信客户端
 * @author  Lucas 
 * 创建时间: 2020-02-26 11:53:23
 */
if (!function_exists('is_weixin')) {
	function is_weixin(){ 
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
				return true;
		}	
		return false;
	}
}

/**
 * 获取毫秒级别的时间戳
 */
if (!function_exists('get_msec_time')) {
	function get_msec_time(){
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}

/**
 * 毫秒转日期
 */
if (!function_exists('get_msec_to_mescdate')) {
	function get_msec_to_mescdate($msectime){
	    $msectime = $msectime * 0.001;
	    if(strstr($msectime,'.')){
	        sprintf("%01.3f",$msectime);
	        list($usec, $sec) = explode(".",$msectime);
	        $sec = str_pad($sec,3,"0",STR_PAD_RIGHT);
	    }else{
	        $usec = $msectime;
	        $sec = "000";
	    }
	    $date = date("YmdHisx",$usec);
	    return $mescdate = str_replace('x', $sec, $date);
	}
}

/**
 * 计算两个经纬度距离
 */
if (!function_exists('get_distance')) {
    function get_distance($lat1, $lng1, $lat2, $lng2){ 
        $earthRadius = 6367000; //approximate radius of earth in meters 
        $lat1 = ($lat1 * pi() ) / 180; 
        $lng1 = ($lng1 * pi() ) / 180; 
        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180; 
        $calcLongitude = $lng2 - $lng1; 
        $calcLatitude = $lat2 - $lat1; 
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2); 
        $stepTwo = 2 * asin(min(1, sqrt($stepOne))); 
        $calculatedDistance = $earthRadius * $stepTwo; 
        return round($calculatedDistance); 
    }
}

/**
 * 微信对账单数据处理
 * @param $response 对账单数据
 * @return array 返回结果
 */
if (!function_exists('deal_wechat_response')) {
    function deal_wechat_response($response){
      $result  = array();
      $response = str_replace(","," ",$response);
      $response = explode(PHP_EOL, $response);
      
      foreach ($response as $key=>$val){
        if(strpos($val, '`') !== false){
          $data = explode('`', $val);
          array_shift($data); // 删除第一个元素并下标从0开始
          if(count($data) == 24){ // 处理账单数据
            $result['bill'][] = array(
              'pay_time'       => $data[0], // 支付时间
              'APP_ID'        => $data[1], // app_id
              'MCH_ID'        => $data[2], // 商户id
              'IMEI'         => $data[4], // 设备号
              'order_sn_wx'     => $data[5], // 微信订单号
              'order_sn_sh'     => $data[6], // 商户订单号
              'user_tag'       => $data[7], // 用户标识
              'pay_type'       => $data[8], // 交易类型
              'pay_status'      => $data[9], // 交易状态
              'bank'         => $data[10], // 付款银行
              'money_type'      => $data[11], // 货币种类
              'total_amount'     => $data[12], // 总金额
              'coupon_amount'    => $data[13], // 代金券或立减优惠金额
              'refund_number_wx'   => $data[14], // 微信退款单号
              'refund_number_sh'   => $data[15], // 商户退款单号
              'refund_amount'    => $data[16], // 退款金额
              'coupon_refund_amount' => $data[17], // 代金券或立减优惠退款金额
              'refund_type'     => $data[18], // 退款类型
              'refund_status'    => $data[19], // 退款状态
              'goods_name'      => $data[20], // 商品名称
              'service_charge'    => $data[22], // 手续费
              'rate'         => $data[23], // 费率
            );
          }
          if(count($data) == 5){ // 统计数据
            $result['summary'] = array(
              'order_num'    => $data[0],  // 总交易单数
              'turnover'    => $data[1],  // 总交易额
              'refund_turnover' => $data[2],  // 总退款金额
              'coupon_turnover' => $data[3],  // 总代金券或立减优惠退款金额
              'rate_turnover'  => $data[4],  // 手续费总金额
            );
          }
        }
      }
      return $result;
    }
}

/**
 * 下载
 * @param $response 下载
 * @return array 返回结果
 */
if (!function_exists('download_file')) {
    function download_file($filePath, $showName, $isOutput){
    	if (is_file($filePath)) {
            //打开文件
            $file = fopen($filePath,"r");

            if($isOutput){
            	//返回的文件类型
	            Header("Content-type: application/octet-stream");
	            //按照字节大小返回
	            Header("Accept-Ranges: bytes");
	            //返回文件的大小
	            Header("Accept-Length: ".filesize($filePath));
	            //这里设置客户端的弹出对话框显示的文件名
            	Header("Content-Disposition: attachment; filename=".$showName);
            }
            //一次性将数据传输给客户端
            //echo fread($file, filesize($filePath));
            //一次只传输1024个字节的数据给客户端
            //向客户端回送数据
            $buffer=1024;//
            //判断文件是否读完
            while (!feof($file)) {
                //将文件读入内存
                $file_data = fread($file, $buffer);
                if($isOutput){
                	//每次向客户端回送1024个字节的数据
                	echo $file_data;
                }
            }
            return true;
        }else {
            return false;
        }
    }
}

/**
 * 下载
 * @param $response 下载
 * @return array 返回结果
 */
if (!function_exists('add_file_to_zip')) {
    function add_file_to_zip($path, $current, $zip){
    	// 打开文件夹资源
        $handler = opendir($path);
        // 循环读取文件夹内容
        while(($filename = readdir($handler)) !== false) {
            // 过滤掉Linux系统下的.和..文件夹
            if ($filename != '.' && $filename != '..') {
                // 文件指针当前位置指向的如果是文件夹，就递归压缩
                if (is_dir($path.'/'.$filename)) {
                    add_file_to_zip($path.'/'.$filename, $filename, $zip);
                }else {
                    // 为了在压缩文件的同时也将文件夹压缩，可以设置第二个参数为文件夹/文件的形式，文件夹不存在自动创建压缩文件夹
                    $zip->addFile($path.'/'.$filename, $current.'/'.$filename);
                }
            }
        }
        @closedir($handler);
    }
}