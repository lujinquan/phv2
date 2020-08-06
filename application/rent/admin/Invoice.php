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
use app\rent\model\Rent as RentModel;
use app\rent\model\Invoice as InvoiceModel;

/**
 * 发票记录
 */
class Invoice extends Admin
{
    
    public function index()
    {
        $InvoiceModel = new InvoiceModel;
        $InvoiceModel->fpcx();
        exit;
        // 余票查询接口
        $data = [
            'serviceid' => 'S0005',
            'leftValue' => '92edfcd96405',
            'rightValue' => '340d9826992051020add',
            'requestContent' => '150000000001000',
        ];
        $result1 = http_request('http://124.204.39.242:7280/bwcs/apiService',$data);
        $this->assign('result1',$result1);
        /**
         * YHLX 用户类型 
         * =====================================
         * @author  Lucas 
         * email:   598936602@qq.com 
         * Website  address:  www.mylucas.com.cn
         * =====================================
         * 创建时间:  <-- 这里输入 ctrl + shift + . 自动生成当前时间戳
         * @return  返回值  
         * @version 版本  1.0
         */
        
        // 发票查询接口
        $data = [
            'serviceid' => 'S0003',
            'leftValue' => '92edfcd96405',
            'rightValue' => '340d9826992051020add',
            'requestContent' => '<REQUEST_COMMON_FPCX>
                <YHLX>1</YHLX>
                <XSFNSRSBH>150000000001000</XSFNSRSBH>
                <FPQQLSH>HL15506462255RR7mH36</FPQQLSH>
                </REQUEST_COMMON_FPCX>',
        ];
        $result2 = http_request('http://124.204.39.242:7280/bwcs/apiService',$data);
        $this->assign('result2',$result2);
        // 发票开具接口
        $data = [
            'serviceid' => 'S0003',
            'leftValue' => '92edfcd96405',
            'rightValue' => '340d9826992051020add',
            'requestContent' => '<REQUEST_COMMON_FPKJ class="REQUEST_COMMON_FPKJ">
  <FPQQLSH><![CDATA[]]></FPQQLSH>
  <KPLX><![CDATA[0]]></KPLX>
  <FPLX><![CDATA[026]]></FPLX>
  <ZSFS><![CDATA[0]]></ZSFS>
  <XSF_MC><![CDATA[税控服务器升级版测试用户10]]></XSF_MC>
  <XSF_NSRSBH><![CDATA[150000000001000]]></XSF_NSRSBH>
  <XSF_DZDH><![CDATA[北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996]]></XSF_DZDH>
  <XSF_YHZH><![CDATA[中信银行 1234567890]]></XSF_YHZH>
  <GMF_NSRSBH><![CDATA[91110133745594417B]]></GMF_NSRSBH>
  <GMF_MC><![CDATA[测试]]></GMF_MC>
  <GMF_DZDH><![CDATA[地址 120]]></GMF_DZDH>
  <GMF_YHZH><![CDATA[银行 123456]]></GMF_YHZH>
  <GMF_SJH><![CDATA[]]></GMF_SJH>
  <GMF_DZYX><![CDATA[]]></GMF_DZYX>
  <SKR><![CDATA[收款人]]></SKR>
  <FHR><![CDATA[复核人]]></FHR>
  <KPR><![CDATA[开票人]]></KPR>
  <YFP_DM><![CDATA[]]></YFP_DM>
  <YFP_HM><![CDATA[]]></YFP_HM>
  <JSHJ><![CDATA[12.00]]></JSHJ>
  <HJJE><![CDATA[12]]></HJJE>
  <HJSE><![CDATA[0]]></HJSE>
  <KCE><![CDATA[]]></KCE>
  <BZ><![CDATA[]]></BZ>
  <HYLX><![CDATA[0]]></HYLX>
  <BY4><![CDATA[]]></BY4>
  <TSPZ><![CDATA[00]]></TSPZ>
  <DKBZ><![CDATA[0]]></DKBZ>
  <COMMON_FPKJ_XMXXS class="COMMON_FPKJ_XMXX" size="1">
    <COMMON_FPKJ_XMXX>
      <uuid><![CDATA[]]></uuid>
      <zb_uuid><![CDATA[]]></zb_uuid>
      <FPHXZ><![CDATA[0]]></FPHXZ>
      <SPBM><![CDATA[1100301010000000000]]></SPBM>
      <ZXBM><![CDATA[]]></ZXBM>
      <YHZCBS><![CDATA[0]]></YHZCBS>
      <LSLBS><![CDATA[]]></LSLBS>
      <ZZSTSGL><![CDATA[]]></ZZSTSGL>
      <XMMC><![CDATA[自来水]]></XMMC>
      <GGXH><![CDATA[]]></GGXH>
      <DW><![CDATA[]]></DW>
      <XMSL><![CDATA[2]]></XMSL>
      <XMDJ><![CDATA[6]]></XMDJ>
      <XMJE><![CDATA[12.0]]></XMJE>
      <SL><![CDATA[0]]></SL>
      <SE><![CDATA[0.0]]></SE>
      <BY1><![CDATA[]]></BY1>
      <BY2><![CDATA[]]></BY2>
      <BY3><![CDATA[]]></BY3>
      <BY4><![CDATA[]]></BY4>
      <BY5><![CDATA[]]></BY5>
    </COMMON_FPKJ_XMXX>
  </COMMON_FPKJ_XMXXS>
</REQUEST_COMMON_FPKJ>',
        ];
        $result3 = http_request('http://124.204.39.242:7280/bwcs/apiService',$data);
        $this->assign('result3',$result3);

        //halt($result);

    	// if ($this->request->isAjax()) {
     //        $page = input('param.page/d', 1);
     //        $limit = input('param.limit/d', 10);
     //        $getData = $this->request->get();
     //        $RentModel = new RentModel;
     //        $where = $RentModel->checkWhere($getData);
     //        $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
     //        $data = [];
     //        $data['data'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
     //        $data['count'] = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.rent_order_id');
     //        $data['code'] = 0;
     //        $data['msg'] = '';
     //        return json($data);
     //    }
        
        return $this->fetch();
    }

    public function detail()
    {
        $id = input('param.id/d');
        $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
        $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        $this->assign('data_info',$row);
        return $this->fetch();
    }
}