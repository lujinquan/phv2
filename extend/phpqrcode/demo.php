<?php

//include "qrlib.php";


function scerweima($url='',){

	require_once 'phpqrcode.php';
	$value = $url;					//二维码内容
	$errorCorrectionLevel = 'L';	//容错级别 
	$matrixPointSize = 5;			//生成图片大小  
	//生成二维码图片
	$QR = QRcode::png($value,false,$errorCorrectionLevel, $matrixPointSize, 2);

}

scerweima('https://www.baidu.com');