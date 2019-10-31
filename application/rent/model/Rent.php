<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\house\model\House;

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
                    $where[] = ['ban_owner_id','eq',$data['ban_owner_id']];
                }
                // 检索【房屋】使用性质
                if(isset($data['house_use_id']) && $data['house_use_id']){
                    $where[] = ['house_use_id','eq',$data['house_use_id']];
                }
                
                // 检索【楼栋】机构
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
                $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];
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
                    $where[] = ['ban_owner_id','eq',$data['ban_owner_id']];
                }
                // 检索【房屋】使用性质
                if(isset($data['house_use_id']) && $data['house_use_id']){
                    $where[] = ['house_use_id','eq',$data['house_use_id']];
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
                // 检索【楼栋】机构
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
                $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];
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
                    $where[] = ['ban_owner_id','eq',$data['ban_owner_id']];
                }
                // 检索【房屋】使用性质
                if(isset($data['house_use_id']) && $data['house_use_id']){
                    $where[] = ['house_use_id','eq',$data['house_use_id']];
                }
                // 检索订单月份
                if(isset($data['rent_order_date']) && $data['rent_order_date']){
                    $queryMonth = substr($data['rent_order_date'],0,4).substr($data['rent_order_date'],-2);
                    $where[] = ['rent_order_date','eq',$queryMonth];
                }
                
                // 检索【楼栋】机构
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
                $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];

                $where[] = ['rent_order_paid','exp',Db::raw('=rent_order_receive')];
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
    public function configRentOrder()
    {
        $currMonth = date('Ym');
        $instid = INST;
        $res = [];
        $undealOrders = self::alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where([['rent_order_date','<',$currMonth],['is_deal','eq',0],['ban_inst_id','in',config('inst_ids')[$instid]]])->count('rent_order_id');
        if($undealOrders){
            return ['code'=>0,'msg'=>'当前有'.$undealOrders.'条订单未处理，无法生成本月订单！'];
        }
        // 只生成当前机构下的订单
        
        //获取当月的租金订单，如果没有则自动生成，有则跳过
        $currMonthOrder = self::alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where([['rent_order_date','eq',$currMonth],['ban_inst_id','in',config('inst_ids')[$instid]]])->value('a.rent_order_id'); 
        //halt($currMonthOrder);
        
        if(!$currMonthOrder){
            $houseModel = new House;
            $where = [];
            $where[] = ['a.house_status','eq',1];
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[$instid]];
            $fields = 'house_id,house_number,tenant_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent';
            $houseArr = $houseModel::alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field($fields)->select();
            //halt($houseArr);
            $str = '';
            foreach ($houseArr as $k => $v) {
                // 减免租金
                $rent_order_cut = 0;
                // 租金订单id
                $rent_order_number = $v['house_number'].$currMonth;

                // 应收 = 规租 + 泵费 + 租差 + 协议租金 - 减免 
                $rent_order_receive = $v['house_pre_rent'] - $rent_order_cut;
                // 待入库的数据
                $str .= "('" . $rent_order_number . "',". $currMonth . ",". $rent_order_cut . ",". $rent_order_receive . ",". $v['house_id'] . "," . $v['tenant_id'] . "),";
            }
            if($str){
                //halt($str);
                $res = Db::execute("insert into ".config('database.prefix')."rent_order (rent_order_number,rent_order_date,rent_order_cut,rent_order_receive,house_id,tenant_id) values " . rtrim($str, ','));
                return ['code'=>1,'msg'=>'生成成功！'];
            }else{
                return ['code'=>0,'msg'=>'未知错误！'];
            }
        }else{
            return ['code'=>0,'msg'=>'生成失败，本月份账单已存在！'];
        }
        
    }

    /**
     *  批量缴费
     */
    public function payList($ids)
    {     
        $res = self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>time(),'pay_way'=>1,'rent_order_paid'=>Db::raw('rent_order_receive')]);
        return $res;
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
     * [payBackList 批量撤回订单]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function payBackList($ids,$nowDate)
    {
        //撤回后，是否处理:0,支付时间:0,支付金额:0,支付方式:0   
         
        $nextDate = date('Y-m',strtotime('1 month',strtotime($nowDate)));
        //halt($nextDate);
        //$where[] = ['rent_order_date','between',[$start,$end]];
        $res = self::where([['rent_order_id','in',$ids],['ptime','between time',[$nowDate,$nextDate]]])->whereOr([['rent_order_date','eq',str_replace('-', '', $nowDate)]])->update(['is_deal'=>0,'ptime'=>0,'rent_order_paid'=>0,'pay_way'=>0]);
        //halt($res);
        return $res;
    }

    public function detail($id)
    {
        $fields = "a.rent_order_id,a.rent_order_number,a.rent_order_date,a.rent_order_number,a.rent_order_receive,a.rent_order_paid,(a.rent_order_receive-a.rent_order_paid) as rent_order_unpaid,a.is_invoice,a.house_id,from_unixtime(a.ptime,'%Y-%m-%d %H:%i:%S') as ptime,a.pay_way,b.house_use_id,c.tenant_id,c.tenant_name,c.tenant_card,c.tenant_tel,d.ban_address,d.ban_owner_id,d.ban_inst_id";
        $row = Db::name('rent_order')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['rent_order_id','eq',$id]])->find();
        $row['pay_info'] = Db::name('rent_order')->where([['house_id','eq',$row['house_id']],['tenant_id','eq',$row['tenant_id']]])->field('sum(rent_order_receive-rent_order_paid) as total_unpaid_rent,sum(rent_order_paid) as total_paid_rent')->find();
        
        return $row;
    }

}