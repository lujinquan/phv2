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
use app\system\admin\Admin;
use app\common\model\SystemExport;
use app\rent\model\Rent as RentModel;

/**
 * 租金欠缴
 */
class Unpaid extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RentModel = new RentModel;
            $where = $RentModel->checkWhere($getData,'unpaid');
            $fields = 'a.house_id,a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,e.member_id';
            $data = [];
            
            $subsql = Db::name('weixin_member_house')->field('house_id,member_id')->group('house_id')->having('count(house_id) > 1')->buildSql();

            // $temp_data = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->join([$subsql =>'e'],'a.house_id = e.house_id','left')->where($where);

            $temp_data = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join([$subsql =>'e'],'a.house_id = e.house_id','left')->field($fields)->where($where);

            if(isset($getData['is_bind_weixin'])){
                if($getData['is_bind_weixin'] == 1){
                    $temp_data->whereNotNull('e.member_id');
                    // $temp_data->where('e.dtime',0)->whereNotNull('e.member_id');

                }elseif($getData['is_bind_weixin'] === ''){
                    // halt($getData['is_bind_weixin']);
                    
                }else{
                    $temp_data->whereNull('e.member_id');
                    // $temp_data->whereOr('e.dtime','>',0)->whereNull('e.member_id');
                }
            }
            
            $data['data'] = $temp_data->page($page)->limit($limit)->order('a.rent_order_date desc')->select();
            // $data['data'] = ->whereNull('e.member_id')->page($page)->limit($limit)->order('a.rent_order_date desc')->select();
            // $last_sql = Db::name('rent_order')->getLastSql();
            // halt($last_sql);
            // ->join('weixin_member_house e','a.house_id = e.house_id','left')
            // foreach($data['data'] as &$v){
            //     $member_id = Db::name('weixin_member_house')->where([['house_id','eq',$v['house_id']],['dtime','eq',0]])->value('member_id');
            //     if(empty($member_id)){
            //         $v['member_id'] = '';
            //     }else{
            //         $v['member_id'] = $member_id;
            //     }
            // }
            // halt($data['data']);
            $data['count'] = $temp_data->count('a.rent_order_id');
            // $data['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('weixin_member_house e','a.house_id = e.house_id','left')->where($where)->count('a.rent_order_id');
            // 统计
            $totalRow = $temp_data->field('sum(a.rent_order_receive) as total_rent_order_receive, sum(a.rent_order_paid) as total_rent_order_paid, sum(a.rent_order_receive - a.rent_order_paid) as total_rent_order_unpaid, sum(a.rent_order_diff) as total_rent_order_diff, sum(a.rent_order_pump) as total_rent_order_pump, sum(a.rent_order_cut) as total_rent_order_cut, sum(b.house_pre_rent) as total_house_pre_rent, sum(b.house_cou_rent) as total_house_cou_rent')->find();
            if($totalRow){
                $data['total_rent_order_receive'] = $totalRow['total_rent_order_receive'];
                $data['total_rent_order_paid'] = $totalRow['total_rent_order_paid'];
                $data['total_rent_order_unpaid'] = $totalRow['total_rent_order_unpaid'];
                $data['total_rent_order_diff'] = $totalRow['total_rent_order_diff'];
                $data['total_rent_order_pump'] = $totalRow['total_rent_order_pump'];
                $data['total_rent_order_cut'] = $totalRow['total_rent_order_cut'];
                $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
                $data['total_house_cou_rent'] = $totalRow['total_house_cou_rent'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 缴费，可以缴纳部分
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-02 14:10:46
     * @return  返回值  
     * @version 版本  1.0
     */
    public function pay()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            if(floatval($data['pay_rent']) <= 0){
                $this->error('缴纳金额必须大于0');
            }
            $RentModel = new RentModel;
            $row = $RentModel->where([['rent_order_id','eq',$data['rent_order_id']]])->field('(rent_order_receive-rent_order_paid) as rent_order_unpaid')->find();
            if($row['rent_order_unpaid'] < $data['pay_rent']){
                $this->error('缴纳金额不能大于欠缴金额');
            }
            $RentModel = new RentModel;
            $res = $RentModel->pay($data['rent_order_id'],$data['pay_rent']);
            if (!$res) {
                $this->error($RentModel->getError());
            }
            return $this->success('缴费成功');
        }
        $id = input('param.id/d');
        $RentModel = new RentModel;      
        $row = $RentModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     *  批量缴费
     */
    public function payList()
    {
        $ids = $this->request->param('id/a'); 

        $user_info = Db::name('system_user')->where([['id','eq',ADMIN_ID]])->field('weixin_member_id')->find();
        //halt($user_info);
        if (empty($user_info['weixin_member_id'])) {
            $this->error('当前管理员未绑定微信会员！');
            return false;
        }

        $RentModel = new RentModel;      
        $res = $RentModel->payList($ids);
        // halt($res->getError());
        if($res){
            $this->success('缴费成功，本次缴费'.$res.'条账单！');
        }else{
            $this->error('缴费失败');
        }
    }

    /**
     *  批量撤回
     */
    /*public function payBackList()
    {
        $ids = $this->request->param('id/a'); 
        $RentModel = new RentModel;
        $res = $RentModel->payBackList($ids,date('Y-m'),'unpaid');
        if($res){
            $this->success('撤回成功，本次撤回'.$res.'条账单！');
        }else{
            $this->error('撤回失败，请检查账单支付日期是否为本月，是否为房管员现金支付订单');
        }
    }*/


    public function detail()
    {
        $id = input('param.id/d');
        $RentModel = new RentModel;      
        $row = $RentModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function export()
    {   
        if ($this->request->isAjax()) {
            $getData = $this->request->post();
            $rentModel = new RentModel;
            $where = $rentModel->checkWhere($getData,'unpaid');
            $fields = 'a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id,e.member_id';
            
            $subsql = Db::name('weixin_member_house')->field('house_id,member_id')->group('house_id')->having('count(house_id) > 1')->buildSql();

            // $temp_data = Db::name('house')->alias('a')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->join([$subsql =>'e'],'a.house_id = e.house_id','left')->where($where);

            $temp_data = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join([$subsql =>'e'],'a.house_id = e.house_id','left')->field($fields)->where($where);

            if(isset($getData['is_bind_weixin'])){
                if($getData['is_bind_weixin'] == 1){
                    $temp_data->whereNotNull('e.member_id');
                    // $temp_data->where('e.dtime',0)->whereNotNull('e.member_id');

                }elseif($getData['is_bind_weixin'] === ''){
                    // halt($getData['is_bind_weixin']);
                    
                }else{
                    $temp_data->whereNull('e.member_id');
                    // $temp_data->whereOr('e.dtime','>',0)->whereNull('e.member_id');
                }
            }
            
            $tableData = $temp_data->field($fields)->order('a.rent_order_date desc')->select();
            foreach ($tableData as $k => &$v) {
                // $member_id = Db::name('weixin_member_house')->where([['house_id','eq',$v['house_id']],['dtime','eq',0]])->value('member_id');
                if(empty($v['member_id'])){
                    $v['is_bind_weixin'] = '否';
                }else{
                    $v['is_bind_weixin'] = '是';
                }
                // if($v['tenant_id']){ //如果当前房屋已经绑定租户
                //     $last_print_time = Db::name('change_lease')->where([['house_id','eq',$v['house_id']],['change_status','eq',1],['tenant_id','eq',$v['tenant_id']]])->order('id desc')->value("from_unixtime(last_print_time, '%Y-%m-%d %H:%i:%s') as last_print_time");
                //     $v['last_print_time'] = $last_print_time?$last_print_time:'';
                // }else{
                //     $v['last_print_time'] = '';
                // }   
                unset($v['member_id']);
            }
            // $tableData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.rent_order_date desc')->select();

            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '租金订单号', 'field' => 'rent_order_number', 'width' => 24,'type' => 'string'),
                    array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                    array('title' => '账单期', 'field' => 'rent_order_date', 'width' => 12,'type' => 'string'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                   
                    array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                    
                    array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                    array('title' => '是否绑定微信', 'field' => 'is_bind_weixin', 'width' => 12,'type' => 'string'),

                    array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                    array('title' => '规定租金', 'field' => 'house_pre_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '计算租金', 'field' => 'house_cou_rent', 'width' => 12,'type' => 'number'),
                    array('title' => '减免', 'field' => 'rent_order_cut', 'width' => 12,'type' => 'number'),
                    array('title' => '租差', 'field' => 'rent_order_diff', 'width' => 12,'type' => 'number'),
                    array('title' => '泵费', 'field' => 'rent_order_pump', 'width' => 12,'type' => 'number'),
                    array('title' => '协议租金', 'field' => 'rent_order_diff', 'width' => 12,'type' => 'number'),
                    array('title' => '应收租金', 'field' => 'rent_order_receive', 'width' => 12,'type' => 'number'),
                    array('title' => '已缴租金', 'field' => 'rent_order_paid', 'width' => 12,'type' => 'number'),
                    array('title' => '欠缴租金', 'field' => 'rent_order_unpaid', 'width' => 12,'type' => 'number'),
                    array('title' => '是否已开发票', 'field' => 'is_invoice', 'width' => 24,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '租金欠缴数据',
                    'Title' => '租金欠缴数据',
                ];
                
                return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
            }else{
                $result = [];
                $result['code'] = 0;
                $result['msg'] = '数据为空！';
                return json($result); 
            }
            
        }
        
    }


}