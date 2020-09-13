<?php

namespace app\rent\model;

use think\Db;
use think\Model;
use hisi\Http;
use app\rent\model\Recharge as RechargeModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;

/**
 * 百旺云发票接口类
 * =====================================
 * @author  Lucas 
 * email:   598936602@qq.com 
 * Website  address:  www.mylucas.com.cn
 * =====================================
 * 创建时间: 2020-08-07 17:27:38
 * @return  返回值  
 * @version 版本  1.0
 */
class Invoice extends Model
{
    // 设置模型名称
    protected $name = 'rent_invoice';
    // 设置主键
    protected $pk = 'invoice_id';
    // 接口地址
    //protected $url = "http://124.205.255.18:28500/api";
    //protected $url = "http://211.151.124.79:8500/api";
    protected $url = "https://dzfpapi.holytax.com/api";
	// 用户appid
    //protected $appid = '92edfcd96405';
    protected $appid = '';
    // 用户appsecret
    //protected $appsecret = '340d9826992051020add';
    protected $appsecret = '';
    // 对应相应的接口报文
    protected $content;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'ban_ctime' => 'timestamp:Y-m-d H:i:s',
    ];
    protected function initialize()
    {
        parent::initialize();
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }

        $where = [];

        // // 检索【租户】姓名
        // if(isset($data['tenant_name']) && $data['tenant_name']){
        //     $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        // }
        // // 检索【房屋】编号
        // if(isset($data['house_number']) && $data['house_number']){
        //     $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        // }
        // // 检索【楼栋】地址
        // if(isset($data['ban_address']) && $data['ban_address']){
        //     $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        // }
        // // 检索【楼栋】产别
        // if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
        //     $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        // }
        // // 检索【房屋】使用性质
        // if(isset($data['house_use_id']) && $data['house_use_id']){
        //     $where[] = ['b.house_use_id','eq',$data['house_use_id']];
        // }
        // // 检索【收欠】支付方式
        // if(isset($data['pay_way']) && $data['pay_way']){
        //     $where[] = ['a.pay_way','eq',$data['pay_way']];
        // }
        // // 检索【收欠】支付时间
        // if(isset($data['ctime']) && $data['ctime']){
        //     $startTime = strtotime(substr($data['ctime'],0,10));
        //     $endTime = strtotime(substr($data['ctime'],-10));
        //     $where[] = ['a.ctime','between',[$startTime,$endTime]];
        // }
        
        // // 检索【楼栋】机构
        // $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        // $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        //$where[] = ['rent_order_date','eq',date('Ym')];
        //halt($where);
        return $where;
    }

    /**
     * 电子发票查询接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-05 10:58:48
     * @return  返回值  
     * @version 版本  1.0
     */
    public function fpcx()
    {
    	$content = "<REQUEST_COMMON_FPCX>\n" .
                "<YHLX>1</YHLX>\n" .
                "<XSFNSRSBH>150000000001000</XSFNSRSBH>\n" .
                "<FPQQLSH>HL15506462255RR7mH36</FPQQLSH>\n" .
                "</REQUEST_COMMON_FPCX>";
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$base64Sign);
    	$queryMap = [];
    	$queryMap['content'] = $base64Sign;
    	$queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
    	$queryMap['serviceid'] = 'S0003';
    	$queryMap['source'] = '1';
    	$queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
    	$result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
    	return $result;
    }

    /**
     * 电子发票开票接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-07 17:14:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function dpkj($id, $type = 1)
    {
        // 初始化数据
        $dpkj = [];
        // 发票请求流水号 20 是 企业内部唯一请求开票流水 号，每个请求流水号只能开一 次 ,流水号前面以公司名称 前 缀 例 如 合 力 中 税 ： HLZS20171128094300001
        $dpkj['fpqqlsh'] = 'LUCAS201711280943001'; 
        // 开票类型 1 是 0-蓝字发票；1-红字发票 (冲红)
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
        //$dpkj['xsf_dzdh'] = '北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996';
        $dpkj['xsf_dzdh'] = '';
        // 销售方银行账号 100 否
        //$dpkj['xsf_yhzh'] = '中信银行 1234567890';
        $dpkj['xsf_yhzh'] = '';
        // 购买方纳税人识 别号 20 否 如为非收购发票，此项可 选；如为收购发票， 此项必填，即开票方纳税人 识别号
        $dpkj['gmf_nsrsbh'] = '';
        // 购买方名称 100 是 如为非收购发票，此项可 选；如为收购发票， 此项必填，即开票方纳税人 识别号
        $dpkj['gmf_mc'] = '';
        // 购买方地址、电话 100 否 如为非收购发票，此项可 选；如为收购发票，此项必 填，即开票纳税人地址、电 话
        $dpkj['gmf_dzdh'] = '';
        // 购买方银行账号 100 否
        //$dpkj['gmf_yhzh'] = '测试银行 123456';
        $dpkj['gmf_yhzh'] = '';
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
        $dpkj['spbm'] = '3040502020102000000';
        // 自行编码 20 否 未填写商品编码时，须使用 自行增加的项目 名称，并填写该商品的编码 至自行编码中
        $dpkj['zxbm'] = '';
        // 优惠政策标识 1 是 0：不使用，1：使用
        $dpkj['yhzcbs'] = 0;
        // 零税率标识 1 否 空：非零税率， 1：免税， 2：不征收，3：普通零税率
        $dpkj['lslbs'] = 1;
        // 增值税特殊管理 50 否 若含有预售卡业务，税率为 0，零税率标示必须为不征 税，优惠政策标示为 1，增 值税特殊管理必须为不征 税
        $dpkj['zzstsgl'] = '';
        // 项目名称 90 是 如果为折扣行，商品名称须 与被折扣行的商品名称相 同，不能多行折扣
        $dpkj['xmmc'] = '租金';
        // 项目数量 12 否 最多保留6位小数，总长度 包含小数点不能超过12位（冲红发票需填写负数）
        $dpkj['xmsl'] = 1;
        // 项目单价 12 否 不含税，最多保留6位小数， 总长度包含小数点不能超 过12位
        $dpkj['xmdj'] = 0;
        // 项目金额 12 是 不含税，单位：元（最多保 留2位小数）
        $dpkj['xmje'] = 0;
        // 税率 3 是 最多保留2位小数，例17%为 0.17。小数点后最末位不能 为零，例10%为0.1
        $dpkj['sl'] = 0;
        // 税额 12 是 单位：元（最多保留2位小 数）
        $dpkj['se'] = 0.0;


        // 缴费开票
        if ($type == 1) {
            $WeixinOrderModel = new WeixinOrderModel;
            $WeixinOrderRow = $WeixinOrderModel->find($id);

            if($WeixinOrderRow['invoice_id']){
               $this->error('支付订单已开票');
               return false;
            }
            if($WeixinOrderRow['order_status'] != 1){
                $this->error('订单状态异常无法开票');
                return false;
            }
            $WeixinOrderTradeArr = Db::name('weixin_order_trade')->where([['out_trade_no','eq',$WeixinOrderRow['out_trade_no']]])->select();

            $rent_orders = [];
            foreach ($WeixinOrderTradeArr as $k => $v) {
                $rent_orders[$v['rent_order_id']] = $v['pay_dan_money'];
            }

            $fields = 'a.rent_order_id,a.house_id,a.tenant_id,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,a.is_invoice,a.rent_order_diff,a.rent_order_pump,a.rent_order_cut,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_inst_pid';
            $RentOrderArr = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','in',array_keys($rent_orders)]])->select();
                $bz = '';
            $curr_date = date('Ym',$WeixinOrderRow->getData('ptime'));
            $curr_year_date = date('Y',$WeixinOrderRow->getData('ptime')).'00';
            $now_rent = 0;
            $old_month = 0;
            $old_year = 0;
            foreach ($RentOrderArr as $u => $p) {
                if ($p['rent_order_date'] == $curr_date) {
                    $now_rent = bcaddMerge([$now_rent,$rent_orders[$p['rent_order_id']]]);
                }
                if ($p['rent_order_date'] > $curr_year_date && $p['rent_order_date'] < $curr_date) {
                    $old_month = bcaddMerge([$old_month,$rent_orders[$p['rent_order_id']]]);
                }
                if ($p['rent_order_date'] < $curr_year_date) {
                    $old_year = bcaddMerge([$old_year,$rent_orders[$p['rent_order_id']]]);
                }   
            }
            if($now_rent > 0){
                $bz .= date('Y年m月',$WeixinOrderRow->getData('ptime')).',金额'.$now_rent.'元;';
                $xmmc = date('Y年m月',$WeixinOrderRow->getData('ptime')).'房屋租金';
            }
            if($old_month > 0){
                $bz .= '以前月订单,金额'.$old_month.'元;';
                $xmmc = '房屋租金';
            }
            if($old_year > 0){
                $bz .= '以前年订单,金额'.$old_year.'元;';
                $xmmc = '房屋租金';
            }
            // 再次查验备注的金额是否与支付的金额一致，不一致则备注至空
            $total_rent = bcaddMerge([$now_rent,$old_month,$old_year]);
            if($total_rent != $WeixinOrderRow['pay_money']){
                $bz = '';
            }
            $bz = trim($bz,';');

            $RentOrderRow = $RentOrderArr[0];
            $SystemUserRow = Db::name('system_user')->where([['inst_id','eq',$RentOrderRow['ban_inst_id']],['role_id','eq',4],['status','eq',1]])->find();

            // 获取毫秒级时间格式,例如：20200818155650612
            $timestamp = get_msec_to_mescdate(get_msec_time());
            // 紫阳所的订单
            if($RentOrderRow['ban_inst_pid'] == 2){
                $dpkj['fpqqlsh'] = 'CZY'.$timestamp; // C代表武昌区，ZY代表紫阳所
                $dpkj['xsf_mc'] = '武汉市武昌区房地产公司紫阳房管所'; // 销售方名称
                $dpkj['xsf_nsrsbh'] = '12420106931266704D'; // 纳税人识别号
                $dpkj['xsf_dzdh'] = '武汉市武昌区彭刘杨路103号 02750768218'; // 销售方地址
                $dpkj['xsf_yhzh'] = '中信武昌支行 7381710182600033257'; // 销售方银行账号
                $dpkj['fhr'] = '冯晖'; // 复核人
                //$dpkj['kpr'] = '冯晖'; // 开票人
                $this->appid = 'b3d3e0bf9221'; // 紫阳所的appid
                $this->appsecret = '33ed9898d7b779ff3804'; // 紫阳所的appsecret
            // 粮道所的订单
            }else{
                $dpkj['fpqqlsh'] = 'CLD'.$timestamp; // C代表武昌区，LD代表粮道所
                $dpkj['xsf_mc'] = '武汉市武昌区房地产公司粮道街房管所'; // 销售方名称
                $dpkj['xsf_nsrsbh'] = '12420106441363712E'; // 纳税人识别号
                $dpkj['xsf_dzdh'] = '武汉市武昌区后补街83号 88872619'; // 销售方地址
                $dpkj['xsf_yhzh'] = '农行胭脂路支行 031401040000062'; // 销售方银行账号
                $dpkj['fhr'] = '冯超'; // 复核人
                //$dpkj['kpr'] = '冯超'; // 开票人
                $this->appid = 'b6fba0d617c7'; // 粮道所的appid
                $this->appsecret = '30868888cbdf99f7b1e7'; // 粮道所的appsecret
            }
            $dpkj['house_id'] = $RentOrderRow['house_id'];
            $dpkj['tenant_id'] = $RentOrderRow['tenant_id'];
            $dpkj['gmf_mc'] = $RentOrderRow['tenant_name']; // 购买方名称
            $dpkj['gmf_dzdh'] = $RentOrderRow['ban_address']. ' ' .$RentOrderRow['tenant_tel'];
            $dpkj['skr'] = $SystemUserRow['nick']; // 收款人
            $dpkj['kpr'] = $SystemUserRow['nick']; // 开票人
            
            $dpkj['gmf_sjh'] = ''; // 购买方手机号（比如，发票开给张三的，就填写张三的手机号）
            $dpkj['gmf_dzyx'] = '598936602@qq.com'; // 购买方电子邮箱（比如，发票开给张三的，就填写张三的邮箱号）

            $dpkj['xmmc'] = $xmmc; // 项目名称
            //$dpkj['xmmc'] = '房地产租赁'; // 项目名称
            
            //$WeixinOrderRow['pay_money'] = '-0.01';

            $dpkj['xmdj'] = $WeixinOrderRow['pay_money']; // 项目单价
            $dpkj['xmje'] = $WeixinOrderRow['pay_money'] * $dpkj['xmsl']; // 项目金额
            $dpkj['jshj'] = $WeixinOrderRow['pay_money'] * $dpkj['xmsl']; // 价税合计
            $dpkj['hjje'] = $WeixinOrderRow['pay_money'] * $dpkj['xmsl']; // 合计金额
            $dpkj['bz'] = $bz; // 备注

        // 充值开票
        } else if ($type == 2) {
            $RechargeModel = new RechargeModel;

            $fields = 'a.*,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_inst_pid';

            $RechargeRow = $RechargeModel->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['a.id','eq',$id]])->find();
            if($RechargeRow['invoice_id']){
               $this->error('支付订单已开票');
               return false;
            }
            if($RechargeRow['recharge_status'] != 1){
                $this->error('充值订单状态异常无法开票');
                return false;
            }

            $bz = '房屋租金'.$RechargeRow['pay_remark'];
            $xmmc = '房屋租金';
            
            $SystemUserRow = Db::name('system_user')->where([['inst_id','eq',$RechargeRow['ban_inst_id']],['role_id','eq',4],['status','eq',1]])->find();

            // 获取毫秒级时间格式,例如：20200818155650612
            $timestamp = get_msec_to_mescdate(get_msec_time());
            // 紫阳所的订单
            if($RechargeRow['ban_inst_pid'] == 2){
                $dpkj['fpqqlsh'] = 'CZY'.$timestamp; // C代表武昌区，ZY代表紫阳所
                $dpkj['xsf_mc'] = '武汉市武昌区房地产公司紫阳房管所'; // 销售方名称
                $dpkj['xsf_nsrsbh'] = '12420106931266704D'; // 纳税人识别号
                $dpkj['xsf_dzdh'] = '武汉市武昌区彭刘杨路103号 02750768218'; // 销售方地址
                $dpkj['xsf_yhzh'] = '中信武昌支行 7381710182600033257'; // 销售方银行账号
                $dpkj['fhr'] = '冯晖'; // 复核人
                //$dpkj['kpr'] = '冯晖'; // 开票人
                $this->appid = 'b3d3e0bf9221'; // 紫阳所的appid
                $this->appsecret = '33ed9898d7b779ff3804'; // 紫阳所的appsecret
            // 粮道所的订单
            }else{
                $dpkj['fpqqlsh'] = 'CLD'.$timestamp; // C代表武昌区，LD代表粮道所
                $dpkj['xsf_mc'] = '武汉市武昌区房地产公司粮道街房管所'; // 销售方名称
                $dpkj['xsf_nsrsbh'] = '12420106441363712E'; // 纳税人识别号
                $dpkj['xsf_dzdh'] = '武汉市武昌区后补街83号 88872619'; // 销售方地址
                $dpkj['xsf_yhzh'] = '农行胭脂路支行 031401040000062'; // 销售方银行账号
                $dpkj['fhr'] = '冯超'; // 复核人
                //$dpkj['kpr'] = '冯超'; // 开票人
                $this->appid = 'b6fba0d617c7'; // 粮道所的appid
                $this->appsecret = '30868888cbdf99f7b1e7'; // 粮道所的appsecret
            }
            $dpkj['house_id'] = $RechargeRow['house_id'];
            $dpkj['tenant_id'] = $RechargeRow['tenant_id'];
            $dpkj['gmf_mc'] = $RechargeRow['tenant_name']; // 购买方名称
            $dpkj['gmf_dzdh'] = $RechargeRow['ban_address']. ' ' .$RechargeRow['tenant_tel'];
            $dpkj['skr'] = $SystemUserRow['nick']; // 收款人
            $dpkj['kpr'] = $SystemUserRow['nick']; // 开票人
            
            $dpkj['gmf_sjh'] = ''; // 购买方手机号（比如，发票开给张三的，就填写张三的手机号）
            $dpkj['gmf_dzyx'] = '598936602@qq.com'; // 购买方电子邮箱（比如，发票开给张三的，就填写张三的邮箱号）

            $dpkj['xmmc'] = $xmmc; // 项目名称
            //$dpkj['xmmc'] = '房地产租赁'; // 项目名称
            
            //$WeixinOrderRow['pay_money'] = '-0.01';

            $dpkj['xmdj'] = $RechargeRow['pay_rent']; // 项目单价
            $dpkj['xmje'] = $RechargeRow['pay_rent'] * $dpkj['xmsl']; // 项目金额
            $dpkj['jshj'] = $RechargeRow['pay_rent'] * $dpkj['xmsl']; // 价税合计
            $dpkj['hjje'] = $RechargeRow['pay_rent'] * $dpkj['xmsl']; // 合计金额
            $dpkj['bz'] = $bz; // 备注
        }
        

       
        //halt($dpkj);    


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

//         $a =    <<<EOF
//       <business id="10008" comment="发票开具">
//         <body yylxdm="1">
//           <returncode><![CDATA[0]]></returncode>
//           <returnmsg><![CDATA[成功]]></returnmsg>
//           <returndata>
//             <fpdm><![CDATA[050003521107]]></fpdm>
//             <fphm><![CDATA[54352895]]></fphm>
//             <kprq><![CDATA[20200810144709]]></kprq>
//             <fwqdz><![CDATA[]]></fwqdz>
//             <fwqdkh><![CDATA[]]></fwqdkh>
//             <jqbh><![CDATA[499098899194]]></jqbh>
//             <fplxdm><![CDATA[]]></fplxdm>
//             <fpcbh><![CDATA[]]></fpcbh>
//             <kplx><![CDATA[0]]></kplx>
//             <bbh><![CDATA[]]></bbh>
//             <tspz><![CDATA[00]]></tspz>
//             <xhdwsbh><![CDATA[150000000001000]]></xhdwsbh>
//             <xhdwmc><![CDATA[税控服务器升级版测试用户10]]></xhdwmc>
//             <xhdwdzdh><![CDATA[北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996]]></xhdwdzdh>
//             <xhdwyhzh><![CDATA[中信银行 1234567890]]></xhdwyhzh>
//             <ghdwsbh><![CDATA[91110133745594417B]]></ghdwsbh>
//             <ghdwmc><![CDATA[测试]]></ghdwmc>
//             <ghdwdzdh><![CDATA[地址 120]]></ghdwdzdh>
//             <ghdwyhzh><![CDATA[银行 123456]]></ghdwyhzh>
//             <bmbbbh><![CDATA[]]></bmbbbh>
//             <zsfs><![CDATA[0]]></zsfs>
//             <fyxm count="1">
//               <group xh="1">
//                 <fphxz><![CDATA[0]]></fphxz>
//                 <spmc><![CDATA[*水冰雪*自来水]]></spmc>
//                 <spsm><![CDATA[]]></spsm>
//                 <ggxh><![CDATA[]]></ggxh>
//                 <dw><![CDATA[]]></dw>
//                 <spsl><![CDATA[2]]></spsl>
//                 <dj><![CDATA[6]]></dj>
//                 <je><![CDATA[12.0]]></je>
//                 <sl><![CDATA[0.0]]></sl>
//                 <se><![CDATA[0.0]]></se>
//                 <hsbz><![CDATA[]]></hsbz>
//                 <spbm><![CDATA[1100301010000000000]]></spbm>
//                 <zxbm><![CDATA[]]></zxbm>
//                 <yhzcbs><![CDATA[0]]></yhzcbs>
//                 <lslbs><![CDATA[]]></lslbs>
//                 <zzstsgl><![CDATA[]]></zzstsgl>
//               </group>
//             </fyxm>
//             <zhsl><![CDATA[]]></zhsl>
//             <hjje><![CDATA[12.0]]></hjje>
//             <hjse><![CDATA[0.0]]></hjse>
//             <jshj><![CDATA[12.0]]></jshj>
//             <bz><![CDATA[]]></bz>
//             <skr><![CDATA[收款人]]></skr>
//             <fhr><![CDATA[复核人]]></fhr>
//             <kpr><![CDATA[开票人]]></kpr>
//             <jmbbh><![CDATA[]]></jmbbh>
//             <zyspmc><![CDATA[]]></zyspmc>
//             <spsm><![CDATA[]]></spsm>
//             <qdbz><![CDATA[]]></qdbz>
//             <ssyf><![CDATA[]]></ssyf>
//             <kpjh><![CDATA[]]></kpjh>
//             <tzdbh><![CDATA[]]></tzdbh>
//             <yfpdm><![CDATA[]]></yfpdm>
//             <yfphm><![CDATA[]]></yfphm>
//             <qmcs><![CDATA[]]></qmcs>
//             <tsbz><![CDATA[]]></tsbz>
//             <gfkhdh><![CDATA[]]></gfkhdh>
//             <gfkhyx><![CDATA[']]></gfkhyx>
//             <skm><![CDATA[]]></skm>
//             <jym><![CDATA[00207416902920906061]]></jym>
//             <ewm><![CDATA[]]></ewm>
//             <pdfUrl><![CDATA[http://api.scnebula.com/pdf/d/8fadb615edbe93d8]]></pdfUrl>
//           </returndata>
//         </body>
//       </business>
// EOF;
        // 打印请求开发票的所有数据，用作调试的
        // dump($this->appid);dump($this->appsecret);dump($content);halt($dpkj);
        
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$base64Sign);
        $queryMap = [];
        $queryMap['content'] = $base64Sign;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0001';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;

        $result = json_decode(Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []),true);
        
        // dump($content);
        // halt($result);
        
        // 这是一个错误的案例，用作调试的
        // $result = [
        //     "msg" => "税控服务器调用异常,请稍后再试,异常码为302;发票同步出错:f0003,税控服务器调用异常,请稍后再试,异常码为302",
        //     "code" => "fpkj0007",
        //     "requestid" => "976c9fda61b642efb74dc835bae3ed0e",
        // ];

        if($result['code'] !== '0000'){
            $this->error = $result['msg'];
            return false;
        }

        // 解析xml
        $xml = simplexml_load_string($result['msg'], null, LIBXML_NOCDATA);

        // 将成功开票的数据写入到invoice表
        $dpkj['pdfurl'] = $xml->body->returndata->pdfUrl[0];
        $row = $this->allowField(true)->create($dpkj);
        if(!$row){
            $this->error = '开票失败';
            return false;
        }

        if ($type == 1) { 
            // 将开票成功的id返回写入weixin_order表
            $WeixinOrderRow->invoice_id = $row['invoice_id'];
            $WeixinOrderRow->save();
        } else if($type == 2){
            // 将开票成功的id返回写入rent_recharge表
            $RechargeRow->invoice_id = $row['invoice_id'];
            $RechargeRow->save();
        }
        

        return true;
    }

    public function down_loacl_pdfurl($id)
    {
        $row = $this->find($id);
        //$url = $row['pdf_url'];
        $file = file_get_contents($row['pdfurl']);
        if(strlen($file) < 10000){ // 请求的不是正常pdf文件
            return false;
        }else{
            $dir = $_SERVER['DOCUMENT_ROOT'].'/upload/invoice/'.date('Ym');
            if(!is_dir($dir)){
                Dir::create($dir);
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir.'/'. $row['fpqqlsh'] .'.pdf', $file);
            $loacl_pdfurl = '/upload/invoice/'.date('Ym').'/'. $row['fpqqlsh'] .'.pdf';
            $row->local_pdfurl = $loacl_pdfurl;
            $row->save();
            return true;
        }
    }

    /**
     * 电子发票开票接口【案例】
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-07 17:14:43
     * @return  返回值  
     * @version 版本  1.0
     */
    public function dpkj_demo($content = '')
    {
        if(!$content){
            $content = "<REQUEST_COMMON_FPKJ class=\"REQUEST_COMMON_FPKJ\">\n" .
                "  <FPQQLSH><![CDATA[LUCAS201711280943001]]></FPQQLSH>\n" .
                "  <KPLX><![CDATA[0]]></KPLX>\n" .
                "  <FPLX><![CDATA[026]]></FPLX>\n" .
                "  <ZSFS><![CDATA[0]]></ZSFS>\n" .
                "  <XSF_MC><![CDATA[税控服务器升级版测试用户10]]></XSF_MC>\n" .
                "  <XSF_NSRSBH><![CDATA[150000000001000]]></XSF_NSRSBH>\n" .
                "  <XSF_DZDH><![CDATA[北京市海淀区复兴路甲23号城乡华懋商厦12层 4006056996]]></XSF_DZDH>\n" .
                "  <XSF_YHZH><![CDATA[中信银行 1234567890]]></XSF_YHZH>\n" .
                "  <GMF_NSRSBH><![CDATA[91110133745594417B]]></GMF_NSRSBH>\n" .
                "  <GMF_MC><![CDATA[测试]]></GMF_MC>\n" .
                "  <GMF_DZDH><![CDATA[地址 120]]></GMF_DZDH>\n" .
                "  <GMF_YHZH><![CDATA[银行 123456]]></GMF_YHZH>\n" .
                "  <GMF_SJH><![CDATA[]]></GMF_SJH>\n" .
                "  <GMF_DZYX><![CDATA[]]></GMF_DZYX>\n" .
                "  <SKR><![CDATA[收款人]]></SKR>\n" .
                "  <FHR><![CDATA[复核人]]></FHR>\n" .
                "  <KPR><![CDATA[开票人]]></KPR>\n" .
                "  <YFP_DM><![CDATA[]]></YFP_DM>\n" .
                "  <YFP_HM><![CDATA[]]></YFP_HM>\n" .
                "  <JSHJ><![CDATA[12.00]]></JSHJ>\n" .
                "  <HJJE><![CDATA[12]]></HJJE>\n" .
                "  <HJSE><![CDATA[0]]></HJSE>\n" .
                "  <KCE><![CDATA[]]></KCE>\n" .
                "  <BZ><![CDATA[]]></BZ>\n" .
                "  <HYLX><![CDATA[0]]></HYLX>\n" .
                "  <BY4><![CDATA[]]></BY4>\n" .
                "  <TSPZ><![CDATA[00]]></TSPZ>\n" .
                "  <DKBZ><![CDATA[0]]></DKBZ>\n" .
                "  <COMMON_FPKJ_XMXXS class=\"COMMON_FPKJ_XMXX\" size=\"1\">\n" .
                "    <COMMON_FPKJ_XMXX>\n" .
                "      <uuid><![CDATA[]]></uuid>\n" .
                "      <zb_uuid><![CDATA[]]></zb_uuid>\n" .
                "      <FPHXZ><![CDATA[0]]></FPHXZ>\n" .
                "      <SPBM><![CDATA[1100301010000000000]]></SPBM>\n" .
                "      <ZXBM><![CDATA[]]></ZXBM>\n" .
                "      <YHZCBS><![CDATA[0]]></YHZCBS>\n" .
                "      <LSLBS><![CDATA[]]></LSLBS>\n" .
                "      <ZZSTSGL><![CDATA[]]></ZZSTSGL>\n" .
                "      <XMMC><![CDATA[自来水]]></XMMC>\n" .
                "      <GGXH><![CDATA[]]></GGXH>\n" .
                "      <DW><![CDATA[]]></DW>\n" .
                "      <XMSL><![CDATA[2]]></XMSL>\n" .
                "      <XMDJ><![CDATA[6]]></XMDJ>\n" .
                "      <XMJE><![CDATA[12.0]]></XMJE>\n" .
                "      <SL><![CDATA[0]]></SL>\n" .
                "      <SE><![CDATA[0.0]]></SE>\n" .
                "      <BY1><![CDATA[]]></BY1>\n" .
                "      <BY2><![CDATA[]]></BY2>\n" .
                "      <BY3><![CDATA[]]></BY3>\n" .
                "      <BY4><![CDATA[]]></BY4>\n" .
                "      <BY5><![CDATA[]]></BY5>\n" .
                "    </COMMON_FPKJ_XMXX>\n" .
                "  </COMMON_FPKJ_XMXXS>\n" .
                "</REQUEST_COMMON_FPKJ>";
        }
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$base64Sign);
        $queryMap = [];
        $queryMap['content'] = $base64Sign;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0001';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
        $result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
        return $result;
    }

    /**
     * 电子发票PDF生成接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-05 10:58:48
     * @return  返回值  
     * @version 版本  1.0
     */
    public function createPdf()
    {
        $content = "PFJFUVVFU1RfQ09NTU9OX0ZQS0ogY2xhc3M9IlJFUVVFU1RfQ09NTU9OX0ZQS0oiPg0KICA8RlBRUUxTSD48IVtDREFUQVsxMjEyMTIxMjEyMTIxMjEyMTNma11dPjwvRlBRUUxTSD4NCiAgPEtQTFg+PCFbQ0RBVEFbMF1dPjwvS1BMWD4NCiAgPEJNQl9CQkg+PCFbQ0RBVEFbXV0+PC9CTUJfQkJIPg0KICA8WlNGUz48IVtDREFUQVswXV0+PC9aU0ZTPg0KICA8WFNGX05TUlNCSD48IVtDREFUQVsxNTAwMDAwMDAwMDEwMDBdXT48L1hTRl9OU1JTQkg+DQogIDxYU0ZfTUM+PCFbQ0RBVEFb56iO5o6n5pyN5Yqh5Zmo5Y2H57qn54mI5rWL6K+V55So5oi3MTBdXT48L1hTRl9NQz4NCiAgPFhTRl9EWkRIPjwhW0NEQVRBW+WMl+S6rOW4gua1t+a3gOWMuuWkjeWFtOi3r+eUsjIz5Y+35Y2B5LqM5bGCMTIwN11dPjwvWFNGX0RaREg+DQogIDxYU0ZfWUhaSD48IVtDREFUQVvotK3kubDmlrnpk7booYzlkI3np7DjgIHpk7booYzotKblj7coMTAwKV1dPjwvWFNGX1lIWkg+DQogIDxHTUZfTlNSU0JIPjwhW0NEQVRBWzkxMTEwMTMzNzQ1NTk0NDE3Ql1dPjwvR01GX05TUlNCSD4NCiAgPEdNRl9NQz48IVtDREFUQVvmtYvor5VdXT48L0dNRl9NQz4NCiAgPEdNRl9EWkRIPjwhW0NEQVRBW+i0reS5sOaWueWcsOWdgOOAgeeUteivnSgxMDApXV0+PC9HTUZfRFpESD4NCiAgPEdNRl9ZSFpIPjwhW0NEQVRBW+i0reS5sOaWuemTtuihjOWQjeensOOAgemTtuihjOi0puWPtygxMDApXV0+PC9HTUZfWUhaSD4NCiAgPEdNRl9TSkg+PCFbQ0RBVEFbXV0+PC9HTUZfU0pIPg0KICA8R01GX0RaWVg+PCFbQ0RBVEFbODk3NTQ2MjQ0QHFxLmNvbV1dPjwvR01GX0RaWVg+DQogIDxEUFBUX1pIPjwhW0NEQVRBW11dPjwvRFBQVF9aSD4NCiAgPFdYX09QRU5JRD48IVtDREFUQVtdXT48L1dYX09QRU5JRD4NCiAgPEtQUj48IVtDREFUQVvov5nlvIDnpajkurpdXT48L0tQUj4NCiAgPFNLUj48IVtDREFUQVtdXT48L1NLUj4NCiAgPEZIUj48IVtDREFUQVtdXT48L0ZIUj4NCiAgPFlGUF9ETT48IVtDREFUQVtdXT48L1lGUF9ETT4NCiAgPFlGUF9ITT48IVtDREFUQVtdXT48L1lGUF9ITT4NCiAgPEpTSEo+PCFbQ0RBVEFbMTAwLjBdXT48L0pTSEo+DQogIDxISkpFPjwhW0NEQVRBWzEwMC4wXV0+PC9ISkpFPg0KICA8SEpTRT48IVtDREFUQVswLjBdXT48L0hKU0U+DQogIDxLQ0U+PCFbQ0RBVEFbXV0+PC9LQ0U+DQogIDxCWj48IVtDREFUQVtdXT48L0JaPg0KICA8SFlMWD48IVtDREFUQVswXV0+PC9IWUxYPg0KICA8QlkxPjwhW0NEQVRBW11dPjwvQlkxPg0KICA8QlkyPjwhW0NEQVRBW11dPjwvQlkyPg0KICA8QlkzPjwhW0NEQVRBW11dPjwvQlkzPg0KICA8Qlk0PjwhW0NEQVRBW11dPjwvQlk0Pg0KICA8Qlk1PjwhW0NEQVRBW11dPjwvQlk1Pg0KICA8Qlk2PjwhW0NEQVRBW11dPjwvQlk2Pg0KICA8Qlk3PjwhW0NEQVRBW11dPjwvQlk3Pg0KICA8Qlk4PjwhW0NEQVRBW11dPjwvQlk4Pg0KICA8Qlk5PjwhW0NEQVRBW11dPjwvQlk5Pg0KICA8QlkxMD48IVtDREFUQVtdXT48L0JZMTA+DQogIDxXWF9PUkRFUl9JRD48IVtDREFUQVtdXT48L1dYX09SREVSX0lEPg0KICA8V1hfQVBQX0lEPjwhW0NEQVRBW11dPjwvV1hfQVBQX0lEPg0KICA8WkZCX1VJRD48IVtDREFUQVtdXT48L1pGQl9VSUQ+DQogIDxUU1BaPjwhW0NEQVRBWzAwXV0+PC9UU1BaPg0KICA8UUpfT1JERVJfSUQ+PCFbQ0RBVEFbXV0+PC9RSl9PUkRFUl9JRD4NCiAgPEpRQkg+PCFbQ0RBVEFbNDk5MDk5OTkyNzAyXV0+PC9KUUJIPg0KICA8RlBfRE0+PCFbQ0RBVEFbMTUwMDAzODg4ODg4XV0+PC9GUF9ETT4NCiAgPEZQX0hNPjwhW0NEQVRBWzk5OTk5OTAyXV0+PC9GUF9ITT4NCiAgPEZQRk0+PCFbQ0RBVEFbMTUwMDAzODg4ODg4OTk5OTk5OTBdXT48L0ZQRk0+DQogIDxLUFJRPjwhW0NEQVRBWzIwMTgwMTE4MDg0MjMwXV0+PC9LUFJRPg0KICA8RlBfTVc+PCFbQ0RBVEFbMDMqPjMvOCs8MTEwMjk1MzA1NDIvNTUrMjM3LTI8PDc1MCotPDIqMDUqNTUtOS0tNSo+MzMwLz4+Njg3MjQ0MDwrMz48MTI4KjgtMjY2MSoxKjgqPC0vMzc5OTw4MzAxMDUwOTE5Pi0qPiozKjErOV1dPjwvRlBfTVc+DQogIDxKWU0+PCFbQ0RBVEFbMDE4NjA3OTUwMDUyNDc2NjIzNDZdXT48L0pZTT4NCiAgPEVXTT48IVtDREFUQVtdXT48L0VXTT4NCiAgPHBkZl91cmw+PC9wZGZfdXJsPg0KICA8Q09NTU9OX0ZQS0pfWE1YWFMgY2xhc3M9IkNPTU1PTl9GUEtKX1hNWFgiIHNpemU9IjEiPg0KICAgIDxDT01NT05fRlBLSl9YTVhYPg0KICAgICAgPEZQSFhaPjwhW0NEQVRBWzBdXT48L0ZQSFhaPg0KICAgICAgPFNQQk0+PCFbQ0RBVEFbMzA0MDgwMjAxMDIwMDAwMDAwMF1dPjwvU1BCTT4NCiAgICAgIDxaWEJNPjwhW0NEQVRBW11dPjwvWlhCTT4NCiAgICAgIDxZSFpDQlM+PCFbQ0RBVEFbMV1dPjwvWUhaQ0JTPg0KICAgICAgPExTTEJTPjwhW0NEQVRBWzFdXT48L0xTTEJTPg0KICAgICAgPFpaU1RTR0w+PCFbQ0RBVEFb5YWN56iOXV0+PC9aWlNUU0dMPg0KICAgICAgPFhNTUM+PCFbQ0RBVEFbKue7j+e6quS7o+eQhuacjeWKoSrnoJTlj5HotLnnlKhdXT48L1hNTUM+DQogICAgICA8R0dYSD48IVtDREFUQVvmrKFdXT48L0dHWEg+DQogICAgICA8RFc+PCFbQ0RBVEFbXV0+PC9EVz4NCiAgICAgIDxYTVNMPjwhW0NEQVRBWzEwMDBdXT48L1hNU0w+DQogICAgICA8WE1ESj48IVtDREFUQVswLjFdXT48L1hNREo+DQogICAgICA8WE1KRT48IVtDREFUQVsxMDAuMF1dPjwvWE1KRT4NCiAgICAgIDxTTD48IVtDREFUQVswXV0+PC9TTD4NCiAgICAgIDxTRT48IVtDREFUQVswLjBdXT48L1NFPg0KICAgICAgPEJZMT48IVtDREFUQVtdXT48L0JZMT4NCiAgICAgIDxCWTI+PCFbQ0RBVEFbXV0+PC9CWTI+DQogICAgICA8QlkzPjwhW0NEQVRBW11dPjwvQlkzPg0KICAgICAgPEJZND48IVtDREFUQVtdXT48L0JZND4NCiAgICAgIDxCWTU+PCFbQ0RBVEFbXV0+PC9CWTU+DQogICAgPC9DT01NT05fRlBLSl9YTVhYPg0KICA8L0NPTU1PTl9GUEtKX1hNWFhTPg0KPC9SRVFVRVNUX0NPTU1PTl9GUEtKPg==";
        // 上述的content不需要base64加密
        //$base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$ase64Sign);
        $queryMap = [];
        $queryMap['content'] = $content;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0002';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
        $result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
        return $result;
    }

    /**
     * 电子发票余票查询接口
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * YHLX 用户类型 1 是 0 为个人 1为企业
     * XSFNSRSBH 销方税号 20 是
     * FPQQLSH 发票请求流水号 是
     * @param string $fpqqlsh 发票请求流水号
     * 创建时间: 2020-08-05 10:58:48
     * @return  返回值  
     * @version 版本  1.0
     */
    public function fpyj()
    {
        // 纳税人识别号（需base64）
        $content = "150000000001000";
        // 上述的content不需要base64加密
        $base64Sign = base64_encode($content);
        //dump('base64加密>>>> '.$ase64Sign);
        $queryMap = [];
        $queryMap['content'] = $base64Sign;
        $queryMap['appid'] = $this->appid;
        // 获取毫秒级的时间格式化字符串
        $queryMap['timestamp'] = get_msec_to_mescdate(get_msec_time());
        $queryMap['serviceid'] = 'S0005';
        $queryMap['source'] = '1';
        $queryMap['signkey'] = "appid,signkey,timestamp,content,serviceid,source";
        $signature = $this->getSignature($queryMap,$this->appsecret);
        //dump('报文签名>>>> '.$signature);
        $queryMap['signature'] = $signature;
        $result = Http::post($this->url, $queryMap, $header = [], $timeout = 30, $options = []);
        return $result;
    }


    /**
      * 签名生成算法
      * @param  array  $params API调用的请求参数集合的关联数组，不包含sign参数
      * @param  string $secret 签名的密钥即获取access token时返回的session secret
      * @return string 返回参数签名值
      */
    public function getSignature($params, $secret)
     {
        $str = '?';  // 待签名字符串
        // 先将参数以其参数名的字典序升序进行排序
        ksort($params);
        // 遍历排序后的参数数组中的每一个key/value对
        foreach ($params as $k => $v) {
            // 为key/value对生成一个key=value格式的字符串，并拼接到待签名字符串后面
            $str .= "$k=$v&";
        }
        // 这是一个java调用signUtil工具类的buildResource方法得到的字符串
        // $buildResource = '?appid=92edfcd96405&content=PFJFUVVFU1RfQ09NTU9OX0ZQQ1g.CjxZSExYPjE8L1lITFg.CjxYU0ZOU1JTQkg.MTUwMDAwMDAwMDAxMDAwPC9YU0ZOU1JTQkg.CjxGUFFRTFNIPkhMMTU1MDY0NjIyNTVSUjdtSDM2PC9GUFFRTFNIPgo8L1JFUVVFU1RfQ09NTU9OX0ZQQ1g.&serviceid=S0003&signkey=appid,signkey,timestamp,content,serviceid,source&source=1&timestamp=20200807151720788';
        $buildResource = trim($str,'&');
        // 将字符串加密后生成签名
        return base64_encode(hash_hmac('sha256', $buildResource, $secret, true));
    }

    public function detail($id)
    {
        $fields = 'a.*,from_unixtime(a.ctime, \'%Y-%m-%d\') as ctime,b.house_pre_rent,b.house_cou_rent,b.house_number,b.house_use_id,c.tenant_name,c.tenant_tel,c.tenant_card,d.ban_address,d.ban_owner_id,d.ban_inst_id,e.*';
        $row = Db::name('rent_invoice')->alias('a')->join('weixin_order e','a.invoice_id = e.invoice_id','inner')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['a.invoice_id','eq',$id]])->find();
        return $row;
    }

}
