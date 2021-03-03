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
namespace app\system\model;

use Env;
use think\Model;
use app\wechat\model\WeixinConfig as WeixinConfigModel;
include EXTEND_PATH.'tencentcloud/TCloudAutoLoader.php';
// 导入对应产品模块的client
use TencentCloud\Sms\V20190711\SmsClient;
// 导入要请求接口对应的Request类
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Sms\V20190711\Models\SendStatusStatisticsRequest;
use TencentCloud\Common\Credential;
// 导入可选配置类
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;

/**
 * 腾讯短信模型
 * @package app\common\model
 */
class Sms extends Model
{
	// 设置模型名称
    protected $name = 'tencent_sms';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 小程序传来的code值
    protected $SecretId ;
    protected $SecretKey ;
    protected $SmsSdkAppid ;
    protected $Sign ;
    protected $TemplateID ;

    protected $liangdaosuo_tencent_sms_signid = '350938';
    protected $liangdaosuo_tencent_sms_tempid = '879573';
    protected $liangdaosuo_tencent_sms_tempdata = array('测试路xx号','xx小程序');

  	protected $liangdao_tencent_app_id = '1304765952';
  	protected $liangdao_tencent_secret_id = 'AKIDSgfPyOMkbFgpj3cGWUHLM8k9H9k4K0iB';
  	protected $liangdao_tencent_secret_key = 'kKhssgCSCTWHn10NUO1PWP4S2Kbb9QRA';

	protected function initialize()
    {
        parent::initialize();
        $configDatas = WeixinConfigModel::column('name,value');
    	$this->SecretId = $configDatas['tencent_secret_id'];
    	$this->SecretKey = str_coding($configDatas['tencent_secret_key'],'DECODE');
    	$this->SmsSdkAppid = $configDatas['tencent_sms_sdk_appid'];
    	$this->Sign = $configDatas['tencent_sms_sign'];
    	$this->TemplateID = $configDatas['tencent_sms_template_id'];
    	// halt($this->SecretKey);
    }

    /**
     * 根据不同的机构配置，发送短信
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-04-26 16:34:15
     * @return  返回值  
     * @version 版本  1.0
     */
    
