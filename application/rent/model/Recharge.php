<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;

class Recharge extends Model
{
	// 设置模型名称
    protected $name = 'rent_recharge';
    // 设置主键
    protected $pk = 'id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('app\house\model\House', 'house_id', 'house_id')->bind('ban_number');
    }

    public function tenant()
    {
        return $this->belongsTo('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_name');
    }

    public function checkWhere($data ,$type = "")
    {
        if(!$data){
            $data = request()->param();
        }

        $where = [];
        $where[] = ['a.recharge_status','eq',1];
        // 检索【租户】姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['c.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索【房屋】编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索【楼栋】地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索【楼栋】产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }

        // 检索【房屋】支付金额
        if(isset($data['pay_rent']) && $data['pay_rent']){
            $where[] = ['a.pay_rent','eq',$data['pay_rent']];
        }
        if($type == "pay"){
            $where[] = ['b.house_use_id','eq',1];
            if(isset($data['pay_way']) && $data['pay_way']){
                $where[] = ['a.pay_way','eq',$data['pay_way']];
            }else{
                $where[] = ['a.pay_way','in',[1,4]];
            }
            // $where[] = ['a.transaction_id','eq','not null'];
             // 检索【收欠】支付时间
            if(isset($data['act_ptime']) && $data['act_ptime']){
                $startTime = strtotime(substr($data['act_ptime'],0,10));
                $endTime = strtotime(substr($data['act_ptime'],-10));
                $where[] = ['a.act_ptime','between',[$startTime,$endTime]];
            }else if(!isset($data['act_ptime'])){
                $startTime = strtotime(date('Y-m'));
                $endTime = strtotime(date('Y-m', strtotime( "first day of next month" ) ));
                $where[] = ['a.act_ptime','between',[$startTime,$endTime]];
            }
        }else{
            // 检索【房屋】使用性质
            if(isset($data['house_use_id']) && $data['house_use_id']){
                $where[] = ['b.house_use_id','in',explode(',',$data['house_use_id'])];
            }
           if(isset($data['pay_way']) && $data['pay_way']){
                $where[] = ['a.pay_way','eq',$data['pay_way']];
            }else{
                $where[] = ['a.pay_way','in',[1,2,4]];
            }
             // 检索【收欠】支付时间
            if(isset($data['ptime']) && $data['ptime']){
                $startTime = strtotime(substr($data['ptime'],0,10));
                $endTime = strtotime(substr($data['ptime'],-10));
                $where[] = ['a.ptime','between',[$startTime,$endTime]];
            }else if(!isset($data['ptime'])){
                $startTime = strtotime(date('Y-m'));
                $endTime = strtotime(date('Y-m', strtotime( "first day of next month" ) ));
                $where[] = ['a.ptime','between',[$startTime,$endTime]];
            }
        }
        
        // 检索租户姓名
        // if(isset($data['pay_way']) && $data['pay_way']){
        //     if ($data['pay_way'] == 1) { // 现金支付
        //         $where[] = ['a.trade_type','in',['CASH']];
        //     } else if($data['pay_way'] == 2){ // 微信支付
        //         $where[] = ['a.trade_type','in',['JSAPI','NATIVE']];
        //     }
           
        // }
        // 检索开票状态
        if(isset($data['invoice_id']) && $data['invoice_id']){
            if ($data['invoice_id'] == 1) { // 现金支付
                $where[] = ['a.invoice_id','>',0];
            } else if($data['invoice_id'] == 2){ // 微信支付
                $where[] = ['a.invoice_id','eq',0];
                $where[] = ['a.is_need_dpkj','eq',1];
            } else if($data['invoice_id'] == 3){ // 微信支付
                $where[] = ['a.is_need_dpkj','eq',0];
            }
           
        }
       
        // 检索机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $insts = explode(',',$data['ban_inst_id']);
            $instid_arr = [];
            foreach ($insts as $inst) {
                foreach (config('inst_ids')[$inst] as $instid) {
                    $instid_arr[] = $instid;
                }
            }
            $where[] = ['ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where[] = ['ban_inst_id','in',config('inst_ids')[INST]];
        }
        // 检索【楼栋】机构
        // $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        // $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
        //$where[] = ['rent_order_date','eq',date('Ym')];
        // halt($where);
        return $where;
    }

    public function dataFilter($data)
    {
        $row = HouseModel::where([['house_number','eq',$data['house_number']]])->field('house_id,tenant_id')->find();
        $data['house_id'] = $row['house_id'];
        $data['tenant_id'] = $row['tenant_id'];
        $data['pay_number'] = date('YmdHis') . random(6);
        unset($data['id'],$data['house_number']);
        return $data;
    }

    public function detail($id)
    {
        $fields = "a.id,a.house_id,a.pay_remark,a.tenant_id,a.pay_number,a.member_id,a.out_trade_no,a.invoice_id,a.pay_remark,a.pay_rent,a.yue,a.pay_way,from_unixtime(a.ctime,'%Y-%m-%d %H:%i:%S') as ctime,b.house_use_id,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['id','eq',$id]])->find();
        return $row;
    }

    public function createPayMark($house_info = array() , $house_id = '' , $pay_rent = 0)
    {
        $before_recharge_balance = $house_info['house_balance'];
        $rent = Db::name('rent_order')->where([['house_id','eq',$house_id],['rent_order_status','eq',1]])->order('rent_order_date desc')->field('rent_order_receive,rent_order_date')->find();
        // 如果当前房屋一条订单都没有生成过，说明该房屋是新发的
        if(empty($rent)){
            $receive = $house_info['house_pre_rent'];
        }else{
            $receive = $rent['rent_order_receive'];
        }
        $number = bcdiv($before_recharge_balance, $receive);
        $new_number = bcdiv($pay_rent, $receive);
        if($new_number == 0){
            return '房屋租金';
        }
        // if($house_info['house_invoice_count']){ // 如果有计数
        //     $last_rent_date = $house_info['house_invoice_count']; 
        // }else{
        //     $last_rent_date = substr_replace($rent['rent_order_date'], '-', 4,0);
        // }
        if($number > 0){
            $last_rent_date = date('Y-m',strtotime('+ '.$number.' month'));
        }else{
            $last_rent_date = substr_replace($rent['rent_order_date'], '-', 4,0);
        }
        

        $start_date = date( "Y-m", strtotime( "first day of next month",strtotime($last_rent_date) ) );
        if($new_number == 1){
            $end_date = $start_date;
            $start_date_front = substr($start_date,0,4);
            $start_date_behind = substr($start_date,5,2);
        }else{
            $n = $new_number - 1;
            $end_date = date( "Y-m", strtotime( "+ ".$n." month",strtotime($start_date) ) );
            $start_date_front = substr($start_date,0,4);
            $start_date_behind = substr($start_date,5,2);
            $end_date_front = substr($end_date,0,4);
            $end_date_behind = substr($end_date,5,2);
        }
        
        if($new_number == 1){
            $remark = $start_date_front.'.'.ltrim($start_date_behind,'0').'月房租'.$receive.'*'.$new_number;
        }else{
            if($start_date_front == $end_date_front){
                $remark = $start_date_front.'.'.ltrim($start_date_behind,'0').'-'.ltrim($end_date_behind,'0').'月房租'.$receive.'*'.$new_number;
            }else{
                $remark = $start_date_front.'.'.ltrim($start_date_behind,'0').'-'.$end_date_front.'.'.ltrim($end_date_behind,'0').'月房租'.$receive.'*'.$new_number;
            }
        }
        
        // Db::name('house')->where([['house_id','eq',$house_id]])->update(['house_invoice_count'=>$end_date]);

        return $remark;
    }

    public function afterWeixinRecharge($data = array())
    {
        // 生成后台订单
        $row = self::where([['out_trade_no','eq',$data['out_trade_no']]])->find();
        // 更新预付订单
        if($row){

            if($row['recharge_status'] == 0){
                $pay_rent = $data['total_fee'] / 100;

                // 更新房屋余额
                $HouseModel = new HouseModel;
                $house_info = $HouseModel->where([['house_id','eq',$row['house_id']]])->find();
                $yue = bcaddMerge([$house_info['house_balance'],$pay_rent]);
                $house_info->house_balance = $yue;
                $house_info->save();

                // 更新预付订单
                $row->transaction_id = $data['transaction_id'];

                $act_ptime = strtotime($data['time_end']); //实际支付时间

                $stant_ptime = strtotime(date('Y-m',$act_ptime).'-28 23:59:59');// 用于统计的支付时间，如果超出本月28号零时零分零秒则当成下月支付

                if ($act_ptime > $stant_ptime) { //超过或等于28号零时零分零秒，则取下个月零时零分零秒作为支付时间
                    $ptime = strtotime(date('Y-m-d',strtotime('first day of next month')).' 00:00:01');
                }else{
                    $ptime = $act_ptime; // 不超过则按照真实支付时间来
                }

                $row->act_ptime = $act_ptime; //实际支付时间
                $row->ptime = $ptime; //支付时间
                $row->pay_remark = $this->createPayMark($house_info,$row['house_id'],$pay_rent);
                $row->pay_rent = $pay_rent; //支付金额
                $row->yue = $yue;
                $row->trade_type = $data['trade_type']; //支付类型，如：JSAPI
                $row->recharge_status = 1; //充值状态，1充值成功
                $row->save();
                
                
                // 添加房屋台账【待测】
                $HouseTaiModel = new HouseTaiModel;
                $HouseTaiModel->house_id = $house_info['house_id'];
                $HouseTaiModel->tenant_id = $house_info['tenant_id'];
                $HouseTaiModel->cuid = 0;
                $HouseTaiModel->house_tai_type = 2;
                $HouseTaiModel->house_tai_remark = '微信充值：'. $pay_rent .'元，剩余余额：'.$yue.'元。';
                $HouseTaiModel->data_json = [];
                $HouseTaiModel->change_type = '';
                $HouseTaiModel->change_id = '';
                $HouseTaiModel->save();

                
            }

            // 开具电子发票
            // $InvoiceModel = new InvoiceModel;
            // $InvoiceModel->dpkj($row['id'] , $type = 2);
            
        // 如果通过out_trae_no无法找到预付订单，则抛出错误
        }else{
            
        }
    }
    
}