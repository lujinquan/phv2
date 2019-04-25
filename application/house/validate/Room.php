<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | Motto ：No pains, no gains !
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>
// +----------------------------------------------------------------------
namespace app\house\validate;

use think\Validate;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;

/**
 * 楼栋验证器
 * @package app\admin\validate
 */
class Room extends Validate
{
    //定义验证规则
    protected $rule = [
        'room_type|房间类型' => 'require|number',
        'room_door|间号' => 'require',
        'room_unit_id|单元号' => 'require|number',
        'room_floor_id|层次' => 'require|number',
        'room_use_area|实有面积' => 'require',
        'ban_number|绑定楼栋' => 'require|existInBan', 
        'house_number|绑定房屋' => 'require|existInHouse', 
    ];

    //定义验证提示
    protected $message = [
        
    ];

    protected function existInBan($value, $rule='', $data)
  	{
    		$row = BanModel::where([['ban_number','eq',$value]])->find();

        if($row){
            if($row['ban_units'] < $data['room_unit_id']){
                return '单元号不能超过所属楼总单元数'.$row['ban_units'];
            }
            if($row['ban_floors'] < $data['room_floor_id']){
                return '楼层号不能超过所属楼总楼层数'.$row['ban_floors'];
            }
            return true;
        }
        return '楼栋编号格式错误';	
  	}

  	protected function existInHouse($value, $rule='', $data)
  	{
		$val = array_filter($value);

		$is = true;
		foreach($val as $v){
			$row = HouseModel::where([['house_number','eq',$v]])->value('house_id');
			if(!$row){
				$is = '房屋编号格式错误';
			}
		}
        if (count($val) != count(array_unique($val))) {
            $is = '房屋编号有重复值';
        }
      	return $is;	
  	}

    //定义验证场景
    protected $scene = [
        //新增
        'sceneForm'  =>  ['room_type','room_door','room_unit_id','room_floor_id','room_use_area','ban_number','house_number'],
        // //修改
        // 'edit'  =>  ['ban_struct_id'],    
    ];
}