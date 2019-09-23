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
            $ChangeCancelModel = new ChangeCancelModel;
            $where = $ChangeCancelModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_cancel')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
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
            $result = $this->validate($data, 'Changecancel.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeCancelModel = new ChangeCancelModel;
            // 数据过滤
            $filData = $ChangeCancelModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            $cancelRow = $ChangeCancelModel->allowField(true)->create($filData);
            if (!$cancelRow) {
                return $this->error('申请失败');
            }

            // 入库审批表
            $ProcessModel = new ProcessModel;
            $filData['change_id'] = $cancelRow['id'];
            if (!$ProcessModel->allowField(true)->create($filData)) {
                return $this->error('未知错误');
            }
            return $this->success('申请成功',url('index'));
        }
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeCancelModel = new ChangeCancelModel;
        $row = $ChangeCancelModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function record()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeCancelModel = new ChangeCancelModel;
            $where = $ChangeCancelModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,from_unixtime(a.ftime, '%Y-%m-%d %H:%i:%S') as ftime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_cancel')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
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