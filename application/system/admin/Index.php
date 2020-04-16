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

namespace app\system\admin;

use Env;
use hisi\Dir;
use app\system\model\SystemNotice;
use app\system\model\SystemData as DataModel;
include EXTEND_PATH.'tencentcloud/TCloudAutoLoader.php';
// 导入对应产品模块的client
use TencentCloud\Sms\V20190711\SmsClient;
// 导入要请求接口对应的Request类
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
// 导入可选配置类
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;

/**
 * 后台默认首页控制器
 * @package app\system\admin
 */

class Index extends Admin
{
    /**
     * 首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->post();
            $systemNotice = new SystemNotice;
            $where = $systemNotice->checkWhere($getData);
            $data['data'] = $systemNotice->field('id,title,type,content,cuid,reads,create_time')->where($where)->page($page)->order('sort asc')->limit($limit)->select();
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }else{
           if (cookie('hisi_iframe')) {  //单页面模式
                $this->view->engine->layout(false);
                return $this->fetch('iframe');
            } else { //ifram模式
                return $this->fetch();
            } 
        }
        
        
    }
    
    /**
     * 调用腾讯云短信发送短信
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-04-16 15:57:11
     * @return  返回值  
     * @version 版本  1.0
     */
    public function sendSms()
    {
        $cred = new Credential("xxx", "xxx");
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
        $req->SmsSdkAppid = "1400353521";
        /* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，签名信息可登录 [短信控制台] 查看 */
        $req->Sign = "武房网信息服务有限公司";
        /* 短信码号扩展号: 默认未开通，如需开通请联系 [sms helper] */
        $req->ExtendCode = "0";
        /* 下发手机号码，采用 e.164 标准，+[国家或地区码][手机号]
         * 示例如：+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
        $req->PhoneNumberSet = array("18674012767");
        /* 国际/港澳台短信 senderid: 国内短信填空，默认未开通，如需开通请联系 [sms helper] */
        $req->SenderId = "";
        /* 用户的 session 内容: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
        $req->SessionContext = "xxx";
        /* 模板 ID: 必须填写已审核通过的模板 ID。模板ID可登录 [短信控制台] 查看 */
        $req->TemplateID = "580690";
        /* 模板参数: 若无模板参数，则设置为空*/
        $req->TemplateParamSet = array("230094","3");
        // 通过client对象调用DescribeInstances方法发起请求。注意请求方法名与请求对象是对应的
        // 返回的resp是一个DescribeInstancesResponse类的实例，与请求对象对应
        $resp = $client->SendSms($req);
        // 输出json格式的字符串回包
        print_r($resp->toJsonString());
        // 也可以取出单个值。
        // 你可以通过官网接口文档或跳转到response对象的定义处查看返回字段的定义
        print_r($resp->TotalCount);
        halt(1);
    }

    //楼栋选择器
	public function querier()
	{
        if ($this->request->isAjax()) {
            $queryWhere = $this->request->param();
            $DataModel = new DataModel;
            $data = $DataModel->queryBan($queryWhere);
            return json($data);
        }
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/ban');
	}
	//楼栋调整——楼栋选择器
	public function queriers()
	{
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/ban_houses');
	}
	 //租户选择器
	public function tenant()
	{
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/tenant');
	}

	//房屋选择器
	public function house()
	{
        if ($this->request->isAjax()) {
            $queryWhere = $this->request->param();
            $DataModel = new DataModel;
            $data = $DataModel->queryHouse($queryWhere);
            return json($data);
        }
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/house');
	}
	
   //房屋选择器
	public function houseselected()
	{
        if ($this->request->isAjax()) {
            $queryWhere = $this->request->param();
            $DataModel = new DataModel;
            $data = $DataModel->queryHouse($queryWhere);
            return json($data);
        }
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/house_selected');
	}
	//异动注销查询器
	public function cancellation()
	{
        if ($this->request->isAjax()) {
            $queryWhere = $this->request->param();
            $DataModel = new DataModel;
            $data = $DataModel->queryBan($queryWhere);
            return json($data);
        }
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/ban_select_houses');
	}
	//楼栋选择器多选
	public function cancellations()
	{
        $changeType = input('param.change_type');
        $this->assign('changeType',$changeType);
		return $this->fetch('block/queriers/bans');
	}
    /**
     * 欢迎首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function welcome()
    {
        return $this->fetch('index');
    }

    /**
     * 欢迎首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function help()
    {
        return $this->fetch('./templete/helpcenter/index.html');
    }

    /**
     * 清理缓存
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function clear()
    {
        $path   = Env::get('runtime_path');
        // $cache  = $this->request->param('cache/d', 0);
        // $log    = $this->request->param('log/d', 0);
        // $temp   = $this->request->param('temp/d', 0);

        // if ($cache == 1) {
        //     Dir::delDir($path.'cache');
        // }

        // if ($temp == 1) {
        //     Dir::delDir($path.'temp');
        // }

        // if ($log == 1) {
        //     Dir::delDir($path.'log');
        // }

        Dir::delDir($path.'cache');
        Dir::delDir($path.'temp');
        Dir::delDir($path.'log');
        //(new \app\common\model\SystemAnnex)->clearAnnex();

        return $this->success('清除成功');
    }
}
