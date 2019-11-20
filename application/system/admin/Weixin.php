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

use think\Db;
use think\Controller;
use SendMessage\ServerCodeAPI;
use app\common\controller\Common;
use app\system\model\SystemNotice;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
//use app\system\model\SystemUser as UserModel;


/**
 * 微信小程序用户版接口
 */
class Weixin extends Controller 
{
	
	/**
	 * [signin 用户版小程序登录]
	 * @return [type] [description]
	 */
    public function signin()
    {
        if($this->request->isPost()){
            // 获取post数据
            $data = $this->request->post();

            $result = [];
            $result['code'] = 0;
            // 验证数据合法性
            if(!isset($data['username']) || !$data['username']){
                $result['msg'] = '请输入登录用户名！';
            }
            /*// 验证数据合法性
            if(!isset($data['code']) || !$data['code']){
                $result['msg'] = '请输入验证码！';
                return json($result);
            }*/
            // 如果有重复的手机号，会只取第一条
            $row = TenantModel::where([['tenant_tel','eq',$data['username']],['tenant_status','eq',1]])->find();
            //halt($data);
            if(!$row){
                $result['msg'] = '用户名错误或被禁用！';
            } else {

                /*$auth = new ServerCodeAPI();    
                $res = $auth->CheckSmsYzm($data['username'],$data['code']);
                $res = json_decode($res);
                // 验证短信码是否正确
                if($res->code == '200'){ */
                    $key = str_coding($row['tenant_id'],'ENCODE');
                    // 更新用户登录的信息
                    TenantModel::where([['tenant_id','eq',$row['tenant_id']],['tenant_status','eq',1]])->update(['tenant_key'=>$key,'tenant_weixin_ctime'=>time()]);
                    
                    $result['data']['key'] = $key;
                    $result['code'] = 1;
                    $result['msg'] = '登录成功！';
                /*} else if($res->code == '413'){
                    $result['msg'] = '验证失败！';
                } else {
                    $result['msg'] = '请重新获取！';
                }*/
              
            }
 
            return json($result);
        }
    }

    public function sendMessage()
    {
        if ($this->request->isPost()) {

            $username   = $this->request->post('username');
            $where = [];
            $where[] = ['tenant_tel','eq',$username];
            $where[] = ['tenant_status','eq',1];
            $row = TenantModel::where($where)->find();
            $result = [];
            $result['code'] = 0;
            if(!$row){
                $result['msg'] = '用户名不存在或被禁用！';
            }else{
                //通过类型判断是否超出当日短信发送限额！
            
                //验证通过即发送短信
                $auth = new ServerCodeAPI();
                $res = json_decode($auth->SendSmsCode($username));

                

                if($res->code == '416'){
                    $result['msg'] = '验证次数过多，请更换登录方式！';
                    //$this->error('验证次数过多，请更换登录方式');
                }else{
                   $result['code'] = 1; 
                   $result['msg'] = '发送成功！';
                }
            }
            
            return json($result);
        }
    }

    public function noticeInfo()
    {
        $key = input('get.key');
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
        $result = [];
        $result['code'] = 0;

        if($tenantInfo){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $systemNotice = new SystemNotice;
            $result['data']['notice'] = $systemNotice->field('id,title,type,content,cuid,reads,create_time')->order('sort asc')->select();
            $result['data']['message'] = [
                '欢迎进入公房用户版小程序！！！','这是第二条消息推送，增加一下长度度度度度度度度度度度度度度度度度度度度……'
            ];
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    public function noticeDetail()
    {
        $key = input('get.key');
        $id = input('get.id');
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
        $result = [];
        $result['code'] = 0;
        if($tenantInfo){
            $systemNotice = new SystemNotice;
            $result['data'] = $systemNotice->get($id);
           
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }

    /**
     * 
     * @param id 消息id
     * @return json
     */
    public function tenantInfo() 
    {
    	$key = input('get.key');
    	$key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
    	//$id = str_coding($key,'DECODE');
    	$tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
    	$result = [];
    	$result['code'] = 0;

    	if($tenantInfo){
    		$result['data']['tenant'] = $tenantInfo;
    		$result['data']['house'] = HouseModel::with('ban')->where([['tenant_id','eq',$tenantInfo['tenant_id']]])->field('house_id,house_balance,ban_id,house_unit_id,house_floor_id')->select();
    		$result['code'] = 1;
    		$result['msg'] = '获取成功！';
    	}else{
    		$result['msg'] = '参数错误！';
    	}

    	return json($result); 
    }


    /**
     * 获取某个房屋的租金订单信息
     * @param id 消息id
     * @return json
     */
    public function rentOrderInfo() 
    {
    	$key = input('get.key');
    	$key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
    	$houseID = input('get.house_id'); //获取房屋id
    	$tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
    	$result = [];
    	$result['code'] = 0;

    	if($tenantInfo){
    		//dump($tenantInfo['tenant_id']);halt($houseID);
    		$result['data']['rent'] = RentModel::where([['house_id','eq',$houseID],['tenant_id','eq',$tenantInfo['tenant_id']]])->select();
            foreach ($result['data']['rent'] as $key => &$value) {
                $value['id'] = $key;
            }
    		$result['data']['tenant'] = $tenantInfo;
    		$result['data']['house'] = HouseModel::with('ban')->where([['tenant_id','eq',$tenantInfo['tenant_id']]])->field('house_balance,ban_id,house_unit_id,house_floor_id')->select();
    		$result['code'] = 1;
    		$result['msg'] = '获取成功！';
    	}else{
    		$result['msg'] = '参数错误！';
    	}

    	return json($result); 
    }

}