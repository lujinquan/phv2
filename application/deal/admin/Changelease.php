<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;

/**
 * 陈欠核销
 */
class Changelease extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeLeaseModel = new ChangeLeaseModel;
            $where = $ChangeLeaseModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.tenant_name,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,b.house_number,b.house_use_id,d.ban_address,d.ban_struct_id,d.ban_damage_id,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function apply()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();//halt($data);
            // 数据验证
            $result = $this->validate($data, 'Changelease.form');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeLeaseModel = new ChangeLeaseModel;
            // 数据过滤
            $filData = $ChangeLeaseModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
//halt($filData);
            // 入库
            $offsetRow = $ChangeLeaseModel->allowField(true)->create($filData);
            if (!$offsetRow) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $offsetRow['id'];
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
            $result = $this->validate($data, 'Changelease.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeLeaseModel = new ChangeLeaseModel;
            // 数据过滤
            $filData = $ChangeLeaseModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $row = $ChangeLeaseModel->allowField(true)->update($filData);
            if (!$row) {
                return $this->error('申请失败');
            }
            //halt($useRow);
            if($data['save_type'] == 'submit' && count($row['child_json']) == 1){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $row['id'];
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }elseif($data['save_type'] == 'submit' && count($row['child_json']) > 1){ 
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $process = $ProcessModel->where([['change_type','eq',3],['change_id','eq',$row['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
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
        // $ChangeLeaseModel = new ChangeLeaseModel;
        // $row = $ChangeLeaseModel->detail($id);
        //halt($row);
        $this->assign('id',$id);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeLeaseModel = new ChangeLeaseModel;
        if ($this->request->isAjax()) {
            $data = [];
            $data['data'] = $ChangeLeaseModel->detail($id);
            $data['msg'] = '获取成功！';
            $data['code'] = 0;
            return json($data);
        }
        
        $row = $ChangeLeaseModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function record()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeLeaseModel = new ChangeLeaseModel;
            $where = $ChangeLeaseModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.change_pause_rent,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,from_unixtime(a.ftime, '%Y-%m-%d %H:%i:%S') as ftime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_pause')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_pause')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function del()
    {
        $id = $this->request->param('id');       

        $row = ChangeLeaseModel::get($id);
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