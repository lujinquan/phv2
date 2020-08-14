<?php

namespace app\rent\model;

use think\Model;
use hisi\Http;

/**
 * 百旺云发票接口类
 * =====================================
 * @author  Lucas 
 * email:   598936602@qq.com 
 * Website  address:  www.mylucas.com.cn
 * =====================================
 * 创建时间: 2020-08-07 17:27:38
 * @return  返回值  
 * @version 版本  1.0
 */
class Invoice extends Model
{
    // 接口地址
    protected $url = "http://124.205.255.18:28500/api";
	// 用户appid
    protected $appid = '92edfcd96405';
    // 用户appsecret
    protected $appsecret = '340d9826992051020add';
    // 对应相应的接口报文
    protected $content;

    protected function initialize()
    {
        parent::initialize();
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
    {
    	$content = "<REQUEST_COMMON_FPCX>\n" .
                "<YHLX>1</YHLX>\n" .
                "<XSFNSRSBH>150000000001000</XSFNSRSBH>\n" .
                "<FPQQLSH>HL15506462255RR7mH36</FPQQLSH>\n" .
                "</REQUEST_COMMON_FPCX>";
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$base64Sign);
    	$queryMap = [];
    	$queryMap['content'] = $base64Sign;
    	$queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
    	$queryMap['serviceid'] = 'S0003';
    	$queryMap['source'] = '1';
    	$queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
    	$result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
    	return $result;
    }

