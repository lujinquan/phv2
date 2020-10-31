<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\rent\model\Recharge as RechargeModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\RentRecycle as RentRecycleModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;

class Rent extends Model
{
    // 设置模型名称
    protected $name = 'rent_order';
    // 设置主键
    protected $pk = 'rent_order_id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ptime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('app\house\model\House', 'house_id', 'house_id')->bind('house_number,house_use_id,ban_id');
    }

    public function tenant()
    {
        return $this->belongsTo('app\house\model\Tenant', 'tenant_id', 'tenant_id')->bind('tenant_name');
    }

    // public function ban()
    // {
    //     return $this->hasManyThrough('app\house\model\Ban','app\house\model\House','house_number','ban_number');
    // }

    public function checkWhere($data,$type)
    {
        if(!$data){
            $data = request()->param();
        }

        $where = [];
        $where[] = ['rent_order_status','eq',1];
        switch ($type) {
            //租金应缴的查询
            case 'rent': 
                // 检索月【租金】订单编号
                if(isset($data['rent_order_number']) && $data['rent_order_number']){
                    $where[] = ['rent_order_number','like','%'.$data['rent_order_number'].'%'];
                }
                // 检索【租户】姓名
                if(isset($data['tenant_name']) && $data['tenant_name']){
                    $where[] = ['tenant_name','like','%'.$data['tenant_name'].'%'];
                }
                // 检索【房屋】房屋编号
                if(isset($data['house_number']) && $data['house_number']){
                    $where[] = ['house_number','like','%'.$data['house_number'].'%'];
                }
                // 检索【订单】租差
                if(isset($data['rent_order_diff']) && $data['rent_order_diff'] != ''){
                    if($data['rent_order_diff']){
                        $where[] = ['rent_order_diff','>',0];
                    }else{
                        $where[] = ['rent_order_diff','eq',0]; 
                    }
                }
                // 检索【订单】泵费
                if(isset($data['rent_order_pump']) && $data['rent_order_pump'] != ''){
                    if($data['rent_order_pump']){
                        $where[] = ['rent_order_pump','>',0];
                    }else{
                        $where[] = ['rent_order_pump','eq',0]; 
                    }
                }
                // 检索【订单】减免
                if(isset($data['rent_order_cut']) && $data['rent_order_cut'] != ''){
                    if($data['rent_order_cut']){
                        $where[] = ['rent_order_cut','>',0];
                    }else{
                        $where[] = ['rent_order_cut','eq',0]; 
                    }
                }

                // 检索【楼栋】地址
                if(isset($data['ban_address']) && $data['ban_address']){
                    $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
                }
                // 检索【楼栋】产别
                if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
                    $where[] = ['ban_owner_id','in',explode(',',$data['ban_owner_id'])];
                }
                // 检索【房屋】使用性质
                if(isset($data['house_use_id']) && $data['house_use_id']){
                    $where[] = ['house_use_id','in',explode(',',$data['house_use_id'])];
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
                // $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];
                //$where[] = ['rent_order_date','eq',date('Ym')];
                $where[] = ['is_deal','eq',0];
                break;

            //租金欠缴的查询
            case 'unpaid': 
                $where[] = ['is_deal','eq',1];
                $where[] = ['rent_order_paid','exp',Db::raw('<rent_order_receive')];
                // 检索月【租金】订单编号
                if(isset($data['rent_order_number']) && $data['rent_order_number']){
                    $where[] = ['rent_order_number','like','%'.$data['rent_order_number'].'%'];
                }
                // 检索【房屋】编号
                if(isset($data['house_number']) && $data['house_number']){
                    $where[] = ['house_number','like','%'.$data['house_number'].'%'];
                }
                // 检索【租户】姓名
                if(isset($data['tenant_name']) && $data['tenant_name']){
                    $where[] = ['tenant_name','like','%'.$data['tenant_name'].'%'];
                }
                // 检索【楼栋】地址
                if(isset($data['ban_address']) && $data['ban_address']){
                    $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
                }
                // 检索【楼栋】产别
                if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
                    $where[] = ['ban_owner_id','in',explode(',',$data['ban_owner_id'])];
                }
                // 检索【房屋】使用性质
                if(isset($data['house_use_id']) && $data['house_use_id']){
                    $where[] = ['house_use_id','in',explode(',',$data['house_use_id'])];
                }
                // 检索以前年或以前月
                if(isset($data['unpaid_date_type']) && $data['unpaid_date_type']){
                    $start = date('Y').'00';
                    if($data['unpaid_date_type'] == 1){
                        $end = date('Ym',strtotime('- 1 month'));
                        $where[] = ['rent_order_date','between',[$start,$end]];
                    }else if($data['unpaid_date_type'] == 2){
                        $where[] = ['rent_order_date','<',$start];
                    }
                    
                }
                // 检索【账单期】
                if(isset($data['rent_order_date']) && $data['rent_order_date']){
                    $tempDate = str_replace('-', '', $data['rent_order_date']);
                    $where[] = ['rent_order_date','eq',$tempDate];
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
                // $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];
                break;

            //租金欠缴的查询
            case 'record': 
                // 检索月【租金】订单编号
                if(isset($data['house_number']) && $data['house_number']){
                    $where[] = ['house_number','like','%'.$data['house_number'].'%'];
                }
                // 检索【收欠】支付方式
                if(isset($data['pay_way']) && $data['pay_way']){
                    $where[] = ['pay_way','eq',$data['pay_way']];
                }
                // 检索【租户】姓名
                if(isset($data['tenant_name']) && $data['tenant_name']){
                    $where[] = ['tenant_name','like','%'.$data['tenant_name'].'%'];
                }
                // 检索【楼栋】地址
                if(isset($data['ban_address']) && $data['ban_address']){
                    $where[] = ['ban_address','like','%'.$data['ban_address'].'%'];
                }
                // 检索【楼栋】产别
                if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
                    $where[] = ['ban_owner_id','in',explode(',',$data['ban_owner_id'])];
                }
                // 检索【房屋】使用性质
                if(isset($data['house_use_id']) && $data['house_use_id']){
                    $where[] = ['house_use_id','in',explode(',',$data['house_use_id'])];
                }
                // 检索订单月份
                if(isset($data['rent_order_date']) && $data['rent_order_date']){
                    $queryMonth = substr($data['rent_order_date'],0,4).substr($data['rent_order_date'],-2);
                    $where[] = ['rent_order_date','eq',$queryMonth];
                }
                // 缴纳日期
                if(!isset($data['ptime'])){
                    $pStartDate = strtotime(date('Y-m').'-01');
                    $pEndDate = strtotime(date('Y-m',strtotime('1 month')).'-01');
                    //dump($pStartDate);halt($pEndDate);
                    $where[] = ['ptime','between',[$pStartDate,$pEndDate]]; 
                }
                if(isset($data['ptime']) && $data['ptime']){
                    $pStartDate = strtotime(substr($data['ptime'],0,10));
                    $pEndDate = strtotime(substr($data['ptime'],13,11));
                    //dump($pStartDate);halt($pEndDate);
                    $where[] = ['ptime','between',[$pStartDate,$pEndDate]];
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
                // $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];

                //$where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
                //$where[] = ['rent_order_paid','>',0];
                //$where[] = ['is_deal','eq',1];
                break;

            default:
                # code...
                break;
        }


        
        return $where;
    }

    /**
     * [configRentOrder 创建当月的应缴订单]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function configRentOrder($is_all_inst = 0)
    {
        //defined('INST');
        $currMonth = date('Ym');
        $instid = $is_all_inst?$is_all_inst:session('admin_user.inst_id');
        
        //$instid = 5;
        //halt($instid);
        $res = [];
        // $undealOrders = self::alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where([['rent_order_date','<',$currMonth],['is_deal','eq',0],['ban_inst_id','in',config('inst_ids')[$instid]]])->count('rent_order_id');
        // if($undealOrders){
        //     return ['code'=>0,'msg'=>'当前有'.$undealOrders.'条订单未处理，无法生成本月订单！'];
        // }
        // 只生成当前机构下的订单
        
        //获取当月的租金订单，如果没有则自动生成，有则跳过
        $currMonthOrder = self::alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where([['rent_order_date','eq',$currMonth],['ban_inst_id','in',config('inst_ids')[$instid]]])->value('a.rent_order_id');
        //halt(config('inst_ids')[$instid]);
        //halt($currMonthOrder);
        
        if(!$currMonthOrder){
            //$houseModel = new HouseModel;
            $where = [];
            $where[] = ['a.house_status','eq',1];
            //$where[] = ['f.change_status','eq',1];
            $where[] = ['a.house_is_pause','eq',0];
            $where[] = ['a.house_pre_rent','>',0];
            //$where[] = ['f.is_valid','eq',1];
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
            $fields = 'a.house_id,a.house_number,a.tenant_id,a.house_pre_rent,a.house_cou_rent,a.house_pump_rent,a.house_diff_rent,a.house_protocol_rent,d.ban_owner_id';
            $houseArr = Db::name('house')->alias('a')->join('ban d','a.ban_id = d.ban_id','right')->where($where)->field($fields)->select();

            $cutsArr = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','inner')->join('ban c','b.ban_id = c.ban_id','inner')->where([['a.is_valid','eq',1],['c.ban_inst_id','in',config('inst_ids')[$instid]]])->column('a.house_id,a.cut_rent');
            //halt($cutsArr);
            $str = '';
            foreach ($houseArr as $k => $v) {

                if($cutsArr && isset($cutsArr[$v['house_id']])){
                   $rent_order_cut = $cutsArr[$v['house_id']]; 
                }else{
                    $rent_order_cut = 0;
                }
                //$rent_order_cut = ($v['end_date'] > date('Ym'))?$v['cut_rent']:0;
                // 租金订单id
                $rent_order_number = $v['house_number'].$v['ban_owner_id'].$currMonth;

                // 应收 = 规租 + 泵费 + 租差 + 协议租金 - 减免 
                $rent_order_receive = $v['house_pre_rent'] - $rent_order_cut;
                // 待入库的数据
                $str .= "('" . $rent_order_number . "',". $currMonth . ",". $rent_order_cut ."," .$v['house_pre_rent']. ",". $v['house_cou_rent'] . ",". $rent_order_receive . ",". $v['house_id'] . "," . $v['tenant_id']. "," . time() . "),";
            }
            //halt($str);
            if($str){
                //halt($str);
                $res = Db::execute("insert into ".config('database.prefix')."rent_order (rent_order_number,rent_order_date,rent_order_cut,rent_order_pre_rent,rent_order_cou_rent,rent_order_receive,house_id,tenant_id,ctime) values " . rtrim($str, ','));

                unset($str);
                //halt($res);
                return ['code'=>1,'msg'=>'生成成功，一共生成'.$res.'条帐单'];
            }else{
                return ['code'=>0,'msg'=>'未知错误！'];
            }
        }else{
            return ['code'=>0,'msg'=>'生成失败，本月份账单已存在！'];
        }
        
    }

    /**
     *  批量扣缴
     */
    public function autopayList($ids = '')
    {   
        // 暂不支持批量扣缴
        // return false;

        $ji = 0;
        // 如果选择了多个房屋，就按照房屋处理租金订单
        if($ids){
            
        // 如果没有，直接处理当前月的所有is_deal = 0 的租金订单
        }else{
            $date = date('Ym');
            $where = [];
            $where[] = ['is_deal','eq',0];
            $where[] = ['rent_order_date','eq',$date];
            $where[] = ['rent_order_status','eq',1];
            // $rent_orders = self::where($where)->field('rent_order_id,rent_order_number,house_id,tenant_id,rent_order_receive,rent_order_pre_rent,rent_order_cou_rent,rent_order_paid,rent_order_cut,rent_order_pump,rent_order_diff,rent_order_date')->select()->toArray();
            // $where[] = ['b.house_number','eq','10101221426271'];
            //$where[] = ['d.ban_inst_id','in',config('inst_ids')[3]];
            $rent_orders = self::where($where)->field('rent_order_id,rent_order_number,a.house_id,a.tenant_id,rent_order_receive,rent_order_pre_rent,rent_order_cou_rent,rent_order_paid,rent_order_cut,rent_order_pump,rent_order_diff,rent_order_date')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->select()->toArray();

            $HouseModel = new HouseModel;
            $houses = $HouseModel->where([['house_balance','>',0]])->column('house_id,house_balance');

          //halt($rent_orders);
            foreach ($rent_orders as $k => $v) {
                if(isset($houses[$v['house_id']])){

                    $unpaid_rent = bcsub($v['rent_order_receive'],$v['rent_order_paid'],2);
                    $yue = bcsub($houses[$v['house_id']],$unpaid_rent,2);
                    
                    if($yue >= 0){ //如果余额充足

                        // 模拟线上支付
                        if ( false ) {
                            $user_info = Db::name('system_user')->where([['id','eq',ADMIN_ID]])->field('weixin_member_id')->find();
                            //halt($user_info);
                            if (empty($user_info['weixin_member_id'])) {
                                $this->error = '当前管理员未绑定微信会员！';
                                return false;
                            }
                            $weixin_member_id = explode(',',$user_info['weixin_member_id']);
                            //$this->pay_for_rent($row['house_id'], $pay_rent, ADMIN_ID, [$id]);
                            $this->part_order_to_pay($v['rent_order_id'], ADMIN_ID, $weixin_member_id[0], $unpaid_rent, $type = 'YUE');
                        } else {
                            // 扣缴
                            self::where([['rent_order_id','eq',$v['rent_order_id']]])->update([
                                'is_deal'=>1,
                                // 'ptime'=>time(),
                                // 'pay_way'=>2,
                                'rent_order_paid' => $unpaid_rent,
                            ]);



                            // 租金记录
                            $RentOrderChildModel = new RentOrderChildModel;
                            $RentOrderChildModel->rent_order_id = $v['rent_order_id'];
                            $RentOrderChildModel->house_id = $v['house_id'];
                            $RentOrderChildModel->tenant_id = $v['tenant_id'];
                            $RentOrderChildModel->rent_order_paid = $unpaid_rent;
                            $RentOrderChildModel->rent_order_number = $v['rent_order_number'];
                            $RentOrderChildModel->rent_order_receive = $v['rent_order_receive'];
                            $RentOrderChildModel->rent_order_pre_rent = $v['rent_order_pre_rent'];
                            $RentOrderChildModel->rent_order_cou_rent = $v['rent_order_cou_rent'];
                            $RentOrderChildModel->rent_order_cut = $v['rent_order_cut'];
                            $RentOrderChildModel->rent_order_diff = $v['rent_order_diff'];
                            $RentOrderChildModel->rent_order_pump = $v['rent_order_pump'];
                            $RentOrderChildModel->rent_order_date = $v['rent_order_date'];
                            $RentOrderChildModel->ptime = time();
                            $RentOrderChildModel->pay_way = 2;
                            $RentOrderChildModel->save();

                            HouseModel::where([['house_id','eq',$v['house_id']]])->update(['house_balance'=>Db::raw('house_balance-'.$unpaid_rent)]);
                            // 台账
                            $HouseTaiModel = new HouseTaiModel;
                            $HouseTaiModel->house_id = $v['house_id'];
                            $HouseTaiModel->tenant_id = $v['tenant_id'];
                            $HouseTaiModel->cuid = ADMIN_ID;
                            $HouseTaiModel->house_tai_type = 2;
                            $HouseTaiModel->house_tai_remark = '扣缴：'.$unpaid_rent.'元，剩余余额：'.$yue.'元。';
                            $HouseTaiModel->data_json = [];
                            $HouseTaiModel->change_type = '';
                            $HouseTaiModel->change_id = '';
                            $HouseTaiModel->save();

                            // 扣缴记录
                            $RechargeModel = new RechargeModel;
                            $RechargeModel->pay_number = $v['rent_order_number'];
                            $RechargeModel->house_id = $v['house_id'];
                            $RechargeModel->tenant_id = $v['tenant_id'];
                            $RechargeModel->pay_rent = -$unpaid_rent;
                            $RechargeModel->yue = $yue;
                            $RechargeModel->pay_way = 2;
                            $RechargeModel->recharge_status = 1;
                            $RechargeModel->save();

                            $ji++; 
                        }

                        
                    }
                }
            }
        } 
        return $ji;
       /* $ji = 0;
        foreach($ids as $id){

            $row = $this->find($id);
            $row->is_deal = 1;
            $row->ptime = time();
            $row->pay_way = 1;
            $row->rent_order_paid = Db::raw('rent_order_receive');
            $res = $row->save();

            $ji += $res;

            // 添加房屋台账，记录缴费状况
            $HouseTaiModel = new HouseTaiModel;
            $HouseTaiModel->house_id = $row['house_id'];
            $HouseTaiModel->tenant_id = $row['tenant_id'];
            $HouseTaiModel->cuid = ADMIN_ID;
            $HouseTaiModel->house_tai_type = 2;
            $HouseTaiModel->house_tai_remark = '现金缴费：'.$row['rent_order_receive'].'元';
            $HouseTaiModel->data_json = [];
            $HouseTaiModel->change_type = '';
            $HouseTaiModel->change_id = '';
            $HouseTaiModel->save();
            //halt($row);
            //self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>time(),'pay_way'=>1,'rent_order_paid'=>Db::raw('rent_order_receive')]);
        }*/
    }

    /*public function pay_old($id,$pay_rent)
    {
        $ctime = time();

        $row = $this->find($id);

        $old_rent_order_paid = $row->rent_order_paid;

        $row->is_deal = 1;
        $row->ptime = $ctime;
        $row->pay_way = 1;
        $row->rent_order_paid = Db::raw('rent_order_paid+'.$pay_rent);
        $res = $row->save();
        $now_date =  date('Ym');

        if($row['rent_order_date'] <$now_date){ //判断是不是以前月或以前年订单，如果是，则添加收欠记录
            $RentOrderChildModel = new RentRecycleModel;
            $RentOrderChildModel->house_id = $row['house_id'];
            $RentOrderChildModel->tenant_id = $row['tenant_id'];
            $RentOrderChildModel->rent_order_id = $row['rent_order_id'];
            $RentOrderChildModel->pay_rent = $pay_rent;
            $RentOrderChildModel->pay_year = substr($row['rent_order_date'],0,4);
            $RentOrderChildModel->pay_month = $row['rent_order_date'];
            $RentOrderChildModel->cdate = date('Ym',$ctime);
            $RentOrderChildModel->ctime = $ctime;
            $RentOrderChildModel->save();
        }

        // 添加房屋台账，记录缴费状况
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->house_id = $row['house_id'];
        $HouseTaiModel->tenant_id = $row['tenant_id'];
        $HouseTaiModel->cuid = ADMIN_ID;
        $HouseTaiModel->house_tai_type = 2;
        $HouseTaiModel->house_tai_remark = '现金缴费：'.$pay_rent.'元';
        $HouseTaiModel->data_json = [];
        $HouseTaiModel->change_type = '';
        $HouseTaiModel->change_id = '';
        $HouseTaiModel->save();

    }*/

    /**
     * 改版后的功能调整（迭代2.0.3）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-07-28 14:15:45 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function pay($id,$pay_rent)
    {
        $ctime = time();

        $row = $this->find($id);

        // 模拟线上支付
        if ( true ) {
            $user_info = Db::name('system_user')->where([['id','eq',ADMIN_ID]])->field('weixin_member_id')->find();
            //halt($user_info);
            if (empty($user_info['weixin_member_id'])) {
                $this->error = '当前管理员未绑定微信会员！';
                return false;
            }
            $weixin_member_id = explode(',',$user_info['weixin_member_id']);
            //$this->pay_for_rent($row['house_id'], $pay_rent, ADMIN_ID, [$id]);
            $this->part_order_to_pay($id, ADMIN_ID, $weixin_member_id[0] ,$pay_rent);
        } else {
            // 缴费生成一条条子订单
            $RentOrderChildModel = new RentOrderChildModel;
            $RentOrderChildModel->rent_order_id = $id;
            $RentOrderChildModel->house_id = $row['house_id'];
            $RentOrderChildModel->tenant_id = $row['tenant_id'];
            $RentOrderChildModel->rent_order_paid = $pay_rent;
            $RentOrderChildModel->rent_order_number = $row->rent_order_number;
            $RentOrderChildModel->rent_order_receive = $row->rent_order_receive;
            $RentOrderChildModel->rent_order_pre_rent = $row->rent_order_pre_rent;
            $RentOrderChildModel->rent_order_cou_rent = $row->rent_order_cou_rent;
            $RentOrderChildModel->rent_order_cut = $row->rent_order_cut;
            $RentOrderChildModel->rent_order_diff = $row->rent_order_diff;
            $RentOrderChildModel->rent_order_pump = $row->rent_order_pump;
            $RentOrderChildModel->rent_order_date = $row->rent_order_date;
            $RentOrderChildModel->ptime = $ctime;
            $RentOrderChildModel->save();


            $row->rent_order_paid = Db::raw('rent_order_paid+'.$pay_rent);
            $row->is_deal = 1;
            $res = $row->save();

            // 添加房屋台账，记录缴费状况
            $HouseTaiModel = new HouseTaiModel;
            $HouseTaiModel->house_id = $row['house_id'];
            $HouseTaiModel->tenant_id = $row['tenant_id'];
            $HouseTaiModel->cuid = ADMIN_ID;
            $HouseTaiModel->house_tai_type = 2;
            $HouseTaiModel->house_tai_remark = '现金缴费：'.$pay_rent.'元';
            $HouseTaiModel->data_json = [];
            $HouseTaiModel->change_type = '';
            $HouseTaiModel->change_id = '';
            $HouseTaiModel->save();
        }  

        return true;

    }

    /**
     * 订单整体支付
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-24 11:37:54 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function whole_orders_to_pay($ids, $uid, $member_id,$is_need_act_time = true)
    {
        $act_ptime = time();

        if ($is_need_act_time) {
            $stant_ptime = strtotime(date('Y-m',$act_ptime).'-27');// 用于统计的支付时间，如果超出本月28号零时零分零秒则当成下月支付

            if ($act_ptime > $stant_ptime) { //超过或等于28号零时零分零秒，则取下个月零时零分零秒作为支付时间
                $ptime = strtotime(date('Y-m-d',strtotime('first day of next month')).' 00:00:01');
            }else{
                $ptime = $act_ptime; // 不超过则按照真实支付时间来
            }
        }else{
            $ptime = $act_ptime;
        }
        

        $i = 0;
        foreach ($ids as $id) {

            $row = $this->find($id);

            $out_trade_no = date('YmdHis') . random(6);
            $transaction_id = '5000000000' . get_msec_to_mescdate(get_msec_time()) . random(1);
            $pay_rent = bcsub($row->rent_order_receive, $row->rent_order_paid , 2);

            if ($pay_rent <= 0) {
                continue;
            }
            // 模拟生成一条微信支付记录，支付方式为现金支付
            $WeixinOrderModel = new WeixinOrderModel;
            $WeixinOrderModel->pay_money = $pay_rent;
            $WeixinOrderModel->member_id = $member_id;
            $WeixinOrderModel->trade_type = 'CASH';
            $WeixinOrderModel->order_status = 1;
            $WeixinOrderModel->out_trade_no = $out_trade_no;
            $WeixinOrderModel->transaction_id = $transaction_id;
            $WeixinOrderModel->ptime = $ptime;
            $WeixinOrderModel->act_ptime = $act_ptime;
            $WeixinOrderModel->save();

            // 模拟生成一条微信支付关联记录
            $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            $WeixinOrderTradeModel->out_trade_no = $out_trade_no;
            $WeixinOrderTradeModel->transaction_id = $transaction_id;
            $WeixinOrderTradeModel->rent_order_id = $id;
            $WeixinOrderTradeModel->pay_dan_money = $pay_rent;
            $WeixinOrderTradeModel->save();

            // 缴费生成一条条子订单
            $RentOrderChildModel = new RentOrderChildModel;
            $RentOrderChildModel->rent_order_id = $id;
            $RentOrderChildModel->house_id = $row['house_id'];
            $RentOrderChildModel->tenant_id = $row['tenant_id'];
            $RentOrderChildModel->rent_order_paid = $pay_rent;
            $RentOrderChildModel->rent_order_number = $row->rent_order_number;
            $RentOrderChildModel->rent_order_receive = $row->rent_order_receive;
            $RentOrderChildModel->rent_order_pre_rent = $row->rent_order_pre_rent;
            $RentOrderChildModel->rent_order_cou_rent = $row->rent_order_cou_rent;
            $RentOrderChildModel->rent_order_cut = $row->rent_order_cut;
            $RentOrderChildModel->rent_order_diff = $row->rent_order_diff;
            $RentOrderChildModel->rent_order_pump = $row->rent_order_pump;
            $RentOrderChildModel->rent_order_date = $row->rent_order_date;
            $RentOrderChildModel->ptime = $ptime;
            $RentOrderChildModel->act_ptime = $act_ptime;
            $RentOrderChildModel->save();


            $row->rent_order_paid = Db::raw('rent_order_paid+'.$pay_rent);
            $row->is_deal = 1;
            $res = $row->save();

            // 添加房屋台账，记录缴费状况
            $HouseTaiModel = new HouseTaiModel;
            $HouseTaiModel->house_id = $row['house_id'];
            $HouseTaiModel->tenant_id = $row['tenant_id'];
            $HouseTaiModel->cuid = $uid;
            $HouseTaiModel->house_tai_type = 2;
            $HouseTaiModel->house_tai_remark = '现金缴费：'.$pay_rent.'元';
            $HouseTaiModel->data_json = [];
            $HouseTaiModel->change_type = '';
            $HouseTaiModel->change_id = '';
            $HouseTaiModel->save();

            $i++;
        }
        
        return $i;
    }

    /**
     * 订单部分支付
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-24 11:37:54 
     * @return  返回值  
     * @version 版本  1.0
     */
    public function part_order_to_pay($id, $uid, $member_id, $pay_rent, $pay_type = 'CASH')
    {
        $ctime = time();

        $row = $this->find($id);

        $out_trade_no = date('YmdHis') . random(6);

        if($pay_type == 'YUE'){
            // 模拟生成一条余额扣缴记录
            $RechargeModel = new RechargeModel;
            $RechargeModel->recharge_status = 1;
            $RechargeModel->house_id = $row['house_id'];
            $RechargeModel->pay_rent = -$pay_rent;
            $RechargeModel->pay_number = date('YmdHis') . random(6);
            $RechargeModel->tenant_id = $row['tenant_id'];
            $RechargeModel->save();

            // 扣除余额
            HouseModel::where([['house_id','eq',$row['house_id']]])->update(['house_balance'=>Db::raw('house_balance-'.$pay_rent)]);
        } else {
            // 模拟生成一条微信支付记录，支付方式为现金支付
            $WeixinOrderModel = new WeixinOrderModel;
            $WeixinOrderModel->pay_money = $pay_rent;
            $WeixinOrderModel->member_id = $member_id;
            $WeixinOrderModel->trade_type = $pay_type;
            $WeixinOrderModel->order_status = 1;
            $WeixinOrderModel->out_trade_no = $out_trade_no;
            $WeixinOrderModel->ptime = $ctime;
            $WeixinOrderModel->save();

            // 模拟生成一条微信支付关联记录
            $WeixinOrderTradeModel = new WeixinOrderTradeModel;
            $WeixinOrderTradeModel->out_trade_no = $out_trade_no;
            $WeixinOrderTradeModel->rent_order_id = $id;
            $WeixinOrderTradeModel->pay_dan_money = $pay_rent;
            $WeixinOrderTradeModel->save();
        }
        //$pay_rent = bcsub($row->rent_order_receive, $row->rent_order_paid , 2);
        

        // 缴费生成一条条子订单
        $RentOrderChildModel = new RentOrderChildModel;
        $RentOrderChildModel->rent_order_id = $id;
        $RentOrderChildModel->house_id = $row['house_id'];
        $RentOrderChildModel->tenant_id = $row['tenant_id'];
        $RentOrderChildModel->rent_order_paid = $pay_rent;
        $RentOrderChildModel->rent_order_number = $row->rent_order_number;
        $RentOrderChildModel->rent_order_receive = $row->rent_order_receive;
        $RentOrderChildModel->rent_order_pre_rent = $row->rent_order_pre_rent;
        $RentOrderChildModel->rent_order_cou_rent = $row->rent_order_cou_rent;
        $RentOrderChildModel->rent_order_cut = $row->rent_order_cut;
        $RentOrderChildModel->rent_order_diff = $row->rent_order_diff;
        $RentOrderChildModel->rent_order_pump = $row->rent_order_pump;
        $RentOrderChildModel->rent_order_date = $row->rent_order_date;
        $RentOrderChildModel->ptime = $ctime;
        $RentOrderChildModel->save();


        $row->rent_order_paid = Db::raw('rent_order_paid+'.$pay_rent);
        $row->is_deal = 1;
        $res = $row->save();

        

        // 添加房屋台账，记录缴费状况
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->house_id = $row['house_id'];
        $HouseTaiModel->tenant_id = $row['tenant_id'];
        $HouseTaiModel->cuid = $uid;
        $HouseTaiModel->house_tai_type = 2;
        $HouseTaiModel->house_tai_remark = $pay_type.'缴费：'.$pay_rent.'元';
        $HouseTaiModel->data_json = [];
        $HouseTaiModel->change_type = '';
        $HouseTaiModel->change_id = '';
        $HouseTaiModel->save();

    }

    /**
     * 订单支付（迭代2.0.3）
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-24 11:37:54 
     * @param $house_id 房屋id
     * @param $pay_rent 缴费金额
     * @param $uid 管理员id
     * @param $rent_order_ids 租金订单id
     * @return  返回值  
     * @version 版本  1.0
     */
    public function pay_for_rent($house_id, $pay_rent, $uid, $rent_order_ids = array())
    {
        $ctime = time();

        $rentOrderWhere = [];
        $rentOrderWhere[] = ['house_id','eq',$house_id];
        $rentOrderWhere[] = ['rent_order_paid','exp',Db::raw('<rent_order_receive')];
        if($rent_order_ids){
            $rentOrderWhere[] = ['rent_order_id','in',$rent_order_ids];
        }
        $all_unpaid_orders = $this->where($rentOrderWhere)->order('rent_order_date asc')->select()->toArray();

        // 缴费
        if ($all_unpaid_orders) {
            foreach ($all_unpaid_orders as $row) {
                $unpaid_rent = bcsub($row->rent_order_receive, $row->rent_order_paid , 2);
                if($pay_rent > $unpaid_rent){
                    $pay_rent = bcsub($pay_rent, $unpaid_rent , 2);
                    // 缴费生成一条条子订单
                    $RentOrderChildModel = new RentOrderChildModel;
                    $RentOrderChildModel->rent_order_id = $row['rent_order_id'];
                    $RentOrderChildModel->house_id = $row['house_id'];
                    $RentOrderChildModel->tenant_id = $row['tenant_id'];
                    $RentOrderChildModel->rent_order_paid = $unpaid_rent;
                    $RentOrderChildModel->rent_order_number = $row->rent_order_number;
                    $RentOrderChildModel->rent_order_receive = $row->rent_order_receive;
                    $RentOrderChildModel->rent_order_pre_rent = $row->rent_order_pre_rent;
                    $RentOrderChildModel->rent_order_cou_rent = $row->rent_order_cou_rent;
                    $RentOrderChildModel->rent_order_cut = $row->rent_order_cut;
                    $RentOrderChildModel->rent_order_diff = $row->rent_order_diff;
                    $RentOrderChildModel->rent_order_pump = $row->rent_order_pump;
                    $RentOrderChildModel->rent_order_date = $row->rent_order_date;
                    $RentOrderChildModel->ptime = $ctime;
                    $RentOrderChildModel->save();


                    $row->rent_order_paid = Db::raw('rent_order_paid+'.$unpaid_rent);
                    $row->is_deal = 1;
                    $res = $row->save();

                    // 添加房屋台账，记录缴费状况
                    $HouseTaiModel = new HouseTaiModel;
                    $HouseTaiModel->house_id = $row['house_id'];
                    $HouseTaiModel->tenant_id = $row['tenant_id'];
                    $HouseTaiModel->cuid = $uid;
                    $HouseTaiModel->house_tai_type = 2;
                    $HouseTaiModel->house_tai_remark = '小程序现金缴费：'.$unpaid_rent.'元';
                    $HouseTaiModel->data_json = [];
                    $HouseTaiModel->change_type = '';
                    $HouseTaiModel->change_id = '';
                    $HouseTaiModel->save();
                }

            } 
        // 充值
        } else {

            if($pay_rent > 0){ // 如果所有的钱都缴完了还有多的，那就充值
                $RechargeModel = new RechargeModel;
                $filData = [];
                $house_info = HouseModel::where([['house_id','eq',$house_id]])->find();
                $filData['yue'] = bcaddMerge([$house_info['house_balance'],$pay_rent]);
                if($filData['yue'] < 0){
                    return $this->error('充值后余额不能为负');
                }

                
                $filData['recharge_status'] = 1;
                $filData['house_id'] = $house_id;
                $filData['pay_rent'] = $pay_rent;
                $filData['pay_number'] = date('YmdHis') . random(6);
                $filData['tenant_id'] = $house_info['tenant_id'];
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
                $HouseTaiModel->cuid = $uid;
                $HouseTaiModel->house_tai_type = 2;
                $HouseTaiModel->house_tai_remark = '小程序充值：'.$filData['pay_rent'].'元，剩余余额：'.$filData['yue'].'元。';
                $HouseTaiModel->data_json = [];
                $HouseTaiModel->change_type = '';
                $HouseTaiModel->change_id = '';
                $HouseTaiModel->save();
                
            }
        }
        
        

    }

    /*public function payList_old($ids)
    {     
        $ji = 0;
        $ctime = time();
        $cdate = date('Ym',$ctime);
        $now_date =  date('Ym');

        foreach($ids as $id){
            // 修改租金订单
            $row = $this->find($id);

            $old_rent_order_paid = $row->rent_order_paid;

            $row->is_deal = 1;
            $row->ptime = $ctime;
            $row->pay_way = 1;
            $row->rent_order_paid = Db::raw('rent_order_receive');
            $res = $row->save();

            if($row['rent_order_date'] < $now_date){ //判断是不是以前月或以前年订单，如果是，则添加收欠记录
                $RentOrderChildModel = new RentRecycleModel;
                $RentOrderChildModel->house_id = $row['house_id'];
                $RentOrderChildModel->tenant_id = $row['tenant_id'];
                $RentOrderChildModel->rent_order_id = $row['rent_order_id'];
                $RentOrderChildModel->pay_rent = bcsub($row->rent_order_receive , $old_rent_order_paid,2);
                $RentOrderChildModel->pay_year = substr($row['rent_order_date'],0,4);
                $RentOrderChildModel->pay_month = $row['rent_order_date'];
                $RentOrderChildModel->cdate = $cdate;
                $RentOrderChildModel->ctime = $ctime;
                $RentOrderChildModel->save();
            }

            $ji += $res;

            // 添加房屋台账，记录缴费状况
            $HouseTaiModel = new HouseTaiModel;
            $HouseTaiModel->house_id = $row['house_id'];
            $HouseTaiModel->tenant_id = $row['tenant_id'];
            $HouseTaiModel->cuid = ADMIN_ID;
            $HouseTaiModel->house_tai_type = 2;
            $HouseTaiModel->house_tai_remark = '现金缴费：'.$row['rent_order_receive'].'元';
            $HouseTaiModel->data_json = [];
            $HouseTaiModel->change_type = '';
            $HouseTaiModel->change_id = '';
            $HouseTaiModel->save();

        }
        return $ji;
    }*/

    /**
     *  批量缴费（迭代2.0.3已完成）
     */
    public function payList($ids)
    {     
        $ji = 0;
        $ctime = time();
        $cdate = date('Ym',$ctime);
        $now_date =  date('Ym');

        // 模拟线上支付
        if ( true ) {
            $user_info = Db::name('system_user')->where([['id','eq',ADMIN_ID]])->field('weixin_member_id')->find();
            //halt($user_info);
            if (empty($user_info['weixin_member_id'])) {
                $this->error = '当前管理员未绑定微信会员！';
                return false;
            }
            $weixin_member_id = explode(',',$user_info['weixin_member_id']);
            //$this->pay_for_rent($row['house_id'], $pay_rent, ADMIN_ID, [$id]);
            $ji = $this->whole_orders_to_pay($ids, ADMIN_ID, $weixin_member_id[0], $is_need_act_time = false);
            return $ji;
        } else {
            foreach($ids as $id){
                // 修改租金订单
                $row = $this->find($id);

                //$old_rent_order_paid = $row->rent_order_paid;

                $row->is_deal = 1;
                //$row->ptime = $ctime;
                //$row->pay_way = 1;
                

                //if($row['rent_order_date'] < $now_date){ //判断是不是以前月或以前年订单，如果是，则添加收欠记录
                    $RentOrderChildModel = new RentOrderChildModel;
                    $rent_order_paid_total = $RentOrderChildModel->where([['rent_order_id','eq',$id],['rent_order_status','eq',1]])->sum('rent_order_paid'); // 获取该订单累计已缴的金额
                    $RentOrderChildModel->house_id = $row['house_id'];
                    $RentOrderChildModel->tenant_id = $row['tenant_id'];
                    $RentOrderChildModel->rent_order_id = $row['rent_order_id'];
                    $RentOrderChildModel->rent_order_number = $row['rent_order_number'];
                    $RentOrderChildModel->rent_order_receive = $row->rent_order_receive;
                    $RentOrderChildModel->rent_order_pre_rent = $row->rent_order_pre_rent;
                    $RentOrderChildModel->rent_order_cou_rent = $row->rent_order_cou_rent;
                    $RentOrderChildModel->rent_order_cut = $row->rent_order_cut;
                    $RentOrderChildModel->rent_order_diff = $row->rent_order_diff;
                    $RentOrderChildModel->rent_order_pump = $row->rent_order_pump;
                    $RentOrderChildModel->rent_order_date = $row->rent_order_date;
                    
                    // $RentOrderChildModel->pay_year = substr($row['rent_order_date'],0,4);
                    // $RentOrderChildModel->pay_month = $row['rent_order_date'];
                    //$RentOrderChildModel->cdate = $cdate;
                    //if($row['rent_order_date'] < $now_date){ //判断是不是以前月或以前年订单，如果是，则添加收欠记录
                        $RentOrderChildModel->rent_order_paid = bcsub($row->rent_order_receive , $rent_order_paid_total,2);
                    // }else{
                    //     $RentOrderChildModel->
                    // }

                    $row->rent_order_paid = Db::raw('rent_order_paid+'.($RentOrderChildModel->rent_order_paid));
                    
                    $res = $row->save();

                    $RentOrderChildModel->ptime = $ctime;
                    $RentOrderChildModel->save();
                // }else{
                //     $RentOrderChildModel = new RentRecycleModel;
                //     $RentOrderChildModel->house_id = $row['house_id'];
                //     $RentOrderChildModel->tenant_id = $row['tenant_id'];
                //     $RentOrderChildModel->rent_order_id = $row['rent_order_id'];
                //     $RentOrderChildModel->pay_rent = $row->rent_order_receive;
                //     $RentOrderChildModel->pay_year = substr($row['rent_order_date'],0,4);
                //     $RentOrderChildModel->pay_month = $row['rent_order_date'];
                //     $RentOrderChildModel->cdate = $cdate;
                //     $RentOrderChildModel->ctime = $ctime;
                //     $RentOrderChildModel->save();
                // }

                $ji += $res;

                // 添加房屋台账，记录缴费状况
                $HouseTaiModel = new HouseTaiModel;
                $HouseTaiModel->house_id = $row['house_id'];
                $HouseTaiModel->tenant_id = $row['tenant_id'];
                $HouseTaiModel->cuid = ADMIN_ID;
                $HouseTaiModel->house_tai_type = 2;
                $HouseTaiModel->house_tai_remark = '现金缴费：'.$RentOrderChildModel->rent_order_paid.'元';
                $HouseTaiModel->data_json = [];
                $HouseTaiModel->change_type = '';
                $HouseTaiModel->change_id = '';
                $HouseTaiModel->save();
                //halt($row);
                //self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>time(),'pay_way'=>1,'rent_order_paid'=>Db::raw('rent_order_receive')]);
            }
        }
        
        return $ji;
    }

    /**
     *  批量欠缴
     */
    public function unpayList($ids)
    {  
        $res = self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1]);
        return $res;
    }

    /**
     * [payBackList 批量撤回订单],只能撤回现金支付的订单，不能撤回线上支付的订单,【未完成】
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function payBackList($ids,$nowDate,$type = 'unpaid')
    {
        // 验证可以撤回的订单有哪些
        $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));
        $payStartTime = strtotime($nowDate);
        $payEndTime = strtotime('1 month',strtotime($nowDate));
        $re = 0;
        if($type == 'unpaid'){ //欠缴列表里，点击的是order表
            foreach ($ids as $id) {
                
                $row = $this->find($id);
                // halt($row);
                $row->is_deal = 0;     
                //如果是本月的订单，而且也没支付过，则直接回退订单状态            
                $RentOrderChildModel = new RentOrderChildModel;
                $rent_order_paid_total = $RentOrderChildModel->where([['rent_order_id','eq',$id],['pay_way','eq',1],['rent_order_status','eq',1],['ptime','between',[$payStartTime,$payEndTime]]])->sum('rent_order_paid');

                if(floatval($rent_order_paid_total) > 0){ //如果有子订单
                    
                    if($row->rent_order_paid > $rent_order_paid_total){ //如果够减
                        $row->rent_order_paid = Db::raw('rent_order_paid-'.$rent_order_paid_total);
                    }else{
                        $row->rent_order_paid = 0;
                        if($row['rent_order_date'] == str_replace('-', '', $nowDate)){ //如果是本月订单
                            $row->is_deal = 0;
                        }
                    }
                    $row->save();
                }else{ //如果没有子订单，直接修改主订单表
                    $row->pay_way = 0;
                    $row->rent_order_paid = 0;
                    $row->save();
                }

                $RentOrderChildModel = new RentOrderChildModel;
                $RentOrderChildModel->where([['rent_order_id','eq',$id],['rent_order_status','eq',1],['ptime','between',[$payStartTime,$payEndTime]]])->update(['rent_order_status'=>0]); //只删除本月处理的订单，且是现金缴纳方式的订单,'muid'=>ADMIN_ID

                $re++;
            }
        }else{ //租金记录表里，点击的是order_child表
            foreach ($ids as $id) {
                $RentOrderChildModel = new RentOrderChildModel;
                $row = $RentOrderChildModel->find($id);
                // halt($row);
                if($row->getData('ptime') < $payStartTime || $row->getData('ptime') >= $payEndTime || $row->pay_way != 1 || $row->rent_order_status != 1){ //如果不是本月操作的实收订单，或者不是现金交的
                    continue;
                }else{ //如果是本月收的订单
                    $order_row = $this->find($row['rent_order_id']);
                    if($order_row->rent_order_paid > $row->rent_order_paid){ //如果够减
                        $order_row->rent_order_paid = Db::raw('rent_order_paid-'.($row->rent_order_paid));
                    }else{
                        $order_row->rent_order_paid = 0;
                        if($order_row['rent_order_date'] == str_replace('-', '', $nowDate)){ //如果是本月订单
                            $order_row->is_deal = 0;
                        }
                    }
                    $order_row->save(); //修改order表

                    $row->rent_order_status = 0;
                    // $row->muid = ADMIN_ID;
                    $row->save(); //删除时间记录

                    $re++;
                }

            }
        }

        return $re;
    }

    // /**
    //  * [payBackList 批量撤回订单],只能撤回现金支付的订单，不能撤回线上支付的订单,【未完成】
    //  * @param  [type] $data [description]
    //  * @return [type]       [description]
    //  */
    // public function payBackList_old($ids,$nowDate)
    // {
    //     // 验证可以撤回的订单有哪些
    //     $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));

    //     //$rentList = self::where([['pay_way','eq','1'],['rent_order_id','in',$ids],['ptime','between time',[$nowDate,$nextDate]]])->whereOr([['pay_way','eq','1'],['rent_order_id','in',$ids],['rent_order_date','eq',str_replace('-', '', $nowDate)]])->select()->toArray();

    //     $payStartTime = strtotime($nowDate);
    //     $payEndTime = strtotime('1 month',strtotime($nowDate));
    //     $res = 0;
    //     foreach ($ids as $id) {
    //         $row = $this->find($id);
    //         //halt($row);
    //         // 支付方式不是现金支付,跳过
    //         if($row['pay_way'] != 1){
    //             continue;
    //         }
    //         // 如果是当月的订单
    //         if($row['rent_order_date'] == str_replace('-', '', $nowDate)){ 
    //             if($row->getData('ptime') > 0){ //如果有支付时间，
    //                 $row->is_deal = 0; 
    //                 $row->rent_order_paid = 0;
    //                 $row->pay_way = 0;
    //                 $row->ptime = 0;
    //                 $row->save();
    //             }else{ //如果是本月的订单，而且也没支付过，则直接回退订单状态
    //                 $row->is_deal = 0; 
    //                 $row->rent_order_paid = 0;
    //                 $row->pay_way = 0;
    //                 $row->save();
    //             }
    //         // 如果不是当月的订单 
    //         }else{ 
    //             // 如果支付时间不是当月，则跳过
    //             if($row->getData('ptime') < $payStartTime || $row->getData('ptime') >= $payEndTime){ 
    //                 continue;
    //             }

    //             // 如果支付时间是当月，代表是撤回以前年或以前月的收欠
    //             $RentOrderChildModel = new RentRecycleModel;

    //             $rent_recycle = $RentOrderChildModel->where([['house_id','eq',$row['house_id']],['rent_order_status','eq',1],['cdate','eq',str_replace('-', '', $nowDate)],['pay_month','eq',$row['rent_order_date']]])->field('sum(pay_rent) as pay_rents')->find();

    //             $RentOrderChildModel->where([['house_id','eq',$row['house_id']],['cdate','eq',str_replace('-', '', $nowDate)],['rent_order_status','eq',1],['pay_month','eq',$row['rent_order_date']]])->update(['rent_order_status'=>0]);

    //             $row->is_deal = 1; 
    //             $row->rent_order_paid = Db::raw('rent_order_paid-'.$rent_recycle['pay_rents']);
    //             $row->pay_way = 0;
    //             $row->ptime = 0;
    //             $row->save();


    //             // 是否需要添加撤销的台账记录？
                
    //         }
    //         if($row->getData('ptime') > 0){ //如果有支付时间，
    //             continue;
    //         }
    //         // // 如果是欠缴的订单
    //         // if($row['rent_order_receive'] > $row['rent_order_paid']){ 

    //         // }
    //         // // 支付时间不是本月，或者支付方式不是现金支付，或者订单期不是本月
    //         // if(($row['ptime'] < $payStartTime || $row['ptime'] >= $payEndTime)  || $row['pay_way'] != 1){ 
    //         //     continue;
    //         // }

    //         // if($row['ptime'] > ){

    //         // }else{

    //         // }
    //         // 
    //         $res++;
    //     }
        
    //     //撤回后，是否处理:0,支付时间:0,支付金额:0,支付方式:0   
         
    //     // $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));

    //     // $res1 = self::where([['pay_way','eq','1'],['rent_order_id','in',$ids],['ptime','between time',[$nowDate,$nextDate]]])->update(['is_deal'=>0,'ptime'=>0,'rent_order_paid'=>0,'pay_way'=>0]);
    //     // $res2 = self::where([['pay_way','eq','1'],['rent_order_id','in',$ids],['rent_order_date','eq',str_replace('-', '', $nowDate)]])->update(['is_deal'=>0,'ptime'=>0,'rent_order_paid'=>0,'pay_way'=>0]);
    //     return $res;
    // }

    public function detail($id)
    {
        $fields = "a.rent_order_id,a.rent_order_number,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.house_id,from_unixtime(a.ptime,'%Y-%m-%d %H:%i:%S') as ptime,a.pay_way,b.house_use_id,b.house_number,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        $row['pay_info'] = Db::name('rent_order')->where([['house_id','eq',$row['house_id']],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive-rent_order_paid) as total_unpaid_rent,sum(rent_order_paid) as total_paid_rent')->find();
        
        return $row;
    }

    public function get_data($getData,$page = 1,$limit = 10)
    {
        //halt($page);
            // $ownerid = input('param.owner_id/d',1); //默认查询市属
            //       $instid = input('param.inst_id/d',INST); //默认查询当前机构
            //       $useid = input('param.use_id/d',1); //默认查询住宅
            $curMonth = input('param.query_month',date('Y-m')); //默认查询当前年月
            $month = str_replace('-','',$curMonth);
            $params = ParamModel::getCparams();
            $separate = substr($month,0,4).'00';
            $where = [];
            $where[] = ['a.rent_order_date','<=',$month];
            //$where[] = ['a.is_deal','eq',1];

            if(isset($getData['house_id']) && $getData['house_id']){
                $where[] = ['b.house_id','eq',$getData['house_id']];
            }
            //$where[] = ['d.ban_address','like','%烈士街27%'];
            if(isset($getData['house_number']) && $getData['house_number']){
                $where[] = ['b.house_number','like','%'.$getData['house_number'].'%'];
            }
             // 检索【房屋】暂停计租
            if(isset($getData['house_is_pause']) && $getData['house_is_pause'] != ''){
                $where[] = ['b.house_is_pause','eq',$getData['house_is_pause']];
            }
            // 检索【房屋】规定租金
            if(isset($getData['house_pre_rent']) && $getData['house_pre_rent']){
                $where[] = ['b.house_pre_rent','eq',$getData['house_pre_rent']];
            }
            // 检索【房屋】计算租金
            if(isset($getData['house_cou_rent']) && $getData['house_cou_rent']){
                $where[] = ['b.house_cou_rent','eq',$getData['house_cou_rent']];
            }
            // 检索【房屋】计租面积
            if(isset($getData['house_lease_area']) && $getData['house_lease_area']){
                $where[] = ['b.house_lease_area','eq',$getData['house_lease_area']];
            }
            // 检索【房屋】使用性质
            if(isset($getData['house_use_id']) && $getData['house_use_id']){
                $where[] = ['b.house_use_id','in',explode(',',$getData['house_use_id'])];
            }
            // 检索【租户】姓名
            if(isset($getData['tenant_name']) && $getData['tenant_name']){
                $where[] = ['c.tenant_name','like','%'.$getData['tenant_name'].'%'];
            }
            // 检索【楼栋】编号
            if(isset($getData['ban_number']) && $getData['ban_number']){
                $where[] = ['d.ban_number','like','%'.$getData['ban_number'].'%'];
            }
            // 检索【楼栋】地址
            if(isset($getData['ban_address']) && $getData['ban_address']){
                $where[] = ['d.ban_address','like','%'.$getData['ban_address'].'%'];
            }
            // 检索【楼栋】产别
            if(isset($getData['ban_owner_id']) && $getData['ban_owner_id']){
                $where[] = ['d.ban_owner_id','in',explode(',',$getData['ban_owner_id'])];
            }
            // 检索【楼栋】结构类别
            if(isset($getData['ban_struct_id']) && $getData['ban_struct_id']){
                $where[] = ['d.ban_struct_id','in',explode(',',$getData['ban_struct_id'])];
            }
            // 检索【楼栋】完损等级
            if(isset($getData['ban_damage_id']) && $getData['ban_damage_id']){
                $where[] = ['d.ban_damage_id','in',explode(',',$getData['ban_damage_id'])];
            }
            // 检索机构
            if(isset($getData['ban_inst_id']) && $getData['ban_inst_id']){
                $insts = explode(',',$getData['ban_inst_id']);
                $instid_arr = [];
                foreach ($insts as $inst) {
                    foreach (config('inst_ids')[$inst] as $instid) {
                        $instid_arr[] = $instid;
                    }
                }
                $where[] = ['d.ban_inst_id','in',array_unique($instid_arr)];
            }else{
                $where[] = ['d.ban_inst_id','in',config('inst_ids')[INST]];
            }

            // 列表页多选
            if(isset($getData['id']) && is_array($getData['id']) && $getData['id']){
                $where[] = ['b.house_id','in',$getData['id']];
            }

            $systemUsers = Db::name('system_user')->where([['role_id','eq',4],['status','eq',1]])->column('inst_id,nick,mobile');

            // if($useid != 0){
            //     $where[] = ['b.house_use_id','eq',$useid];
            // }
            // if($ownerid != 0){
            //     $where[] = ['d.ban_owner_id','eq',$ownerid];
            // }
            $where[] = ['a.rent_order_receive','>','a.rent_order_paid'];
            //$where[] = ['rent_order_receive','eq',rent_order_paid];
            //$where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
            $fields = 'a.house_id,b.house_number,a.rent_order_date,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive - a.rent_order_paid) as rent_order_unpaid,b.house_use_id,b.house_pre_rent,b.house_share_img,c.tenant_name,d.ban_number,d.ban_address,d.ban_owner_id,d.ban_inst_id,d.ban_owner_id';
            $result = $data = [];
            $baseData = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->select();
            //dump($where);halt($baseData);
            //dump($month);dump($separate);
            $total_cur_month_unpaid_rent = 0;
            $total_before_month_unpaid_rent = 0;
            $total_before_year_unpaid_rent = 0;
            $total_unpaid_rent = 0;
            foreach($baseData as $b){ //dump($b['rent_order_date']);dump($separate);halt('201906' > $separate);//halt($b);

                if($b['rent_order_unpaid'] == 0){
                    continue;
                }
                $data[$b['house_id']]['house_id'] = $b['house_id'];
                $data[$b['house_id']]['house_share_img'] = $b['house_share_img'];
                $data[$b['house_id']]['house_number'] = $b['house_number'];
                $data[$b['house_id']]['ban_number'] = $b['ban_number'];
                $data[$b['house_id']]['ban_address'] = $b['ban_address'];
                $data[$b['house_id']]['tenant_name'] = $b['tenant_name'];
                $data[$b['house_id']]['house_pre_rent'] = $b['house_pre_rent'];
                $data[$b['house_id']]['house_use_id'] = $params['uses'][$b['house_use_id']];
                $data[$b['house_id']]['ban_owner_id'] = $params['owners'][$b['ban_owner_id']];
                $data[$b['house_id']]['ban_inst_id'] = $params['insts'][$b['ban_inst_id']];
                $data[$b['house_id']]['system_user_mobile'] = $systemUsers[$b['ban_inst_id']]['mobile'];
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

            if($page){ // 如果分页
                $result['data'] = array_slice($data, ($page - 1) * $limit, $limit);
            }else{ // 如果不分页
                $result['data'] = $data;
            }
            
            $result['count'] = count($data);
            $result['total_cur_month_unpaid_rent'] = $total_cur_month_unpaid_rent;
            $result['total_before_month_unpaid_rent'] = $total_before_month_unpaid_rent;
            $result['total_before_year_unpaid_rent'] = $total_before_year_unpaid_rent;
            $result['total_unpaid_rent'] = $total_unpaid_rent;
            $result['code'] = 0;
            $result['msg'] = '';
            return $result;
        
    }

}