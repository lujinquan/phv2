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
use app\report\model\Report as ReportModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;
use app\system\model\Sms as SmsModel;
use app\common\model\SystemTcpdf;

/**
 * 催缴单
 */
class Ask extends Admin
{

    public function index()
    {
        // $template_info = Db::name('weixin_template')->where([['name','in',['app_user_payment_remind','app_user_wx_tips_remind']]])->column('value');
        // halt(implode(',',$template_info)); // 模板id
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RentModel = new RentModel;
            $data = $RentModel->get_data($getData,$page,$limit);
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 催缴短信提示
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2021-03-03 11:32:51 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function send_tips_tel_sms()
    {
        // return $this->success('发送成功，共计发送1条短信！');exit;
        // return $this->error('发送失败，请检查手机号是否正确或该房屋是否已达到短信催缴次数');exit;
        if(date('d') < 10){
            return $this->error('每月10号前，无法发送催缴信息'); 
        }
        if ($this->request->isAjax()) {

            $getData = $this->request->post();
            // 模拟前端传过来的房屋id数组
            // $getData['id'] = ['1446'];
            // 需要发送的总短信数量
            $count = count($getData['id']);
            $houseData = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id')->join('ban c','a.ban_id = c.ban_id')->where([['house_id','in',$getData['id']]])->field('house_id,house_number,house_curr_month_send_tel_sms,house_curr_month_send_wx_sms,b.tenant_tel,c.ban_address,c.ban_inst_pid')->select();
            // halt($houseData);
            $ziyangHouses = [];
            $liangdaoHouses = [];
            $success_count = 0;
            // halt($houseData);
            foreach($houseData as $v){
                // halt($v);
                if($v['house_curr_month_send_tel_sms'] >= 2){
                    continue;
                }
                $SmsModel = new SmsModel;
                // 发送的手机号
                $phone = ['+86'.$v['tenant_tel']];

                $paramsSet1 = $v['ban_address'];

                $paramsSet2 = '智慧公房小程序';
                // 模板信息
                $tempdata = [$paramsSet1 , $paramsSet2];
                if($v['ban_inst_pid'] == 2){
                    $tempid = '879554'; //紫阳所的催缴短信模板
                    $resData = $SmsModel->sendSmsOfInst($tempid , $tempdata , $phone ,$insttype = 'ziyang');
                }elseif($v['ban_inst_pid'] == 3){
                    $tempid = '879573'; //粮道所的催缴短信模板
                    $resData = $SmsModel->sendSmsOfInst($tempid , $tempdata , $phone ,$insttype = 'liangdao');
                }
                

                foreach($resData['SendStatusSet'] as $row){
                    if($row['Code'] === 'Ok'){
                        $row['PhoneNumber'] = substr($row['PhoneNumber'],3);
                        //session('sms_verification_', $login,);
                        $SmsModel = new SmsModel;
                        $ji = $SmsModel->save(['serial_no'=>$row['SerialNo'],'phone'=>$row['PhoneNumber'],'session_context'=>$row['SessionContext'],'content'=>json_encode($tempdata)]);
                        if($ji){
                            Db::name('house')->where([['house_id','eq',$v['house_id']]])->setInc('house_curr_month_send_tel_sms');
                            $success_count++;
                        }
                    }else{
                        // return $this->error('发送失败,错误码：'.$row['Message']);
                    }
                }
            }
            $error_count = $count - $success_count;
            if($success_count){
                if($error_count){
                    return $this->success('发送成功，共计发送'.$success_count.'条短信！发送失败'.$error_count.'条短信！请检查手机号是否正确或该房屋是否已达到短信催缴次数');
                }else{
                    return $this->success('发送成功，共计发送'.$success_count.'条短信！');
                }
                
            }else{
                return $this->error('发送失败，请检查手机号是否正确或该房屋是否已达到短信催缴次数'); 
            }
            
          
        }
  
    }

    /**
     * 发送微信催缴信息提示
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2021-03-03 11:32:51 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function send_tips_wx_sms()
    {
        if(date('d') < 10){
            return $this->error('每月10号前，无法发送催缴信息'); 
        }
        if ($this->request->isAjax()) {
            // 检测是否配置微信公众平台订阅模板
            $template_id = Db::name('weixin_template')->where([['name','eq','app_user_wx_tips_remind']])->value('value');
            if (empty($template_id)) {
                $result['code'] = 0;
                $result['msg'] = '未配置房租逾期催缴提醒模板！';
                return json($result);
            }

            // 获取表单提交的数据
            $getData = $this->request->post();
            $RentModel = new RentModel;
            $filterData = $RentModel->get_data($getData,$page = false);
            // halt($filterData);
            // 需要发送的总信息数量
            $count = count($getData['id']);
            // 成功发送的信息数量
            $success_count = 0;
            // 获取
            // $houseData = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id')->join('ban c','a.ban_id = c.ban_id')->where([['house_id','in',$getData['id']]])->field('house_number,house_curr_month_send_tel_sms,house_curr_month_send_wx_sms,b.tenant_name,b.tenant_tel,c.ban_address,c.ban_inst_pid')->select();
            foreach ($filterData['data'] as $v) {
                $house_info = Db::name('house')->where([['house_id','eq',$v['house_id']]])->field('house_curr_month_send_tel_sms,house_curr_month_send_wx_sms')->find();
                // 如果超出每月的微信发送数量限制，则无法再次发送
                if($house_info['house_curr_month_send_wx_sms'] >= 2){
                    continue;
                }
                $members = Db::name('weixin_member_house')->alias('a')->join('weixin_member b','a.member_id = b.member_id')->where([['dtime','eq',0],['house_id','eq',$v['house_id']]])->field('b.openid')->select();

                // 开始发送订阅消息
                if(!empty($members)){
                    foreach($members as $m){
                        // 模板信息
                        $data = [
                            // 'touser' => 'oaUZL5Ocoqr6EEnXN7nDBpcQUxXg', //要发送给用户的openId
                            'touser' => $m['openid'], //要发送给用户的openId
                            //改成自己的模板id，在微信接口权限里一次性订阅消息的查看模板id
                            'template_id' => $template_id,
                            'data'=>array(
                                // 租户名
                                'thing7'=>array(
                                    'value'=>$v['tenant_name'],
                                ),
                                // 房屋地址
                                'thing5'=>array(
                                    'value'=>$v['ban_address'],
                                ),
                                // 历史欠租
                                'amount9'=>array(
                                    'value'=>"￥".bcadd($v['beforeMonthUnpaidRent'],$v['beforeYearUnpaidRent'],2),
                                ),
                                // 本期欠租
                                'amount8'=>array(
                                    'value'=>"￥".$v['curMonthUnpaidRent'],
                                ),
                                // 温馨提示
                                'thing6'=>array(
                                    'value'=>"请于2日内缴纳，以免影响使用",
                                ),
                            )
                        ];
                        $WeixinModel = new WeixinModel;
                        $res = $WeixinModel->sendSubscribeTemplate($data);
                        // halt($res);
                        if(is_array($res)){
                            if($res['errcode'] == 0){
                                // 房屋本月微信订阅催缴信息数量+1
                                Db::name('house')->where([['house_id','eq',$v['house_id']]])->setInc('house_curr_month_send_wx_sms');
                                $success_count++;
                            }else{
                                // $result['code'] = 0;
                                // $result['msg'] = '发送失败！';
                            }
                        }else{
                            // $result['code'] = 0;
                            // $result['msg'] = $res;
                        }
                    }
                }
                
            }


            $error_count = $count - $success_count;
            if($success_count){
                if($error_count){
                    return $this->success('发送成功，共计发送'.$success_count.'条信息！发送失败'.$error_count.'条信息！');
                }else{
                    return $this->success('发送成功，共计发送'.$success_count.'条信息！');
                }
                
            }else{
                return $this->error('发送失败'); 
            }

        }

    }


    public function print_out()
    {
        if ($this->request->isAjax()) {

            $getData = $this->request->post();
            $RentModel = new RentModel;
            $data = $RentModel->get_data($getData,$page = false);
            //halt($data);
            $htmlArr = [];

            foreach ($data['data'] as $k => $v) {
                //halt($v);
                $html = '';
                $html .= "<style>.PageNext{page-break-after:always;font-family:'Microsoft YaHei';margin: 0 auto;width:360px}.j-print-title{width:360px;font-size:20px;padding:0 0 10px;font-weight:bold;display:inline-block;text-align:center}.j-print-table{border:1px solid #333;border-collapse:collapse;width:360px;font-size:14px;font-weight:200;box-sizing:border-box;display:inline-block;padding:6px}.j-print-table td{border:1px solid #333;border-collapse:collapse;background-color:#fff;box-sizing:border-box;height:20px;line-height:20px}.j-print-table td.j-print-90{width:90px}.j-print-table td.j-print-120{width:120px}.j-print-table td.j-print-con{border:1px solid #333;border-collapse:collapse;background-color:#fff;box-sizing:border-box;line-height:18px;font-size:12px}.j-print-table td.j-print-con span{line-height:18px;display:block}.j-datetime{text-align: right;font-size: 12px;margin-bottom: 5px;}</style>";
                
                $html .= '<div class="PageNext" style="margin: 0 auto;width: 360px;"><div class="j-print-title" style="width: 360px; text-align: center;font-size: 20px;padding: 20px 0 10px;">缴费单</div><div class="j-datetime" style="width:360px; text-align: right;font-size: 12px;padding-bottom: 5px;">打印时间：';
                $html .= date('Y-m-d');
                $html .= '</div><table class="j-print-table" style="width: 360px;margin: 0 auto;"><tr><td class="j-print-90" align="left">租户名</td><td colspan="2" align="left">';
                $html .= $v['tenant_name'];
                $html .= '</td></tr><tr><td class="j-print-90" align="left">租户地址</td><td colspan="2" align="left">';
                $html .= $v['ban_address'];
                $html .= '</td></tr><tr><td class="j-print-90" align="left">历史欠租</td><td class="j-print-120" align="left">';
                $html .= bcaddMerge([$v['beforeMonthUnpaidRent'] + $v['beforeYearUnpaidRent']]);

                $html .= ' 元</td><td  valign="middle" align="center" rowspan="3"><img style="width: 80px;box-sizing: border-box;" src="';
                //$html .= 'https://procheck.ctnmit.com/upload/wechat/qrcode/share_1_10020050010001.png';
                $html .= $v['house_share_img'];
                $html .= '" /></td></tr><tr><td class="j-print-90" align="left">本期欠租</td><td class="j-print-120" align="left">';
                $html .= $v['curMonthUnpaidRent'];
                $html .= ' 元</td></tr><tr><td class="j-print-90" align="left">合计欠租</td><td class="j-print-120" align="left">';
                $html .= $v['total'];
                $html .= ' 元</td></tr><tr><td class="j-print-con" colspan="3" align="left">                 <span>尊敬的租户：</span>                 <span>可能是您的疏忽或者其它原因未来得及处理，请务必于当月25日之前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>                 <span>特此通知，谢谢合作！房管员手机号：';
                $html .= $v['system_user_mobile'];
                $html .= '</span></td></tr></table></div>';
                $htmlArr[] = $html;
            }
            //halt($htmlArr);
            
            $SystemTcpdf = new SystemTcpdf;
            $data = $SystemTcpdf->example_000($htmlArr,[95,95]);

            return json($data);
        } 

        $house_id = input('id');
        //halt($id);
        $RentModel = new RentModel;
        $data = $RentModel->get_data($getData = ['house_id'=>$house_id],$page = false);
        //halt($data);

        $htmlArr = [];
        foreach ($data['data'] as $k => $v) {
            $this->assign('data_info',$v);
            break;
        }


        $this->assign('data_info',$v);
        return $this->fetch();


    }

    

    /*public function print_out()
    {

        $ids = input('id');

        $type = input('type');

        set_time_limit(0);

        if(!$ids){
            return false;
        }
        $params = ParamModel::getCparams();

        $month = date('Ym');
        $separate = substr($month,0,4).'00';

        $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaid,b.house_use_id,b.house_share_img,b.house_pre_rent,c.tenant_name,d.ban_number,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';

        $result = $data = $where = [];
        $where[] = ['a.rent_order_receive','>','a.rent_order_paid'];
        $where[] = ['b.house_id','in',explode(',',$ids)];
        $baseData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();

        $total_cur_month_unpaid_rent = 0;
        $total_before_month_unpaid_rent = 0;
        $total_before_year_unpaid_rent = 0;
        $total_unpaid_rent = 0;

        foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);

            if($b['rent_order_unpaid'] == 0){
                continue;
            }
            $data[$b['house_id']]['house_id'] = $b['house_id'];
            $data[$b['house_id']]['house_number'] = $b['house_number'];
            $data[$b['house_id']]['ban_number'] = $b['ban_number'];
            $data[$b['house_id']]['ban_address'] = $b['ban_address'];
            $data[$b['house_id']]['tenant_name'] = $b['tenant_name'];
            $data[$b['house_id']]['house_pre_rent'] = $b['house_pre_rent'];
            $data[$b['house_id']]['house_use_id'] = $params['uses'][$b['house_use_id']];
            $data[$b['house_id']]['ban_owner_id'] = $params['owners'][$b['ban_owner_id']];
            $data[$b['house_id']]['ban_inst_id'] = $params['insts'][$b['ban_inst_id']];
            if(!isset($data[$b['house_id']]['total'])){
              $data[$b['house_id']]['total'] = 0;  
            }
            if(!isset($data[$b['house_id']]['curMonthUnpaidRent'])){
              $data[$b['house_id']]['curMonthUnpaidRent'] = 0;  
            }
            if(!isset($data[$b['house_id']]['beforeMonthUnpaidRent'])){
              $data[$b['house_id']]['beforeMonthUnpaidRent'] = 0;  
            }
            if(!isset($data[$b['house_id']]['beforeYearUnpaidRent'])){
              $data[$b['house_id']]['beforeYearUnpaidRent'] = 0;  
            }

            //dump($month);dump($separate);dump($b['rent_order_date']);exit;
            if($b['rent_order_date'] == $month){ // 统计本月欠租
                $data[$b['house_id']]['curMonthUnpaidRent'] = $b['rent_order_unpaid'];
                $total_cur_month_unpaid_rent += $b['rent_order_unpaid'];
            }else if($b['rent_order_date'] > $separate && $b['rent_order_date'] < $month){ // 统计以前月欠租
                
                $data[$b['house_id']]['beforeMonthUnpaidRent'] += $b['rent_order_unpaid'];
                $total_before_month_unpaid_rent += $b['rent_order_unpaid'];
            }else if($b['rent_order_date'] < $separate){ //统计以前年欠租
                $data[$b['house_id']]['beforeYearUnpaidRent'] += $b['rent_order_unpaid'];
                $total_before_year_unpaid_rent += $b['rent_order_unpaid'];

            }

            //halt($data[$b['house_id']]);
            $data[$b['house_id']]['total'] += $b['rent_order_unpaid'];
            $total_unpaid_rent += $b['rent_order_unpaid'];
            $data[$b['house_id']]['remark'] = '';
        }
        $htmlArr = [];
        foreach ($data as $k => $v) {
            $html = '';
            $html .= "<style>.PageNext{page-break-after:always;font-family:'Microsoft YaHei';width:310px}.j-print-title{width:310px;font-size:20px;padding:0 0 10px;font-weight:bold;display:inline-block;text-align:center}.j-print-table{border:1px solid #333;border-collapse:collapse;width:310px;font-size:14px;font-weight:200;box-sizing:border-box;display:inline-block;padding:6px}.j-print-table td{border:1px solid #333;border-collapse:collapse;background-color:#fff;box-sizing:border-box;height:20px;line-height:20px}.j-print-table td.j-print-90{width:90px}.j-print-table td.j-print-120{width:103px}.j-print-table td.j-print-con{border:1px solid #333;border-collapse:collapse;background-color:#fff;box-sizing:border-box;line-height:18px;font-size:12px}.j-print-table td.j-print-con span{line-height:18px;display:block}</style>";
            $html .= '<div class="PageNext"><div class="j-print-title">缴费单<br/></div><table class="j-print-table"><tr><td class="j-print-90" align="left">租户名</td><td colspan="2" align="left">';
            $html .= $v['tenant_name'];
            $html .= '</td></tr><tr><td class="j-print-90" align="left">租户地址</td><td colspan="2" align="left">';
            $html .= $v['ban_address'];
            $html .= '</td></tr><tr><td class="j-print-90" align="left">历史欠租</td><td class="j-print-120" align="left">';
            $html .= bcaddMerge([$v['beforeMonthUnpaidRent'] + $v['beforeYearUnpaidRent']]);

            $html .= ' 元</td><td rowspan="3"><img style="width: 100px;box-sizing: border-box;" src="';
            $html .= 'https://procheck.ctnmit.com/upload/wechat/qrcode/share_1_10020050010001.png';
            $html .= '" /></td></tr><tr><td class="j-print-90" align="left">本期欠租</td><td class="j-print-120" align="left">';
            $html .= $v['curMonthUnpaidRent'];
            $html .= ' 元</td></tr><tr><td class="j-print-90" align="left">合计欠租</td><td class="j-print-120" align="left">';
            $html .= $v['total'];
            $html .= ' 元</td></tr><tr><td class="j-print-con" colspan="3" align="left">                 <span>尊敬的租户：</span>                 <span>可能是您的疏忽或者其它原因未来得及处理，请务必于2020年6月25日前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>                 <span>特此通知，谢谢合作！</span></td></tr></table></div>';
            $htmlArr[] = $html;
        }
        if($type == 1){

            $this->assign('data_info',$v);
            return $this->fetch();exit;
        }
        //halt($htmlArr);
        //$result['data'] = array_slice($data, ($page - 1) * $limit, $limit);
        

        // if ($this->request->isAjax()) {
     //        $page = input('param.page/d', 1);
     //        $limit = input('param.limit/d', 10);

     //        $getData = $this->request->get();
     //        //$this->redirect('ask/print_out');
     //    }


        //         $html = <<<EOF
        //     <style>
        //         .PageNext {page-break-after: always;font-family: 'Microsoft YaHei';width: 310px;}
        //         .j-print-title{width: 310px; font-size: 20px;padding: 0 0 10px;font-weight: bold;display: inline-block;text-align: center;}
        //         .j-print-table{border: 1px solid #333;border-collapse: collapse; width: 310px;font-size: 14px;font-weight: 200;box-sizing: border-box;display: inline-block;padding:6px;}
        //         .j-print-table td{border: 1px solid #333;border-collapse: collapse;background-color: #fff;box-sizing: border-box;height:20px;line-height: 20px;}
        //         .j-print-table td.j-print-90{width: 90px;}
        //         .j-print-table td.j-print-120{width: 103px;}
        //         .j-print-table td.j-print-con{border: 1px solid #333;border-collapse: collapse;background-color: #fff;box-sizing: border-box;line-height: 18px;font-size: 12px;}
        //         .j-print-table td.j-print-con span{line-height: 18px;display:block;}
        //     </style>
        //     <div class="PageNext">
        //         <div class="j-print-title">缴费单<br/></div>
        //         <table class="j-print-table">
        //             <tr>
        //                 <td class="j-print-90" align="left">租户名</td>
        //                 <td colspan="2"  align="left">刘道荣</td>
        //             </tr>
        //             <tr>
        //                 <td class="j-print-90" align="left">租户地址</td>
        //                 <td colspan="2" align="left">新生里还建楼1栋</td>
        //             </tr>
        //             <tr>
        //                 <td class="j-print-90" align="left">历史欠租</td>
        //                 <td class="j-print-120" align="left">1667.2</td>
        //                 <td rowspan="3">
        //                     <img  style="width: 100px;box-sizing: border-box;" src="https://procheck.ctnmit.com/upload/wechat/qrcode/share_1_10020050010001.png" />
        //                 </td>
        //             </tr>
        //             <tr>
        //                 <td class="j-print-90" align="left">本期欠租</td>
        //                 <td class="j-print-120" align="left">97.5</td>
        //             </tr>
        //             <tr>
        //                 <td class="j-print-90" align="left">合计欠租</td>
        //                 <td class="j-print-120" align="left">16672</td>
        //             </tr>
        //             <tr>
        //                 <td class="j-print-con" colspan="3" align="left">
        //                  <span>尊敬的租户：</span>
        //                  <span>可能是您的疏忽或者其它原因未来得及处理，请务必于2020年6月25日前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>
        //                  <span>特此通知，谢谢合作！</span>  
        //                 </td>
        //             </tr>
        //         </table>
        //     </div>
        // EOF;
        //$html .= $html;
        //echo $html;exit;
        $SystemTcpdf = new SystemTcpdf;
        $SystemTcpdf->example_000($htmlArr,[95,95]);
        //$SystemTcpdf->example_000($html,[95,95]);


        //    $this->assign('ids',$ids);
        // return $this->fetch();
    }*/
}