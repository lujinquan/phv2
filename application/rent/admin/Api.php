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
            $limit = input('param.limit/d', 10);
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
            $fields = "a.id,a.change_id,a.change_type,a.curr_role,from_unixtime(a.ctime, '%Y-%m-%d') as ctime";
            $data = $result = [];
            
            $temps = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ctime asc')->select();

            foreach($temps as $k => $v){
                if($v['curr_role'] == session('admin_user.role_id')){
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

   
}