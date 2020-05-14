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

use TencentCloud\Cvm\V20170312\CvmClient;
// 导入要请求接口对应的Request类
use TencentCloud\Cvm\V20170312\Models\DescribeInstancesRequest;
use TencentCloud\Cvm\V20170312\Models\Filter;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
// 导入可选配置类
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;



use TencentCloud\Cfs\V20190719\CfsClient;
use TencentCloud\Cfs\V20190719\Models\DescribeAvailableZoneInfoRequest;
use TencentCloud\Cfs\V20190719\Models\DescribeMountTargetsRequest;
use TencentCloud\Cfs\V20190719\Models\UpdateCfsFileSystemSizeLimitRequest;

/**
 * 腾讯文件系统模型
 * @package app\common\model
 */
class Cvm extends Model
{
	// 设置模型名称
    // protected $name = 'tencent_sms';
    // // 定义时间戳字段名
    // protected $createTime = 'ctime';
    // protected $updateTime = false;

    // // 自动写入时间戳
    // protected $autoWriteTimestamp = true;

    // 小程序传来的code值
    protected $SecretId ;
    protected $SecretKey ;

	protected function initialize()
    {
        parent::initialize();
        $configDatas = WeixinConfigModel::column('name,value');
    	$this->SecretId = $configDatas['tencent_secret_id'];
    	$this->SecretKey = str_coding($configDatas['tencent_secret_key'],'DECODE');
    }

    /**
     * 查询文件系统挂载点 cloud.tencent.com/document/product/582/38169
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-05-11 16:46:32 
     * @param  $FileSystemId String 文件系统ID
     * @return  返回值  
     * @version 版本  1.0
     */
    public function describeMountTargets($FileSystemId = 'cfs-1s3s9yyh')
    {
    	$cred = new Credential($this->SecretId, $this->SecretKey);
	    $httpProfile = new HttpProfile();
	    $httpProfile->setEndpoint("cfs.tencentcloudapi.com");
	      
	    $clientProfile = new ClientProfile();
	    $clientProfile->setHttpProfile($httpProfile);
	    $client = new CfsClient($cred, "ap-shanghai", $clientProfile);

	    $req = new DescribeMountTargetsRequest();
	    
	    //必须传参数FileSystemId，文件服务器的id
	    $params = '{"FileSystemId":"'.$FileSystemId.'"}';
	    $req->fromJsonString($params);


	    $resp = $client->DescribeMountTargets($req);

	    return $resp->toJsonString();
    }

    /**
     * 查询文件系统 
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-05-11 16:46:32 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function describeAvailableZoneInfo()
    {
    	$cred = new Credential($this->SecretId, $this->SecretKey);
	    $httpProfile = new HttpProfile();
	    $httpProfile->setEndpoint("cfs.tencentcloudapi.com");
	      
	    $clientProfile = new ClientProfile();
	    $clientProfile->setHttpProfile($httpProfile);
	    $client = new CfsClient($cred, "", $clientProfile);

	    $req = new DescribeAvailableZoneInfoRequest();
	    
	    $params = '{}'; //"X-TC-Region":"ap-shanghai" "Region":"ap-shanghai"
	    $req->fromJsonString($params);


	    $resp = $client->DescribeAvailableZoneInfo($req);

	    return $resp->toJsonString();
    }

    /**
     * 
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-05-14 14:35:52
     * @param  $FsLimit Integer 必填 文件系统容量限制大小，输入范围0-1073741824, 单位为GB；其中输入值为0时，表示不限制文件系统容量
     * @param  $FileSystemId String 必填 文件系统ID
     * @return  返回值  
     * @version 版本  1.0
     */
    public function updateCfsFileSystemSizeLimitRequest($FsLimit = 100 , $FileSystemId = 'cfs-1s3s9yyh')
    {
    	$cred = new Credential($this->SecretId, $this->SecretKey);
	    $httpProfile = new HttpProfile();
	    $httpProfile->setEndpoint("cfs.tencentcloudapi.com");
	      
	    $clientProfile = new ClientProfile();
	    $clientProfile->setHttpProfile($httpProfile);
	    $client = new CfsClient($cred, "", $clientProfile);

	    $req = new UpdateCfsFileSystemSizeLimitRequest();
	    
	    $params = '{"Region":"ap-shanghai","FsLimit":'.$FsLimit.',"FileSystemId":"'.$FileSystemId.'"}';
	    $req->fromJsonString($params);

	    $resp = $client->UpdateCfsFileSystemSizeLimit($req);

	    return $resp->toJsonString();
    }

