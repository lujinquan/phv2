<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeNew as ChangeNewModel;

/**
 * 使用权变更
 */
class Changenew extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeNewModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.new_type,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,d.ban_address,b.house_number,b.house_pre_rent,b.house_oprice,b.house_area,b.house_use_area,b.house_lease_area,b.house_use_id,c.tenant_name,d.ban_owner_id,d.ban_inst_id,d.ban_struct_id,d.ban_damage_id";
            $data = [];
            $data['data'] = Db::name('change_new')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->order('etime desc')->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_new')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_new')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(house_pre_rent) as total_house_pre_rent, sum(house_oprice) as total_house_oprice, sum(house_area) as total_house_area, sum(house_lease_area) as total_house_lease_area')->find();
            if($totalRow){
                $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
                $data['total_house_oprice'] = $totalRow['total_house_oprice'];
                $data['total_house_area'] = $totalRow['total_house_area'];
                $data['total_house_lease_area'] = $totalRow['total_house_lease_area'];
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
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changenew.form');
            if($result !== true) {
                return $this->error($result);
            }

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changenew_file_upload']])->value('value');
            $file = [];
            if(isset($data['Houselease']) && $data['Houselease']){ // 计租表 
                $file = array_merge($file,$data['Houselease']);
            }else{
                if(strpos($fileUploadConfig, 'Houselease') !== false){
                    return $this->error('请上传计租表');
                }
            }
            if(isset($data['HouseForm']) && $data['HouseForm']){ // 租约
                $file = array_merge($file,$data['HouseForm']);
            }else{
                if(strpos($fileUploadConfig, 'HouseForm') !== false){
                    return $this->error('请上传租约');
                }
            }
            if(isset($data['TenantReIDCard']) && $data['TenantReIDCard']){ // 身份证 
                $file = array_merge($file,$data['TenantReIDCard']);
            }else{
                if(strpos($fileUploadConfig, 'TenantReIDCard') !== false){
                    return $this->error('请上传身份证');
                }
            }
            if(isset($data['ChangenewPaper']) && $data['ChangenewPaper']){ // 收欠票据 
                $file = array_merge($file,$data['ChangenewPaper']);
            }else{
                if(strpos($fileUploadConfig, 'ChangenewPaper') !== false){
                    return $this->error('请上传收欠票据');
                }
            }
            if(isset($data['BanCard']) && $data['BanCard']){ // 图卡 
                $file = array_merge($file,$data['BanCard']);
            }else{
                if(strpos($fileUploadConfig, 'BanCard') !== false){
                    return $this->error('请上传图卡');
                }
            }
            if(isset($data['ChangenewOther']) && $data['ChangenewOther']){ // 情况说明 
                $file = array_merge($file,$data['ChangenewOther']);
            }else{
                if(strpos($fileUploadConfig, 'ChangenewOther') !== false){
                    return $this->error('请上传情况说明');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E

            $ChangeModel = new ChangeNewModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库使用权变更表
            unset($filData['id']);
            $row = $ChangeModel->allowField(true)->create($filData);
            if (!$row) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $row['id'];//halt($filData);
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
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changenew.edit');
            if($result !== true) {
                return $this->error($result);
            }

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changenew_file_upload']])->value('value');
            $file = [];
            if(isset($data['Houselease']) && $data['Houselease']){ // 计租表 
                $file = array_merge($file,$data['Houselease']);
            }else{
                if(strpos($fileUploadConfig, 'Houselease') !== false){
                    return $this->error('请上传计租表');
                }
            }
            if(isset($data['HouseForm']) && $data['HouseForm']){ // 租约
                $file = array_merge($file,$data['HouseForm']);
            }else{
                if(strpos($fileUploadConfig, 'HouseForm') !== false){
                    return $this->error('请上传租约');
                }
            }
            if(isset($data['TenantReIDCard']) && $data['TenantReIDCard']){ // 身份证 
                $file = array_merge($file,$data['TenantReIDCard']);
            }else{
                if(strpos($fileUploadConfig, 'TenantReIDCard') !== false){
                    return $this->error('请上传身份证');
                }
            }
            if(isset($data['ChangenewPaper']) && $data['ChangenewPaper']){ // 收欠票据 
                $file = array_merge($file,$data['ChangenewPaper']);
            }else{
                if(strpos($fileUploadConfig, 'ChangenewPaper') !== false){
                    return $this->error('请上传收欠票据');
                }
            }
            if(isset($data['BanCard']) && $data['BanCard']){ // 图卡 
                $file = array_merge($file,$data['BanCard']);
            }else{
                if(strpos($fileUploadConfig, 'BanCard') !== false){
                    return $this->error('请上传图卡');
                }
            }
            if(isset($data['ChangenewOther']) && $data['ChangenewOther']){ // 情况说明 
                $file = array_merge($file,$data['ChangenewOther']);
            }else{
                if(strpos($fileUploadConfig, 'ChangenewOther') !== false){
                    return $this->error('请上传情况说明');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E

            $ChangeModel = new ChangeNewModel;
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
                $process = $ProcessModel->where([['change_type','eq',7],['change_id','eq',$row['id']]])->update(['curr_role'=>5,'change_desc'=>'待资料员初审']);
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
        $ChangeModel = new ChangeNewModel;
        $row = $ChangeModel->detail($id);
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeNewModel;
        $row = $ChangeModel->detail($id);
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function record()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeNewModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.new_type,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.entry_date,a.is_back,d.ban_address,b.house_number,b.house_pre_rent,b.house_oprice,b.house_area,b.house_use_area,b.house_lease_area,e.change_rent,e.change_oprice,e.change_area,e.change_use_area,b.house_use_id,c.tenant_name,d.ban_owner_id,d.ban_inst_id,d.ban_struct_id,d.ban_damage_id";
            $data = [];
            $data['data'] = Db::name('change_new')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('change_table e','a.change_order_number = e.change_order_number','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,a.ftime desc')->limit($limit)->select();
            //halt(Db::name('change_new')->getLastSql());
            //halt($data['data']);
            $data['count'] = Db::name('change_new')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_new')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(house_pre_rent) as total_house_pre_rent, sum(house_oprice) as total_house_oprice, sum(house_area) as total_house_area, sum(house_lease_area) as total_house_lease_area')->find();
            if($totalRow){
                $data['total_house_pre_rent'] = $totalRow['total_house_pre_rent'];
                $data['total_house_oprice'] = $totalRow['total_house_oprice'];
                $data['total_house_area'] = $totalRow['total_house_area'];
                $data['total_house_lease_area'] = $totalRow['total_house_lease_area'];
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
        $row = ChangeNewModel::get($id);
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