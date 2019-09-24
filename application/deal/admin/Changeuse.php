<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
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
            $fields = "a.id,a.change_order_number,a.change_use_type,a.old_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,a.is_back,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
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
            $result = $this->validate($data, 'Changeuse.form');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeUseModel = new ChangeUseModel;
            // 数据过滤
            $filData = $ChangeUseModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
        
            // 入库使用权变更表
            $useRow = $ChangeUseModel->allowField(true)->create($filData);
            if (!$useRow) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $useRow['id'];
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }else{
                $msg = '保存成功';
            }
            return $this->success($msg,url('index'));
        }
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changeuse.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeUseModel = new ChangeUseModel;
            // 数据过滤
            $filData = $ChangeUseModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $useRow = $ChangeUseModel->allowField(true)->update($filData);
            if (!$useRow) {
                return $this->error('申请失败');
            }
            //halt($useRow);
            if($data['save_type'] == 'submit' && count($useRow['child_json']) == 1){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $useRow['id'];
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }elseif($data['save_type'] == 'submit' && count($useRow['child_json']) > 1){ 
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $process = $ProcessModel->where([['change_type','eq',13],['change_id','eq',$useRow['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
                if (!$process) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }else{
                $msg = '保存成功';
            }
            return $this->success($msg,url('index'));
        }
        $id = $this->request->param('id');
        $ChangeUseModel = new ChangeUseModel;
        $row = $ChangeUseModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeUseModel = new ChangeUseModel;
        $row = $ChangeUseModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function record()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeUseModel = new ChangeUseModel;
            $where = $ChangeUseModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.change_use_type,a.old_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
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

    public function del()
    {
        $id = $this->request->param('id');       

        $row = ChangeUseModel::get($id);
        if($row['change_status'] != 3){
            $this->error('已被审批，无法删除！');
        }
        
        if($row->delete()){
            ProcessModel::where([['change_order_number','eq',$row['change_order_number']]])->delete();
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}