<?php
namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\Tenant as TenantModel;

class Tenant extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $data = $this->request->get();
            $TenantModel = new TenantModel;
            $where = $TenantModel->checkWhere($data);
            $fields = 'tenant_id,tenant_inst_id,tenant_inst_pid,tenant_number,tenant_name,tenant_tel,tenant_card';
            $data['data'] = TenantModel::field($fields)->where($where)->page($page)->order('tenant_ctime desc')->limit($limit)->select();
            $data['count'] = TenantModel::where($where)->count('tenant_id');
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
            $result = $this->validate($data, 'Tenant.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $TenantModel = new TenantModel();
            // 数据过滤
            $filData = $TenantModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$TenantModel->allowField(true)->create($filData)) {
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
            $result = $this->validate($data, 'Tenant.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $TenantModel = new TenantModel();
            // 入库
            if (!$TenantModel->allowField(true)->update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $id = input('param.id/d');
        $row = TenantModel::get($id);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function detail()
    {
        $id = input('param.id/d');
        $row = TenantModel::get($id);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function del()
    {
        $ids = $this->request->param('id/a');        
        $res = TenantModel::where([['tenant_id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }  
    }
}