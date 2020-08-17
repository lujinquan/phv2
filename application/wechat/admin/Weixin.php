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

namespace app\wechat\admin;

use think\Db;
use app\system\admin\Admin;
use app\rent\model\Rent as RentModel;
use app\wechat\model\Weixin as WeixinModel;
use app\rent\model\Invoice as InvoiceModel;
use app\house\model\House as HouseModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinLeadMember as WeixinLeadMemberModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;

/**
 * 微信小程序用户版
 */
class Weixin extends Admin
{
	public function userIndex()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinMemberModel = new WeixinMemberModel;
            $where = $WeixinMemberModel->checkWhere($getData);
            $fields = 'member_id,tenant_id,member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
            $data = [];
            $data['data'] = WeixinMemberModel::field($fields)->where($where)->page($page)->order('create_time desc')->limit($limit)->select();
            $data['count'] = WeixinMemberModel::where($where)->count('member_id');//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}

	public function leaderIndex()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinLeadMemberModel = new WeixinLeadMemberModel;
            $where = $WeixinLeadMemberModel->checkWhere($getData);
            $fields = 'lead_member_id,tenant_id,lead_member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
            $data = [];
            $data['data'] = WeixinLeadMemberModel::field($fields)->where($where)->page($page)->order('create_time desc')->limit($limit)->select();
            $data['count'] = WeixinLeadMemberModel::where($where)->count('member_id');//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}


