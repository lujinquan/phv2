<?php

namespace app\system\model;

use think\Model;
use think\Db;
use app\house\model\Tenant as TenantModel;
use app\house\model\House as HouseModel;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;

/**
 * 数据查询器模型
 * @package app\system\model
 */
class SystemData extends Model
{
    

    public function queryBan($queryWhere)
    {
    	$page = input('param.page/d', 1);
        $limit = input('param.limit/d', 10);
        $where[] = [
            ['ban_status','eq',1], // 默认查询正常状态下的房屋
        ];
        $where[] = [
            ['ban_inst_id','in',config('inst_ids')[INST]], // 默认查询当前机构下的房屋
        ];
        
        if(isset($queryWhere['ban_number']) && $queryWhere['ban_number']){ //查询楼栋编号
            $where[] = ['ban_number','like','%'.$queryWhere['ban_number'].'%'];
        }
        if(isset($queryWhere['ban_address']) && $queryWhere['ban_address']){ //查询楼栋地址
            $where[] = ['ban_address','like','%'.$queryWhere['ban_address'].'%'];
        }
        if(isset($queryWhere['ban_inst_id']) && $queryWhere['ban_inst_id']){ //查询机构
            $where[] = ['ban_inst_id','in',config('inst_ids')[$queryWhere['ban_inst_id']]];
        }
        if(isset($queryWhere['ban_status'])){ //查询房屋状态
            $where[] = ['ban_status','eq',$queryWhere['ban_status']];
        }

        $BanModel = new BanModel;
        $fields = 'ban_id,ban_number,ban_inst_id,ban_address,ban_owner_id,ban_damage_id,ban_struct_id,ban_civil_area,ban_party_area,ban_career_area,(ban_civil_area + ban_party_area + ban_career_area) as ban_area,ban_civil_num,ban_party_num,ban_career_num,(ban_civil_num+ban_party_num+ban_career_num) as ban_num,ban_civil_rent,ban_party_rent,ban_career_rent,(ban_civil_rent + ban_party_rent + ban_career_rent) as ban_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_use_area,ban_floors';

        $data = [];
        //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
        $data['data'] = $BanModel->field($fields)->where($where)->page($page)->order('ban_ctime desc,ban_id desc')->limit($limit)->select();
        $data['count'] = $BanModel->where($where)->count();
        $data['code'] = 0;
        $data['msg'] = '';

        return $data;
    }

    public function queryTenant($queryWhere)
    {
    	$page = input('param.page/d', 1);
        $limit = input('param.limit/d', 10);
        $where[] = [
            ['tenant_status','eq',1], // 默认查询正常状态下的租户
        ];
        $where[] = [
            ['tenant_inst_id','in',config('inst_ids')[INST]], // 默认查询当前机构下的租户
        ];
        
        if(isset($queryWhere['tenant_number']) && $queryWhere['tenant_number']){ //查询租户编号
            $where[] = ['tenant_number','like','%'.$queryWhere['tenant_number'].'%'];
        }
        if(isset($queryWhere['tenant_name']) && $queryWhere['tenant_name']){ //查询租户姓名
            $where['ban'][] = ['tenant_name','like','%'.$queryWhere['tenant_name'].'%'];
        }
        if(isset($queryWhere['tenant_inst_id']) && $queryWhere['tenant_inst_id']){ //查询机构
            $where[] = ['tenant_inst_id','in',config('inst_ids')[$queryWhere['tenant_inst_id']]];
        }
        if(isset($queryWhere['tenant_status'])){ //查询租户状态
            $where[] = ['tenant_status','eq',$queryWhere['tenant_status']];
        }
        
        $TenantModel = new TenantModel;

        $fields = 'tenant_id,tenant_inst_id,tenant_number,tenant_name,tenant_tel,tenant_card';

        $data = [];
        //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
        $data['data'] = $TenantModel->field($fields)->where($where)->page($page)->order('tenant_ctime desc,tenant_id desc')->limit($limit)->select();
        $data['count'] = $TenantModel->where($where)->count();
        $data['code'] = 0;
        $data['msg'] = '';

        return $data;
    }

