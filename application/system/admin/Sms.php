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

use app\system\model\Sms as SmsModel;


/**
 * 应用市场控制器
 * @package app\system\admin
 */
class Sms extends Admin
{
    
    
    /**
     * 应用列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
  
            $SmsModel = new SmsModel;

            $data['data'] = $SmsModel->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            $data['count'] = $SmsModel->where($where)->count('id');//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 发送手机登录或注册验证码
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-04-26 15:24:28
     * @return  返回值  
     * @version 版本  1.0
     */
    public function send_sign_sms()
    {
        $SmsModel = new SmsModel;
        //发送短信验证
        $tempid = '580690'; //短信模板
        $sendcode = random(6, 1);
        $tempdata = [$sendcode,'3']; //模板参数
        $phone = ['+8618674012767']; //手机号
		$data = $SmsModel->sendSms($tempid , $tempdata , $phone);
		$count = 0;
		foreach($data['SendStatusSet'] as $row){
			if($row['Code'] === 'Ok'){
				$row['PhoneNumber'] = substr($row['PhoneNumber'],3);
				//session('sms_verification_', $login,);
				$SmsModel = new SmsModel;
				$ji = $SmsModel->save(['serial_no'=>$row['SerialNo'],'phone'=>$row['PhoneNumber'],'session_context'=>$row['SessionContext'],'content'=>$sendcode]);
				if($ji){
					$count++;
				}
			}else{
				return $this->error('发送失败,错误码：'.$row['Message']);
			}
		}
		if($count){
			return json(['code'=>0,'msg'=>'发送成功','count'=>$count]);
		}else{
			return $this->error('发送失败');
		}
        
    }

    /**
     * 拉取某个时间段短信发送情况统计
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-04-26 15:24:28
     * @return  返回值  
     * @version 版本  1.0
     */
    public function send_status_statistics()
    {
        $SmsModel = new SmsModel;
        $start_date = '2020042600';
        $end_date = '2020042700';
        $limit = 10;
        $offset = 0;
		$data = $SmsModel->sendStatusStatistics($start_date, $end_date, $limit, $offset);
		/*返回格式如下：array(2) {
		  ["SendStatusStatistics"] => array(3) {
		    ["FeeCount"] => int(9)
		    ["RequestCount"] => int(16)
		    ["RequestSuccessCount"] => int(9)
		  }
		  ["RequestId"] => string(36) "01dd8885-aeff-4e7b-9e98-9192b822c78e"
		}*/
		
    }

    
}
