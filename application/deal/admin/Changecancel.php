<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeCancel as ChangeCancelModel;

/**
 * 注销
 */
class Changecancel extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeCancelModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            $fields = "a.id,a.is_back,a.change_order_number,a.cancel_type,a.cancel_rent,a.cancel_area,a.cancel_use_area,a.cancel_oprice,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('etime desc')->page($page)->limit($limit)->select();
            $data['count'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(cancel_rent) as total_cancel_rent, sum(cancel_area) as total_cancel_area, sum(cancel_use_area) as total_cancel_use_area, sum(cancel_oprice) as total_cancel_oprice')->find();
            if($totalRow){
                $data['total_cancel_rent'] = $totalRow['total_cancel_rent'];
                $data['total_cancel_area'] = $totalRow['total_cancel_area'];
                $data['total_cancel_use_area'] = $totalRow['total_cancel_use_area'];
                $data['total_cancel_oprice'] = $totalRow['total_cancel_oprice'];
            }
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
            $result = $this->validate($data, 'Changecancel.form');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeModel = new ChangeCancelModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }
        
            // 入库使用权变更表
            unset($filData['id']);
            $useRow = $ChangeModel->allowField(true)->create($filData);
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
            $result = $this->validate($data, 'Changecancel.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeModel = new ChangeCancelModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'edit');
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $useRow = $ChangeModel->allowField(true)->update($filData);
            if ($useRow === false) {
                return $this->error('申请失败');
            }
            //halt($useRow);
            if($data['save_type'] == 'submit' && count($useRow['child_json']) == 1){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $useRow['id'];
                unset($filData['id']);
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }elseif($data['save_type'] == 'submit' && count($useRow['child_json']) > 1){ 
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $process = $ProcessModel->where([['change_type','eq',8],['change_id','eq',$useRow['id']]])->update(['curr_role'=>5,'change_desc'=>'待资料员初审']);
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
        //halt($id);
        $ChangeModel = new ChangeCancelModel;
        $row = $ChangeModel->detail($id);//halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeCancelModel;
        $row = $ChangeModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function record()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeCancelModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            $fields = "a.id,a.is_back,a.change_order_number,a.cancel_type,a.cancel_rent,a.cancel_area,a.cancel_use_area,a.cancel_oprice,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as ftime,a.change_status,a.entry_date,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
            $data['count'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(cancel_rent) as total_cancel_rent, sum(cancel_area) as total_cancel_area, sum(cancel_use_area) as total_cancel_use_area, sum(cancel_oprice) as total_cancel_oprice')->find();
            if($totalRow){
                $data['total_cancel_rent'] = $totalRow['total_cancel_rent'];
                $data['total_cancel_area'] = $totalRow['total_cancel_area'];
                $data['total_cancel_use_area'] = $totalRow['total_cancel_use_area'];
                $data['total_cancel_oprice'] = $totalRow['total_cancel_oprice'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function del()
    {
        $id = $this->request->param('id');       
        $row = ChangeCancelModel::get($id);
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