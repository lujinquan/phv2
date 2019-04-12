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
            $data = $this->request->get();
            $banModel = new BanModel;
            $where = $banModel->checkWhere($data);
            $fields = 'ban_id,ban_number,ban_inst_id,ban_owner_id,ban_address,ban_property_id,ban_build_year,ban_damage_id,ban_struct_id,ban_civil_rent,ban_party_rent,ban_career_rent,ban_civil_area,ban_party_area,ban_career_area,ban_use_area,ban_civil_oprice,ban_party_oprice,ban_career_oprice';
            $data['data'] = $banModel->field($fields)->where($where)->page($page)->order('ban_ctime desc')->limit($limit)->select();
            $data['count'] = $banModel->where($where)->count('ban_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $group = input('group','y');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '正常',
                'url' => '?group=y',
            ],
            [
                'title' => '异常',
                'url' => '?group=n',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Ban.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $BanModel = new BanModel();
            // 数据过滤
            $filData = $BanModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$BanModel->allowField(true)->create($filData)) {
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Ban.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $BanModel = new BanModel();
            // 入库
            if (!$BanModel->allowField(true)->update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $id = input('param.id/d');
        $row = BanModel::get($id);
        $this->assign('data_info',$row);
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
        $ids = $this->request->param('id/a');        
        $res = BanModel::where([['ban_id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function struct()
    {
        return $this->fetch();
    }

    public function ceshi()
    {
        return $this->fetch();
    }
}