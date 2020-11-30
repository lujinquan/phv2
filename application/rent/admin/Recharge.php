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
use app\house\model\House as HouseModel;
use app\rent\model\Invoice as InvoiceModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\Recharge as RechargeModel;
use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;

/**
 * 账户充值
 */
class Recharge extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RechargeModel = new RechargeModel;
            $where = $RechargeModel->checkWhere($getData);

            $fields = "a.id,a.house_id,a.invoice_id,a.tenant_id,a.pay_rent,a.yue,a.pay_way,from_unixtime(a.ptime, '%Y-%m-%d %H:%i:%S') as ptime,a.recharge_status,b.house_use_id,b.house_number,b.house_pre_rent,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('ptime desc')->select();
            $data['count'] = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            // 统计
            $totalRow = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(a.pay_rent) as total_pay_rent')->find();
            if($totalRow){
                $data['total_pay_rent'] = $totalRow['total_pay_rent'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Recharge.add');
            if($result !== true) {
                return $this->error($result);
            }
            // if(round($data['pay_rent'],2) == 0){
            //     return $this->error('充值余额不能为零,或金额小于1分钱');
            // }
            if(round($data['pay_rent'],2) <= 0){
                return $this->error('充值余额不能为零,或为负数');
            }
            $RechargeModel = new RechargeModel;
            // 数据过滤
            $filData = $RechargeModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            $filData['recharge_status'] = 1;

            $house_info = HouseModel::where([['house_id','eq',$filData['house_id']]])->find();
            $filData['yue'] = bcaddMerge([$house_info['house_balance'],$filData['pay_rent']]);
            if($filData['yue'] < 0){
                return $this->error('充值后余额不能为负');
            }
            $filData['trade_type'] = 'CASH';
            $curr_time = time();
            $filData['ptime'] = $curr_time;
            $filData['act_ptime'] = $curr_time;
            if($house_info['house_use_id'] == 1){
                $transaction_id = '5000000000' . get_msec_to_mescdate(get_msec_time()) . random(1);
                $filData['transaction_id'] = $transaction_id;
            }
            // 模拟线上支付
        
            // if ( true ) {
            //     $user_info = Db::name('system_user')->where([['id','eq',ADMIN_ID]])->field('weixin_member_id')->find();
            //     //halt($user_info);
            //     if (empty($user_info['weixin_member_id'])) {
            //         $this->error = '当前管理员未绑定微信会员！';
            //         return false;
            //     }
            //     $weixin_member_id = explode(',',$user_info['weixin_member_id']);
            //     //$this->pay_for_rent($row['house_id'], $pay_rent, ADMIN_ID, [$id]);
            //     $this->part_order_to_pay($id, ADMIN_ID, $weixin_member_id[0] ,$pay_rent);
            // } else {
            //     // 缴费生成一条条子订单
            //     $RentOrderChildModel = new RentOrderChildModel;
            //     $RentOrderChildModel->rent_order_id = $id;
            //     $RentOrderChildModel->house_id = $row['house_id'];
            //     $RentOrderChildModel->tenant_id = $row['tenant_id'];
            //     $RentOrderChildModel->rent_order_paid = $pay_rent;
            //     $RentOrderChildModel->rent_order_number = $row->rent_order_number;
            //     $RentOrderChildModel->rent_order_receive = $row->rent_order_receive;
            //     $RentOrderChildModel->rent_order_pre_rent = $row->rent_order_pre_rent;
            //     $RentOrderChildModel->rent_order_cou_rent = $row->rent_order_cou_rent;
            //     $RentOrderChildModel->rent_order_cut = $row->rent_order_cut;
            //     $RentOrderChildModel->rent_order_diff = $row->rent_order_diff;
            //     $RentOrderChildModel->rent_order_pump = $row->rent_order_pump;
            //     $RentOrderChildModel->rent_order_date = $row->rent_order_date;
            //     $RentOrderChildModel->ptime = $ctime;
            //     $RentOrderChildModel->save();


            //     $row->rent_order_paid = Db::raw('rent_order_paid+'.$pay_rent);
            //     $row->is_deal = 1;
            //     $res = $row->save();

            //     // 添加房屋台账，记录缴费状况
            //     $HouseTaiModel = new HouseTaiModel;
            //     $HouseTaiModel->house_id = $row['house_id'];
            //     $HouseTaiModel->tenant_id = $row['tenant_id'];
            //     $HouseTaiModel->cuid = ADMIN_ID;
            //     $HouseTaiModel->house_tai_type = 2;
            //     $HouseTaiModel->house_tai_remark = '现金缴费：'.$pay_rent.'元';
            //     $HouseTaiModel->data_json = [];
            //     $HouseTaiModel->change_type = '';
            //     $HouseTaiModel->change_id = '';
            //     $HouseTaiModel->save();
            // }  



            // 入库
            if (!$RechargeModel->allowField(true)->create($filData)) {
                return $this->error('充值失败');
            }
            $house_info->house_balance = $filData['yue'];
            $house_info->save();

            // // 模拟生成一条微信支付关联记录
            // $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            // $WeixinOrderTradeModel->out_trade_no = $out_trade_no;
            // $WeixinOrderTradeModel->rent_order_id = $id;
            // $WeixinOrderTradeModel->pay_dan_money = $filData['pay_rent'];
            // $WeixinOrderTradeModel->save();
            
            //增加房屋台账记录
            $HouseTaiModel = new HouseTaiModel;
            $HouseTaiModel->house_id = $house_info['house_id'];
            $HouseTaiModel->tenant_id = $house_info['tenant_id'];
            $HouseTaiModel->cuid = ADMIN_ID;
            $HouseTaiModel->house_tai_type = 2;
            $HouseTaiModel->house_tai_remark = '平台充值：'.$filData['pay_rent'].'元，剩余余额：'.$filData['yue'].'元。';
            $HouseTaiModel->data_json = [];
            $HouseTaiModel->change_type = '';
            $HouseTaiModel->change_id = '';
            $HouseTaiModel->save();
            return $this->success('充值成功');
        }
        return $this->fetch();
    }

    public function detail()
    {
        $id = input('param.id/d');
        $RechargeModel = new RechargeModel;      
        $row = $RechargeModel->detail($id);
        // 如果是微信支付，则显示充值的微信会员
        if($row['pay_way'] == 4){ 
            $member_name = '未知会员';
            $weixin_member_info = WeixinMemberModel::where([['member_id','eq',$row['member_id']]])->field('member_name')->find();
            if($weixin_member_info){
                $member_name = $weixin_member_info['member_name'];
            }
            $row['member_name'] = $member_name;
        }
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     * 功能描述：撤销（非线上支付）
     * @author  Lucas 
     * 创建时间: 2020-09-18 16:09:51
     */
    public function payBack()
    {
        if ($this->request->isPost()) {
            $id = input('param.id');
            $RechargeModel = new RechargeModel; 
            // 验证     
            $row = $RechargeModel->detail($id);
            if($row['pay_way'] != 2){
                return $this->error('非扣缴类型无法撤回');
            }
            if(substr($row['ctime'],0,7) !== date('Y-m')){
                //return $this->error('非本月扣缴无法撤回'); 
            }
            // 将流水记录变成无效状态
            RechargeModel::where([['id','eq',$id]])->update(['recharge_status'=>2]);
            // 将金额返还给房屋余额
            HouseModel::where([['house_id','eq',$row['house_id']]])->setInc('house_balance',abs($row['pay_rent']));

            // 添加撤销台账
            $taiHouseData = [];
            $taiHouseData['house_id'] = $row['house_id'];
            $taiHouseData['tenant_id'] = $row['tenant_id'];
            $taiHouseData['house_tai_type'] = 100;
            $taiHouseData['cuid'] = ADMIN_ID;
            $taiHouseData['house_tai_remark'] = '扣缴记录撤回：'.abs($row['pay_rent']).'元';
            $taiHouseData['data_json'] = [];
            $taiHouseData['change_type'] = 0;
            $taiHouseData['change_id'] = 0;
            $HouseTaiModel = new HouseTaiModel;
            $HouseTaiModel->allowField(true)->create($taiHouseData);

            return $this->success('撤销成功');
        }
    }

    /**
     * 功能描述：退款
     * @author  Lucas 
     * 创建时间: 2020-09-18 16:09:51
     */
    public function payRefund()
    {
        $id = input('id');
        if ($this->request->isAjax()) {
            $ref_description = input('ref_description');
            $WeixinModel = new WeixinModel;
            $refund_result = $WeixinModel->refundCreate($id ,$ref_description, $table = 'recharge');
            return $refund_result?$this->success($refund_result):$this->error($WeixinModel->getError());
        }
        $recharge_info = RechargeModel::alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->join('weixin_member d','a.member_id = d.member_id','inner')->where([['a.id','eq',$id]])->find();
        $this->assign('data_info',$recharge_info);
        return $this->fetch();
    }

    /**
     * 功能描述：开票
     * @author  Lucas 
     * 创建时间: 2020-09-18 16:09:51
     */
    public function dpkj()
    {
        $id = input('param.id');
        $InvoiceModel = new InvoiceModel;
        return !$InvoiceModel->dpkj($id , $type = 2) ? $this->error($InvoiceModel->getError()) : $this->success('开票成功') ;
    }

    /**
     * 功能描述：开票
     * @author  Lucas 
     * 创建时间: 2020-09-18 16:10:44
     */
    public function export()
    {   
        if ($this->request->isAjax()) {
            $getData = $this->request->post();
            $rechargeModel = new RechargeModel;
            $where = $rechargeModel->checkWhere($getData);

            $fields = "a.pay_rent,a.pay_way,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,b.house_use_id,b.house_number,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";

            $tableData = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('ctime desc')->select();

            if($tableData){

                $SystemExportModel = new SystemExport;

                $titleArr = array(
                    array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                    array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                   
                    array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                    array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                    
                    array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                    array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                    array('title' => '充值方式', 'field' => 'pay_way', 'width' => 12,'type' => 'number'),
                    array('title' => '充值时间', 'field' => 'ctime', 'width' => 24,'type' => 'number'),
                    array('title' => '充值金额', 'field' => 'pay_rent', 'width' => 12,'type' => 'number'),
                );

                $tableInfo = [
                    'FileName' => '账户充值记录数据',
                    'Title' => '账户充值记录数据',
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