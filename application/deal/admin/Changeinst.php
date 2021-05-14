<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeInst as ChangeInstModel;

/**
 * 管段調整
 */
class Changeinst extends Admin
{

    public function index()
    {   
        // $ChangeModel = new ChangeInstModel; 
        // $ChangeModel->nextMonthDeal();
        // exit;
        if ($this->request->isAjax()) {
            
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeInstModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.old_inst_id,a.new_inst_id,a.change_ban_num,a.change_ban_rent,a.change_ban_area,a.change_ban_use_area,a.change_ban_oprice,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,c.nick";
            $data = [];
            $data['data'] = Db::name('change_inst')->alias('a')->join('system_user c','a.cuid = c.id','left')->field($fields)->where($where)->order('etime desc')->page($page)->limit($limit)->select();
            $data['count'] = Db::name('change_inst')->alias('a')->join('system_user c','a.cuid = c.id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_inst')->alias('a')->join('system_user c','a.cuid = c.id','left')->where($where)->field('sum(change_ban_rent) as total_change_ban_rent, sum(change_ban_area) as total_change_ban_area, sum(change_ban_use_area) as total_change_ban_use_area, sum(change_ban_oprice) as total_change_ban_oprice')->find();
            if($totalRow){
                $data['total_change_ban_rent'] = $totalRow['total_change_ban_rent'];
                $data['total_change_ban_area'] = $totalRow['total_change_ban_area'];
                $data['total_change_ban_use_area'] = $totalRow['total_change_ban_use_area'];
                $data['total_change_ban_oprice'] = $totalRow['total_change_ban_oprice'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        $pid = Db::name('base_inst')->where([['inst_id','eq',INST]])->value('inst_pid');
        $insts = Db::name('base_inst')->where([['inst_pid','eq',$pid]])->column('inst_id,inst_name');
        $this->assign('insts',$insts);
        return $this->fetch();
    }
    public function test()
    {
        // $ChangeModel = new ChangeInstModel; 
        // $ChangeModel->nextMonthDeal();
    }

    public function apply()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changeinst.form');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeModel = new ChangeInstModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }
        //halt($filData);
            // 入库
            unset($filData['id']);
            $row = $ChangeModel->allowField(true)->create($filData);
            if (!$row) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $row['id'];
                $filData['change_order_number'] = $row['change_order_number'];
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }else{
                $msg = '保存成功';
            }
            return $this->success($msg,url('index'));
        }
        $pid = Db::name('base_inst')->where([['inst_id','eq',INST]])->value('inst_pid');
        $insts = Db::name('base_inst')->where([['inst_pid','eq',$pid]])->column('inst_id,inst_name');
        $this->assign('insts',$insts);
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changeinst.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeModel = new ChangeInstModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'edit');
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $row = $ChangeModel->allowField(true)->update($filData);
            if ($row === false) {
                return $this->error('申请失败');
            }
            //halt($row);
            if($data['save_type'] == 'submit' && count($row['child_json']) == 1){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $row['id'];
                $filData['change_order_number'] = $row['change_order_number'];
                unset($filData['id']);
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }elseif($data['save_type'] == 'submit' && count($row['child_json']) > 1){ 
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $process = $ProcessModel->where([['change_type','eq',10],['change_id','eq',$row['id']]])->update(['curr_role'=>5,'change_desc'=>'待资料员初审']);
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
        $ChangeModel = new ChangeInstModel;
        $row = $ChangeModel->detail($id);//halt($row);
        $this->assign('data_info',$row);
        $pid = Db::name('base_inst')->where([['inst_id','eq',INST]])->value('inst_pid');
        $insts = Db::name('base_inst')->where([['inst_pid','eq',$pid]])->column('inst_id,inst_name');
        $this->assign('insts',$insts);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeInstModel;
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
            $ChangeModel = new ChangeInstModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.old_inst_id,a.new_inst_id,a.change_ban_num,a.change_ban_rent,a.change_ban_area,a.change_ban_use_area,a.change_ban_oprice,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as ftime,a.change_status,a.is_back,a.entry_date,c.nick";
            $data = [];
            $data['data'] = Db::name('change_inst')->alias('a')->join('system_user c','a.cuid = c.id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc,id desc')->limit($limit)->select();
            // halt(Db::name('change_inst')->getLastSql());
            $data['count'] = Db::name('change_inst')->alias('a')->join('system_user c','a.cuid = c.id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_inst')->alias('a')->join('system_user c','a.cuid = c.id','left')->where($where)->field('sum(change_ban_num) as total_change_ban_num, sum(change_ban_rent) as total_change_ban_rent, sum(change_ban_area) as total_change_ban_area, sum(change_ban_use_area) as total_change_ban_use_area, sum(change_ban_oprice) as total_change_ban_oprice')->find();
            if($totalRow){
                $data['total_change_ban_num'] = $totalRow['total_change_ban_num'];
                $data['total_change_ban_rent'] = $totalRow['total_change_ban_rent'];
                $data['total_change_ban_area'] = $totalRow['total_change_ban_area'];
                $data['total_change_ban_use_area'] = $totalRow['total_change_ban_use_area'];
                $data['total_change_ban_oprice'] = $totalRow['total_change_ban_oprice'];
            }
            $data['code'] = 0;
            $data['msg'] = '';
            //halt($data);
            return json($data);
        }
        $pid = Db::name('base_inst')->where([['inst_id','eq',INST]])->value('inst_pid');
        $insts = Db::name('base_inst')->where([['inst_pid','eq',$pid]])->column('inst_id,inst_name');
        $this->assign('insts',$insts);
        return $this->fetch();
    }

    public function del()
    {
        $id = $this->request->param('id');       
        $row = ChangeInstModel::get($id);
        if($row['change_status'] == 2){
            $row->dtime = time();
            $row->save();
            ProcessModel::where([['change_order_number','eq',$row['change_order_number']]])->delete();
            $this->success('删除成功！');
        }else{
            $this->error('非房管员处理状态，无法删除！');
        }
    }

}