<?php
namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\House as HouseModel;

class House extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $keywords = input('param.keywords');
            
            $where = [
                ['house_status','eq',1],
            ];
            if($keywords){
                $where[] = ['keywords','like','%'.$keywords.'%'];
            }
            
            $fields = 'house_number,ban_number,tenant_number,house_pre_rent,house_cou_rent,house_use_id,house_unit_id,house_floor_id,house_lease_area,house_area';
            $data['data'] = HouseModel::with('ban,tenant')->field($fields)->where($where)->page($page)->order('house_ctime desc')->limit($limit)->select();
            $data['count'] = HouseModel::where($where)->count('house_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $mod = new HouseModel();
            if (!$mod->storage()) {
                return $this->error($mod->getError());
            }
            return $this->success('保存成功');
        }
        return $this->fetch('form');
    }

    public function renttable()
    {
        return $this->fetch('renttable');
    }

    public function detail()
    {
        return $this->fetch();
    }

    public function del()
    {
        
    }
}