<?php
namespace app\rent\model;

use think\Db;
use think\Model;
use app\common\model\Cparam as ParamModel;
use app\house\model\House as HouseModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\rent\model\Recharge as RechargeModel;
use app\rent\model\RentRecycle as RentRecycleModel;

class RentOrderChild extends Model
{
	// 设置模型名称
    protected $name = 'rent_order_child';
    // 设置主键
    protected $pk = 'id';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        'ptime' => 'timestamp:Y-m-d H:i:s',
    ];
}