<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 减免
 */
class Changecut extends Admin
{

    public function index()
    {
        $group = input('group','x');
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            if($group = 'x'){
                $ChangeCutModel = new ChangeCutModel;
                $where = $ChangeCutModel->checkWhere($getData,'apply');
                //halt($where);
                $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,a.is_back,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id";
                $data = [];
                $data['data'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
                //halt($data['data']);
                $data['count'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            }else{

            }
            
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);       
        }
        //$group = input('group','x');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '租金减免',
                'url' => '?group=x',
            ],
            [
                'title' => '租金减免年审',
                'url' => '?group=y',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        //$this->assign('ban_number',input('param.ban_number',''));
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch('index_'.$group);
    }

    public function apply()
    {
        $group = input('group','x');
    	if ($this->request->isAjax()) {
            $data = $this->request->post();
            if($data['group'] = 'x'){
                // 数据验证
                $result = $this->validate($data, 'Changecut.form');
                if($result !== true) {
                    return $this->error($result);
                }
                $ChangeCutModel = new ChangeCutModel;
                // 数据过滤
                $filData = $ChangeCutModel->dataFilter($data);
                if(!is_array($filData)){
                    return $this->error($filData);
                }
            
                // 入库使用权变更表
                $useRow = $ChangeCutModel->allowField(true)->create($filData);
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
            }else{

            }
            
            return $this->success($msg,url('index')); 
        }       
        return $this->fetch('apply_'.$group);
    }

    public function edit()
    {
        $group = input('group','x');
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            if($data['group'] = 'x'){
                // 数据验证
                $result = $this->validate($data, 'Changecut.edit');
                if($result !== true) {
                    return $this->error($result);
                }
                $ChangeCutModel = new ChangeCutModel;
                // 数据过滤
                $filData = $ChangeCutModel->dataFilter($data);
                if(!is_array($filData)){
                    return $this->error($filData);
                }
                // 入库使用权变更表
                $useRow = $ChangeCutModel->allowField(true)->update($filData);
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
                    $process = $ProcessModel->where([['change_type','eq',1],['change_id','eq',$useRow['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
                    if (!$process) {
                        return $this->error('未知错误');
                    }
                    $msg = '保存并提交成功';
                }else{
                    $msg = '保存成功';
                }
            }else{

            }
            
            return $this->success($msg,url('index'));
        }
        $id = $this->request->param('id');
        $ChangeCutModel = new ChangeCutModel;
        $row = $ChangeCutModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch('edit_'.$group);
    }

    public function detail()
    {
        $group = input('group','x');
        $id = $this->request->param('id');
        $ChangeCutModel = new ChangeCutModel;
        $row = $ChangeCutModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch('detail_'.$group);
    }

    /**
     * 取消减免
     * @return [type] [description]
     */
    public function calloff()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            //halt($data);
        }
        $id = $this->request->param('id');
        $ChangeCutModel = new ChangeCutModel;
        $row = $ChangeCutModel->detail($id);
        $this->assign('data_info',$row);
        //halt($row);
        return $this->fetch('callof');
    }

    public function record()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            if($group = 'x'){
                $ChangeCutModel = new ChangeCutModel;
                $where = $ChangeCutModel->checkWhere($getData,'record');
                //halt($where);
                $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,a.is_back,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id";
                $data = [];
                $data['data'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
                //halt($data['data']);
                $data['count'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            }else{

            }
            
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);        
        }
        $group = input('group','x');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '租金减免',
                'url' => '?group=x',
            ],
            [
                'title' => '租金减免年审',
                'url' => '?group=y',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        //$this->assign('ban_number',input('param.ban_number',''));
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch('record_'.$group);
    }

    public function del()
    {
        $id = $this->request->param('id');       
        $row = ChangeCutModel::get($id);
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