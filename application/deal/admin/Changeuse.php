<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\ChangeUse as ChangeUseModel;

/**
 * 使用权变更
 */
class Changeuse extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeUseModel = new ChangeUseModel;
            $where = $ChangeUseModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.change_type,a.old_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_use')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('system_user c','a.cuid = c.id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_use')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function apply()
    {
    	if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changeuse.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeUseModel = new ChangeUseModel;
            // 数据过滤
            $filData = $ChangeUseModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$ChangeUseModel->allowField(true)->create($filData)) {
                return $this->error('申请失败');
            }
            return $this->success('申请成功');
        }
        return $this->fetch();
    }

    public function record()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    public function del()
    {
        $id = $this->request->param('id');       

        $row = ChangeUseModel::get($id);
        if($row['change_status'] != 3){
            $this->error('已被审批，无法删除！');
        }
        
        if($row->delete()){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}