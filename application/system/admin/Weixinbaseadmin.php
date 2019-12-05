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
use app\system\model\SystemUser as UserModel;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;


/**
 * 微信小程序房管员版接口
 */
class Weixinbaseadmin extends Controller 
{
	
	/**
	 * [signin 房管员版小程序登录]
	 * @return [type] [description]
	 */
    public function signin()
    {
        if($this->request->isPost()){
            // 获取post数据
            $data = $this->request->post();
            $result = [];
            $result['code'] = 0;
            // 验证是否输入账户名
            if(!isset($data['username']) || !$data['username']){
                $result['msg'] = '请输入账户名！';
                return json($result);
            }
            // 验证是否输入密码
            if(!isset($data['password']) || !$data['password']){
                $result['msg'] = '请输入密码！';
                return json($result);
            }
            $row = UserModel::where([['username','eq',$data['username']],['status','eq',1]])->find();
            // 验证账户是否存在且状态正常
            if(!$row){
                $result['msg'] = '账户异常！';
                return json($result);
            }
        	// 验证角色
        	if($row['role_id'] != 4){
        		$result['msg'] = '角色异常！';
        		return json($result);
        		
        	}
        	// 验证登录账户是否为房管员
        	if($row['inst_level'] != 3){
        		$result['msg'] = '非房管员用户！';
        		return json($result);
        	}
    		// 验证密码是否正确
    		if (!password_verify(md5(trim($data['password'])), $row['password'])) {
	            $result['msg'] = '登录密码错误！';
	            return json($result);
	        }
        	$key = str_coding($row['id'],'ENCODE');
        	// 更新用户登录的信息
            UserModel::where([['id','eq',$row['id']]])->update(['user_key'=>$key,'user_weixin_ctime'=>time()]);
            
            $result['data']['key'] = $key;
            $result['code'] = 1;
            $result['msg'] = '登录成功！';
            return json($result);

        }
    }

