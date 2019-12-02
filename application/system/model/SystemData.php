<?php

namespace app\system\model;


use think\Db;
use think\Model;
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
        $where[] = ['ban_inst_id','in',config('inst_ids')[INST]]; // 默认查询当前机构下的房屋
        if(isset($queryWhere['ban_number']) && $queryWhere['ban_number']){ //查询楼栋编号
            $where[] = ['ban_number','like','%'.$queryWhere['ban_number'].'%'];
        }
        if(isset($queryWhere['ban_address']) && $queryWhere['ban_address']){ //查询楼栋地址
            $where[] = ['ban_address','like','%'.$queryWhere['ban_address'].'%'];
        }
        if(isset($queryWhere['ban_inst_id']) && $queryWhere['ban_inst_id']){ //查询机构
            $where[] = ['ban_inst_id','in',config('inst_ids')[$queryWhere['ban_inst_id']]];
        }
        if(isset($queryWhere['ban_owner_id']) && $queryWhere['ban_owner_id']){
            $where[] = ['ban_owner_id','eq',$queryWhere['ban_owner_id']];
        }
        if(isset($queryWhere['change_type']) && $queryWhere['change_type']){ //如果异动类型有值，则验证房屋是否符合暂停计租要求
            switch ($queryWhere['change_type']) {
                case 3: //暂停计租
                    $where[] = ['ban_civil_holds|ban_party_holds|ban_career_holds','>',0];
                    break;
                case 7: //新发
                    $where[] = ['ban_status','<',2];
                    break;
                case 10: //管段调整
                    $where[] = ['ban_status','eq',1];
                    $tempApplyBanidArr = Db::name('change_inst')->where([['change_status','>',1]])->column('ban_ids');
                    if($tempApplyBanidArr){
                        $applyBanidArr = explode(',',implode(',',$tempApplyBanidArr));
                    }
                    break;
                case 14: //楼栋调整
                    $where[] = ['ban_status','eq',1];
                    $applyBanidArr = Db::name('change_ban')->where([['change_status','>',1]])->column('ban_id');
                    break;
                case 17: //别字更正
                    break;
                case 18: //发租约
                    break;
                
                default:
                    $where[] = ['ban_status','eq',1]; // 默认查询正常状态下的房屋
                    break;
            }  
        }else{
            $where[] = ['ban_status','eq',1]; // 默认查询正常状态下的房屋
        }
        

        $BanModel = new BanModel;
        $fields = 'ban_id,ban_holds,ban_number,ban_inst_id,ban_address,ban_owner_id,ban_damage_id,ban_struct_id,ban_civil_area,ban_party_area,ban_career_area,(ban_civil_area + ban_party_area + ban_career_area) as ban_area,ban_civil_num,ban_party_num,ban_career_num,(ban_civil_num+ban_party_num+ban_career_num) as ban_num,ban_civil_rent,ban_party_rent,ban_career_rent,(ban_civil_rent + ban_party_rent + ban_career_rent) as ban_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,(ban_civil_oprice+ban_party_oprice+ban_career_oprice) as ban_oprice,ban_use_area,ban_floors';

        $data = [];
        //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
        $temps = $BanModel->field($fields)->where($where)->page($page)->order('ban_ctime desc,ban_id desc')->limit($limit)->select();
