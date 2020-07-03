<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\Recharge as RechargeModel;
use app\rent\model\RentRecycle as RentRecycleModel;

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
                $where[] = ['rent_order_paid','>',0];
                $where[] = ['is_deal','eq',1];
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
            //$where[] = ['f.end_date','>',date('Ym')];
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
            $fields = 'a.house_id,a.house_number,a.tenant_id,a.house_pre_rent,a.house_cou_rent,a.house_pump_rent,a.house_diff_rent,a.house_protocol_rent,f.cut_rent,f.end_date,f.is_valid,d.ban_owner_id';
            $houseArr = Db::name('house')->alias('a')->join('change_cut f','f.house_id = a.house_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field($fields)->select();
            
            //halt($houseArr);
            $str = '';
            foreach ($houseArr as $k => $v) {
                // if($v['house_id'] == '14239'){
                //     dump(1);
                // }
                // 减免租金
                if($v['is_valid'] == 1){
                    $rent_order_cut = ($v['end_date'] > date('Ym'))?$v['cut_rent']:0;
                }elseif($v['is_valid'] === null){
                    $rent_order_cut = 0;
                }else{
                    continue;
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
        $ji = 0;
        // 如果选择了多个房屋，就按照房屋处理租金订单
        if($ids){
            
        // 如果没有，直接处理当前月的所有is_deal = 0 的租金订单
        }else{
            $date = date('Ym');
            
            $where = [];
            $where[] = ['is_deal','eq',0];
            $rent_orders = self::where($where)->field('rent_order_id,rent_order_number,house_id,tenant_id,rent_order_receive,rent_order_paid')->select()->toArray();

            $HouseModel = new HouseModel;
            $houses = $HouseModel->where([['house_balance','>',0]])->column('house_id,house_balance');

           
            foreach ($rent_orders as $k => $v) {
                if(isset($houses[$v['house_id']])){
                    $unpaid_rent = bcsub($v['rent_order_receive'],$v['rent_order_paid'],2);
                    $yue = bcsub($houses[$v['house_id']],$unpaid_rent,2);
                    //halt($unpaid_rent);
                    if($yue >= 0){ //如果余额充足
                        // 扣缴
                        self::where([['rent_order_id','eq',$v['rent_order_id']]])->update([
                            'is_deal'=>1,
                            'ptime'=>time(),
                            'pay_way'=>2,
                            'rent_order_paid' => $unpaid_rent,
                        ]);

                        HouseModel::where([['house_id','eq',$v['house_id']]])->update(['house_balance'=>$unpaid_rent]);
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

    public function pay($id,$pay_rent)
    {
        $ctime = time();

        $row = $this->find($id);

        $old_rent_order_paid = $row->rent_order_paid;

        $row->is_deal = 1;
        $row->ptime = $ctime;
        $row->pay_way = 1;
        $row->rent_order_paid = Db::raw('rent_order_paid+'.$pay_rent);
        $res = $row->save();

        if($row['rent_order_date'] < date('Ym')){ //判断是不是以前月或以前年订单，如果是，则添加收欠记录
            $RentRecycleModel = new RentRecycleModel;
            $RentRecycleModel->house_id = $row['house_id'];
            $RentRecycleModel->tenant_id = $row['tenant_id'];
            //$RentRecycleModel->rent_order_id = $id;
            $RentRecycleModel->pay_rent = $pay_rent;
            $RentRecycleModel->pay_year = substr($row['rent_order_date'],0,4);
            $RentRecycleModel->pay_month = $row['rent_order_date'];
            $RentRecycleModel->cdate = date('Ym',$ctime);
            $RentRecycleModel->ctime = $ctime;
            $RentRecycleModel->save();
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

    }

    /**
     *  批量缴费
     */
    public function payList($ids)
    {     
        $ji = 0;

        $ctime = time();
        $cdate = date('Ym',$ctime);
        foreach($ids as $id){
            // 修改租金订单
            $row = $this->find($id);

            $old_rent_order_paid = $row->rent_order_paid;

            $row->is_deal = 1;
            $row->ptime = $ctime;
            $row->pay_way = 1;
            $row->rent_order_paid = Db::raw('rent_order_receive');
            $res = $row->save();

            if($row['rent_order_date'] < date('Ym')){ //判断是不是以前月或以前年订单，如果是，则添加收欠记录
                $RentRecycleModel = new RentRecycleModel;
                $RentRecycleModel->house_id = $row['house_id'];
                $RentRecycleModel->tenant_id = $row['tenant_id'];
                //$RentRecycleModel->rent_order_id = $id;
                $RentRecycleModel->pay_rent = bcsub($row->rent_order_receive , $old_rent_order_paid,2);
                $RentRecycleModel->pay_year = substr($row['rent_order_date'],0,4);
                $RentRecycleModel->pay_month = $row['rent_order_date'];
                $RentRecycleModel->cdate = $cdate;
                $RentRecycleModel->ctime = $ctime;
                $RentRecycleModel->save();
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
            //halt($row);
            //self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>time(),'pay_way'=>1,'rent_order_paid'=>Db::raw('rent_order_receive')]);
        }
        //$res = self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>time(),'pay_way'=>1,'rent_order_paid'=>Db::raw('rent_order_receive')]);
        //halt($res);
        // 缴费台账
        // $HouseTaiModel = new HouseTaiModel;   
        // $HouseTaiModel->house_id = $v['house_id'];
        // $taiHouseData[$k]['tenant_id'] = $v['tenant_id'];
        // $taiHouseData[$k]['cuid'] = $finalRow['cuid'];
        // $taiHouseData[$k]['house_tai_type'] = 4;
        // $taiHouseData[$k]['house_tai_remark'] = '注销异动单号：'.$finalRow['change_order_number'];
        // $taiHouseData[$k]['data_json'] = [];
        // $taiHouseData[$k]['change_type'] = 8;
        // $taiHouseData[$k]['change_id'] = $finalRow['id']; 
        // $HouseTaiModel->save();
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
    public function payBackList($ids,$nowDate)
    {
        // 验证可以撤回的订单有哪些
        $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));

        //$rentList = self::where([['pay_way','eq','1'],['rent_order_id','in',$ids],['ptime','between time',[$nowDate,$nextDate]]])->whereOr([['pay_way','eq','1'],['rent_order_id','in',$ids],['rent_order_date','eq',str_replace('-', '', $nowDate)]])->select()->toArray();

        $payStartTime = strtotime($nowDate);
        $payEndTime = strtotime('1 month',strtotime($nowDate));
        $res = 0;
        foreach ($ids as $id) {
            $row = $this->find($id);
            //halt($row);
            // 支付方式不是现金支付,跳过
            if($row['pay_way'] != 1){
                continue;
            }
            // 如果是当月的订单
            if($row['rent_order_date'] == str_replace('-', '', $nowDate)){ 
                if($row->getData('ptime') > 0){ //如果有支付时间，
                    $row->is_deal = 0; 
                    $row->rent_order_paid = 0;
                    $row->pay_way = 0;
                    $row->ptime = 0;
                    $row->save();
                }else{ //如果是本月的订单，而且也没支付过，则直接回退订单状态
                    $row->is_deal = 0; 
                    $row->rent_order_paid = 0;
                    $row->pay_way = 0;
                    $row->save();
                }
            // 如果不是当月的订单 
            }else{ 
                // 如果支付时间不是当月，则跳过
                if($row->getData('ptime') < $payStartTime || $row->getData('ptime') >= $payEndTime){ 
                    continue;
                }

                // 如果支付时间是当月，代表是撤回以前年或以前月的收欠
                $RentRecycleModel = new RentRecycleModel;

                $rent_recycle = $RentRecycleModel->where([['house_id','eq',$row['house_id']],['cdate','eq',str_replace('-', '', $nowDate)],['pay_month','eq',$row['rent_order_date']]])->field('sum(pay_rent) as pay_rents')->find();

                $RentRecycleModel->where([['house_id','eq',$row['house_id']],['cdate','eq',str_replace('-', '', $nowDate)],['pay_month','eq',$row['rent_order_date']]])->delete();

                $row->is_deal = 1; 
                $row->rent_order_paid = Db::raw('rent_order_paid-'.$rent_recycle['pay_rents']);
                $row->pay_way = 0;
                $row->ptime = 0;
                $row->save();


                // 是否需要添加撤销的台账记录？
                
            }
            if($row->getData('ptime') > 0){ //如果有支付时间，
                continue;
            }
            // // 如果是欠缴的订单
            // if($row['rent_order_receive'] > $row['rent_order_paid']){ 

            // }
            // // 支付时间不是本月，或者支付方式不是现金支付，或者订单期不是本月
            // if(($row['ptime'] < $payStartTime || $row['ptime'] >= $payEndTime)  || $row['pay_way'] != 1){ 
            //     continue;
            // }

            // if($row['ptime'] > ){

            // }else{

            // }
            // 
            $res++;
        }
        
        //撤回后，是否处理:0,支付时间:0,支付金额:0,支付方式:0   
         
        // $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));

        // $res1 = self::where([['pay_way','eq','1'],['rent_order_id','in',$ids],['ptime','between time',[$nowDate,$nextDate]]])->update(['is_deal'=>0,'ptime'=>0,'rent_order_paid'=>0,'pay_way'=>0]);
        // $res2 = self::where([['pay_way','eq','1'],['rent_order_id','in',$ids],['rent_order_date','eq',str_replace('-', '', $nowDate)]])->update(['is_deal'=>0,'ptime'=>0,'rent_order_paid'=>0,'pay_way'=>0]);
        return $res;
    }

    public function detail($id)
    {
        $fields = "a.rent_order_id,a.rent_order_number,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.house_id,from_unixtime(a.ptime,'%Y-%m-%d %H:%i:%S') as ptime,a.pay_way,b.house_use_id,b.house_number,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        $row['pay_info'] = Db::name('rent_order')->where([['house_id','eq',$row['house_id']],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive-rent_order_paid) as total_unpaid_rent,sum(rent_order_paid) as total_paid_rent')->find();
        
        return $row;
    }

}