	/**
	 * 功能描述： 支付记录列表
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-03-25 14:59:38
	 * @example 
	 * @link    文档参考地址：
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function payRecord()
	{	
		
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinOrderModel = new WeixinOrderModel;
            $where = $WeixinOrderModel->checkWhere($getData);
            //halt($where);
            // $fields = 'member_id,tenant_id,member_name,real_name,tel,weixin_tel,avatar,openid,login_count,last_login_time,last_login_ip,is_show,create_time';
            $data = [];
            $temp = WeixinOrderModel::with('weixinMember')->where($where)->page($page)->order('ctime desc')->limit($limit)->select()->toArray();
            foreach ($temp as $k => &$v) {
            	$rent_order_id = WeixinOrderTradeModel::where([['out_trade_no','eq',$v['out_trade_no']]])->value('rent_order_id');
            	//halt($rent_order_id);
				$info = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_number,d.ban_address')->where([['a.rent_order_id','eq',$rent_order_id]])->find();
				if($info){
					$v['house_number'] = $info['house_number'];
					$v['ban_address'] = $info['ban_address'];
				}
				
            }

            //halt($temp);
            $data['data'] = $temp;
            $data['count'] = WeixinOrderModel::where($where)->count();//halt($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $currExpirTime = time()-7200;
        // 删除过期的预支付订单
        $prepay_orders = WeixinOrderModel::where([['order_status','eq',3],['ctime','<',$currExpirTime]])->field('out_trade_no')->select()->toArray();
        if($prepay_orders){
        	foreach ($prepay_orders as $key => $val) {
	        	WeixinOrderTradeModel::where([['out_trade_no','eq',$val['out_trade_no']]])->delete();
	        }
	        WeixinOrderModel::where([['order_status','eq',3],['ctime','<',$currExpirTime]])->delete();
        }
        
		return $this->fetch();
	}

	// 开票
    public function dpkj()
    {
        // if ($this->request->isPost()) {
        //     $data = $this->request->post();
        //     $InvoiceModel = new InvoiceModel;
        //     halt(json_decode($InvoiceModel->dpkj(),true));
        // }
        $id = input('param.id/d');
        // $fields = 'a.rent_order_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,b.house_use_id,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id';
        // $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        $dpkj = [];
        // 发票请求流水号 20 是 企业内部唯一请求开票流水 号，每个请求流水号只能开一 次 ,流水号前面以公司名称 前 缀 例 如 合 力 中 税 ： HLZS20171128094300001
        $dpkj['fpqqlsh'] = 'LUCAS201711280943001'; 
        // 开票类型 1 是 0-蓝字发票；1-红字发票
        $dpkj['kplx'] = 0;
        // 发票类型 10 是 026-增值税电子发票 032-区块链发票
        $dpkj['fplx'] = '026';
        // 征税方式 1 是 0：普通征税 2：差额征税
        $dpkj['zsfs'] = 0;
        // 销售方名称 100 是 如为非收购发票，此销 售方指开票纳税人；如 为收购发票，指销售方 名称，例如烟叶农户
        $dpkj['xsf_mc'] = "税控服务器升级版测试用户10";
        // 销售方纳税方纳 税人识别号 20 是 如为非收购发票，此项 必填，即开票方纳税人 识别号；如为收购发 票，此项为可选
        $dpkj['xsf_nsrsbh'] = '150000000001000';
        // 销售方地址、电 话 100 是 如为非收购发票，此项必填， 即开票方纳税人地址、电话； 如为收购发票，此项为可选
        $dpkj['xsf_dzdh'] = '北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996';
        // 销售方银行账号 100 否
        $dpkj['xsf_yhzh'] = '中信银行 1234567890';
        // 购买方纳税人识 别号 20 否 如为非收购发票，此项可 选；如为收购发票， 此项必填，即开票方纳税人 识别号
        $dpkj['gmf_nsrsbh'] = '91110133745594417B';
        // 购买方名称 100 是 如为非收购发票，此项可 选；如为收购发票， 此项必填，即开票方纳税人 识别号
        $dpkj['gmf_mc'] = '测试';
        // 购买方地址、电话 100 否 如为非收购发票，此项可 选；如为收购发票，此项必 填，即开票纳税人地址、电 话
        $dpkj['gmf_dzdh'] = '地址 120';
        // 购买方银行账号 100 否
        $dpkj['gmf_yhzh'] = '银行 123456';
        // 购买方手机号 11 否 用于接收和归集电子发票 购买方手机号与电子邮箱 不能同时为空，如为非收购 发票则为购买方手机号；如 收购发票则为销售方手机 号
        $dpkj['gmf_sjh'] = '';
        // 购买方电子邮箱 100 否 用于接收和归集电子发票 购买方手机号与电子邮箱 不能同时为空，如为非收购 发票则为购买方电子邮箱； 如为收购发票则为销货方 电子邮箱
        $dpkj['gmf_dzyx'] = '';
        // 收款人 8 否
        $dpkj['skr'] = '收款人';
        // 复核人 8 否
        $dpkj['fhr'] = '复核人';
        // 开票人 8 是
        $dpkj['kpr'] = '开票人';
        // 原发票代码 12 红字发票时必须
        $dpkj['yfp_dm'] = '';
        // 原发票号码 8 红字发票时必须
        $dpkj['yfp_hm'] = '';
        // 价税合计 12 是 单位：元（2位小数）
        $dpkj['jshj'] = '12.00';
        // 合计金额 12 是 不含税，单位：元（最多保 留2位小数）
        $dpkj['hjje'] = '12';
        // 合计税额 12 是 单位：元（2位小数）
        $dpkj['hjse'] = 0;
        // 扣除额 12 否 最多保留至小数点后2位， 当 ZSFS为 2时扣 除额为必填项
        $dpkj['kce'] = 0;
        // 备注 130 否
        $dpkj['bz'] = '';
        // 行业类型 1 是 0商业、1其它
        $dpkj['hylx'] = 0;
        // 特殊票种标识 2 是 “00”不是 “01”农产品销售 “02”农产品收购
        $dpkj['tspz'] = '00';
        // 代开标志 1 否 0-非代开，1-代开
        $dpkj['dkbz'] = 0;
        // 发票行性质 1 是 0正常行、1折扣行、2被折 扣行
        $dpkj['fphxz'] = 0;
        // 商品编码 19 否 税局下发的商品编码表中 最末级节点的编码
        $dpkj['spbm'] = '1100301010000000000';
        // 自行编码 20 否 未填写商品编码时，须使用 自行增加的项目 名称，并填写该商品的编码 至自行编码中
        $dpkj['zxbm'] = '';
        // 优惠政策标识 1 是 0：不使用，1：使用
        $dpkj['yhzcbs'] = 0;
        // 零税率标识 1 否 空：非零税率， 1：免税， 2：不征收，3：普通零税率
        $dpkj['lslbs'] = '';
        // 增值税特殊管理 50 否 若含有预售卡业务，税率为 0，零税率标示必须为不征 税，优惠政策标示为 1，增 值税特殊管理必须为不征 税
        $dpkj['zzstsgl'] = '';
        // 项目名称 90 是 如果为折扣行，商品名称须 与被折扣行的商品名称相 同，不能多行折扣
        $dpkj['xmmc'] = '自来水';
        // 项目数量 12 否 最多保留6位小数，总长度 包含小数点不能超过12位
        $dpkj['xmsl'] = 2;
        // 项目单价 12 否 不含税，最多保留6位小数， 总长度包含小数点不能超 过12位
        $dpkj['xmdj'] = 6;
        // 项目金额 12 是 不含税，单位：元（最多保 留2位小数）
        $dpkj['xmje'] = 12.0;
        // 税率 3 是 最多保留2位小数，例17%为 0.17。小数点后最末位不能 为零，例10%为0.1
        $dpkj['sl'] = 0;
        // 税额 12 是 单位：元（最多保留2位小 数）
        $dpkj['se'] = 0.0;





		    $content = "<REQUEST_COMMON_FPKJ class=\"REQUEST_COMMON_FPKJ\">\n";
        $content .= "  <FPQQLSH><![CDATA[". $dpkj['fpqqlsh'] ."]]></FPQQLSH>\n";
        $content .= "  <KPLX><![CDATA[". $dpkj['kplx'] ."]]></KPLX>\n";
        $content .= "  <FPLX><![CDATA[". $dpkj['fplx'] ."]]></FPLX>\n";
        $content .= "  <ZSFS><![CDATA[". $dpkj['zsfs'] ."]]></ZSFS>\n";
        $content .= "  <XSF_MC><![CDATA[". $dpkj['xsf_mc'] ."]]></XSF_MC>\n";
        $content .= "  <XSF_NSRSBH><![CDATA[". $dpkj['xsf_nsrsbh'] ."]]></XSF_NSRSBH>\n";
        $content .= "  <XSF_DZDH><![CDATA[". $dpkj['xsf_dzdh'] ."]]></XSF_DZDH>\n";
        $content .= "  <XSF_YHZH><![CDATA[". $dpkj['xsf_yhzh'] ."]]></XSF_YHZH>\n";
        $content .= "  <GMF_NSRSBH><![CDATA[". $dpkj['gmf_nsrsbh'] ."]]></GMF_NSRSBH>\n";
        $content .= "  <GMF_MC><![CDATA[". $dpkj['gmf_mc'] ."]]></GMF_MC>\n";
        $content .= "  <GMF_DZDH><![CDATA[". $dpkj['gmf_dzdh'] ."]]></GMF_DZDH>\n";
        $content .= "  <GMF_YHZH><![CDATA[". $dpkj['gmf_yhzh'] ."]]></GMF_YHZH>\n";
        $content .= "  <GMF_SJH><![CDATA[". $dpkj['gmf_sjh'] ."]]></GMF_SJH>\n";
        $content .= "  <GMF_DZYX><![CDATA[". $dpkj['gmf_dzyx'] ."]]></GMF_DZYX>\n";
        $content .= "  <SKR><![CDATA[". $dpkj['skr'] ."]]></SKR>\n";
        $content .= "  <FHR><![CDATA[". $dpkj['fhr'] ."]]></FHR>\n";
        $content .= "  <KPR><![CDATA[". $dpkj['kpr'] ."]]></KPR>\n";
        $content .= "  <YFP_DM><![CDATA[". $dpkj['yfp_dm'] ."]]></YFP_DM>\n";
        $content .= "  <YFP_HM><![CDATA[". $dpkj['yfp_hm'] ."]]></YFP_HM>\n";
        $content .= "  <JSHJ><![CDATA[". $dpkj['jshj'] ."]]></JSHJ>\n";
        $content .= "  <HJJE><![CDATA[". $dpkj['hjje'] ."]]></HJJE>\n";
        $content .= "  <HJSE><![CDATA[". $dpkj['hjse'] ."]]></HJSE>\n";
        $content .= "  <KCE><![CDATA[". $dpkj['kce'] ."]]></KCE>\n";
        $content .= "  <BZ><![CDATA[". $dpkj['bz'] ."]]></BZ>\n";
        $content .= "  <HYLX><![CDATA[". $dpkj['hylx'] ."]]></HYLX>\n";
        $content .= "  <BY4><![CDATA[]]></BY4>\n";
        $content .= "  <TSPZ><![CDATA[". $dpkj['tspz'] ."]]></TSPZ>\n";
        $content .= "  <DKBZ><![CDATA[". $dpkj['dkbz'] ."]]></DKBZ>\n";
        $content .= "  <COMMON_FPKJ_XMXXS class=\"COMMON_FPKJ_XMXX\" size=\"1\">\n";
        $content .= "    <COMMON_FPKJ_XMXX>\n";
        $content .= "      <uuid><![CDATA[]]></uuid>\n";
        $content .= "      <zb_uuid><![CDATA[]]></zb_uuid>\n";
        $content .= "      <FPHXZ><![CDATA[". $dpkj['fphxz'] ."]]></FPHXZ>\n";
        $content .= "      <SPBM><![CDATA[". $dpkj['spbm'] ."]]></SPBM>\n";
        $content .= "      <ZXBM><![CDATA[". $dpkj['zxbm'] ."]]></ZXBM>\n";
        $content .= "      <YHZCBS><![CDATA[". $dpkj['yhzcbs'] ."]]></YHZCBS>\n";
        $content .= "      <LSLBS><![CDATA[". $dpkj['lslbs'] ."]]></LSLBS>\n";
        $content .= "      <ZZSTSGL><![CDATA[". $dpkj['zzstsgl'] ."]]></ZZSTSGL>\n";
        $content .= "      <XMMC><![CDATA[". $dpkj['xmmc'] ."]]></XMMC>\n";
        $content .= "      <GGXH><![CDATA[]]></GGXH>\n";
        $content .= "      <DW><![CDATA[]]></DW>\n";
        $content .= "      <XMSL><![CDATA[". $dpkj['xmsl'] ."]]></XMSL>\n";
        $content .= "      <XMDJ><![CDATA[". $dpkj['xmdj'] ."]]></XMDJ>\n";
        $content .= "      <XMJE><![CDATA[". $dpkj['xmje'] ."]]></XMJE>\n";
        $content .= "      <SL><![CDATA[". $dpkj['sl'] ."]]></SL>\n";
        $content .= "      <SE><![CDATA[". $dpkj['se'] ."]]></SE>\n";
        $content .= "      <BY1><![CDATA[]]></BY1>\n";
        $content .= "      <BY2><![CDATA[]]></BY2>\n";
        $content .= "      <BY3><![CDATA[]]></BY3>\n";
        $content .= "      <BY4><![CDATA[]]></BY4>\n";
        $content .= "      <BY5><![CDATA[]]></BY5>\n";
        $content .= "    </COMMON_FPKJ_XMXX>\n" .
             "  </COMMON_FPKJ_XMXXS>\n" .
            "</REQUEST_COMMON_FPKJ>";

        $InvoiceModel = new InvoiceModel;
        // $result = json_decode($InvoiceModel->dpkj($content),true);
        // halt($result['msg']);

	$a =    <<<EOF
<business id="10008" comment="发票开具">
  <body yylxdm="1">
    <returncode><![CDATA[0]]></returncode>
    <returnmsg><![CDATA[成功]]></returnmsg>
    <returndata>
      <fpdm><![CDATA[050003521107]]></fpdm>
      <fphm><![CDATA[54352895]]></fphm>
      <kprq><![CDATA[20200810144709]]></kprq>
      <fwqdz><![CDATA[]]></fwqdz>
      <fwqdkh><![CDATA[]]></fwqdkh>
      <jqbh><![CDATA[499098899194]]></jqbh>
      <fplxdm><![CDATA[]]></fplxdm>
      <fpcbh><![CDATA[]]></fpcbh>
      <kplx><![CDATA[0]]></kplx>
      <bbh><![CDATA[]]></bbh>
      <tspz><![CDATA[00]]></tspz>
      <xhdwsbh><![CDATA[150000000001000]]></xhdwsbh>
      <xhdwmc><![CDATA[税控服务器升级版测试用户10]]></xhdwmc>
      <xhdwdzdh><![CDATA[北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996]]></xhdwdzdh>
      <xhdwyhzh><![CDATA[中信银行 1234567890]]></xhdwyhzh>
      <ghdwsbh><![CDATA[91110133745594417B]]></ghdwsbh>
      <ghdwmc><![CDATA[测试]]></ghdwmc>
      <ghdwdzdh><![CDATA[地址 120]]></ghdwdzdh>
      <ghdwyhzh><![CDATA[银行 123456]]></ghdwyhzh>
      <bmbbbh><![CDATA[]]></bmbbbh>
      <zsfs><![CDATA[0]]></zsfs>
      <fyxm count="1">
        <group xh="1">
          <fphxz><![CDATA[0]]></fphxz>
          <spmc><![CDATA[*水冰雪*自来水]]></spmc>
          <spsm><![CDATA[]]></spsm>
          <ggxh><![CDATA[]]></ggxh>
          <dw><![CDATA[]]></dw>
          <spsl><![CDATA[2]]></spsl>
          <dj><![CDATA[6]]></dj>
          <je><![CDATA[12.0]]></je>
          <sl><![CDATA[0.0]]></sl>
          <se><![CDATA[0.0]]></se>
          <hsbz><![CDATA[]]></hsbz>
          <spbm><![CDATA[1100301010000000000]]></spbm>
          <zxbm><![CDATA[]]></zxbm>
          <yhzcbs><![CDATA[0]]></yhzcbs>
          <lslbs><![CDATA[]]></lslbs>
          <zzstsgl><![CDATA[]]></zzstsgl>
        </group>
      </fyxm>
      <zhsl><![CDATA[]]></zhsl>
      <hjje><![CDATA[12.0]]></hjje>
      <hjse><![CDATA[0.0]]></hjse>
      <jshj><![CDATA[12.0]]></jshj>
      <bz><![CDATA[]]></bz>
      <skr><![CDATA[收款人]]></skr>
      <fhr><![CDATA[复核人]]></fhr>
      <kpr><![CDATA[开票人]]></kpr>
      <jmbbh><![CDATA[]]></jmbbh>
      <zyspmc><![CDATA[]]></zyspmc>
      <spsm><![CDATA[]]></spsm>
      <qdbz><![CDATA[]]></qdbz>
      <ssyf><![CDATA[]]></ssyf>
      <kpjh><![CDATA[]]></kpjh>
      <tzdbh><![CDATA[]]></tzdbh>
      <yfpdm><![CDATA[]]></yfpdm>
      <yfphm><![CDATA[]]></yfphm>
      <qmcs><![CDATA[]]></qmcs>
      <tsbz><![CDATA[]]></tsbz>
      <gfkhdh><![CDATA[]]></gfkhdh>
      <gfkhyx><![CDATA[']]></gfkhyx>
      <skm><![CDATA[]]></skm>
      <jym><![CDATA[00207416902920906061]]></jym>
      <ewm><![CDATA[]]></ewm>
      <pdfUrl><![CDATA[http://api.scnebula.com/pdf/d/8fadb615edbe93d8]]></pdfUrl>
    </returndata>
  </body>
</business>
EOF;

//$file = $_SERVER['DOCUMENT_ROOT'].'/a.xml';

// 解析xml
$xml = simplexml_load_string($a, null, LIBXML_NOCDATA);

$InvoiceModel = new InvoiceModel;
$dpkj['pdfurl'] = $xml->body->returndata->pdfUrl[0];
//halt($dpkj['pdfurl']);
if(!$InvoiceModel->allowField(true)->create($dpkj)){
  return $this->error('开票失败');
}
return $this->success('开票成功');

// dump($xml);
// halt($xml->body->returndata->fpdm);
// $demo = file_get_contents($file);
// //halt(file_get_contents($file));
// // $b = "<skr><![CDATA[收款人]]></skr>\n" .
// // "<fhr><![CDATA[复核人]]></fhr>";
//         //dump($a);
//         halt(xml2array($demo));
        
        //return $this->fetch();
    }

	/**
	 * 功能描述：支付记录详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function payDetail()
	{
		$id = input('id');
		//halt($id);
		$WeixinOrderModel = new WeixinOrderModel;
		$order_info = $WeixinOrderModel->with('weixinMember')->find($id)->toArray();
		if($order_info['order_status'] == 2){ //如果状态是已退款
			$WeixinOrderRefundModel = new WeixinOrderRefundModel;
			$order_refund_info = $WeixinOrderRefundModel->where([['order_id','eq',$id]])->find();
			$this->assign('order_refund_info',$order_refund_info);
		}
		$WeixinOrderTradeModel = new WeixinOrderTradeModel;
		$rent_orders = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
		$rent_order_ids = array_keys($rent_orders);
		$houses = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->where([['a.rent_order_id','in',$rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
        foreach ($houses as $k => &$v) {
        	$v['rent_order_date'] = substr($v['rent_order_date'], 0,4).'-'.substr($v['rent_order_date'], 4,2);
        	$v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
     	}
		$this->assign('houses',$houses);
		$this->assign('data_info',$order_info);
		//获取绑定的房屋数量
		// $WeixinMemberHouseModel = new WeixinMemberHouseModel;
		// $houselist = $WeixinMemberHouseModel->house_list($id);
		// $this->assign('houselist',$houselist);
		
		return $this->fetch();
	}

	/**
	 * 功能描述：支付记录详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function payRefund()
	{
		$id = input('id');
		
		// halt($id);
		$WeixinOrderModel = new WeixinOrderModel;
		$order_info = $WeixinOrderModel->with('weixinMember')->find($id);
		if($order_info['order_status'] == 2){ //如果状态是已退款
			$WeixinOrderRefundModel = new WeixinOrderRefundModel;
			$order_refund_info = $WeixinOrderRefundModel->where([['order_id','eq',$id]])->find();
			$this->assign('order_refund_info',$order_refund_info);
		}
		$WeixinOrderTradeModel = new WeixinOrderTradeModel;
		$rent_orders = $WeixinOrderTradeModel->where([['out_trade_no','eq',$order_info['out_trade_no']]])->column('rent_order_id,pay_dan_money');
		$rent_order_ids = array_keys($rent_orders);
		$houses = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->where([['a.rent_order_id','in',$rent_order_ids]])->field('b.house_number,a.rent_order_id,a.rent_order_number,a.rent_order_date')->select();
        foreach ($houses as $k => &$v) {
        	$v['rent_order_date'] = substr($v['rent_order_date'], 0,4).'-'.substr($v['rent_order_date'], 4,2);
        	$v['pay_dan_money'] = $rent_orders[$v['rent_order_id']];
     	}
		$this->assign('houses',$houses);
		// halt($order_info);
		$this->assign('data_info',$order_info);
		// 获取绑定的房屋数量
		// $WeixinMemberHouseModel = new WeixinMemberHouseModel;
		// $houselist = $WeixinMemberHouseModel->house_list($id);
		// $this->assign('houselist',$houselist);
		return $this->fetch();
	}

	public function payRecordlist()
	{
		$id = input('id');
		$memberinfo = WeixinMemberModel::where([['member_id','eq',$id]])->find();
		$orderlist = WeixinOrderModel::where([['member_id','eq',$id],['order_status','neq',3]])->select()->toArray();

		foreach ($orderlist as $k => &$v) {
        	$rent_order_id = WeixinOrderTradeModel::where([['out_trade_no','eq',$v['out_trade_no']]])->value('rent_order_id');
        	//halt($rent_order_id);
			$info = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field('b.house_number,d.ban_address')->where([['a.rent_order_id','eq',$rent_order_id]])->find();
			if($info){
				$v['house_number'] = $info['house_number'];
				$v['ban_address'] = $info['ban_address'];
			}else{
				// 找不到关联的数据了，这里应该爆出错误
				$v['house_number'] = '10101222616239';
				$v['ban_address'] = '康平小区7栋';
			}
			
			$v['avatar'] = $memberinfo['avatar'];
			$v['openid'] = $memberinfo['openid'];
			$v['member_name'] = $memberinfo['member_name'];
        }
        //halt($orderlist);
		$this->assign('orderlist',$orderlist);
		return $this->fetch();
	}

	public function bindHouselist()
	{
		$id = input('id');
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$houselist = WeixinMemberHouseModel::where([['member_id','eq',$id],['dtime','eq',0]])->select()->toArray();
		foreach($houselist as &$h){
			//halt($h);
			$house_info = HouseModel::with(['ban','tenant'])->where([['house_id','eq',$h['house_id']]])->find();
			//halt($house_info);
			$h['house_number'] = $house_info['house_number'];
			$h['house_pre_rent'] = $house_info['house_pre_rent'];
			$h['ban_address'] = $house_info['ban_address'];
			$h['tenant_name'] = $house_info['tenant_name'];

		}
		$member_info = WeixinMemberModel::where([['member_id','eq',$id]])->find();
		//$houselist = $WeixinMemberHouseModel->house_list($id);
		$this->assign('member_info',$member_info);
		$this->assign('houselist',$houselist);
		return $this->fetch();
	}

	/**
	 * 添加会员与房屋的绑定
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-04-16 10:37:50
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	
	public function addbindhouse()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post();
            //halt($data);
            // 数据验证
            // $result = $this->validate($data, 'WeixinGuide');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // 
            $WeixinMemberModel = new WeixinMemberModel;
	        $member_info = $WeixinMemberModel->where([['member_id','eq',$data['member_id']]])->find();
	        if($member_info['is_show'] == 2){
	            return $this->error('用户已被禁止访问');
	        }
	        // 绑定手机号 
	        $HouseModel = new HouseModel;
	        $house_info = $HouseModel->where([['house_number','eq',$data['house_number']]])->find();
	        if(!$house_info){
	            return $this->error('房屋编号不存在');
	        }
	        if($house_info['house_status'] != 1){
	        	return $this->error('房屋已注销或未发租');
	        }
	        if($house_info['house_is_pause'] == 1){
	        	return $this->error('房屋已被暂停计租');
	        }
	        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
	        
	        //halt($houses);
	        $counts = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0],['is_auth','eq',0]])->count();
	        if($counts > 9){ //会员绑定的房屋数量达到>9个，提示超出数量
	            return $this->error('绑定房屋数量不能超过10个');
	        }
	        $find = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$house_info['house_id']],['dtime','eq',0]])->find();
	        if($find){
	            return $this->error('请勿重复绑定该房屋');
	        }
	        $WeixinMemberHouseModel->house_id = $house_info['house_id'];
	        $WeixinMemberHouseModel->member_id = $member_info['member_id'];
	        $res = $WeixinMemberHouseModel->save();
	        // 如果当前会员已认证，则每次添加房屋的时候刷新认证房屋数据
	        if($member_info['tenant_id']){
	            $WeixinMemberHouseModel = new WeixinMemberHouseModel;
	            // 调试
	            $auth_house_ids = $HouseModel->where([['tenant_id','eq',$member_info['tenant_id']],['house_is_pause','eq',0],['house_status','eq',1]])->column('house_id');
	            $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']]])->column('house_id,is_auth');
	            foreach ($auth_house_ids as $a) {
	                if(isset($houses[$a]) && $houses[$a] == 0){
	                    $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['house_id','eq',$a]])->update(['is_auth'=>1]);
	                }
	                if(!isset($houses[$a])){
	                    $WeixinMemberHouseModel = new WeixinMemberHouseModel;
	                    $WeixinMemberHouseModel->save(['member_id'=>$member_info['member_id'],'house_id'=>$a,'is_auth'=>1]);
	                }
	            }
	        }          
            // 入库
            if (!$res) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }

	}

	/**
	 * 解除会员与房屋的绑定
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-04-01 09:11:08
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	
	public function delbindhouse()
	{
		$id = input('id');
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$info = $WeixinMemberHouseModel->get($id);
		if($info['is_auth']){
			$this->error('当前房屋已认证无法解绑');
		}
		$info->dtime = time();
		$res = $info->save();
		//$res = WeixinMemberHouseModel::where([['id','eq',$id]])->delete();
		if($res){
			$this->success('解绑成功');
		}else{
			$this->error('解绑失败');
		}
	}

	/**
	 * 功能描述：用户详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function memberDetail()
	{
		$id = input('id');
		$WeixinMemberModel = new WeixinMemberModel;
		if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            // $result = $this->validate($data, 'WeixinNotice');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // 入库
            if (!$WeixinMemberModel->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
		
		$member_info = $WeixinMemberModel->with('tenant')->find($id);
		$this->assign('data_info',$member_info);
		// 获取绑定的房屋数量
		$WeixinMemberHouseModel = new WeixinMemberHouseModel;
		$housePayCount = WeixinMemberHouseModel::where([['member_id','eq',$id]])->count();
		$this->assign('housePayCount',$housePayCount);
		// 获取支付的订单数
		$order_info = WeixinOrderModel::where([['order_status','eq',1],['member_id','eq',$id]])->column('pay_money');
		$orderMoneys = bcaddMerge($order_info);
		//halt($order_info);
		$this->assign('orderMoneys',$orderMoneys);
		$this->assign('orderCount',count($order_info));
		return $this->fetch();
	}

	/**
	 * 功能描述：认证详情
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:31:01
	 */
	public function AuthDetail()
	{
		$id = input('id');
		$WeixinMemberModel = new WeixinMemberModel;
		$member_info = $WeixinMemberModel->with('tenant')->find($id);
		$this->assign('data_info',$member_info);
		return $this->fetch();
	}

	/**
	 * 功能描述：启用禁用状态切换
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:30:34
	 */
	public function isShow()
	{
		$id = input('id');
		$WeixinMemberModel = new WeixinMemberModel;
		$memberInfo = $WeixinMemberModel->find($id);
		if($memberInfo->is_show == 1){
			$memberInfo->is_show = 2;
			$msg = '禁用成功！';
		}else{
			$memberInfo->is_show = 1;
			$msg = '启用成功！';
		}
		$result = $memberInfo->save();
		if ($result === false) {
            return $this->error('状态设置失败');
        }

        return $this->success($msg);
	}
}