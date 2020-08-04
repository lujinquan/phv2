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
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\Recharge as RechargeModel;
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

            $fields = "a.id,a.house_id,a.tenant_id,a.pay_rent,a.yue,a.pay_way,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,b.house_use_id,b.house_number,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('ctime desc')->select();
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
            //halt($data);
            // 数据验证
            $result = $this->validate($data, 'Recharge.add');
            if($result !== true) {
                return $this->error($result);
            }
            if(round($data['pay_rent'],2) == 0){
                return $this->error('充值余额不能为零,或金额小于1分钱');
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
            //halt($filData['yue']);
            // 入库
            if (!$RechargeModel->allowField(true)->create($filData)) {
                return $this->error('充值失败');
            }
            $house_info->house_balance = $filData['yue'];
            $house_info->save();
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
            //HouseModel::where([['house_id','eq',$filData['house_id']]])->setInc('house_balance',$filData['pay_rent']);
            return $this->success('充值成功');
        }
        return $this->fetch();
    }

    public function detail()
    {
        $id = input('param.id/d');
        $RechargeModel = new RechargeModel;      
        $row = $RechargeModel->detail($id);
        if($row['pay_way'] == 4){ //如果是微信支付，则显示充值的微信会员
            $member_name = '测试账户，已被移除';
            $weixin_order_info = WeixinOrderModel::where([['out_trade_no','eq',$row['pay_number']]])->find();
            if($weixin_order_info){
                $weixin_member_info = WeixinMemberModel::where([['member_id','eq',$weixin_order_info['member_id']]])->find();
                $member_name = $weixin_member_info['member_name'];
            }
            
            $row['member_name'] = $member_name;
            //halt($weixin_member_info);
        }
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

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