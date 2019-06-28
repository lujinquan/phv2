<?php
namespace app\house\model;

use app\system\model\SystemBase;

class FloorPoint extends SystemBase
{
	// 设置模型名称
    protected $name = 'floor_point';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $type = [
        //'tenant_ctime' => 'timestamp:Y-m-d H:i:s',
    ];

    /**
     * 计算层次调解率
     * @param  [type] $liveFloor   [居住层]
     * @param  [type] $BanFloorNum [所属楼总楼层数]
     * @param  [type] $ifElevator  [是否有电梯，默认无电梯]
     * @return [type] 
     */
    public function get_floor_point($liveFloor,$BanFloorNum,$ifElevator = 0){
        //dump($liveFloor);dump($BanFloorNum);dump($ifElevator);
        if($ifElevator == 0){ //无电梯
            if($BanFloorNum>3){
                $floorPoint = self::where([['floor_total','eq',$liveFloor],['floor_live','eq',$BanFloorNum]])->value('floor_point');
                //$floorPoint = Db::name('floor_point')->where(['TotalFloor'=> $BanFloorNum ,'LiveFloor'=> $liveFloor])->value('FloorPoint');
                if ($liveFloor >= 9) $floorPoint = 0.85; //9楼以上层次调解率为0.85
            }else{
                $floorPoint = 1;
            }
            
        }elseif($ifElevator == 1){  //有电梯，免费用
            if($liveFloor<4){
                if($BanFloorNum>3){
                    $floorPoint = self::where([['floor_total','eq',$liveFloor],['floor_live','eq',$BanFloorNum]])->value('floor_point');
                    //$floorPoint = Db::name('floor_point')->where(['TotalFloor'=> $BanFloorNum ,'LiveFloor'=> $liveFloor])->value('FloorPoint');
                    if ($liveFloor >= 9) $floorPoint = 0.85; //9楼以上层次调解率为0.85
                }else{
                    $floorPoint = 1;
                }
                
            }elseif($liveFloor>3 && $liveFloor!=$BanFloorNum){
                $floorPoint = 1.05;
            }elseif($liveFloor>3 && $liveFloor==$BanFloorNum){
                $floorPoint = 0.85;
            }
        }elseif($ifElevator == 2){ //有电梯，需交费
            if($liveFloor<3){
               if($BanFloorNum>3){
                    $floorPoint = self::where([['floor_total','eq',$liveFloor],['floor_live','eq',$BanFloorNum]])->value('floor_point');
                    //$floorPoint = Db::name('floor_point')->where(['TotalFloor'=> $BanFloorNum ,'LiveFloor'=> $liveFloor])->value('FloorPoint');
                    if ($liveFloor >= 9) $floorPoint = 0.85; //9楼以上层次调解率为0.85
                }else{
                    $floorPoint = 1;
                }
            }elseif($liveFloor>2 && $liveFloor!=$BanFloorNum){
                $floorPoint = 1;
            }elseif($liveFloor>2 && $liveFloor==$BanFloorNum){
                $floorPoint = 0.85;
            }
        }

        return isset($floorPoint)?$floorPoint:1;
    }

    
}