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
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    public function house()
    {
        return $this->belongsTo('app\house\model\House', 'house_id', 'house_id')->bind('ban_number');
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
                $where[] = ['rent_order_date','eq',date('Ym')];
                $where[] = ['is_deal','eq',0];
                break;

            //租金欠缴的查询
            case 'unpaid': 
                $where[] = ['rent_order_date','<',date('Ym')];
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
                // 检索【楼栋】机构
                $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
                $where[] = ['ban_inst_id','in',config('inst_ids')[$instid]];
                break;

            //租金欠缴的查询
            case 'record': 
                // 检索月【租金】订单编号
                if(isset($data['rent_order_number']) && $data['rent_order_number']){
                    $where[] = ['rent_order_number','like','%'.$data['rent_order_number'].'%'];
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

        $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        //$where[] = ;
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
                $rent_order_receive = $v['house_pre_rent'] + $v['house_pump_rent'] + $v['house_diff_rent'] + $v['house_protocol_rent'] - $rent_order_cut;
                // 待入库的数据
                $str .= "('" . $rent_order_number . "',". $currMonth . ",". $rent_order_cut . ",". $rent_order_receive . ",". $v['house_id'] . "," . $v['tenant_id'] . "),";
            }
            if($str){
                //halt($str);
                $res = Db::execute("insert into ".config('database.prefix')."rent_order (rent_order_number,rent_order_date,rent_order_cut,rent_order_receive,house_id,tenant_id) values " . rtrim($str, ','));
                return $res;
            }else{
                return true;
            }
        }else{
            return true;
        }
        
    }

    /**
     *  批量缴费
     */
    public function payList($ids)
    {     
        $res = self::where([['rent_order_id','in',$ids]])->update(['is_deal'=>1,'ptime'=>time(),'rent_order_paid'=>Db::raw('rent_order_receive')]);
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
        $res = self::where([['rent_order_id','in',$ids],['rent_order_date','eq',$nowDate]])->update(['is_deal'=>0,'ptime'=>0,'rent_order_paid'=>0,'pay_way'=>0]);
        return $res;
    }
}