    /**
     * 实例信息查询请求对象
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-05-11 14:34:38
     * @return  返回值  
     * @version 版本  1.0
     */
    public function describeInstances()
    {
    	// 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
	    //$cred = new Credential("secretId", "secretKey");
	    $cred = new Credential($this->SecretId, $this->SecretKey);

	    // 实例化一个http选项，可选的，没有特殊需求可以跳过
	    $httpProfile = new HttpProfile();
	    $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
	    $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
	    $httpProfile->setEndpoint("cvm.ap-shanghai.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

	    // 实例化一个client选项，可选的，没有特殊需求可以跳过
	    $clientProfile = new ClientProfile();
	    $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
	    $clientProfile->setHttpProfile($httpProfile);

	    // 实例化要请求产品(以cvm为例)的client对象,clientProfile是可选的
	    $client = new CvmClient($cred, "ap-shanghai", $clientProfile);

	    // 实例化一个cvm实例信息查询请求对象,每个接口都会对应一个request对象。
	    $req = new DescribeInstancesRequest();

	    // 填充请求参数,这里request对象的成员变量即对应接口的入参
	    // 你可以通过官网接口文档或跳转到request对象的定义处查看请求参数的定义
	    $respFilter = new Filter();  // 创建Filter对象, 以zone的维度来查询cvm实例
	    $respFilter->Name = "zone";
	    $respFilter->Values = ["ap-shanghai-1", "ap-shanghai-2"];
	    $req->Filters = [$respFilter];  // Filters 是成员为Filter对象的列表

	    // 这里还支持以标准json格式的string来赋值请求参数的方式。下面的代码跟上面的参数赋值是等效的
	    $params = [
	        "Filters" => [
	            [
	                "Name" => "zone",
	                "Values" => ["ap-shanghai-1", "ap-shanghai-2"]
	            ]
	        ]
	    ];
	    $req->fromJsonString(json_encode($params));

	    // 通过client对象调用DescribeInstances方法发起请求。注意请求方法名与请求对象是对应的
	    // 返回的resp是一个DescribeInstancesResponse类的实例，与请求对象对应
	    $resp = $client->DescribeInstances($req);

	    // 输出json格式的字符串回包
	   	return $resp->toJsonString();

	   	/*{
			"TotalCount": 10,
			"InstanceSet": [{
				"Placement": {
					"Zone": "ap-shanghai-2",
					"ProjectId": 0
				},
				"InstanceId": "ins-p5imb7ij",
				"InstanceType": "S3.4XLARGE64",
				"CPU": 16,
				"Memory": 64,
				"RestrictState": "NORMAL",
				"InstanceName": "高性能武房小店",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "CLOUD_PREMIUM",
					"DiskId": "disk-rack0fyn",
					"DiskSize": 500
				},
				"PrivateIpAddresses": ["10.105.46.65"],
				"PublicIpAddresses": ["49.235.119.176"],
				"InternetAccessible": {
					"InternetChargeType": "TRAFFIC_POSTPAID_BY_HOUR",
					"InternetMaxBandwidthOut": 50
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-dkwyg6sr",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2020-04-01T12:28:43Z",
				"ExpiredTime": "2023-04-01T12:28:43Z",
				"OsName": "CentOS 7.3 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "c7bb6846-ca86-43ff-a650-1f7ef455f14f",
				"LatestOperation": "RebootInstances",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "d75ffd3c-77a5-45c8-aaa8-700662e677f4",
				"DisasterRecoverGroupId": "",
				"CamRoleName": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-2k17knsf",
				"InstanceType": "S2.MEDIUM2",
				"CPU": 2,
				"Memory": 2,
				"RestrictState": "NORMAL",
				"InstanceName": "文件管理系统",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "CLOUD_PREMIUM",
					"DiskId": "disk-j0yyum07",
					"DiskSize": 200
				},
				"PrivateIpAddresses": ["172.17.0.8"],
				"PublicIpAddresses": ["118.89.138.206"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 2
				},
				"VirtualPrivateCloud": {
					"VpcId": "vpc-pxpvefgk",
					"SubnetId": "subnet-d0d1dmrf",
					"AsVpcGateway": false
				},
				"ImageId": "img-nusfrt03",
				"RenewFlag": "NOTIFY_AND_AUTO_RENEW",
				"CreatedTime": "2019-12-31T05:38:16Z",
				"ExpiredTime": "2022-12-31T05:38:16Z",
				"OsName": "CentOS 7.4 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "6858d5d7-fa18-4a44-aac5-248c51e8aedb",
				"LatestOperation": "RebootInstances",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "5d6a86be-c8c2-4323-8b95-e20c3e0904ca",
				"DisasterRecoverGroupId": "",
				"CamRoleName": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-2",
					"ProjectId": 0
				},
				"InstanceId": "ins-gx03vde3",
				"InstanceType": "S2.MEDIUM4",
				"CPU": 2,
				"Memory": 4,
				"RestrictState": "NORMAL",
				"InstanceName": "武房网公房",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "LOCAL_BASIC",
					"DiskId": "ldisk-lkmi232a",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 500,
					"DiskType": "LOCAL_BASIC",
					"DiskId": "ldisk-puomxrqi"
				}],
				"PrivateIpAddresses": ["10.105.193.9"],
				"PublicIpAddresses": ["118.25.128.122"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 10
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-31tjrtph",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2018-05-12T03:45:23Z",
				"ExpiredTime": "2022-11-12T03:45:23Z",
				"OsName": "CentOS 7.2 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "c3e7a612-380c-43e0-b114-ee11484730d6",
				"LatestOperation": "RenewInstances",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "bea8c7ef-ec87-457b-af68-8d5ce3fbca52",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-matpyj4b",
				"InstanceType": "S1.MEDIUM4",
				"CPU": 2,
				"Memory": 4,
				"RestrictState": "NORMAL",
				"InstanceName": "公房网文件服务器",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "CLOUD_BASIC",
					"DiskId": "disk-i76gp9du",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 100,
					"DiskType": "CLOUD_PREMIUM",
					"DiskId": "disk-cgg8jyrt",
					"DeleteWithInstance": false
				}],
				"PrivateIpAddresses": ["10.105.62.172"],
				"PublicIpAddresses": ["115.159.45.155"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 4
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-egif9bvl",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2017-09-22T09:25:36Z",
				"ExpiredTime": "2022-03-22T09:25:39Z",
				"OsName": "Windows Server 2012 R2 Standard 64bitCN",
				"SecurityGroupIds": ["sg-9w26stdy", "sg-69wvtpke"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "865d97b9-6d2b-4432-a19b-eb48679ea532",
				"LatestOperation": "ResetInstancesPassword",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "b8cb0579-be67-41b6-8989-51e75ae72724",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-421amg03",
				"InstanceType": "S1.MEDIUM2",
				"CPU": 2,
				"Memory": 2,
				"RestrictState": "NORMAL",
				"InstanceName": "公房管理系统测试服务器",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "CLOUD_BASIC",
					"DiskId": "disk-4guqb8bz",
					"DiskSize": 500
				},
				"DataDisks": [{
					"DiskSize": 60,
					"DiskType": "CLOUD_BASIC",
					"DiskId": "disk-ff88lfah",
					"DeleteWithInstance": false
				}],
				"PrivateIpAddresses": ["10.154.201.129"],
				"PublicIpAddresses": ["118.89.169.68"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 1
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-k8vwmp25",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2017-07-31T04:38:02Z",
				"ExpiredTime": "2022-02-28T04:38:07Z",
				"OsName": "CentOS 7.2 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "6e3961a1-a14a-40cc-a215-f4780525f148",
				"LatestOperation": "RenewInstances",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "1959735e-683e-4d1c-89cf-606281478c39",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-2",
					"ProjectId": 0
				},
				"InstanceId": "ins-ht2p87d9",
				"InstanceType": "I2.LARGE16",
				"CPU": 4,
				"Memory": 16,
				"RestrictState": "NORMAL",
				"InstanceName": "我家",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "CLOUD_SSD",
					"DiskId": "disk-lzy7d9kz",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 200,
					"DiskType": "CLOUD_SSD",
					"DiskId": "disk-eknpuk4r",
					"DeleteWithInstance": false
				}],
				"PrivateIpAddresses": ["10.154.131.13"],
				"PublicIpAddresses": ["111.231.118.33"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 4
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2017-06-26T06:54:05Z",
				"ExpiredTime": "2022-01-26T06:54:08Z",
				"OsName": "CentOS 7.3 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "e0ae2236-a211-4edd-a8f4-a30c447aae29",
				"LatestOperation": "ResetInstance",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "bcb3b2ea-b3d8-41c9-a41b-07698ef518a1",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-753z909z",
				"InstanceType": "I1.LARGE8",
				"CPU": 4,
				"Memory": 8,
				"RestrictState": "NORMAL",
				"InstanceName": "test",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "LOCAL_SSD",
					"DiskId": "ldisk-256rurqa",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 100,
					"DiskType": "LOCAL_SSD",
					"DiskId": "ldisk-7ummgn3g"
				}],
				"PrivateIpAddresses": ["10.105.97.157"],
				"PublicIpAddresses": ["115.159.102.49"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 10
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-6kl2rvlj",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2016-07-14T08:43:42Z",
				"ExpiredTime": "2022-01-14T08:43:47Z",
				"OsName": "Windows Server 2008 R2 Enterprise SP1 64bit",
				"SecurityGroupIds": ["sg-9w26stdy", "sg-69wvtpke"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "1b4eee72-2b67-4aaf-b982-8f34df60c09c",
				"LatestOperation": "RebootInstances",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "1d67aa86-d038-4a94-96b5-3ceae8078bf1",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-ankl8i33",
				"InstanceType": "S1.LARGE12",
				"CPU": 4,
				"Memory": 12,
				"RestrictState": "NORMAL",
				"InstanceName": "back",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "CLOUD_BASIC",
					"DiskId": "disk-2lsw1e77",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 500,
					"DiskType": "CLOUD_BASIC",
					"DiskId": "disk-5g3ftokp",
					"DeleteWithInstance": false
				}],
				"PrivateIpAddresses": ["10.105.108.134"],
				"PublicIpAddresses": ["115.159.98.221"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 4
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-0vbqvzfn",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2016-07-14T08:19:41Z",
				"ExpiredTime": "2022-02-14T08:19:46Z",
				"OsName": "Windows Server 2008 R2 Enterprise SP1 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "89011d59-a5a3-429e-b7e2-88e7d77c4a1a",
				"LatestOperation": "RebootInstances",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "14ce7677-d506-48a0-a625-07023da73703",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-nkhem3s1",
				"InstanceType": "M1.LARGE32",
				"CPU": 4,
				"Memory": 32,
				"RestrictState": "NORMAL",
				"InstanceName": "wfw_web2",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "LOCAL_BASIC",
					"DiskId": "ldisk-5zzvmjsy",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 160,
					"DiskType": "LOCAL_BASIC",
					"DiskId": "ldisk-ccwy8s6k"
				}],
				"PrivateIpAddresses": ["10.105.122.244"],
				"PublicIpAddresses": ["115.159.36.155"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 10
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"ImageId": "img-kpc6q0sn",
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2016-07-05T06:55:43Z",
				"ExpiredTime": "2022-02-05T06:55:48Z",
				"OsName": "CentOS 7.3 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [{
					"Key": "tencentcloud:autoscaling:auto-scaling-group-id",
					"Value": "asg-2tayppxt"
				}],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "cc6a1ca2-18c8-42d4-8074-0e17b0002349",
				"LatestOperation": "ResetInstancesPassword",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "1a970eeb-c05f-411e-9026-6688d6b36c25",
				"DisasterRecoverGroupId": ""
			}, {
				"Placement": {
					"Zone": "ap-shanghai-1",
					"ProjectId": 0
				},
				"InstanceId": "ins-knfgm66b",
				"InstanceType": "M1.LARGE32",
				"CPU": 4,
				"Memory": 32,
				"RestrictState": "NORMAL",
				"InstanceName": "wfw_web1",
				"InstanceChargeType": "PREPAID",
				"SystemDisk": {
					"DiskType": "LOCAL_BASIC",
					"DiskId": "ldisk-htgensjm",
					"DiskSize": 50
				},
				"DataDisks": [{
					"DiskSize": 160,
					"DiskType": "LOCAL_BASIC",
					"DiskId": "ldisk-csd02pqg"
				}],
				"PrivateIpAddresses": ["10.105.98.187"],
				"PublicIpAddresses": ["182.254.215.61"],
				"InternetAccessible": {
					"InternetChargeType": "BANDWIDTH_PREPAID",
					"InternetMaxBandwidthOut": 10
				},
				"VirtualPrivateCloud": {
					"AsVpcGateway": false
				},
				"RenewFlag": "NOTIFY_AND_MANUAL_RENEW",
				"CreatedTime": "2016-07-04T08:29:57Z",
				"ExpiredTime": "2022-02-04T08:30:02Z",
				"OsName": "CentOS 7.3 64bit",
				"SecurityGroupIds": ["sg-9w26stdy"],
				"LoginSettings": [],
				"InstanceState": "RUNNING",
				"Tags": [],
				"StopChargingMode": "NOT_APPLICABLE",
				"Uuid": "0a833b28-e51a-4ede-97ea-c84d80b017ab",
				"LatestOperation": "ResetInstancesPassword",
				"LatestOperationState": "SUCCESS",
				"LatestOperationRequestId": "490f8a65-e440-45e2-8c0a-0369fbc09176",
				"DisasterRecoverGroupId": ""
			}],
			"RequestId": "fa863460-c728-4c6d-9fc9-662052610055"
		}*/

    }


}