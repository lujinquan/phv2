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

use app\common\model\SystemTcpdf;

/**
 * 催缴单
 */
class Ask extends Admin
{

    public function index()
    {
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

    public function print_out()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RentModel = new RentModel;
            $data = $RentModel->get_data($getData,$page,$limit);
            $htmlArr = [];
	        foreach ($data['data'] as $k => $v) {
	        	//halt($v);
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
	        	$html .= ' 元</td></tr><tr><td class="j-print-con" colspan="3" align="left">					<span>尊敬的租户：</span>					<span>可能是您的疏忽或者其它原因未来得及处理，请务必于2020年6月25日前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>					<span>特此通知，谢谢合作！</span></td></tr></table></div>';
	        	$htmlArr[] = $html;
	        }
	        //halt($htmlArr);
	        
	        $SystemTcpdf = new SystemTcpdf;
	        $data = $SystemTcpdf->example_000($htmlArr,[95,95]);
            return json($data);
        }    	
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
        	$html .= ' 元</td></tr><tr><td class="j-print-con" colspan="3" align="left">					<span>尊敬的租户：</span>					<span>可能是您的疏忽或者其它原因未来得及处理，请务必于2020年6月25日前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>					<span>特此通知，谢谢合作！</span></td></tr></table></div>';
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
		// 					<span>尊敬的租户：</span>
		// 					<span>可能是您的疏忽或者其它原因未来得及处理，请务必于2020年6月25日前到房管所或本单二维码在线支付。避免欠缴产生滞纳金，造成您不必要的损失！</span>
		// 					<span>特此通知，谢谢合作！</span>  
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