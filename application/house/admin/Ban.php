<?php
namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\common\model\Cparam as ParamModel;

class Ban extends Admin
{

    public function index()
    {   
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $keywords = input('param.keywords');
            
            $where = [
                ['is_show','eq',1],
            ];
            if(INST_LEVEL == 2){
                $where[] = ['ban_inst_pid','eq',INST];
            }elseif(INST_LEVEL == 3){
                $where[] = ['ban_inst_id','eq',INST];
            }
            if($keywords){
                $where[] = ['keywords','like','%'.$keywords.'%'];
            }
            
            $fields = 'ban_id,ban_number,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,ban_civil_rent,ban_party_rent,ban_career_rent,ban_civil_area,ban_party_area,ban_career_area,ban_use_area,ban_civil_oprice,ban_party_oprice,ban_career_oprice';
            $data['data'] = BanModel::field($fields)->where($where)->page($page)->order('ban_ctime desc')->limit($limit)->select();
            $data['count'] = BanModel::where($where)->count('ban_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'Article.sceneAdd');
            if($result !== true) {
                return $this->error($result);
            }
            $mod = new BanModel();
            if (!$mod->allowField(true)->create($data)) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
        return $this->fetch('form');
    }

    public function edit()
    {
        return $this->fetch('form');
    }

    public function detail()
    {
        $id = input('param.id/d');
        $row = BanModel::get($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function del()
    {
        
    }
}