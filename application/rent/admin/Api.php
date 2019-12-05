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

namespace app\rent\admin;

use think\Db;
use app\common\controller\Common;
use app\rent\model\Rent as RentModel;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 首页的第一部分
     * @param ban_inst_id 机构id 
     * @param ctime 月份
     * @return json 10：市属、2区属、5自管、11所有
     */
    public function indexPartOne() 
    {
        $getData = $this->request->get();
    	// 检索楼栋机构
        $insts = config('inst_ids');
        if(isset($getData['ban_inst_id']) && $getData['ban_inst_id']){
            $where[] = ['d.ban_inst_id','in',$insts[$getData['ban_inst_id']]];
        }else{
            $instid = (isset($getData['ban_inst_id']) && $getData['ban_inst_id'])?$getData['ban_inst_id']:session('admin_user.inst_id');
            $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        }
        // 检索月份时间
        if(isset($getData['ctime']) && $getData['ctime']){
            $startTime = str_replace('/', '', $getData['ctime']);
            $where[] = ['a.rent_order_date','eq',$startTime];
        }
        $fields = 'sum(a.rent_order_receive) as rent_order_receives,sum(a.rent_order_paid) as rent_order_paids,b.house_use_id,d.ban_owner_id';
        $data = [];
        $temp = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->group('b.house_use_id,d.ban_owner_id')->where($where)->select();
        $result = [];
        foreach($temp as $t){
        	$result[$t['ban_owner_id']][$t['house_use_id']] = [
        		'rent_order_receives' => (float)$t['rent_order_receives'],
        		'rent_order_paids' => (float)$t['rent_order_paids'],
        	];
        }
        $ownertypes = [1,2,3,5,7]; //市、区、代、自、托
        foreach ($ownertypes as $owner) {
            for ($i=1;$i<4;$i++ ) {
                if(!isset($result[$owner][$i])){
                    $result[$owner][$i] = [
                        'rent_order_receives' => 0, 
                        'rent_order_paids' => 0, 
                    ];
                }
            }
        }
        $result[10][1]['rent_order_receives'] = $result[1][1]['rent_order_receives'] + $result[3][1]['rent_order_receives'] + $result[7][1]['rent_order_receives'];
        $result[10][2]['rent_order_receives'] = $result[1][2]['rent_order_receives'] + $result[3][2]['rent_order_receives'] + $result[7][2]['rent_order_receives'];
        $result[10][3]['rent_order_receives'] = $result[1][3]['rent_order_receives'] + $result[3][3]['rent_order_receives'] + $result[7][3]['rent_order_receives'];
        $result[10][1]['rent_order_paids'] = $result[1][1]['rent_order_paids'] + $result[3][1]['rent_order_paids'] + $result[7][1]['rent_order_paids'];
        $result[10][2]['rent_order_paids'] = $result[1][2]['rent_order_paids'] + $result[3][2]['rent_order_paids'] + $result[7][2]['rent_order_paids'];
        $result[10][3]['rent_order_paids'] = $result[1][3]['rent_order_paids'] + $result[3][3]['rent_order_paids'] + $result[7][3]['rent_order_paids'];

        $result[11][1]['rent_order_receives'] = $result[1][1]['rent_order_receives'] + $result[2][1]['rent_order_receives'] + $result[3][1]['rent_order_receives'] + $result[5][1]['rent_order_receives'] + $result[7][1]['rent_order_receives'];
        $result[11][2]['rent_order_receives'] = $result[1][2]['rent_order_receives'] + $result[2][2]['rent_order_receives'] + $result[3][2]['rent_order_receives'] + $result[5][2]['rent_order_receives'] + $result[7][2]['rent_order_receives'];
        $result[11][3]['rent_order_receives'] = $result[1][3]['rent_order_receives'] + $result[2][3]['rent_order_receives'] + $result[3][3]['rent_order_receives'] + $result[5][3]['rent_order_receives'] + $result[7][3]['rent_order_receives'];
        $result[11][1]['rent_order_paids'] = $result[1][1]['rent_order_paids'] + $result[2][1]['rent_order_paids'] + $result[3][1]['rent_order_paids'] + $result[5][1]['rent_order_paids'] + $result[7][1]['rent_order_paids'];
        $result[11][2]['rent_order_paids'] = $result[1][2]['rent_order_paids'] + $result[2][2]['rent_order_paids'] + $result[3][2]['rent_order_paids'] + $result[5][2]['rent_order_paids'] + $result[7][2]['rent_order_paids'];
        $result[11][3]['rent_order_paids'] = $result[1][3]['rent_order_paids'] + $result[2][3]['rent_order_paids'] + $result[3][3]['rent_order_paids'] + $result[5][3]['rent_order_paids'] + $result[7][3]['rent_order_paids'];
        $data['data'] = $result;
        $data['code'] = 0;
        $data['msg'] = '获取成功';
        return json($data);
    }

    /**
     * 首页的第二部分
     * @param ctime 月份
     * @return json 
     */
    public function indexPartTwo()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->get();
            $where[] = ['a.status','eq',1];
            // 检索申请时间
	        if(isset($getData['ctime']) && $getData['ctime']){
	            $startTime = strtotime($getData['ctime']);
	            $where[] = ['a.ctime','between time',[$startTime,$startTime+3600*24]];
	        }
            if(isset($getData['change_type']) && $getData['change_type']){
                $where[] = ['change_type','eq',$getData['change_type']];
            }
            // 检索楼栋机构
            $insts = config('inst_ids');
            if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
                $where[] = ['d.ban_inst_id','in',$insts[$data['ban_inst_id']]];
            }else{
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:session('admin_user.inst_id');
                $where[] = ['d.ban_inst_id','in',$insts[$instid]];
            }
            $fields = "a.id,a.change_id,a.print_times,a.change_type,a.curr_role,from_unixtime(a.ctime, '%Y-%m-%d') as ctime";
            $data = $result = [];
            
            $temps = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ctime asc')->select();

            foreach($temps as $k => $v){
                // 如果业务审批角色 = 当前登录角色，且当前角色不是房管员
                if($v['curr_role'] == session('admin_user.role_id') && session('admin_user.role_id') != 4){
                	$result[] = $v;
                }
            }
            $data['data'] = array_slice($result, ($page- 1) * $limit, $limit);
            $data['count'] = count($result);
            $data['code'] = 0;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

    /**
     * 首页的第二部分
     * @param ctime 月份
     * @return json 
     */
    public function indexPartThree()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->get();
            $where[] = ['change_status','eq',1];
            $where[] = ['change_type','in',[3,7,8]];
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                $startTime = str_replace('/', '', $getData['ctime']);
                $where[] = ['order_date','eq',$startTime];
                //$where[] = ['order_date','eq',201911];
            }else{
                $where[] = ['order_date','eq',date('Ym')];
                //$where[] = ['order_date','eq',201911];
            }
