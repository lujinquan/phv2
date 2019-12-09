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
use app\common\controller\Common;
use app\system\model\SystemUser as UserModel;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;


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
            }else{
                $where[] = ['ban_status','eq',1];
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
            $tenant = input('tenant_name');
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

    public function rentList()
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
            $type = input('type');
            $use = input('house_use_id');
            $owner = input('ban_owner_id');
            $tenant = input('tenant_name');
            $status = input('house_status');
            $address = input('ban_address');
            $date = input('rent_order_date');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            
            $where = [];
            $where[] = ['d.ban_inst_id','eq',$row['inst_id']];
            if($type){
                $where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
                $where[] = ['a.ptime','>',0];
            }else{
               $where[] = ['rent_order_paid','exp',Db::raw('<rent_order_receive')]; 
            }
            
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
            if($date){
                $tempDate = str_replace('-', '', $date);
                $where[] = ['rent_order_date','eq',$tempDate];
            }
            //halt($where);
            $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.ptime,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $data = [];
            $temps = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.rent_order_date desc')->select();
           

            $result['data'] = [];
            foreach ($temps as $v) {
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['house_use_id'] = $params['uses'][$v['house_use_id']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['rent_order_date'] = substr($v['rent_order_date'],0,4).'年'.substr($v['rent_order_date'],4,2).'月01日';
                if($v['ptime']){
                    $v['ptime'] = date('Y年m月d日',$v['ptime']);
                }
                //$v['house_status'] = $params['status'][$v['house_status']];
                //$v['ban_struct_id'] = $params['structs'][$v['ban_struct_id']];
                //$v['ban_damage_id'] = $params['damages'][$v['ban_damage_id']];
                $result['data'][] = $v;
            }
            $result['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
//halt($result);
        return json($result); 

        
    }

    public function rentDetail()
    {
        $key = input('get.key');
        $id = input('get.rent_order_id');
        $result = [];
        $result['code'] = 0;
        if(!$key || !$id){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $BanModel = new BanModel;

            $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.ptime,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id';
            $temp = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();

            $params = ParamModel::getCparams();

            $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
            $temp['house_use_id'] = $params['uses'][$temp['house_use_id']];
            $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            $temp['rent_order_date'] = substr($temp['rent_order_date'],0,4).'年'.substr($temp['rent_order_date'],4,2).'月01日';
            if($temp['ptime']){
                $temp['ptime'] = date('Y年m月d日',$temp['ptime']);
            }

            $result['data'] = $temp;
//halt($result['data']);
                      
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
        if(!$key || !$id){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $BanModel = new BanModel;
            $temp = $BanModel->get($id);
            $params = ParamModel::getCparams();

            $temp['ban_rent'] = bcaddMerge($temp['ban_civil_rent'],$temp['ban_party_rent'],$temp['ban_career_rent']);
            $temp['ban_area'] = bcaddMerge($temp['ban_civil_area'],$temp['ban_party_area'],$temp['ban_career_area']);
            $temp['ban_oprice'] = bcaddMerge($temp['ban_civil_oprice'],$temp['ban_party_oprice'],$temp['ban_career_oprice']);
            $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
            $temp['ban_status'] = $params['status'][$temp['ban_status']];
            $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            $temp['ban_imgs'] = SystemAnnex::changeFormat($temp['ban_imgs'],$complete = true);
            $temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');

            $result['data'] = $temp;     
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }

    public function houseDetail()
    {
        $key = input('get.key');
        $id = input('get.house_id');
        $result = [];
        $result['code'] = 0;
        if(!$key || !$id){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $HouseModel = new HouseModel;
            $temp = HouseModel::with(['ban','tenant'])->get($id);
            $params = ParamModel::getCparams();

            $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
            $temp['house_use_id'] = $params['uses'][$temp['house_use_id']];
            $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            // $temp['ban_imgs'] = SystemAnnex::changeFormat($temp['ban_imgs'],$complete = true);
            // $temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');
            $temp['rooms'] = $HouseModel->get_house_renttable($id);
//halt($roomTables);
            $result['data'] = $temp;
//halt($result['data']);  
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }

    public function tenantDetail()
    {
        $key = input('get.key');
        $id = input('get.tenant_id');
        $result = [];
        $result['code'] = 0;
        if(!$key || !$id){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        //$id = str_coding($key,'DECODE');
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        if($row){
            $TenantModel = new TenantModel;
            $temp = $TenantModel->get($id);
            $params = ParamModel::getCparams();

            $temp['tenant_inst_id'] = $params['insts'][$temp['tenant_inst_id']];
            // $temp['ban_status'] = $params['status'][$temp['ban_status']];
            // $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            // $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            // $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            $temp['tenant_imgs'] = SystemAnnex::changeFormat($temp['tenant_imgs'],$complete = true);
            //$temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');

            $result['data'] = $temp;
//halt($result['data']);
                      
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
    public function userInfo() 
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
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,role_id,nick,mobile')->find();
        if($row){
            $params = ParamModel::getCparams();
            $row['role'] = Db::name('system_role')->where([['id','eq',$row['role_id']]])->value('name');
            $row['inst_id'] = $params['insts'][$row['inst_id']];
            $result['data'] = $row;
//halt($result['data']);
                      
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }


}