//halt();
        foreach ($temps as $k => &$v) {
            
            $v['color_status'] = 1; //正常的
            
            if(isset($applyBanidArr) && $applyBanidArr){
                if(in_array($v['ban_id'], $applyBanidArr)){
                    $v['color_status'] = 2; // 已在异动中
                }
            }
        }
        $data['data'] = $temps;
        $data['count'] = $BanModel->where($where)->count();
        $data['code'] = 0;
        $data['msg'] = '';

        return $data;
    }

    public function queryTenant($queryWhere)
    {
    	$page = input('param.page/d', 1);
        $limit = input('param.limit/d', 10);
        $where[] = ['tenant_inst_id','in',config('inst_ids')[INST]]; // 默认查询当前机构下的租户
        if(isset($queryWhere['tenant_number']) && $queryWhere['tenant_number']){ //查询租户编号
            $where[] = ['tenant_number','like','%'.$queryWhere['tenant_number'].'%'];
        }
        if(isset($queryWhere['tenant_name']) && $queryWhere['tenant_name']){ //查询租户姓名
            $where[] = ['tenant_name','like','%'.$queryWhere['tenant_name'].'%'];
        }
        if(isset($queryWhere['tenant_tel']) && $queryWhere['tenant_tel']){ //查询租户手机号
            $where[] = ['tenant_tel','like','%'.$queryWhere['tenant_tel'].'%'];
        }
        if(isset($queryWhere['tenant_inst_id']) && $queryWhere['tenant_inst_id']){ //查询机构
            $where[] = ['tenant_inst_id','in',config('inst_ids')[$queryWhere['tenant_inst_id']]];
        }
        if(isset($queryWhere['tenant_status'])){ //查询租户状态
            $where[] = ['tenant_status','eq',$queryWhere['tenant_status']];
        }
        if(isset($queryWhere['change_type']) && $queryWhere['change_type']){ //如果异动类型有值，则验证房屋是否符合暂停计租要求
            switch ($queryWhere['change_type']) {
                case 7: //新发
                    $where[] = ['tenant_status','<',2];
                    break;
                case 17: //别字更正
                    break;
                case 18: //发租约
                    break;
                
                default:
                    $where[] = ['tenant_status','eq',1]; // 默认查询正常状态下的租户 
                    break;
            }  
        }else{
            $where[] = ['tenant_status','eq',1]; // 默认查询正常状态下的租户 
        }
//halt($where);    
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
        
        $where[] = ['c.ban_inst_id','in',config('inst_ids')[INST]]; // 默认查询当前机构下的房屋
        if(isset($queryWhere['house_number']) && $queryWhere['house_number']){ //查询房屋编号
            $where[] = ['a.house_number','like','%'.$queryWhere['house_number'].'%'];
        }
        if(isset($queryWhere['tenant_name']) && $queryWhere['tenant_name']){ //查询租户姓名
            $where[] = ['b.tenant_name','like','%'.$queryWhere['tenant_name'].'%'];
        }
        if(isset($queryWhere['ban_address']) && $queryWhere['ban_address']){ //查询楼栋地址
            $where[] = ['c.ban_address','like','%'.$queryWhere['ban_address'].'%'];
        }
        if(isset($queryWhere['ban_number']) && $queryWhere['ban_number']){ //查询楼栋编号
            $where[] = ['c.ban_number','like','%'.$queryWhere['ban_number'].'%'];
        }
        if(isset($queryWhere['ban_inst_id']) && $queryWhere['ban_inst_id']){ //查询机构
            $where[] = ['c.ban_inst_id','in',config('inst_ids')[$queryWhere['ban_inst_id']]];
        }
        
        if(isset($queryWhere['change_type']) && $queryWhere['change_type']){ //如果异动类型有值，则验证房屋是否符合暂停计租要求
            switch ($queryWhere['change_type']) {
                case 1: //租金减免
                    $where[] = ['a.house_status','eq',1];
                    $where[] = ['a.house_is_pause','eq',0];
                    $applyHouseidArr = Db::name('change_cut')->where([['change_status','>',0]])->column('house_id'); //减免成功的或在减免中的不能再次申请
                    break;
                case 3: //暂停计租 【待优化】
                    $tempApplyHouseidArr = Db::name('change_pause')->where([['change_status','>',1]])->column('house_id');
                    if($tempApplyHouseidArr){
                        $applyHouseidArr = explode(',',implode(',',$tempApplyHouseidArr));
                    }
                    $where[] = ['house_status','eq',1];
                    $where[] = ['house_is_pause','eq',0];
                    break;
                case 4: //陈欠核销 【待优化】
                    $houseids = RentModel::where([['rent_order_paid','exp',Db::raw('<rent_order_receive')]])->group('house_id')->column('house_id');
                    $applyHouseidArr = Db::name('change_offset')->where([['change_status','>',1]])->column('house_id');
                    $where[] = ['house_id','in',$houseids];
                    $where[] = ['house_status','eq',1];
                    break;
                case 7: //新发租
                    $where[] = ['house_status','eq',0];
                    break;
                case 9: //房屋调整
                    $where[] = ['a.house_status','eq',1];
                    $applyHouseidArr = Db::name('change_house')->where([['change_status','>',1]])->column('house_id');
                    break;
                case 11: //租金追加调整
                    $where[] = ['a.house_status','eq',1];
                    $applyHouseidArr = Db::name('change_rentadd')->where([['change_status','>',1]])->column('house_id');
                    break;
                case 13: //使用权变更
                    $where[] = ['a.house_status','eq',1];
                    $where[] = ['house_is_pause','eq',0];
                    $applyHouseidArr = Db::name('change_use')->where([['change_status','>',1]])->column('house_id');
                    break;

                case 16: //减免年审
                    $houseids = Db::name('change_cut')->where([['change_status','eq',1]])->column('house_id');
                    $where[] = ['house_id','in',$houseids];
                    $where[] = ['house_status','eq',1];
                    $where[] = ['house_is_pause','eq',0];
                    $applyHouseidArr = Db::name('change_cut_year')->where([['change_status','>',1]])->column('house_id');
                    break;
                case 17: //别字更正
                    $where[] = ['a.house_status','eq',1];
                    $where[] = ['a.house_is_pause','eq',0];
                    $applyHouseidArr = Db::name('change_name')->where([['change_status','>',1]])->column('house_id');
                    break;
                case 18: //发租约
                    // $houseids = Db::name('change_cut')->where([['change_status','eq',1]])->column('house_id');
                    // $where['house'][] = ['house_id','in',$houseids];
                    $where[] = ['house_use_id','eq',1];
                    $where[] = ['house_status','eq',1];
                    $applyHouseidArr = Db::name('change_lease')->where([['change_status','>',1]])->column('house_id');
                    //halt($where);
                    break;
                
                default:
                    $where[] = ['a.house_status','eq',1]; // 默认查询正常状态下的房屋
                    
                    break;
            }
            
        }else{
            $where[] = ['a.house_status','eq',1]; // 默认查询正常状态下的房屋
        }

        $HouseModel = new HouseModel;

        $fields = 'a.house_id,a.house_number,a.house_door,a.house_balance,a.house_pump_rent,a.house_oprice,a.house_diff_rent,a.house_pre_rent,a.house_protocol_rent,a.house_cou_rent,a.house_use_id,a.house_unit_id,a.house_floor_id,a.house_lease_area,a.house_use_area,a.house_area,b.*,c.*';

        $data = [];
        //一、这种可以实现关联模型查询，并只保留查询的结果【无法关联的数据剔除掉】）
        // $temps = $HouseModel->withJoin([
        //      'ban'=> function($query)use($where){ //注意闭包传参的方式
        //          $query->where($where['ban']);
        //      },
        //      'tenant'=> function($query)use($where){
        //          $query->where($where['tenant']);
        //      },
        //      ],'left')->field($fields)->where($where['house'])->page($page)->order('house_ctime desc,house_id desc')->limit($limit)->select();
        
        $temps = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','inner')->join('ban c','a.ban_id = c.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
      //halt($temps);  
        foreach ($temps as $k => &$v) {
            
            $unpaids = Db::name('rent_order')->where([['house_id','eq',$v['house_id']],['tenant_id','eq',$v['tenant_id']],['rent_order_receive','exp',Db::raw('!=rent_order_paid')]])->find();
            $v['color_status'] = 1; //正常的
            if($unpaids){ 
                if(isset($queryWhere['change_type']) && $queryWhere['change_type'] != 4 && $queryWhere['change_type'] != 11){
                    $v['color_status'] = 3; // 有欠租的
                }
            }
            if(isset($applyHouseidArr) && $applyHouseidArr){
                if(in_array($v['house_id'], $applyHouseidArr)){
                    $v['color_status'] = 2; // 已在异动中
                }
            }

        }

        $data['data'] = $temps;
        $data['count'] = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->join('ban c','a.ban_id = c.ban_id','left')->field($fields)->where($where)->count();
        $data['code'] = 0;
        $data['msg'] = '';

        return $data;
    }



}
