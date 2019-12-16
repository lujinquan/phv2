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
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeBan as ChangeBanModel;
use app\deal\model\ChangeHouse as ChangeHouseModel;
use app\deal\model\ChangeCancel as ChangeCancelModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;
use app\deal\model\ChangeName as ChangeNameModel;
use app\deal\model\ChangeNew as ChangeNewModel;
use app\deal\model\ChangeOffset as ChangeOffsetModel;
use app\deal\model\ChangePause as ChangePauseModel;
use app\deal\model\ChangeRentAdd as ChangeRentAddModel;
use app\deal\model\ChangeUse as ChangeUseModel;
use app\deal\model\ChangeInst as ChangeInstModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\deal\model\ChangeCutYear as ChangeCutYearModel;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;


/**
 * 微信小程序高管版接口
 */
class Weixinleaderadmin extends Controller 
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
        	if(in_array($row['role_id'], [4,5,6])){
        		$result['msg'] = '角色异常！';
        		return json($result);
        	}
        	// 验证登录账户是否为房管员
        	if($row['inst_level'] != 1){
        		$result['msg'] = '非区公司用户！';
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
        //halt(get_domain());
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
            
            $where[] = ['ban_inst_id','in',config('inst_ids')[$row['inst_id']]];
            
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
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$row['inst_id']]];
            
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
            $status = input('tenant_status');
            $tenant = input('tenant_name');
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);

            $where = [];
            $where[] = ['tenant_inst_id','in',config('inst_ids')[$row['inst_id']]];
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

    public function processList()
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
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $type = input('type',1);
            $changetype = input('change_type');
            $inst = input('ban_inst_id');

            $ProcessModel = new ProcessModel;
            if($changetype){
                $where[] = ['change_type','eq',$changetype];
            }else{
                $where[] = ['change_type','in',[1,3,4,7,8,9,10,11,13,14,17]];
            }
            $insts = config('inst_ids');
            if($inst){
                $where[] = ['d.ban_inst_id','in',$insts[$inst]];
            }else{
                $instid = $inst?$data['ban_inst_id']:$row['inst_id'];
                $where[] = ['d.ban_inst_id','in',$insts[$instid]];
            }
            $where[] = ['a.status','eq',1];

            $fields = "a.id,a.change_id,a.change_type,a.print_times,a.change_order_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_desc,a.curr_role,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $result = [];
            $result['data'] = $dataTemps = [];
            $temps = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ctime asc')->select();
            foreach($temps as $k => $v){
                $v['ban_inst_id'] = $params['insts'][$v['ban_inst_id']];
                $v['ban_owner_id'] = $params['owners'][$v['ban_owner_id']];
                $v['change_type_name'] = $params['changes'][$v['change_type']];
                if($type == 1){ // 
                    if($v['curr_role'] == $row['role_id']){
                        array_unshift($dataTemps,$v);
                    }
                }
                // else{
                //     if($v['curr_role'] != $row['role_id']){
                //         array_unshift($dataTemps,$v);
                //     }
                // }
            }

            $result['data'] = array_slice($dataTemps, ($page - 1) * $limit, $limit);
            $result['count'] = count($dataTemps);   

        
            $result['pages'] = ceil($result['count'] / $limit);
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }

        return json($result); 

        
    }

    public function processDetail()
    {
        $key = input('get.key');
        $id = input('get.id/d');
        $change_type = input('param.change_type/d');
        $result = [];
        $result['code'] = 0;
        if(!$key || !$id || !$change_type){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,nick,mobile')->find();

        $userRoles = UserModel::alias('a')->join('system_role b','a.role_id = b.id','left')->column('a.id,a.nick,a.role_id,b.name as role_name');
        //halt(session('systemusers'));

        if($row){
            $params = ParamModel::getCparams();
            // 显示对应的审批页面
            $id = input('param.id/d');
            
            if(!$change_type || !$id){
                return $this->error('参数错误！');
            }
            $PorcessModel = new ProcessModel;
            $result = [];
            $temps = $PorcessModel->detail($change_type,$id);
            $temps['row'] = $temps['row']->toArray();
            switch ($change_type) {
                case 1: // 租金减免
                    $temps['row']['cut_type'] = $params['cuttypes'][$temps['row']['cut_type']];
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];
                    $temps['row']['house_use_id'] = $params['uses'][$temps['row']['house_use_id']];
                    break;
                case 3: // 暂停计租
                    if($temps['row']['data_json']){
                        foreach ($temps['row']['data_json'] as $a => $b) {
                            $temps['row']['data_json'][$a]['house_use_id'] = $params['uses'][$b['house_use_id']];
                        }
                    }
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];
                    $temps['row']['ban_info']['ban_struct_id'] = $params['structs'][$temps['row']['ban_info']['ban_struct_id']];
                    $temps['row']['ban_info']['ban_damage_id'] = $params['damages'][$temps['row']['ban_info']['ban_damage_id']];
                    break;
                case 4: // 陈欠核销
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];

                    $temps['row']['ban_info']['ban_inst_id'] = $params['insts'][$temps['row']['ban_info']['ban_inst_id']];
                    if($temps['row']['data_json']){
                        foreach ($temps['row']['data_json'] as $a => $b) {
                            $temps['row']['data_json'][$a]['house_use_id'] = $params['uses'][$b['house_use_id']];
                            $temps['row']['data_json'][$a]['ban_owner_id'] = $params['owners'][$b['ban_owner_id']]; 
                        }
                    }
                    break;
                case 7: // 新发租
                    $temps['row']['new_type'] = $params['news'][$temps['row']['new_type']];
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];
                    $temps['row']['house_info']['house_use_id'] = $params['uses'][$temps['row']['house_info']['house_use_id']];
                    break;
                case 8: // 注销
                    $temps['row']['cancel_type'] = $params['cancels'][$temps['row']['cancel_type']];
                    break;
                case 9: // 房屋调整
                    
                    break;
                case 10: // 管段调整
                    $temps['row']['old_inst_id'] = $params['insts'][$temps['row']['old_inst_id']];
                    $temps['row']['new_inst_id'] = $params['insts'][$temps['row']['new_inst_id']];
                    if($temps['row']['data_json']){
                        foreach ($temps['row']['data_json'] as $a => $b) {
                            $temps['row']['data_json'][$a]['ban_inst_id'] = $params['insts'][$b['ban_inst_id']];
                        }
                    }
                    break;
                case 11: // 租金追加调整
                    $temps['row']['ban_info']['ban_owner_id'] = $params['owners'][$temps['row']['ban_info']['ban_owner_id']];

                    $temps['row']['ban_info']['ban_inst_id'] = $params['insts'][$temps['row']['ban_info']['ban_inst_id']];
                    break;
                case 13: // 使用权变更
                    $temps['row']['change_use_type'] = $params['usetypes'][$temps['row']['change_use_type']];
                    break;
                case 14: // 楼栋调整
                    $temps['row']['ban_change_id_name'] = $params['ban_change_ids'][$temps['row']['ban_change_id']];
                    if($temps['row']['old_damage']){
                        $temps['row']['old_damage'] = $params['damages'][$temps['row']['old_damage']];
                    }
                    if($temps['row']['new_damage']){
                        $temps['row']['new_damage'] = $params['damages'][$temps['row']['new_damage']];
                    }
                    if($temps['row']['old_struct']){
                        $temps['row']['old_struct'] = $params['structs'][$temps['row']['old_struct']];
                    }
                    if($temps['row']['new_struct']){
                        $temps['row']['new_struct'] = $params['structs'][$temps['row']['new_struct']];
                    }
                    $temps['row']['ban_info']['ban_inst_id'] = $params['insts'][$temps['row']['ban_info']['ban_inst_id']];
                    break;
                case 17: // 别字更正
                    
                    break;
                default:
                    break;
            }
            
            
            if($temps['row']['change_imgs']){
                foreach ($temps['row']['change_imgs'] as $k => $v) {
                    $temps['row']['change_imgs'][$k]['file'] = get_domain().$v['file'];
                }
            }        
            if($temps['row']['child_json']){
                foreach ($temps['row']['child_json'] as $a => $b) {
                    $temps['row']['child_json'][$a]['role_name'] = $userRoles[$b['uid']]['role_name'];
                    $temps['row']['child_json'][$a]['nick'] = $userRoles[$b['uid']]['nick'];
                }
            }

            $result['data'] = $temps;      
            $result['code'] = 1;
            $result['msg'] = '获取成功！';
        }else{
            $result['msg'] = '参数错误！';
        }
        return json($result);  
    }

    public function process()
    {
        $key = input('get.key');
        $id = input('param.id/d');
        $change_type = input('param.change_type/d');
        $result = [];
        $result['code'] = 0;
        if(!$key || !$id || !$change_type){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        $key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
        $row = UserModel::where([['user_key','eq',$key]])->field('id,inst_id,role_id,nick,mobile')->find();
//halt($row);
        define('ADMIN_ID', $row['id']);
        define('ADMIN_ROLE', $row['role_id']);


        if(!$row){
            $result['msg'] = '参数错误！';
            return json($result);
        }
        // 显示对应的审批页面
        //$id = input('param.id/d');
        //$change_type = input('param.change_type/d');
        
        // if(!$change_type || !$id){
        //     return $this->error('参数错误！');
        // }

        //检查当前页面或当前表单，是否允许被请求？
        $PorcessModel = new ProcessModel;
        $rowProcess = $PorcessModel->where([['change_id','eq',$id],['change_type','eq',$change_type]])->find();
        //dump($rowProcess);halt(ADMIN_ROLE);
        if($rowProcess['curr_role'] != ADMIN_ROLE){
            //return $this->error('审批状态错误');
            $result['msg'] = '审批状态错误!';
            return json($result);
        }
        if($rowProcess['ftime'] > 0){
            $result['msg'] = '异动已经完成，请刷新重试！';
            return json($result);
            //return $this->error('异动已经完成，请刷新重试！');
        }

        //if($this->request->isPost()) {
            $data = $this->request->get();
            //halt($data);
            if($change_type == 18 && ADMIN_ROLE == 6){
                $ChangeModel = new ChangeLeaseModel;
                $changeRow = $ChangeModel->where([['id','eq',$id]])->find();
                if(!$changeRow['print_times']){
                    $result['msg'] = '请先打印租约后再审批！';
                    return json($result);
                    //return $this->error('请先打印租约后再审批！');
                }
            }

            // 如果审批失败，数据回滚
            Db::transaction(function () {
                $model = new ProcessModel;
                $change_type = input('param.change_type/d');
                $data = $this->request->get();
                $model->process($change_type,$data); //$data必须包含子表的id
            });
            // if (!$res) {
            //     return $this->error('审批失败');
            // }
            $result['msg'] = '审批成功！';
            $result['code'] = 1;
            return json($result);
        //}


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
            $temp['ban_rent'] = bcaddMerge([$temp['ban_civil_rent'],$temp['ban_party_rent'],$temp['ban_career_rent']]);
            $temp['ban_area'] = bcaddMerge([$temp['ban_civil_area'],$temp['ban_party_area'],$temp['ban_career_area']]);
            $temp['ban_oprice'] = bcaddMerge([$temp['ban_civil_oprice'],$temp['ban_party_oprice'],$temp['ban_career_oprice']]);
            $temp['ban_inst_id'] = $params['insts'][$temp['ban_inst_id']];
            $temp['ban_status'] = $params['status'][$temp['ban_status']];
            $temp['ban_owner_id'] = $params['owners'][$temp['ban_owner_id']];
            $temp['ban_struct_id'] = $params['structs'][$temp['ban_struct_id']];
            $temp['ban_damage_id'] = $params['damages'][$temp['ban_damage_id']];
            $temp['ban_imgs'] = SystemAnnex::changeFormat($temp['ban_imgs'],$complete = true);
            $temp['cuid'] = Db::name('system_user')->where([['id','eq',$temp['ban_cuid']]])->value('nick');

            $result['data'] = $temp;
//halt($result['data']);
                      
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