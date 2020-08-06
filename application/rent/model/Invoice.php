<?php

namespace app\rent\model;

use think\Model;

class Invoice extends Model
{
	// 用户appid
    protected $appid = '92edfcd96405';
    // 用户appsecret
    protected $appsecret = '340d9826992051020add';
    // 毫秒时间戳
    protected $microtimestamp;
    // 对应相应的接口报文
    protected $content;

    protected function initialize()
    {
        parent::initialize();

        list($msec, $sec) = explode(' ' , microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $this->microtimestamp = $msectime;
    }

    /**
     * 电子发票查询接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-05 10:58:48
     * @return  返回值  
     * @version 版本  1.0
     */
    public function fpcx()
    { //$content , $fpqqlsh

    	$appid = $this->appid;
    	$appsecret = $this->appsecret;
    	$microtimestamp = $this->microtimestamp;

    	$fpqqlsh = 'HL15506462255RR7mH36';

    	$content = '<REQUEST_COMMON_FPCX>
                <YHLX>1</YHLX>
                <XSFNSRSBH>'. $microtimestamp .'</XSFNSRSBH>
                <FPQQLSH>'. $fpqqlsh .'</FPQQLSH>
                </REQUEST_COMMON_FPCX>';

    	$data = [];
    	$data['content'] = $content;
    	$data['appid'] = $appid;
    	$data['timestamp'] = $microtimestamp;
    	$data['serviceid'] = 'S0003';
    	$data['source'] = '1';
    	$data['signkey'] = "appid,signkey,timestamp,content,serviceid,source";

    	
    	$signature = $this->signature($appsecret , $data);
    	//$data['signature'] = $signature;


    	$result2 = http_request('http://124.204.39.242:7280/bwcs/apiService',$data);

    	return $result;
    }

    /**
     * 签名
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-05 11:16:28
     * @return  返回值  
     * @version 版本  1.0
     */
    public function signature($appsecret , $data ,$signMethod = 'md5')
    {
    	$str = '';
    	foreach ($data as $k => $v) {
    		$str .= $k;
    		$str .= '=';
    		$str .= $v;
    		$str .= '&';
    	}
    	$str .= 
    	halt($str);
    	//dump($appsecret);halt($data);
    }



}