// 暂停计租，新发组，注销，
            // if(isset($getData['change_type']) && $getData['change_type']){
            //     
            // }
            // 检索楼栋机构
            $insts = config('inst_ids');
            if(isset($data['inst_id']) && $data['inst_id']){
                $where[] = ['inst_id','in',$insts[$data['inst_id']]];
            }else{
                $instid = (isset($data['inst_id']) && $data['inst_id'])?$data['inst_id']:session('admin_user.inst_id');
                $where[] = ['inst_id','in',$insts[$instid]];
            }
            
            $data = $result = [];
            
            $result = Db::name('change_table')->group('change_type')->where($where)->column('change_type,sum(change_rent) as change_rents');

            if(!isset($result[3])){
               $result[3] = 0; 
            }
            if(!isset($result[7])){
               $result[7] = 0; 
            }
            if(!isset($result[8])){
               $result[8] = 0; 
            }
//halt($result);

            // foreach($temps as $k => $v){
            //     // 如果业务审批角色 = 当前登录角色，且当前角色不是房管员
            //     if($v['curr_role'] == session('admin_user.role_id') && session('admin_user.role_id') != 4){
            //         $result[] = $v;
            //     }
            // }
            $data['data'] = $result;
            //$data['count'] = count($result);
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        }  
    }

    /**
     * 首页的第二部分
     * @param ctime 月份
     * @return json 
     */
    public function indexPartFour()
    {
        //if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 5);
            $getData = $this->request->get();
            $where = $where1 = $where2 =[];
            $where[] = ['is_deal','eq',1];
            // $where[] = ['change_type','in',[3,7,8]];
            // 检索月份时间
            if(isset($getData['ctime']) && $getData['ctime']){
                $startTime = str_replace('/', '', $getData['ctime']);
                $where1[] = ['rent_order_date','eq',$startTime];
                $where2[] = ['rent_order_date','<',$startTime];
            }else{
                $where1[] = ['rent_order_date','eq',date('Ym')];
                $where2[] = ['rent_order_date','<',date('Ym')];
            }
            // 检索楼栋机构
            $insts = config('inst_ids');
            if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
                $where[] = ['ban_inst_id','in',$insts[$data['ban_inst_id']]];
            }else{
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:session('admin_user.inst_id');
                $where[] = ['ban_inst_id','in',$insts[$instid]];
            }
            
            $data = $result = [];
            
            $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->where($where1)->field('sum(rent_order_receive-rent_order_paid) as rent_unpaids,sum(rent_order_paid) as rent_paids')->find();
            if(!$row){
                $result['rent_paids'] = 0;
                $result['rent_unpaids'] = 0;
            }else{
                $result['rent_paids'] = $row['rent_paids'];
                $result['rent_unpaids'] = $row['rent_unpaids'];
            }

            $rent_before_unpaids = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->where($where2)->value('sum(rent_order_receive-rent_order_paid) as rent_unpaids');
//halt($rent_order_before_unpaids);
            
            if(!$rent_before_unpaids){
                $result['rent_before_unpaids'] = 0; 
            }else{
                $result['rent_before_unpaids'] = $rent_before_unpaids; 
            }
            //$result['rent_before_unpaids'] = 20000;
//halt($result);

            // foreach($temps as $k => $v){
            //     // 如果业务审批角色 = 当前登录角色，且当前角色不是房管员
            //     if($v['curr_role'] == session('admin_user.role_id') && session('admin_user.role_id') != 4){
            //         $result[] = $v;
            //     }
            // }
            $data['data'] = $result;
            //$data['count'] = count($result);
            $data['code'] = 1;
            $data['msg'] = '获取成功';
            return json($data);
        //}  
    }

   
}