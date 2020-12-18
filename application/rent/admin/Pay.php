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
use hisi\Dir;
use hisi\PclZip;
use app\system\admin\Admin;
use app\common\model\SystemExport;
use app\rent\model\Rent as RentModel;
use app\house\model\House as HouseModel;
use app\wechat\model\Weixin as WeixinModel;
use app\rent\model\Invoice as InvoiceModel;
use app\rent\model\Recharge as RechargeModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinLeadMember as WeixinLeadMemberModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;

/**
 * 支付记录
 */
class Pay extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $getData = $this->request->get();
            $group = isset($getData['group']) ? $getData['group'] : 'y';

            // switch ($group) {
            //     case 'y':
            //         $temp = '';
            //         break;
            //     case 'x':
            //         $order = 'house_ctime desc';
            //         break;
            //     case 'z':
            //         $order = 'house_dtime desc';
            //         break;
            //     default:
            //         $order = 'house_ctime desc';
            //         break;
            // }
            if ($group == 'y') {
                $page = input('param.page/d', 1);
                $limit = input('param.limit/d', 10);

                $WeixinOrderModel = new WeixinOrderModel;
                $where = $WeixinOrderModel->checkWhere($getData);
                // halt($where);
                $fields = 'a.*,b.pay_dan_money,c.house_id,d.house_number,d.house_use_id,d.house_pre_rent,d.house_pre_rent,e.ban_inst_id,e.ban_id,e.ban_owner_id,e.ban_address,f.tenant_name';
                $data = [];

                // 子查询
                $subsql = Db::name('weixin_order_trade')->field('*')->group('out_trade_no')->buildSql();

                $temp = WeixinOrderModel::with('weixinMember')->alias('a')->join([$subsql =>' b'], 'a.out_trade_no = b.out_trade_no', 'right')->join('rent_order c', 'b.rent_order_id = c.rent_order_id', 'left')->join('house d', 'c.house_id = d.house_id', 'left')->join('ban e', 'd.ban_id = e.ban_id', 'left')->join('tenant f', 'c.tenant_id = f.tenant_id', 'left')->field($fields)->where($where)->page($page)->order('ctime desc')->limit($limit)->select()->toArray();

                // halt($temp);
                $data['data'] = $temp;

                $all = WeixinOrderModel::with('weixinMember')->alias('a')->join([$subsql =>' b'], 'a.out_trade_no = b.out_trade_no', 'left')->join('rent_order c', 'b.rent_order_id = c.rent_order_id', 'left')->join('house d', 'c.house_id = d.house_id', 'left')->join('ban e', 'd.ban_id = e.ban_id', 'left')->join('tenant f', 'c.tenant_id = f.tenant_id', 'left')->field('a.order_id')->where($where)->column('a.pay_money');
                $data['total_pay_money'] = array_sum($all);
                $data['count'] = count($all);//halt($data['data']);

                //$data['count'] = WeixinOrderModel::with('weixinMember')->alias('a')->join('weixin_order_trade b', 'a.out_trade_no = b.out_trade_no', 'left')->join('rent_order c', 'b.rent_order_id = c.rent_order_id', 'left')->join('house d', 'c.house_id = d.house_id', 'left')->join('ban e', 'd.ban_id = e.ban_id', 'left')->join('tenant f', 'c.tenant_id = f.tenant_id', 'left')->where($where)->count();//halt($data['data']);
                $data['code'] = 0;
                $data['msg'] = '';
                return json($data);
            } else if ($group == 'x') {
                $page = input('param.page/d', 1);
                $limit = input('param.limit/d', 10);

                $RechargeModel = new RechargeModel;
                $where = $RechargeModel->checkWhere($getData, $type = "pay");
                //halt($where);
                $fields = "a.id,a.house_id,a.is_need_dpkj,a.transaction_id,a.invoice_id,a.tenant_id,a.pay_rent,a.yue,a.pay_way,a.trade_type,from_unixtime(a.act_ptime, '%Y-%m-%d %H:%i:%S') as act_ptime,a.recharge_status,b.house_use_id,b.house_number,b.house_pre_rent,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
                $data = [];
                $data['data'] = Db::name('rent_recharge')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->join('tenant c', 'a.tenant_id = c.tenant_id', 'left')->join('ban d', 'b.ban_id = d.ban_id', 'left')->field($fields)->where($where)->where([['a.transaction_id','neq','']])->page($page)->limit($limit)->order('act_ptime desc')->select();
                // halt($data['data']);
                $data['count'] = Db::name('rent_recharge')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->join('tenant c', 'a.tenant_id = c.tenant_id', 'left')->join('ban d', 'b.ban_id = d.ban_id', 'left')->where($where)->where([['a.transaction_id','neq','']])->count('a.id');
                // 统计
                $totalRow = Db::name('rent_recharge')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->join('tenant c', 'a.tenant_id = c.tenant_id', 'left')->join('ban d', 'b.ban_id = d.ban_id', 'left')->where($where)->where([['a.transaction_id','neq','']])->field('sum(a.pay_rent) as total_pay_rent')->find();
                if ($totalRow) {
                    $data['total_pay_rent'] = $totalRow['total_pay_rent'];
                }
                $data['code'] = 0;
                $data['msg'] = '';
                //halt($data);
                return json($data);
            }

        }
        $group = input('group', 'y');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '缴费',
                'url' => '?group=y',
            ],
            [
                'title' => '充值',
                'url' => '?group=x',
            ]
        ];
        $tabData['current'] = url('?group=' . $group);
        $this->assign('ban_number', input('param.ban_number', ''));
        $this->assign('group', $group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);

        $currExpirTime = time() - 7200;
        // 删除过期的预支付订单
        $prepay_orders = WeixinOrderModel::where([['order_status', 'eq', 3], ['ctime', '<', $currExpirTime]])->field('out_trade_no')->select()->toArray();
        if ($prepay_orders) {
            foreach ($prepay_orders as $key => $val) {
                WeixinOrderTradeModel::where([['out_trade_no', 'eq', $val['out_trade_no']]])->delete();
            }
            WeixinOrderModel::where([['order_status', 'eq', 3], ['ctime', '<', $currExpirTime]])->delete();
        }

        return $this->fetch('index_' . $group);
    }

    public function export()
    {
        if ($this->request->isAjax()) {
            $getData = $this->request->param();
            $group = isset($getData['group']) ? $getData['group'] : 'y';
            if ($group == 'y') {

                $WeixinOrderModel = new WeixinOrderModel;
                $where = $WeixinOrderModel->checkWhere($getData);
                // halt($where);
                $fields = 'a.out_trade_no,a.order_status,a.member_id,a.pay_money,a.trade_type,a.ptime,c.house_id,d.house_number,d.house_use_id,d.house_pre_rent,d.house_pre_rent,e.ban_inst_id,e.ban_owner_id,e.ban_address,f.tenant_name';
                $data = [];

                // 子查询
                $subsql = Db::name('weixin_order_trade')->field('*')->group('out_trade_no')->buildSql();


                $temp = WeixinOrderModel::with('weixinMember')->alias('a')->join([$subsql =>' b'], 'a.out_trade_no = b.out_trade_no', 'left')->join('rent_order c', 'b.rent_order_id = c.rent_order_id', 'left')->join('house d', 'c.house_id = d.house_id', 'left')->join('ban e', 'd.ban_id = e.ban_id', 'left')->join('tenant f', 'c.tenant_id = f.tenant_id', 'left')->field($fields)->where($where)->order('a.ctime desc')->select()->toArray();
                // halt($temp);
                $tableData = array();
                foreach($temp as $k => $v){
                    $tableData[$k]['out_trade_no'] = $v['out_trade_no'];
                    $tableData[$k]['house_number'] = $v['house_number'];
                    $tableData[$k]['tenant_name'] = $v['tenant_name'];
                    $tableData[$k]['ban_inst_id'] = $v['ban_inst_id'];
                    $tableData[$k]['ban_address'] = $v['ban_address'];
                    $tableData[$k]['ban_owner_id'] = $v['ban_owner_id'];
                    $tableData[$k]['house_use_id'] = $v['house_use_id'];
                    $tableData[$k]['house_pre_rent'] = $v['house_pre_rent'];
                    $tableData[$k]['pay_money'] = $v['pay_money'];
                    $tableData[$k]['member_name'] = $v['member_name'];
                    $tableData[$k]['trade_type'] = $v['trade_type'];
                    $v['order_status'];
                    if($v['order_status'] == 1){
                        $tableData[$k]['order_status'] = '已成功';
                    }else if($v['order_status'] == 2){
                        $tableData[$k]['order_status'] = '已退款';
                    }else if($v['order_status'] == 3){
                        $tableData[$k]['order_status'] = '预支付';
                    }else if($v['order_status'] == 4){
                        $tableData[$k]['order_status'] = '已撤回';
                    }
                    if ($v['trade_type'] == 'CASH') {
                        $tableData[$k]['trade_type'] = '现金支付';
                    } else if ($v['trade_type'] == 'JSAPI') {
                        $tableData[$k]['trade_type'] = '微信支付';
                    } else if ($v['trade_type'] == 'NATIVE') {
                        $tableData[$k]['trade_type'] = '微信支付';
                    }
                    $tableData[$k]['ptime'] = $v['ptime'];

                }
                // halt($tableData);
                if($tableData){

                    $SystemExportModel = new SystemExport;

                    $titleArr = array(
                        array('title' => '支付订单号', 'field' => 'out_trade_no', 'width' => 24,'type' => 'string'),
                        array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                        array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                        array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                        array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),   
                        array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                        array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                        array('title' => '规定租金', 'field' => 'house_pre_rent', 'width' => 12,'type' => 'number'),
                        array('title' => '支付金额', 'field' => 'pay_money', 'width' => 12,'type' => 'number'),
                        array('title' => '支付用户', 'field' => 'member_name', 'width' => 12,'type' => 'number'),
                        array('title' => '支付方式', 'field' => 'trade_type', 'width' => 12,'type' => 'number'),
                        array('title' => '支付状态', 'field' => 'order_status', 'width' => 12,'type' => 'number'),
                        array('title' => '实际支付时间', 'field' => 'ptime', 'width' => 12,'type' => 'number'),
                    );

                    $tableInfo = [
                        'FileName' => '支付记录数据',
                        'Title' => '支付记录数据',
                    ];

                    return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
                }else{
                    $result = [];
                    $result['code'] = 0;
                    $result['msg'] = '数据为空！';
                    return json($result);
                }
                $data['code'] = 0;
                $data['msg'] = '';
                return json($data);
            } else if ($group == 'x') {
               
               
                $RechargeModel = new RechargeModel;
                $where = $RechargeModel->checkWhere($getData, $type = "pay");
                // halt($where);
                $fields = "a.pay_rent,a.yue,a.pay_way,from_unixtime(a.act_ptime, '%Y-%m-%d %H:%i:%S') as act_ptime,b.house_use_id,b.house_number,b.house_pre_rent,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";

                // $fields = "a.id,a.house_id,a.invoice_id,a.tenant_id,a.pay_rent,a.yue,a.pay_way,a.trade_type,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.recharge_status,b.house_use_id,b.house_number,b.house_pre_rent,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";

                $data = [];
                // $tableData = Db::name('rent_recharge')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->join('tenant c', 'a.tenant_id = c.tenant_id', 'left')->join('ban d', 'b.ban_id = d.ban_id', 'left')->field($fields)->where($where)->order('ctime desc')->select();
                $tableData = Db::name('rent_recharge')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->join('tenant c', 'a.tenant_id = c.tenant_id', 'left')->join('ban d', 'b.ban_id = d.ban_id', 'left')->field($fields)->where($where)->where([['a.transaction_id','neq','']])->order('act_ptime desc')->select();

                if($tableData){

                    $SystemExportModel = new SystemExport;

                    $titleArr = array(
                        array('title' => '地址', 'field' => 'ban_address', 'width' => 24,'type' => 'string'),
                        array('title' => '管段', 'field' => 'ban_inst_id', 'width' => 12 ,'type' => 'number'),
                        array('title' => '产别', 'field' => 'ban_owner_id', 'width' => 12,'type' => 'number'),
                        array('title' => '使用性质', 'field' => 'house_use_id', 'width' => 12,'type' => 'string'),
                        array('title' => '房屋编号', 'field' => 'house_number', 'width' => 24,'type' => 'string'),
                        array('title' => '租户姓名', 'field' => 'tenant_name', 'width' => 12,'type' => 'number'),
                        array('title' => '收支方式', 'field' => 'pay_way', 'width' => 12,'type' => 'number'),
                        array('title' => '规定租金', 'field' => 'house_pre_rent', 'width' => 12,'type' => 'number'),
                        array('title' => '缴纳金额', 'field' => 'pay_rent', 'width' => 12,'type' => 'number'),
                        array('title' => '当前余额', 'field' => 'yue', 'width' => 12,'type' => 'number'),
                        array('title' => '实际支付时间', 'field' => 'act_ptime', 'width' => 24,'type' => 'number'),
                    );

                    $tableInfo = [
                        'FileName' => '充值记录数据',
                        'Title' => '充值记录数据',
                    ];

                    return $SystemExportModel->exportExcel($tableData, $titleArr, $sheetType = 1 , $tableInfo , $downloadType = 3);
                }else{
                    $result = [];
                    $result['code'] = 0;
                    $result['msg'] = '数据为空！';
                    return json($result);
                }
                $data['code'] = 0;
                $data['msg'] = '';
                return json($data);
            }

            //ini_set('memory_limit', '300M');
            $getData = $this->request->get();
            $RentOrderChildModel = new RentOrderChildModel;
            $where = $RentOrderChildModel->checkWhere($getData);
            $fields = "a.pay_way,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.rent_order_diff,a.rent_order_pump,b.house_protocol_rent,a.rent_order_cut,from_unixtime(a.ptime, '%Y-%m-%d %H-%i-%s') as ptime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $tableData = Db::name('rent_order_child')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.ptime desc')->select();

            //halt($tableData);
           

        }

    }

    // 一键开票
    public function allDpkj()
    {
        $group = input('group');
        if ($group== 'y') {
            $weixin_id_undpkj = WeixinOrderModel::where([['is_need_dpkj', 'eq', 1],['order_status', 'eq', 1], ['invoice_id', 'eq', 0]])->field('order_id')->select()->toArray();

            if (empty($weixin_id_undpkj)) {
                return $this->error('暂无需要开票的订单');
            }
            $i = 0;
            foreach ($weixin_id_undpkj as $v) {
                // 每5秒执行一次
                // sleep(5);
                $InvoiceModel = new InvoiceModel;
                if (!$InvoiceModel->dpkj($v['order_id'])) {
                    if ($i) {
                        return $this->error($InvoiceModel->getError() . ',本次开具' . $i . '张发票！');
                    }
                    return $this->error($InvoiceModel->getError());
                } else {
                    $i++;
                }
            }
        } else{
            $weixin_id_undpkj = RechargeModel::where([['is_need_dpkj', 'eq', 1],['recharge_status', 'eq', 1], ['transaction_id', '>', 0], ['invoice_id', 'eq', 0]])->field('id')->select()->toArray();

            if (empty($weixin_id_undpkj)) {
                return $this->error('暂无需要开票的订单');
            }
            //halt($weixin_id_undpkj);
            $i = 0;
            foreach ($weixin_id_undpkj as $v) {
                // 每5秒执行一次
                // sleep(5);
                $InvoiceModel = new InvoiceModel;
                if (!$InvoiceModel->dpkj($v['id'] ,$type = 2)) {
                    if ($i) {
                        return $this->error($InvoiceModel->getError() . ',本次开具' . $i . '张发票！');
                    }
                    return $this->error($InvoiceModel->getError());
                } else {
                    $i++;
                }
            }
        }
        
        
        
        
        return $this->success('开票成功,本次开具' . $i . '张发票！');
    }

    // 开票
    public function dpkj()
    {
        // return $this->success('开票失败');
        $group = input('group');
        $id = input('param.id');
        $InvoiceModel = new InvoiceModel;
        if ($group == 'y') {
            return !$InvoiceModel->dpkj($id) ? $this->error($InvoiceModel->getError()) : $this->success('开票成功');
        } else {
            return !$InvoiceModel->dpkj($id, $type = 2) ? $this->error($InvoiceModel->getError()) : $this->success('开票成功');
        }

    }

    // 标记为不开票
    public function undpkj()
    {
        // return $this->success('开票失败');
        $group = input('group');
        $id = input('param.id');
        
        if ($group == 'y') {
            $WeixinOrderModel = new WeixinOrderModel;
            $res = $WeixinOrderModel->where([['order_id','eq',$id]])->update(['is_need_dpkj'=>0]);
            return $this->success('标记成功');
        } else {
            $RechargeModel = new RechargeModel;
            $res = $RechargeModel->where([['id','eq',$id]])->update(['is_need_dpkj'=>0]);
            return $this->success('标记成功');
        }

    }

    // 标记为不开票
    public function is_need_dpkj()
    {
        // return $this->success('开票失败');
        $group = input('group');
        $id = input('param.id');
        $val = input('param.val');
        
        if ($group == 'y') {
            $WeixinOrderModel = new WeixinOrderModel;
            $row = $WeixinOrderModel->where([['order_id','eq',$id]])->find();
            if($val == 0 && $row['invoice_id']){
                return $this->error('已开票无法切换');
            }
            $res = $WeixinOrderModel->where([['order_id','eq',$id]])->update(['is_need_dpkj'=>$val]);
            return $this->success('标记成功');
        } else {
            $RechargeModel = new RechargeModel;
            $res = $RechargeModel->where([['id','eq',$id]])->update(['is_need_dpkj'=>$val]);
            return $this->success('标记成功');
        }

    }

    // 一键同步发票，所有已开的发票，未下载到服务器的统一，一键下载
    public function allVoiceDown()
    {
        $group = input('group');
        //halt($group);
        // 获取所有需要下载pdf的数据
        $InvoiceModel = new InvoiceModel;
        $allInvoice = $InvoiceModel->where([['pdfurl', 'neq', ''], ['local_pdfurl', 'eq', '']])->select();

        $i = 0;
        foreach ($allInvoice as $v) {

            $url = $v['pdfurl'];
            $file = file_get_contents($url);
            if (strlen($file) < 10000) { // 请求的不是正常pdf文件
                continue;
            } else {
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/invoice/' . date('Ym');
                if (!is_dir($dir)) {
                    Dir::create($dir);
                    mkdir($dir, 0755, true);
                }
                file_put_contents($dir . '/' . $v['fpqqlsh'] . '.pdf', $file);
                $loacl_pdfurl = '/upload/invoice/' . date('Ym') . '/' . $v['fpqqlsh'] . '.pdf';

                InvoiceModel::where([['invoice_id', 'eq', $v['invoice_id']]])->update(['local_pdfurl' => $loacl_pdfurl]);
                $i++;
            }
        }
        $this->success('下载成功，本次下载' . $i . '张发票！');
        //halt($allInvoice);
        // return !$InvoiceModel->dpkj($id) ? $this->error($InvoiceModel->getError()) : $this->success('开票成功') ;
    }

    // 下载发票到本地，所有已开的发票，未下载到服务器的统一
    public function allVoiceDownLoad()
    {
        $group = input('group');
        //halt($group);

        //$getData = $this->request->get();
        $ids = input('id');


        // $fields = 'member_id,tenant_id,member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
        $fileLists = [];

        // 下载缴费订单发票
        if ($group == 'y') {
            $where[] = ['a.order_id', 'in', $ids];
            $where[] = ['a.invoice_id', '>', 0];
            $where[] = ['b.local_pdfurl', 'neq', ''];
            $temp = WeixinOrderModel::alias('a')->join('rent_invoice b', 'a.invoice_id = b.invoice_id', 'inner')->field('b.pdfurl,b.local_pdfurl')->where($where)->select()->toArray();
            // 下载充值发票
        } else {
            $where[] = ['a.id', 'in', $ids];
            $where[] = ['a.invoice_id', '>', 0];
            $where[] = ['b.local_pdfurl', 'neq', ''];
            $temp = RechargeModel::alias('a')->join('rent_invoice b', 'a.invoice_id = b.invoice_id', 'inner')->field('b.pdfurl,b.local_pdfurl')->where($where)->select()->toArray();
        }


        if (!$temp) {
            $this->error('未找到发票，下载失败！');
        }
        foreach ($temp as $k => $v) {
            $fileLists[] = $_SERVER['DOCUMENT_ROOT'] . $v['local_pdfurl'];
        }

        $random = date('YmdHis') . random(10);
//halt($fileLists);
        // 压缩的文件夹
        // $path = 'D:/PHPTutorial/WWW/phv2/public/upload/pdf/';
        // $fileLists = ['D:/PHPTutorial/WWW/phv2/public/upload/pdf/2020-07-27-15-05-47.pdf'];
        // 压缩文件生成后所放的位置
        $zipName = 'upload/' . $random . '.zip';
        // 如果压缩文件不存在，就创建压缩文件
        if (!is_file($zipName)) {
            $fp = fopen($zipName, 'w');
            fclose($fp);
        }
        $zip = new \ZipArchive();
        // OVERWRITE选项表示每次压缩时都覆盖原有内容，但是如果没有那个压缩文件的话就会报错，所以事先要创建好压缩文件
        // 也可以使用CREATE选项，此选项表示每次压缩时都是追加，不是覆盖，如果事先压缩文件不存在会自动创建
        if ($zip->open($zipName, \ZipArchive::OVERWRITE) === true) {
            $current = 'pdf'; // 你要压缩的文件的主目录

            // 压缩多个文件
            if ($fileLists) {
                foreach ($fileLists as $f) {
                    $filename = basename($f);
                    if (is_file($f)) {
                        $zip->addFile($f, $current . '/' . $filename);
                    }
                }
            }
            // 压缩目录
            //add_file_to_zip($path, $current, $zip);
            $zip->close();
        } else {
            exit('下载失败！');
        }
        // 客户端下载时看到的文件名称
        $showName = 'pdf.zip';

        //echo 'sd';
        // $this->success('下载成功！'.$zipName) ;
        if (!download_file($zipName, $showName, $isOutput = false)) {
            return "<script>alert('下载失败！')</script>";
        } else {
            $result = [];
            $result['url'] = get_domain() . '/' . $zipName;
            $result['code'] = 0;
            $result['msg'] = '下载成功！';
            return json($result);
            //@unlink($zipName);
        }
    }

    /**
     * 功能描述：支付记录详情
     * @author  Lucas
     * 创建时间: 2020-03-09 16:31:01
     */
    public function payDetail()
    {
        $group = input('group');
        if ($group == 'y') {

            $id = input('id');
            //halt($id);
            $WeixinOrderModel = new WeixinOrderModel;
            $order_info = $WeixinOrderModel->with('weixinMember')->find($id)->toArray();
            if ($order_info['order_status'] == 2) { //如果状态是已退款
                $WeixinOrderRefundModel = new WeixinOrderRefundModel;
                $order_refund_info = $WeixinOrderRefundModel->where([['order_id', 'eq', $id]])->find();
                $this->assign('order_refund_info', $order_refund_info);
            }
            if ($order_info['invoice_id']) {
                $InvoiceModel = new InvoiceModel;
                $invoice_info = $InvoiceModel->find($order_info['invoice_id']);
                if (!$invoice_info['local_pdfurl']) {
                    $is_down = $InvoiceModel->down_loacl_pdfurl($order_info['invoice_id']);
                    if ($is_down) {
                        $invoice_info = $InvoiceModel->find($order_info['invoice_id']);
                    }
                }
                $this->assign('invoice_info', $invoice_info);
            }
            $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            $rent_orders = $WeixinOrderTradeModel->where([['out_trade_no', 'eq', $order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
            $rent_order_ids = array_keys($rent_orders);
            $houses = Db::name('rent_order')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->where([['a.rent_order_id', 'in', $rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
            foreach ($houses as $k => &$v) {
                $v['rent_order_date'] = substr($v['rent_order_date'], 0, 4) . '-' . substr($v['rent_order_date'], 4, 2);
                $v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
            }
            $this->assign('group', $group);
            $this->assign('houses', $houses);
            $this->assign('data_info', $order_info);
            //获取绑定的房屋数量
            // $WeixinMemberHouseModel = new WeixinMemberHouseModel;
            // $houselist = $WeixinMemberHouseModel->house_list($id);
            // $this->assign('houselist',$houselist);
        } else {
            $id = input('param.id/d');
            $RechargeModel = new RechargeModel;
            $row = $RechargeModel->detail($id);
            // 如果是微信支付，则显示充值的微信会员
            if ($row['pay_way'] == 4) {
                $member_name = '未知会员';
                $avatar = '';
                $weixin_tel = '';
                $weixin_member_info = WeixinMemberModel::where([['member_id', 'eq', $row['member_id']]])->field('member_name,avatar,weixin_tel')->find();
                if ($weixin_member_info) {
                    $member_name = $weixin_member_info['member_name'];
                    $avatar = $weixin_member_info['avatar'];
                    $weixin_tel = $weixin_member_info['weixin_tel'];
                }
                //halt($weixin_order_info);
                $row['member_name'] = $member_name;
                $row['avatar'] = $avatar;
                $row['weixin_tel'] = $weixin_tel;
            }
            if ($row['invoice_id']) {
                $InvoiceModel = new InvoiceModel;
                $invoice_info = $InvoiceModel->find($row['invoice_id']);
                if (!$invoice_info['local_pdfurl']) {
                    $is_down = $InvoiceModel->down_loacl_pdfurl($row['invoice_id']);
                    if ($is_down) {
                        $invoice_info = $InvoiceModel->find($row['invoice_id']);
                    }
                }
                $this->assign('invoice_info', $invoice_info);
            }
            $this->assign('group', $group);
            $this->assign('data_info', $row);
        }
        return $this->fetch();
    }



    /**
     * 功能描述：支付退款
     * @author  Lucas
     * 创建时间: 2020-03-09 16:31:01
     */
    public function payRefund()
    {
        $group = input('group');
        if ($group == 'y') {
            $id = input('id');
            if ($this->request->isPost()) {
                $ref_description = input('ref_description');
                $WeixinModel = new WeixinModel;
                $refund_result = $WeixinModel->refundCreate($id, $ref_description, $table = 'order');
                return $refund_result ? $this->success($refund_result) : $this->error($WeixinModel->getError());
            }
            $WeixinOrderModel = new WeixinOrderModel;
            $order_info = $WeixinOrderModel->with('weixinMember')->find($id);
            // 如果状态是已退款
            if ($order_info['order_status'] == 2) {
                $WeixinOrderRefundModel = new WeixinOrderRefundModel;
                $order_refund_info = $WeixinOrderRefundModel->where([['order_id', 'eq', $id]])->find();
                $this->assign('order_refund_info', $order_refund_info);
            }
            $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            $rent_orders = $WeixinOrderTradeModel->where([['out_trade_no', 'eq', $order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
            $rent_order_ids = array_keys($rent_orders);
            $houses = Db::name('rent_order')->alias('a')->join('house b', 'a.house_id = b.house_id', 'left')->where([['a.rent_order_id', 'in', $rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
            foreach ($houses as $k => &$v) {
                $v['rent_order_date'] = substr($v['rent_order_date'], 0, 4) . '-' . substr($v['rent_order_date'], 4, 2);
                $v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
            }
            $this->assign('group', $group);
            $this->assign('houses', $houses);
            $this->assign('data_info', $order_info);

        } else {
            $id = input('id');
            if ($this->request->isAjax()) {
                $ref_description = input('ref_description');
                $WeixinModel = new WeixinModel;
                $refund_result = $WeixinModel->refundCreate($id, $ref_description, $table = 'recharge');
                return $refund_result ? $this->success($refund_result) : $this->error($WeixinModel->getError());
            }
            $recharge_info = RechargeModel::alias('a')->join('house b', 'a.house_id = b.house_id', 'inner')->join('ban c', 'b.ban_id = c.ban_id', 'inner')->join('weixin_member d', 'a.member_id = d.member_id', 'inner')->where([['a.id', 'eq', $id]])->find();
            $this->assign('data_info', $recharge_info);
            $this->assign('group', $group);
        }
        return $this->fetch();

    }

}