    public function queryHouse($queryWhere)
    {
    	$page = input('param.page/d', 1);
        $limit = input('param.limit/d', 10);
        $where['house'] = [
            ['house_status','eq',1], // 默认查询正常状态下的房屋
        ];
        $where['ban'] = [
            ['ban_inst_id','in',config('inst_ids')[INST]], // 默认查询当前机构下的房屋
        ];
        $where['tenant'] = [
        ];
        if(isset($queryWhere['house_number']) && $queryWhere['house_number']){ //查询房屋编号
            $where['house'][] = ['house_number','like','%'.$queryWhere['house_number'].'%'];
        }
        if(isset($queryWhere['tenant_name']) && $queryWhere['tenant_name']){ //查询租户姓名
            $where['tenant'][] = ['tenant_name','like','%'.$queryWhere['tenant_name'].'%'];
        }
        if(isset($queryWhere['ban_address']) && $queryWhere['ban_address']){ //查询楼栋地址
            $where['ban'][] = ['ban_address','like','%'.$queryWhere['ban_address'].'%'];
        }
        if(isset($queryWhere['ban_number']) && $queryWhere['ban_number']){ //查询楼栋编号
            $where['ban'][] = ['ban_number','like','%'.$queryWhere['ban_number'].'%'];
        }
        if(isset($queryWhere['ban_inst_id']) && $queryWhere['ban_inst_id']){ //查询机构
            $where['ban'][] = ['ban_inst_id','in',config('inst_ids')[$queryWhere['ban_inst_id']]];
        }
        if(isset($queryWhere['house_status'])){ //查询房屋状态
            $where['house'][] = ['house_status','eq',$queryWhere['house_status']];
        }
        
        if(isset($queryWhere['change_type']) && $queryWhere['change_type']){ //如果异动类型有值，则验证房屋是否符合暂停计租要求
            switch ($queryWhere['change_type']) {
                case 4: //陈欠核销 【待优化】
                    $houseids = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->group('house_id')->column('house_id');
                    //halt($houseids);
                    $where['house'][] = ['house_id','in',$houseids];
                    $where['house'][] = ['house_status','eq',1];
                    break;

                case 13: //使用权变更
                    $where['house'][] = ['house_status','eq',1];
                    break;

                case 16: //减免年审
                    $houseids = Db::name('change_cut')->where([['change_status','eq',1]])->column('house_id');
                    $where['house'][] = ['house_id','in',$houseids];
                    $where['house'][] = ['house_status','eq',1];
                    //halt($where);
                    break;

                case 18: //发租约
                    // $houseids = Db::name('change_cut')->where([['change_status','eq',1]])->column('house_id');
                    // $where['house'][] = ['house_id','in',$houseids];
                    $where['house'][] = ['house_use_id','eq',1];
                    $where['house'][] = ['house_status','eq',1];
                    //halt($where);
                    break;
                
                default:
                    # code...
                    break;
            }
            
        }

        $HouseModel = new HouseModel;

        $fields = 'house_id,house_balance,house_pre_rent,house_cou_rent,house_use_id,house_unit_id,house_floor_id,house_lease_area,house_area,(house_pre_rent + house_diff_rent + house_pump_rent) as house_yue_rent';

        $data = [];
        //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
        $data['data'] = $HouseModel->withJoin([
             'ban'=> function($query)use($where){ //注意闭包传参的方式
                 $query->where($where['ban']);
             },
             'tenant'=> function($query)use($where){
                 $query->where($where['tenant']);
             },
             ],'left')->field($fields)->where($where['house'])->page($page)->order('house_ctime desc,house_id desc')->limit($limit)->select();
        $data['count'] = $HouseModel->withJoin([
             'ban'=> function($query)use($where){ //注意闭包传参的方式
                 $query->where($where['ban']);
             },
             ],'left')->where($where['house'])->count();
        $data['code'] = 0;
        $data['msg'] = '';

        return $data;
    }



}