    public function sendSmsOfInst($tempid = '', $tempdata = array() , $phone = array() , $insttype = 'ziyang')
    {
    	// 读取配置，紫阳所还是粮道所
    	if($insttype == 'ziyang' || $insttype == 'liangdao'){
		    $configDatas = WeixinConfigModel::column('name,value');
	    	$this->SecretId = $configDatas[$insttype.'_tencent_secret_id'];
	    	$this->SecretKey = str_coding($configDatas[$insttype.'_tencent_secret_key'],'DECODE');
	    	$this->SmsSdkAppid = $configDatas[$insttype.'_tencent_sms_sdk_appid'];
	    	$this->Sign = $configDatas[$insttype.'_tencent_sms_sign'];
	    	// $this->TemplateID = $configDatas[$insttype.'_tencent_sms_template_id'];
	    	dump($this->SecretId);dump($this->SecretKey);dump($this->SmsSdkAppid);halt($this->Sign);
	    	// halt($configDatas);
    	}else{
    		// 没有配置，其他选项
    		return false;
    	}

        $cred = new Credential($this->SecretId, $this->SecretKey);
	    //$cred = new Credential(getenv("TENCENTCLOUD_SECRET_ID"), getenv("TENCENTCLOUD_SECRET_KEY"));

	    // 实例化一个http选项，可选的，没有特殊需求可以跳过
	    $httpProfile = new HttpProfile();
	    $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
	    $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
	    $httpProfile->setEndpoint("sms.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

	    // 实例化一个client选项，可选的，没有特殊需求可以跳过
	    $clientProfile = new ClientProfile();
	    $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
	    $clientProfile->setHttpProfile($httpProfile);

	    // 实例化要请求产品(以sms为例)的client对象,clientProfile是可选的
	    $client = new SmsClient($cred, "ap-shanghai", $clientProfile);

	    // 实例化一个 sms 发送短信请求对象,每个接口都会对应一个request对象。
	    $req = new SendSmsRequest();

	    /* 填充请求参数,这里request对象的成员变量即对应接口的入参
	     * 你可以通过官网接口文档或跳转到request对象的定义处查看请求参数的定义
	     * 基本类型的设置:
		 * 帮助链接：
		 * 短信控制台: https://console.cloud.tencent.com/sms/smslist
		 * sms helper: https://cloud.tencent.com/document/product/382/3773 */

	    /* 短信应用ID: 短信SdkAppid在 [短信控制台] 添加应用后生成的实际SdkAppid，示例如1400006666 */
	    $req->SmsSdkAppid = $this->SmsSdkAppid;
	    /* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，签名信息可登录 [短信控制台] 查看 */
	    $req->Sign = $this->Sign;
	    /* 短信码号扩展号: 默认未开通，如需开通请联系 [sms helper] */
	    $req->ExtendCode = "0";
	    /* 下发手机号码，采用 e.164 标准，+[国家或地区码][手机号]
		 * 示例如：+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
	    $req->PhoneNumberSet = $phone;
	    /* 国际/港澳台短信 senderid: 国内短信填空，默认未开通，如需开通请联系 [sms helper] */
	    $req->SenderId = "";
	    /* 用户的 session 内容: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
	    //$SessionContext = date('YmdHis').random(6, 1);
	    $req->SessionContext = "xxx";
	    /* 模板 ID: 必须填写已审核通过的模板 ID。模板ID可登录 [短信控制台] 查看 */
	    $req->TemplateID = $tempid;
	    /* 模板参数: 若无模板参数，则设置为空*/
	    //$req->TemplateParamSet = array("0");

	    $req->TemplateParamSet = $tempdata;
	    // 通过client对象调用DescribeInstances方法发起请求。注意请求方法名与请求对象是对应的
	    // 返回的resp是一个DescribeInstancesResponse类的实例，与请求对象对应
	    $resp = $client->SendSms($req);

	    //halt(json_decode($resp->toJsonString(),true));
	    // 输出json格式的字符串回包
	    return json_decode($resp->toJsonString(),true);
    }

    /**
     * 发送短信
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-04-26 16:34:15
     * @return  返回值  
     * @version 版本  1.0
     */
    
    public function sendSms($tempid = '', $tempdata = array() , $phone = array())
    {
    	// 读取配置，紫阳所还是粮道所
    	
        $cred = new Credential($this->SecretId, $this->SecretKey);
	    //$cred = new Credential(getenv("TENCENTCLOUD_SECRET_ID"), getenv("TENCENTCLOUD_SECRET_KEY"));

	    // 实例化一个http选项，可选的，没有特殊需求可以跳过
	    $httpProfile = new HttpProfile();
	    $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
	    $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
	    $httpProfile->setEndpoint("sms.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

	    // 实例化一个client选项，可选的，没有特殊需求可以跳过
	    $clientProfile = new ClientProfile();
	    $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
	    $clientProfile->setHttpProfile($httpProfile);

	    // 实例化要请求产品(以sms为例)的client对象,clientProfile是可选的
	    $client = new SmsClient($cred, "ap-shanghai", $clientProfile);

	    // 实例化一个 sms 发送短信请求对象,每个接口都会对应一个request对象。
	    $req = new SendSmsRequest();

	    /* 填充请求参数,这里request对象的成员变量即对应接口的入参
	     * 你可以通过官网接口文档或跳转到request对象的定义处查看请求参数的定义
	     * 基本类型的设置:
		 * 帮助链接：
		 * 短信控制台: https://console.cloud.tencent.com/sms/smslist
		 * sms helper: https://cloud.tencent.com/document/product/382/3773 */

	    /* 短信应用ID: 短信SdkAppid在 [短信控制台] 添加应用后生成的实际SdkAppid，示例如1400006666 */
	    $req->SmsSdkAppid = $this->SmsSdkAppid;
	    /* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，签名信息可登录 [短信控制台] 查看 */
	    $req->Sign = $this->Sign;
	    /* 短信码号扩展号: 默认未开通，如需开通请联系 [sms helper] */
	    $req->ExtendCode = "0";
	    /* 下发手机号码，采用 e.164 标准，+[国家或地区码][手机号]
		 * 示例如：+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
	    $req->PhoneNumberSet = $phone;
	    /* 国际/港澳台短信 senderid: 国内短信填空，默认未开通，如需开通请联系 [sms helper] */
	    $req->SenderId = "";
	    /* 用户的 session 内容: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
	    //$SessionContext = date('YmdHis').random(6, 1);
	    $req->SessionContext = "xxx";
	    /* 模板 ID: 必须填写已审核通过的模板 ID。模板ID可登录 [短信控制台] 查看 */
	    $req->TemplateID = $tempid;
	    /* 模板参数: 若无模板参数，则设置为空*/
	    //$req->TemplateParamSet = array("0");

	    $req->TemplateParamSet = $tempdata;
	    // 通过client对象调用DescribeInstances方法发起请求。注意请求方法名与请求对象是对应的
	    // 返回的resp是一个DescribeInstancesResponse类的实例，与请求对象对应
	    $resp = $client->SendSms($req);

	    //halt(json_decode($resp->toJsonString(),true));
	    // 输出json格式的字符串回包
	    return json_decode($resp->toJsonString(),true);
    }

    /**
     * 拉取某个时间段的短信统计数据
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-04-26 16:40:42 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function sendStatusStatistics($start_date = '2020042600', $end_date = '2020042700', $limit = 10, $offset = 0)
    {
        $cred = new Credential($this->SecretId, $this->SecretKey);
	    $httpProfile = new HttpProfile();
	    $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
	    $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
	    $httpProfile->setEndpoint("sms.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)
	    $clientProfile = new ClientProfile();
	    $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
	    $clientProfile->setHttpProfile($httpProfile);
	    $client = new SmsClient($cred, "ap-shanghai", $clientProfile);
	    $req = new SendStatusStatisticsRequest();
	    $req->SmsSdkAppid = $this->SmsSdkAppid;
	    /* 拉取最大条数，最多100条 */
	    $req->Limit = $limit;
	    /* 偏移量 注：目前固定设置为0 */
	    $req->Offset = $offset;
	    /* 开始时间，yyyymmddhh 需要拉取的起始时间，精确到小时 */
	    $req->StartDateTime = $start_date;
	    /* 结束时间，yyyymmddhh 需要拉取的截止时间，精确到小时
		 * 注：EndDataTime 必须大于 StartDateTime */
	    $req->EndDataTime = $end_date;
	    $resp = $client->SendStatusStatistics($req);
	    //halt(json_decode($resp->toJsonString(),true));
	    // 输出json格式的字符串回包
	    return json_decode($resp->toJsonString(),true);
    }



}