    /**
     * 电子发票开票接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-07 17:14:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function dpkj($content = '')
    {
        if(!$content){
            $content = "<REQUEST_COMMON_FPKJ class=\"REQUEST_COMMON_FPKJ\">\n" .
                "  <FPQQLSH><![CDATA[LUCAS201711280943001]]></FPQQLSH>\n" .
                "  <KPLX><![CDATA[0]]></KPLX>\n" .
                "  <FPLX><![CDATA[026]]></FPLX>\n" .
                "  <ZSFS><![CDATA[0]]></ZSFS>\n" .
                "  <XSF_MC><![CDATA[税控服务器升级版测试用户10]]></XSF_MC>\n" .
                "  <XSF_NSRSBH><![CDATA[150000000001000]]></XSF_NSRSBH>\n" .
                "  <XSF_DZDH><![CDATA[北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996]]></XSF_DZDH>\n" .
                "  <XSF_YHZH><![CDATA[中信银行 1234567890]]></XSF_YHZH>\n" .
                "  <GMF_NSRSBH><![CDATA[91110133745594417B]]></GMF_NSRSBH>\n" .
                "  <GMF_MC><![CDATA[测试]]></GMF_MC>\n" .
                "  <GMF_DZDH><![CDATA[地址 120]]></GMF_DZDH>\n" .
                "  <GMF_YHZH><![CDATA[银行 123456]]></GMF_YHZH>\n" .
                "  <GMF_SJH><![CDATA[]]></GMF_SJH>\n" .
                "  <GMF_DZYX><![CDATA[]]></GMF_DZYX>\n" .
                "  <SKR><![CDATA[收款人]]></SKR>\n" .
                "  <FHR><![CDATA[复核人]]></FHR>\n" .
                "  <KPR><![CDATA[开票人]]></KPR>\n" .
                "  <YFP_DM><![CDATA[]]></YFP_DM>\n" .
                "  <YFP_HM><![CDATA[]]></YFP_HM>\n" .
                "  <JSHJ><![CDATA[12.00]]></JSHJ>\n" .
                "  <HJJE><![CDATA[12]]></HJJE>\n" .
                "  <HJSE><![CDATA[0]]></HJSE>\n" .
                "  <KCE><![CDATA[]]></KCE>\n" .
                "  <BZ><![CDATA[]]></BZ>\n" .
                "  <HYLX><![CDATA[0]]></HYLX>\n" .
                "  <BY4><![CDATA[]]></BY4>\n" .
                "  <TSPZ><![CDATA[00]]></TSPZ>\n" .
                "  <DKBZ><![CDATA[0]]></DKBZ>\n" .
                "  <COMMON_FPKJ_XMXXS class=\"COMMON_FPKJ_XMXX\" size=\"1\">\n" .
                "    <COMMON_FPKJ_XMXX>\n" .
                "      <uuid><![CDATA[]]></uuid>\n" .
                "      <zb_uuid><![CDATA[]]></zb_uuid>\n" .
                "      <FPHXZ><![CDATA[0]]></FPHXZ>\n" .
                "      <SPBM><![CDATA[1100301010000000000]]></SPBM>\n" .
                "      <ZXBM><![CDATA[]]></ZXBM>\n" .
                "      <YHZCBS><![CDATA[0]]></YHZCBS>\n" .
                "      <LSLBS><![CDATA[]]></LSLBS>\n" .
                "      <ZZSTSGL><![CDATA[]]></ZZSTSGL>\n" .
                "      <XMMC><![CDATA[自来水]]></XMMC>\n" .
                "      <GGXH><![CDATA[]]></GGXH>\n" .
                "      <DW><![CDATA[]]></DW>\n" .
                "      <XMSL><![CDATA[2]]></XMSL>\n" .
                "      <XMDJ><![CDATA[6]]></XMDJ>\n" .
                "      <XMJE><![CDATA[12.0]]></XMJE>\n" .
                "      <SL><![CDATA[0]]></SL>\n" .
                "      <SE><![CDATA[0.0]]></SE>\n" .
                "      <BY1><![CDATA[]]></BY1>\n" .
                "      <BY2><![CDATA[]]></BY2>\n" .
                "      <BY3><![CDATA[]]></BY3>\n" .
                "      <BY4><![CDATA[]]></BY4>\n" .
                "      <BY5><![CDATA[]]></BY5>\n" .
                "    </COMMON_FPKJ_XMXX>\n" .
                "  </COMMON_FPKJ_XMXXS>\n" .
                "</REQUEST_COMMON_FPKJ>";
        }
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$base64Sign);
        $queryMap = [];
        $queryMap['content'] = $base64Sign;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0001';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
        $result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
        return $result;
    }

    /**
     * 电子发票PDF生成接口
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
    public function createPdf()
    {
        $content = "PFJFUVVFU1RfQ09NTU9OX0ZQS0ogY2xhc3M9IlJFUVVFU1RfQ09NTU9OX0ZQS0oiPg0KICA8RlBRUUxTSD48IVtDREFUQVsxMjEyMTIxMjEyMTIxMjEyMTNma11dPjwvRlBRUUxTSD4NCiAgPEtQTFg+PCFbQ0RBVEFbMF1dPjwvS1BMWD4NCiAgPEJNQl9CQkg+PCFbQ0RBVEFbXV0+PC9CTUJfQkJIPg0KICA8WlNGUz48IVtDREFUQVswXV0+PC9aU0ZTPg0KICA8WFNGX05TUlNCSD48IVtDREFUQVsxNTAwMDAwMDAwMDEwMDBdXT48L1hTRl9OU1JTQkg+DQogIDxYU0ZfTUM+PCFbQ0RBVEFb56iO5o6n5pyN5Yqh5Zmo5Y2H57qn54mI5rWL6K+V55So5oi3MTBdXT48L1hTRl9NQz4NCiAgPFhTRl9EWkRIPjwhW0NEQVRBW+WMl+S6rOW4gua1t+a3gOWMuuWkjeWFtOi3r+eUsjIz5Y+35Y2B5LqM5bGCMTIwN11dPjwvWFNGX0RaREg+DQogIDxYU0ZfWUhaSD48IVtDREFUQVvotK3kubDmlrnpk7booYzlkI3np7DjgIHpk7booYzotKblj7coMTAwKV1dPjwvWFNGX1lIWkg+DQogIDxHTUZfTlNSU0JIPjwhW0NEQVRBWzkxMTEwMTMzNzQ1NTk0NDE3Ql1dPjwvR01GX05TUlNCSD4NCiAgPEdNRl9NQz48IVtDREFUQVvmtYvor5VdXT48L0dNRl9NQz4NCiAgPEdNRl9EWkRIPjwhW0NEQVRBW+i0reS5sOaWueWcsOWdgOOAgeeUteivnSgxMDApXV0+PC9HTUZfRFpESD4NCiAgPEdNRl9ZSFpIPjwhW0NEQVRBW+i0reS5sOaWuemTtuihjOWQjeensOOAgemTtuihjOi0puWPtygxMDApXV0+PC9HTUZfWUhaSD4NCiAgPEdNRl9TSkg+PCFbQ0RBVEFbXV0+PC9HTUZfU0pIPg0KICA8R01GX0RaWVg+PCFbQ0RBVEFbODk3NTQ2MjQ0QHFxLmNvbV1dPjwvR01GX0RaWVg+DQogIDxEUFBUX1pIPjwhW0NEQVRBW11dPjwvRFBQVF9aSD4NCiAgPFdYX09QRU5JRD48IVtDREFUQVtdXT48L1dYX09QRU5JRD4NCiAgPEtQUj48IVtDREFUQVvov5nlvIDnpajkurpdXT48L0tQUj4NCiAgPFNLUj48IVtDREFUQVtdXT48L1NLUj4NCiAgPEZIUj48IVtDREFUQVtdXT48L0ZIUj4NCiAgPFlGUF9ETT48IVtDREFUQVtdXT48L1lGUF9ETT4NCiAgPFlGUF9ITT48IVtDREFUQVtdXT48L1lGUF9ITT4NCiAgPEpTSEo+PCFbQ0RBVEFbMTAwLjBdXT48L0pTSEo+DQogIDxISkpFPjwhW0NEQVRBWzEwMC4wXV0+PC9ISkpFPg0KICA8SEpTRT48IVtDREFUQVswLjBdXT48L0hKU0U+DQogIDxLQ0U+PCFbQ0RBVEFbXV0+PC9LQ0U+DQogIDxCWj48IVtDREFUQVtdXT48L0JaPg0KICA8SFlMWD48IVtDREFUQVswXV0+PC9IWUxYPg0KICA8QlkxPjwhW0NEQVRBW11dPjwvQlkxPg0KICA8QlkyPjwhW0NEQVRBW11dPjwvQlkyPg0KICA8QlkzPjwhW0NEQVRBW11dPjwvQlkzPg0KICA8Qlk0PjwhW0NEQVRBW11dPjwvQlk0Pg0KICA8Qlk1PjwhW0NEQVRBW11dPjwvQlk1Pg0KICA8Qlk2PjwhW0NEQVRBW11dPjwvQlk2Pg0KICA8Qlk3PjwhW0NEQVRBW11dPjwvQlk3Pg0KICA8Qlk4PjwhW0NEQVRBW11dPjwvQlk4Pg0KICA8Qlk5PjwhW0NEQVRBW11dPjwvQlk5Pg0KICA8QlkxMD48IVtDREFUQVtdXT48L0JZMTA+DQogIDxXWF9PUkRFUl9JRD48IVtDREFUQVtdXT48L1dYX09SREVSX0lEPg0KICA8V1hfQVBQX0lEPjwhW0NEQVRBW11dPjwvV1hfQVBQX0lEPg0KICA8WkZCX1VJRD48IVtDREFUQVtdXT48L1pGQl9VSUQ+DQogIDxUU1BaPjwhW0NEQVRBWzAwXV0+PC9UU1BaPg0KICA8UUpfT1JERVJfSUQ+PCFbQ0RBVEFbXV0+PC9RSl9PUkRFUl9JRD4NCiAgPEpRQkg+PCFbQ0RBVEFbNDk5MDk5OTkyNzAyXV0+PC9KUUJIPg0KICA8RlBfRE0+PCFbQ0RBVEFbMTUwMDAzODg4ODg4XV0+PC9GUF9ETT4NCiAgPEZQX0hNPjwhW0NEQVRBWzk5OTk5OTAyXV0+PC9GUF9ITT4NCiAgPEZQRk0+PCFbQ0RBVEFbMTUwMDAzODg4ODg4OTk5OTk5OTBdXT48L0ZQRk0+DQogIDxLUFJRPjwhW0NEQVRBWzIwMTgwMTE4MDg0MjMwXV0+PC9LUFJRPg0KICA8RlBfTVc+PCFbQ0RBVEFbMDMqPjMvOCs8MTEwMjk1MzA1NDIvNTUrMjM3LTI8PDc1MCotPDIqMDUqNTUtOS0tNSo+MzMwLz4+Njg3MjQ0MDwrMz48MTI4KjgtMjY2MSoxKjgqPC0vMzc5OTw4MzAxMDUwOTE5Pi0qPiozKjErOV1dPjwvRlBfTVc+DQogIDxKWU0+PCFbQ0RBVEFbMDE4NjA3OTUwMDUyNDc2NjIzNDZdXT48L0pZTT4NCiAgPEVXTT48IVtDREFUQVtdXT48L0VXTT4NCiAgPHBkZl91cmw+PC9wZGZfdXJsPg0KICA8Q09NTU9OX0ZQS0pfWE1YWFMgY2xhc3M9IkNPTU1PTl9GUEtKX1hNWFgiIHNpemU9IjEiPg0KICAgIDxDT01NT05fRlBLSl9YTVhYPg0KICAgICAgPEZQSFhaPjwhW0NEQVRBWzBdXT48L0ZQSFhaPg0KICAgICAgPFNQQk0+PCFbQ0RBVEFbMzA0MDgwMjAxMDIwMDAwMDAwMF1dPjwvU1BCTT4NCiAgICAgIDxaWEJNPjwhW0NEQVRBW11dPjwvWlhCTT4NCiAgICAgIDxZSFpDQlM+PCFbQ0RBVEFbMV1dPjwvWUhaQ0JTPg0KICAgICAgPExTTEJTPjwhW0NEQVRBWzFdXT48L0xTTEJTPg0KICAgICAgPFpaU1RTR0w+PCFbQ0RBVEFb5YWN56iOXV0+PC9aWlNUU0dMPg0KICAgICAgPFhNTUM+PCFbQ0RBVEFbKue7j+e6quS7o+eQhuacjeWKoSrnoJTlj5HotLnnlKhdXT48L1hNTUM+DQogICAgICA8R0dYSD48IVtDREFUQVvmrKFdXT48L0dHWEg+DQogICAgICA8RFc+PCFbQ0RBVEFbXV0+PC9EVz4NCiAgICAgIDxYTVNMPjwhW0NEQVRBWzEwMDBdXT48L1hNU0w+DQogICAgICA8WE1ESj48IVtDREFUQVswLjFdXT48L1hNREo+DQogICAgICA8WE1KRT48IVtDREFUQVsxMDAuMF1dPjwvWE1KRT4NCiAgICAgIDxTTD48IVtDREFUQVswXV0+PC9TTD4NCiAgICAgIDxTRT48IVtDREFUQVswLjBdXT48L1NFPg0KICAgICAgPEJZMT48IVtDREFUQVtdXT48L0JZMT4NCiAgICAgIDxCWTI+PCFbQ0RBVEFbXV0+PC9CWTI+DQogICAgICA8QlkzPjwhW0NEQVRBW11dPjwvQlkzPg0KICAgICAgPEJZND48IVtDREFUQVtdXT48L0JZND4NCiAgICAgIDxCWTU+PCFbQ0RBVEFbXV0+PC9CWTU+DQogICAgPC9DT01NT05fRlBLSl9YTVhYPg0KICA8L0NPTU1PTl9GUEtKX1hNWFhTPg0KPC9SRVFVRVNUX0NPTU1PTl9GUEtKPg==";
        // 上述的content不需要base64加密
        //$base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$ase64Sign);
        $queryMap = [];
        $queryMap['content'] = $content;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0002';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
        $result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
        return $result;
    }

    /**
     * 电子发票余票查询接口
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
    public function fpyj()
    {
        // 纳税人识别号（需base64）
        $content = "150000000001000";
        // 上述的content不需要base64加密
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$ase64Sign);
        $queryMap = [];
        $queryMap['content'] = $base64Sign;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0005';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
        $result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
        return $result;
    }


    /**
      * 签名生成算法
      * @param  array  $params API调用的请求参数集合的关联数组，不包含sign参数
      * @param  string $secret 签名的密钥即获取access token时返回的session secret
      * @return string 返回参数签名值
      */
    public function getSignature($params, $secret)
     {
        $str = '?';  // 待签名字符串
        // 先将参数以其参数名的字典序升序进行排序
        ksort($params);
        // 遍历排序后的参数数组中的每一个key/value对
        foreach ($params as $k => $v) {
            // 为key/value对生成一个key=value格式的字符串，并拼接到待签名字符串后面
            $str .= "$k=$v&";
        }
        // 这是一个java调用signUtil工具类的buildResource方法得到的字符串
        // $buildResource = '?appid=92edfcd96405&content=PFJFUVVFU1RfQ09NTU9OX0ZQQ1g.CjxZSExYPjE8L1lITFg.CjxYU0ZOU1JTQkg.MTUwMDAwMDAwMDAxMDAwPC9YU0ZOU1JTQkg.CjxGUFFRTFNIPkhMMTU1MDY0NjIyNTVSUjdtSDM2PC9GUFFRTFNIPgo8L1JFUVVFU1RfQ09NTU9OX0ZQQ1g.&serviceid=S0003&signkey=appid,signkey,timestamp,content,serviceid,source&source=1&timestamp=20200807151720788';
        $buildResource = trim($str,'&');
        // 将字符串加密后生成签名
        return base64_encode(hash_hmac('sha256', $buildResource, $secret, true));
    }

}
