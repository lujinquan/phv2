<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangePause as ChangePauseModel;

/**
 * 暂停计租
 */
class Changepause extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangePauseModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            $fields = "a.id,a.is_back,a.change_order_number,a.change_pause_rent,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_number,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_pause')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('etime desc')->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_pause')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_pause')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(change_pause_rent) as total_change_pause_rent')->find();
            if($totalRow){
                $data['total_change_pause_rent'] = $totalRow['total_change_pause_rent'];
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
            $result = $this->validate($data, 'Changepause.form');
            if($result !== true) {
                return $this->error($result);
            }

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changepause_file_upload']])->value('value');
            $file = [];
            if(isset($data['ChangepauseRedline']) && $data['ChangepauseRedline']){ // 拆迁或征收红线图  
                $file = array_merge($file,$data['ChangepauseRedline']);
            }else{
                if(strpos($fileUploadConfig, 'ChangepauseRedline') !== false){
                    return $this->error('请上传附件拆迁或征收红线图');
                }
            }
            if(isset($data['ChangepauseRange']) && $data['ChangepauseRange']){ // 如果有传附件
                $file = array_merge($file,$data['ChangepauseRange']);
            }else{
                if(strpos($fileUploadConfig, 'ChangepauseRange') !== false){
                    return $this->error('请上传附件拆迁或征收范围明细');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E
            
            $ChangeModel = new ChangePauseModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }

            // 入库
            unset($filData['id']);
            $pauseRow = $ChangeModel->allowField(true)->create($filData);
            if (!$pauseRow) {
                return $this->error('申请失败');
            }
            if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $pauseRow['id'];
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
            $result = $this->validate($data, 'Changepause.edit');
            if($result !== true) {
                return $this->error($result);
            }
            $fileUploadConfig = Db::name('config')->where([['title','eq','changepause_file_upload']])->value('value');
            $file = [];
            if(isset($data['ChangepauseRedline']) && $data['ChangepauseRedline']){ // 拆迁或征收红线图  
                $file = array_merge($file,$data['ChangepauseRedline']);
            }else{
                if(strpos($fileUploadConfig, 'ChangepauseRedline') !== false){
                    return $this->error('请上传附件拆迁或征收红线图');
                }
            }
            if(isset($data['ChangepauseRange']) && $data['ChangepauseRange']){ // 如果有传附件
                $file = array_merge($file,$data['ChangepauseRange']);
            }else{
                if(strpos($fileUploadConfig, 'ChangepauseRange') !== false){
                    return $this->error('请上传附件拆迁或征收范围明细');
                }
            }
            $data['file'] = $file;
            
            $ChangeModel = new ChangePauseModel;
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
            //halt($useRow);
            if($data['save_type'] == 'submit' && count($row['child_json']) == 1){ //如果是保存并提交，则入库审批表
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $filData['change_id'] = $row['id'];
                unset($filData['id']);
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
        $ChangeModel = new ChangePauseModel;
        $row = $ChangeModel->detail($id);

        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangePauseModel;
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
            $ChangeModel = new ChangePauseModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.change_pause_rent,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.entry_date,a.is_valid,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_pause')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
            $data['count'] = Db::name('change_pause')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_pause')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->field('sum(change_pause_rent) as total_change_pause_rent')->find();
            if($totalRow){
                $data['total_change_pause_rent'] = $totalRow['total_change_pause_rent'];
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
        $row = ChangePauseModel::get($id);
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