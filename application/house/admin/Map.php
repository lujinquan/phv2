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

namespace app\house\admin;

use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;

class Map extends Admin
{
	public function index()
	{
		if ($this->request->isAjax()) {
            // $page = input('param.page/d', 1);
            // $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $banModel = new BanModel;
            $where = $banModel->checkWhere($getData);
            //$fields = 'ban_id,ban_number,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,(ban_civil_rent+ban_party_rent+ban_career_rent) as ban_rent,(ban_civil_area+ban_party_area+ban_career_area) as ban_area,ban_use_area,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_property_source,ban_units,ban_floors,(ban_civil_holds+ban_party_holds+ban_career_holds) as ban_holds';
            $fields = 'ban_id,ban_number,ban_area_two,ban_area_three,ban_address as z,ban_gpsx as x,ban_gpsy as y,b.area_title';
            $data = [];
            $points = Db::name('ban')->alias('a')->join('area b','a.AreaThree = b.id','left')->field('BanID ,BanGpsX ,BanGpsY,a.AreaFour,a.AreaThree,b.GpsX,b.GpsY,b.AreaTitle')->where($where)->select();
            /*$houses = HouseModel::group('ban_id')->column('ban_id, count(house_id) as house_ids');
            $points = $banModel->alias('a')->join('base_area b','a.ban_area_three = b.id','left')->field($fields)->where($where)->order('ban_ctime desc')->select()->toArray();*/
            $data['data'] = $points;
           
 
            /*foreach($points as $key => $value){

                  $data['point'][$value['ban_area_three']]['name'] = $value['area_title'];
                  $data['point'][$value['ban_area_three']]['x'] = $value['x'];
                  $data['point'][$value['ban_area_three']]['y'] = $value['y'];
                  $data['point'][$value['ban_area_three']]['detail'][] = $value;
                  if(!isset($data['point'][$value['ban_area_three']]['total_house'])){
                      $data['point'][$value['ban_area_three']]['total_house'] = 0;
                  }
                  if(isset($houses[$value['ban_id']])){
                        $data['point'][$value['ban_area_three']]['total_house'] += $houses[$value['ban_id']];
                  }

            }*/
            $data['count'] = count($data['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}
}