    public function params()
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
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();
        $result = [];
        $result['code'] = 0;
        if($row){
            $result['code'] = 1;
            $params = ParamModel::getCparams();
            $result['data'] = $params;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result); 

    }

    public function banList()
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
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $damage = input('ban_damage_id');
            $owner = input('ban_owner_id');
            $struct = input('ban_struct_id');
            $status = input('ban_status');
            $address = input('ban_address');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $BanModel = new BanModel;
            $where = [];
            $where[] = ['ban_status','eq',1];
            $where[] = ['ban_inst_id','eq',$row['inst_id']];
            
            if($damage){
            	$where[] = ['ban_damage_id','eq',$damage];
            }
            if($owner){
            	$where[] = ['ban_owner_id','eq',$owner];
            }
            if($address){
            	$where[] = ['ban_address','like','%'.$address.'%'];
            }
            if($struct){
                $where[] = ['ban_struct_id','eq',$struct];
            }
            if($status !== null){
            	$where[] = ['ban_status','eq',$status];
            }
            //halt($where);
            $temps = $BanModel->field('ban_id,ban_number,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,(ban_civil_rent+ban_party_rent+ban_career_rent) as ban_rent,(ban_civil_area+ban_party_area+ban_career_area) as ban_area,ban_use_area,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_property_source,ban_units,ban_floors,(ban_civil_holds+ban_party_holds+ban_career_holds) as ban_holds,ban_status')->where($where)->page($page)->limit($limit)->order('ban_ctime desc')->select()->toArray();
            $result['data'] = [];
            foreach ($temps as $v) {
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['ban_status'] = $params['status'][$v['ban_status']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                $v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = $BanModel->where($where)->order('ban_ctime desc')->count('ban_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    public function houseList()
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
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $params = ParamModel::getCparams();
            $result['data']['params'] = $params;
            $use = input('house_use_id');
            $owner = input('ban_owner_id');
            $tenant = input('tenant_name');
            $status = input('house_status');
            $address = input('ban_address');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            
            $where = [];
            $where[] = ['d.ban_inst_id','eq',$row['inst_id']];
            
            if($use){
                $where[] = ['a.house_use_id','eq',$use];
            }
            if($owner){
                $where[] = ['d.ban_owner_id','eq',$owner];
            }
            if($address){
                $where[] = ['d.ban_address','like','%'.$address.'%'];
            }
            if($tenant){
                $where[] = ['c.tenant_name','like','%'.$tenant.'%'];
            }
            if($status !== null){
                $where[] = ['a.house_status','eq',$status];
            }else{
                $where[] = ['d.ban_status','eq',1]; 
            }
            //halt($where);
            $fields = 'a.house_id,a.house_number,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_area,a.house_diff_rent,a.house_pump_rent,a.house_pre_rent,a.house_oprice,a.house_door,a.house_is_pause,a.house_status,c.tenant_id,c.tenant_name,d.ban_units,d.ban_floors,d.ban_number,d.ban_address,d.ban_damage_id,d.ban_struct_id,d.ban_owner_id,d.ban_inst_id';
            //halt($where);
            $data = [];
            $temps = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();

            $result['data'] = [];
            foreach ($temps as $v) {
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['house_use_id'] = $params['uses'][$v['house_use_id']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['house_status'] = $params['status'][$v['house_status']];
                //$v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                //$v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.house_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    public function tenantList()
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
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $params = ParamModel::getCparams();
            //$result['data']['params'] = $params;
            $status = input('ban_status');
            $tenant = input('tenant');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $where = [];
            $where[] = ['tenant_inst_id','eq',$row['inst_id']];
            if($tenant){
                $where[] = ['a.tenant_name','like','%'.$tenant.'%'];
            }
            if($status !== null){
                $where[] = ['a.tenant_status','eq',$status];
            }else{
                $where[] = ['a.tenant_status','eq',1];   
            }
            $fields = 'a.tenant_id,tenant_inst_id,tenant_inst_pid,tenant_number,tenant_name,tenant_tel,tenant_card,sum(house_balance) as tenant_balance,a.tenant_status';
            $result = [];
            //halt($where);
            $temps = Db::name('tenant')->alias('a')->join('house b','a.tenant_id = b.tenant_id','left')->field($fields)->where($where)->page($page)->group('a.tenant_id')->order('tenant_ctime desc')->limit($limit)->select();
            //halt($temps);
            $result['data'] = [];
            foreach ($temps as $v) {
                // $v['tenant_inst_id'] = $params['insts'][$v['tenant_inst_id']];
                $v['tenant_status'] = $params['status'][$v['tenant_status']];
                // $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                // $v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                // $v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = Db::name('tenant')->alias('a')->join('house b','a.tenant_id = b.tenant_id','left')->where($where)->count('a.tenant_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }



    public function banDetail()
    {
        $key = input('get.key');
        $id = input('get.ban_id');
        $result = [];
        $result['code'] = 0;
        if(!$key || $id){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $BanModel = new BanModel;
            $result['data'] = $BanModel->get($id);

            //$result['data']['content'] = str_replace('/static/js/editor/', 'https://pro.ctnmit.com/static/js/editor/', htmlspecialchars_decode($result['data']['content']));
            //$result['data']['content'] = htmlspecialchars_decode($result['data']['content']);
            
            //$result['data']['cuid'] = Db::name('system_user')->where([['id','eq',$result['data']['cuid']]])->value('nick');
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
    		$result['data']['rent'] = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')],['house_id','eq',$houseID],['tenant_id','eq',$tenantInfo['tenant_id']]])->select();
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
    	$tenantInfo = TenantModel::where([['tenant_key','eq',$key]])->field('tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card,tenant_imgs')->find();
    	$where = [];


    	if($tenantInfo){

    		$fields = "a.rent_order_id,a.house_id,from_unixtime(a.ptime, '%Y-%m-%d %H:%i:%s') as ptime,a.tenant_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_floor_id,b.house_door,b.house_unit_id,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
         
         	$where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
         	$where[] = ['a.tenant_id','eq',$tenantInfo['tenant_id']];
         	if($houseID){
         		$where[] = ['a.house_id','eq',$houseID];
         	}

            $result['data']['rent'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();

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

}