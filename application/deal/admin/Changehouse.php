<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\house\model\HouseTemp as HouseTempModel;
use app\deal\model\ChangeHouse as ChangeHouseModel;

/**
 * 房屋调整
 */
class Changehouse extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeHouseModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            $fields = "a.id,a.change_order_number,a.ban_change_id,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,d.ban_number,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_house')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            $data['count'] = Db::name('change_house')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
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
            $result = $this->validate($data, 'Changehouse.form');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeModel = new ChangeHouseModel;
            //halt($data);
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            
            // 入库使用权变更表
            unset($filData['id']);
            $row = $ChangeModel->allowField(true)->create($filData);

            //更新档案临时表
            HouseTempModel::where([['house_id','eq',$data['house_id']]])->update([
                'house_diff_rent' => $data['changes_difference_news'],
                'house_pump_rent' => $data['changes_pumpfee_news'],
                'house_protocol_rent' => $data['changes_agreement_news'],
                'house_area' => $data['changes_architecture_news'],
                'house_oprice' => $data['changes_originalprice_news'],
            ]);
            
            if (!$row) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $row['id'];
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
            $result = $this->validate($data, 'Changehouse.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $ChangeModel = new ChangeHouseModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            $useRow = $ChangeModel->allowField(true)->update($filData);
            if (!$useRow) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){
                if(count($useRow['child_json']) == 1){
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $useRow['id'];
                    unset($filData['id']);
                    if (!$ProcessModel->allowField(true)->create($filData)) {
                        return $this->error('未知错误');
                    }
                }elseif(count($useRow['child_json']) > 1){
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $process = $ProcessModel->where([['change_type','eq',14],['change_id','eq',$useRow['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
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
        $ChangeModel = new ChangeHouseModel;
        $row = $ChangeModel->detail($id);//halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeHouseModel;
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
            $ChangeModel = new ChangeHouseModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.ban_change_id,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as ftime,a.change_status,a.is_back,d.ban_number,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_ban')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            $data['count'] = Db::name('change_ban')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function del()
    {die();
        $id = $this->request->param('id');       
        $row = ChangeHouseModel::get($id);
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