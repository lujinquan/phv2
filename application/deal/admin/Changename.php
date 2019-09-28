<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeName as ChangeNameModel;

/**
 * 别字更正
 */
class Changename extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeNameModel = new ChangeNameModel;
            $where = $ChangeNameModel->checkWhere($getData,'apply');
            $fields = "a.id,a.change_order_number,a.old_tenant_name,a.new_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,b.house_use_id,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            $data['count'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
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
            $result = $this->validate($data, 'Changename.form');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeNameModel = new ChangeNameModel;
            // 数据过滤
            $filData = $ChangeNameModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
        
            // 入库使用权变更表
            $useRow = $ChangeNameModel->allowField(true)->create($filData);
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
            $result = $this->validate($data, 'Changename.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeNameModel = new ChangeNameModel;
            // 数据过滤
            $filData = $ChangeNameModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $useRow = $ChangeNameModel->allowField(true)->update($filData);
            if (!$useRow) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){
                if(count($useRow['child_json']) == 1){
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $useRow['id'];
                    if (!$ProcessModel->allowField(true)->create($filData)) {
                        return $this->error('未知错误');
                    }
                }elseif(count($useRow['child_json']) > 1){
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $process = $ProcessModel->where([['change_type','eq',17],['change_id','eq',$useRow['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
                    if (!$process) {
                        return $this->error('未知错误');
                    }
                }
                $msg = '保存并提交成功';
            }else{
                $msg = '保存成功';
            }
            return $this->success($msg,url('index'));
        }
        $id = $this->request->param('id');
        $ChangeNameModel = new ChangeNameModel;
        $row = $ChangeNameModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeNameModel = new ChangeNameModel;
        $row = $ChangeNameModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function record()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeNameModel = new ChangeNameModel;
            $where = $ChangeNameModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.old_tenant_name,a.new_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as ftime,a.change_status,a.is_back,b.house_use_id,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            $data['count'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function del()
    {
        $id = $this->request->param('id');       
        $row = ChangeNameModel::get($id);
        if($row['change_status'] == 2 && $row['is_back'] == 0){
           if($row->delete()){
                ProcessModel::where([['change_order_number','eq',$row['change_order_number']]])->delete();
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            } 
        }else{
            $this->error('已被审批，无法删除！');
        }

    }

}