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
            $ChangeModel = new ChangeLeaseModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.tenant_name,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,b.house_number,a.is_back,b.house_use_id,d.ban_address,d.ban_struct_id,d.ban_damage_id,d.ban_owner_id,d.ban_inst_id";
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
            $ChangeModel = new ChangeLeaseModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }
//halt($filData);
            // 入库
            unset($filData['id']);
            $offsetRow = $ChangeModel->allowField(true)->create($filData);
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
            $ChangeModel = new ChangeLeaseModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'edit');
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $row = $ChangeModel->allowField(true)->update($filData);
            //$row = $ChangeModel->allowField(true)->update($filData);
            if (!$row) {
                return $this->error('申请失败');
            }

            if($data['save_type'] == 'submit'){
                if(count($row['child_json']) == 1){
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $row['id'];
                    unset($filData['id']);
                    if (!$ProcessModel->allowField(true)->create($filData)) {
                        return $this->error('未知错误');
                    }
                }elseif(count($row['child_json']) > 1){
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $process = $ProcessModel->where([['change_type','eq',18],['change_id','eq',$row['id']]])->update(['curr_role'=>5,'change_desc'=>'待资料员补充资料']);
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
        $ChangeModel = new ChangeLeaseModel;
        $row = $ChangeModel->detail($id);
        $this->assign('data_info',$row);
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * 上传签字图片
     * @return [type] [description]
     */
    public function uploadsign()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeLeaseModel;
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            if(isset($data['file']) && $data['file']){
                $status = Db::name('change_lease')->where([['id','eq',$data['id']]])->value('change_status');
                if($status < 2){
                    return $this->error('请勿重复提交签字图片！');
                }
                $ProcessModel = new ProcessModel;

                $res = $ProcessModel->process(18,$data);
                if (!$res) {
                    return $this->error('上传失败');
                }
                return $this->success('上传成功');
            }else{
                return $this->error('请上传签字图片');
            }
        }
        $row = $ChangeModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     * 不通过
     * @return [type] [description]
     */
    public function unpass()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeLeaseModel;
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            
                
                if(!$data['change_reason']){
                    return $this->error('请输入不通过的原因！');
                }
                $ProcessModel = new ProcessModel;

                $res = $ProcessModel->process(18,$data);
                if (!$res) {
                    return $this->error('操作失败');
                }
                return $this->success('操作成功');
            
        }
        $row = $ChangeModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }


    /**
     * 租约详情
     * @return [type] [description]
     */
    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeLeaseModel;
        if ($this->request->isAjax()) {
            $data = [];
            $data['data'] = $ChangeModel->detail($id);
            $data['msg'] = '获取成功！';
            $data['code'] = 0;
            return json($data);
        }
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
            $ChangeModel = new ChangeLeaseModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            $fields = "a.id,a.change_order_number,a.tenant_name,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as ftime,a.change_status,a.entry_date,b.house_number,a.is_back,a.is_valid,b.house_use_id,d.ban_address,d.ban_struct_id,d.ban_damage_id,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
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