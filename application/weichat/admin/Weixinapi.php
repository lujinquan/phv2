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

namespace app\weichat\admin;

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
use app\weichat\model\Weixin as WeixinModel;

/**
 * 微信小程序用户版接口
 */
class Weixinapi extends Controller 
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
                $result['msg'] = '请输入账户名！';
            }
            // 验证数据合法性
            if(!isset($data['code']) || !$data['code']){
                $result['msg'] = '请输入验证码！';
                return json($result);
            }
            // 如果有重复的手机号，会只取第一条
            $row = TenantModel::where([['tenant_tel','eq',$data['username']],['tenant_status','eq',1]])->find();
            //halt($data);
            if(!$row){
                $result['msg'] = '账户异常！';
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


	public function getWeixinBaseInfo()
	{
	    $code = input('code');//小程序传来的code值
	    $WeixinModel = new WeixinModel;
	    $resultOpenid = $WeixinModel->getOpenid($code); 
        $result = [];
        if(is_array($resultOpenid)){
            $resultAccessToken = $WeixinModel->getAccessToken();
            $resultOpenid['access_token'] = $resultAccessToken['access_token'];
            $result['code'] = 1;
            $result['data'] = $resultOpenid;
            $result['msg'] = '获取成功！';
        }else{
            $result['code'] = 0;
            $result['msg'] = $resultOpenid;
        }
        return json($result);  
	}

    public function sendSubscribeTemplate()
    {
        $openid = input('openid','oxgVt5RZHUzam9oAHlJRGRlpDwFY'); //接收openid
        $template_id = input('template_id','2kL0FTh48uEpTgBcLAwp2siR7eTrKOgNiHZSdXA_r_k'); //接收template_id
        // $action = input('action'); //接收action
        // $scene = input('scene'); //接收scene
       //halt($openid);
        $data = [
            'touser' => $openid, //要发送给用户的openId
            //改成自己的模板id，在微信接口权限里一次性订阅消息的查看模板id
            'template_id' => $template_id,
            //'url' => "自己网站链接url ", //自己网站链接url 
            //'scene'=>"$scene",
            //'title'=>"title", //标题
            //下面的data格式必须与小程序后台设置的模板详情参数一致！
            'data'=>array(
                'character_string1'=>array(
                    'value'=>"202002200000",
                ),
                'amount2'=>array(
                    'value'=>"￥100",
                ),
                'date3'=>array(
                    'value'=>"2020-02-21",
                ),
                'phrase6'=>array(
                    'value'=>"微信",
                ),
                'phrase9'=>array(
                    'value'=>"支付成功",
                ),
            )
        ];
        $WeixinModel = new WeixinModel;
        $res = $WeixinModel->sendSubscribeTemplate($data);
        $result = [];
        if($res['errcode'] == 0){
            $result['code'] = 1;
            $result['msg'] = '发送成功！';
        }else{
            $result['code'] = 0;
            $result['msg'] = '发送失败！';
        }
        //halt($res);
        return json($result);  
    }

    public function getAccessToken()
    {
        $WeixinModel = new WeixinModel;
        $res = $WeixinModel->getAccessToken();

        halt($res); 
    }

	

    public function sendMessage()
    {
        if ($this->request->isPost()) {

            $username = $this->request->post('username');
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
        $result = [];
        $result['code'] = 0;
        if(!$key){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();

        if($tenantInfo){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $systemNotice = new SystemNotice;
            $result['data']['notice'] = $systemNotice->field('id,title,type,content,cuid,reads,create_time')->where([['delete_time','eq',0],['inst_id','eq',4]])->order('sort asc')->select()->toArray();
            $result['data']['message'] = [
                '欢迎使用公房用户版小程序！！！','小程序由智慧公房系统提供数据服务支持，更多功能敬请期待……'
            ];
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
//halt($result);
        return json($result); 

        
    }

    public function noticeDetail()
    {
        $key = input('get.key');
        $result = [];
        $result['code'] = 0;
        if(!$key){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $id = input('get.id');
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();

        if($tenantInfo){
            $systemNotice = new SystemNotice;
            $result['data'] = $systemNotice->get($id);

            //$result['data']['content'] = str_replace('/static/js/editor/', 'https://pro.ctnmit.com/static/js/editor/', htmlspecialchars_decode($result['data']['content']));
            $result['data']['content'] = htmlspecialchars_decode($result['data']['content']);
            
            $result['data']['cuid'] = Db::name('system_user')->where([['id','eq',$result['data']['cuid']]])->value('nick');
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
        $result = [];
        $result['code'] = 0;
        if(!$key){
            $result['msg'] = '参数错误！';
            return json($result);
        }
    	$key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
    	//$id = str_coding($key,'DECODE');
    	$tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
    
    	if($tenantInfo){
    		$result['data']['tenant'] = $tenantInfo;
    		$result['data']['house'] = HouseModel::with('ban')->where([['tenant_id','eq',$tenantInfo['tenant_id']]])->field('house_id,house_balance,ban_id,tenant_id,house_unit_id,house_is_pause,house_pre_rent,house_status,house_floor_id')->select()->toArray();
    		foreach ($result['data']['house'] as $k => &$v) {
    			//halt($v);
    			$row = Db::name('rent_order')->where([['house_id','eq',$v['house_id']],['tenant_id','eq',$v['tenant_id']]])->field('sum(rent_order_receive - rent_order_paid) as rent_order_unpaids,sum(rent_order_paid) as rent_order_paids')->find();

    			$v['rent_order_unpaids'] = $row['rent_order_unpaids']?$row['rent_order_unpaids']:0;
    			$v['rent_order_paids'] = $row['rent_order_paids']?$row['rent_order_paids']:0;
                //$value['id'] = $key + 1;
            }
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
        $result = [];
        $result['code'] = 0;
        if(!$key){
            $result['msg'] = '参数错误！';
            return json($result);
        }

    	$key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
    	$houseID = input('get.house_id'); //获取房屋id
    	$tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();

    	if($tenantInfo){
    		//dump($tenantInfo['tenant_id']);halt($houseID);
    		$result['data']['rent'] = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')],['house_id','eq',$houseID],['tenant_id','eq',$tenantInfo['tenant_id']]])->order('rent_order_id desc')->select();
            foreach ($result['data']['rent'] as $key => &$value) {
                $value['id'] = $key + 1;
            }
    		$result['data']['tenant'] = $tenantInfo;
    		$result['data']['house'] = HouseModel::with('ban')->where([['tenant_id','eq',$tenantInfo['tenant_id']]])->field('house_balance,ban_id,house_id,house_pre_rent,house_unit_id,house_floor_id')->select();
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
    public function myOrderInfo() 
    {
    	$key = input('get.key');
        $result = [];
        $result['code'] = 0;
        if(!$key){
            $result['msg'] = '参数错误！';
            return json($result);
        }
    	$key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        $houseID = input('get.house_id');
    	$datasel = input('get.data_sel'); //
    	$tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
    	$where = [];


    	if($tenantInfo){

    		$fields = "a.rent_order_id,a.house_id,from_unixtime(a.ptime, '%Y-%m-%d %H:%i:%s') as ptime,a.tenant_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_floor_id,b.house_door,b.house_unit_id,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
         
         	$where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
         	$where[] = ['a.tenant_id','eq',$tenantInfo['tenant_id']];
         	if($houseID){
         		$where[] = ['a.house_id','eq',$houseID];
         	}
            if($datasel){
                $startDate = substr($datasel,0,4);
                $endDate = substr($datasel,5,2);
                $where[] = ['a.rent_order_date','eq',$startDate.$endDate];
            }
//halt($where);
            $result['data']['rent'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.rent_order_id desc')->select();

    		// $result['data']['rent'] = RentModel::where([['rent_order_paid','exp',Db::raw('=rent_order_receive')],['tenant_id','eq',$tenantInfo['tenant_id']]])->select()->toArray();
            // foreach ($result['data']['rent'] as $key => &$value) {
            //     $value['id'] = $key + 1;
            // }
    		$result['data']['tenant'] = $tenantInfo;
    		$result['data']['house'] = HouseModel::with('ban')->where([['tenant_id','eq',$tenantInfo['tenant_id']]])->field('house_balance,house_id,house_pre_rent,ban_id,house_unit_id,house_floor_id')->select();
    		$result['code'] = 1;
    		$result['msg'] = '获取成功！';
    	}else{
    		$result['msg'] = '参数错误！';
    	}
//halt($result);
    	return json($result); 
    }


    public function houseDetail()
    {
        
        $key = input('get.key');
        $result = [];
        $result['code'] = 0;
        if(!$key){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        $id = input('get.house_id');
        $tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
        $where = [];

        if($tenantInfo){
            $HouseModel = new HouseModel;
            $temp = HouseModel::with(['ban','tenant'])->get($id);
            $cutRent = Db::name('change_cut')->where([['house_id','eq',$id],['tenant_id','eq',$temp['tenant_id']],['change_status','eq',1],['end_date','>',date('Ym')]])->value('cut_rent');
            $temp['cut_rent'] = $cutRent?$cutRent:'0.00';
            
            $params = ParamModel::getCparams();

            $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
            $temp['house_use_id'] = $params['uses'][$temp['house_use_id']];
            $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            // $temp['ban_imgs'] = SystemAnnex::changeFormat($temp['ban_imgs'],$complete = true);
            // $temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');
            $rooms = $HouseModel->get_house_renttable($id);
            foreach($rooms as &$t){
                $t['baseinfo']['room_type'] = $params['roomtypes'][$t['baseinfo']['room_type']];
                $t['baseinfo']['room_status'] = $params['status'][$t['baseinfo']['room_status']];
                $t['baseinfo']['ban_owner_id'] = $params['owners'][$t['baseinfo']['ban_owner_id']];
                $t['baseinfo']['ban_inst_id'] = $params['insts'][$t['baseinfo']['ban_inst_id']];
                $t['baseinfo']['ban_struct_id'] = $params['structs'][$t['baseinfo']['ban_struct_id']];
            }
            $temp['rooms'] = $rooms;
//halt($temp['rooms']);
            $result['data'] = $temp;
//halt($result['data']);  
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }


}