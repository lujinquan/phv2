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

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changecancel_file_upload']])->value('value');
            $file = [];
            if(isset($data['ChangeCancelOne']) && $data['ChangeCancelOne']){ // 售房专用票据  
                $file = array_merge($file,$data['ChangeCancelOne']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelOne') !== false){
                    return $this->error('请上传附件拆售房专用票据');
                }
            }
            if(isset($data['ChangeCancelTwo']) && $data['ChangeCancelTwo']){ // 审批表
                $file = array_merge($file,$data['ChangeCancelTwo']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelTwo') !== false){
                    return $this->error('请上传附件审批表');
                }
            }
            if(isset($data['ChangeCancelThree']) && $data['ChangeCancelThree']){ // 危改批文
                $file = array_merge($file,$data['ChangeCancelThree']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelThree') !== false){
                    return $this->error('请上传附件危改批文');
                }
            }
            if(isset($data['ChangeCancelRetread']) && $data['ChangeCancelRetread']){ // 翻新计划
                $file = array_merge($file,$data['ChangeCancelRetread']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelRetread') !== false){
                    return $this->error('请上传附件翻新计划');
                }
            }
            if(isset($data['ChangeCancelApproval']) && $data['ChangeCancelApproval']){ // 发还批文
                $file = array_merge($file,$data['ChangeCancelApproval']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelApproval') !== false){
                    return $this->error('请上传附件发还批文');
                }
            }
            if(isset($data['ChangeCancelReport']) && $data['ChangeCancelReport']){ // 注销报告
                $file = array_merge($file,$data['ChangeCancelReport']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelReport') !== false){
                    return $this->error('请上传附件注销报告');
                }
            }
            if(isset($data['ChangeCancelPaper']) && $data['ChangeCancelPaper']){ // 政府文件
                $file = array_merge($file,$data['ChangeCancelPaper']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelPaper') !== false){
                    return $this->error('请上传附件政府文件');
                }
            }
            if(isset($data['ChangeCancelInvoice']) && $data['ChangeCancelInvoice']){ // 发票（拆迁回款单）
                $file = array_merge($file,$data['ChangeCancelInvoice']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelInvoice') !== false){
                    return $this->error('请上传附件发票');
                }
            }
            if(isset($data['ChangeCancelDetail']) && $data['ChangeCancelDetail']){ // 征收明细表
                $file = array_merge($file,$data['ChangeCancelDetail']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelDetail') !== false){
                    return $this->error('请上传附件征收明细表');
                }
            }
            if(isset($data['ChangeCancelOfficial']) && $data['ChangeCancelOfficial']){ // 划转公文
                $file = array_merge($file,$data['ChangeCancelOfficial']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelOfficial') !== false){
                    return $this->error('请上传附件划转公文');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E

            $ChangeModel = new ChangeCancelModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }
        
            // 入库使用权变更表
            unset($filData['id']);
            $row = $ChangeModel->allowField(true)->create($filData);
            //halt($row);
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

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changecancel_file_upload']])->value('value');
            $file = [];
            if(isset($data['ChangeCancelOne']) && $data['ChangeCancelOne']){ // 售房专用票据  
                $file = array_merge($file,$data['ChangeCancelOne']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelOne') !== false){
                    return $this->error('请上传附件拆售房专用票据');
                }
            }
            if(isset($data['ChangeCancelTwo']) && $data['ChangeCancelTwo']){ // 审批表
                $file = array_merge($file,$data['ChangeCancelTwo']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelTwo') !== false){
                    return $this->error('请上传附件审批表');
                }
            }
            if(isset($data['ChangeCancelThree']) && $data['ChangeCancelThree']){ // 危改批文
                $file = array_merge($file,$data['ChangeCancelThree']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelThree') !== false){
                    return $this->error('请上传附件危改批文');
                }
            }
            if(isset($data['ChangeCancelRetread']) && $data['ChangeCancelRetread']){ // 翻新计划
                $file = array_merge($file,$data['ChangeCancelRetread']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelRetread') !== false){
                    return $this->error('请上传附件翻新计划');
                }
            }
            if(isset($data['ChangeCancelApproval']) && $data['ChangeCancelApproval']){ // 发还批文
                $file = array_merge($file,$data['ChangeCancelApproval']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelApproval') !== false){
                    return $this->error('请上传附件发还批文');
                }
            }
            if(isset($data['ChangeCancelReport']) && $data['ChangeCancelReport']){ // 注销报告
                $file = array_merge($file,$data['ChangeCancelReport']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelReport') !== false){
                    return $this->error('请上传附件注销报告');
                }
            }
            if(isset($data['ChangeCancelPaper']) && $data['ChangeCancelPaper']){ // 政府文件
                $file = array_merge($file,$data['ChangeCancelPaper']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelPaper') !== false){
                    return $this->error('请上传附件政府文件');
                }
            }
            if(isset($data['ChangeCancelInvoice']) && $data['ChangeCancelInvoice']){ // 发票（拆迁回款单）
                $file = array_merge($file,$data['ChangeCancelInvoice']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelInvoice') !== false){
                    return $this->error('请上传附件发票');
                }
            }
            if(isset($data['ChangeCancelDetail']) && $data['ChangeCancelDetail']){ // 征收明细表
                $file = array_merge($file,$data['ChangeCancelDetail']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelDetail') !== false){
                    return $this->error('请上传附件征收明细表');
                }
            }
            if(isset($data['ChangeCancelOfficial']) && $data['ChangeCancelOfficial']){ // 划转公文
                $file = array_merge($file,$data['ChangeCancelOfficial']);
            }else{
                if(strpos($fileUploadConfig, 'ChangeCancelOfficial') !== false){
                    return $this->error('请上传附件划转公文');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E
            
            $ChangeModel = new ChangeCancelModel;
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
                $process = $ProcessModel->where([['change_type','eq',8],['change_id','eq',$row['id']]])->update(['curr_role'=>5,'change_desc'=>'待资料员初审']);
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
            $fields = "a.id,a.is_back,a.change_order_number,a.cancel_type,a.cancel_rent,a.cancel_area,a.cancel_use_area,a.cancel_oprice,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.entry_date,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
            foreach($data['data'] as &$s){
                $table_data = Db::name('change_table')->where([['change_type','eq',8],['change_order_number','eq',$s['change_order_number']]])->field('change_order_number,sum(change_area) as total_change_area,sum(change_rent) as total_change_rent,sum(change_use_area) as total_change_use_area,sum(change_oprice) as total_change_oprice')->group('change_order_number')->having('count(change_order_number) > 1')->find();
                if(!empty($table_data)){
                    $s['cancel_rent'] = $table_data['total_change_rent'];
                    $s['cancel_area'] = $table_data['total_change_area'];
                    $s['cancel_use_area'] = $table_data['total_change_use_area'];
                    $s['cancel_oprice'] = $table_data['total_change_oprice'];
                    
                }
            }
            $data['count'] = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');

            $total_data = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('a.change_status desc,ftime desc')->select();
            $total_cancel_rent = $total_cancel_area = $total_cancel_use_area = $total_cancel_oprice = 0;
            foreach($total_data as &$a){
                $table_data = Db::name('change_table')->where([['change_type','eq',8],['change_order_number','eq',$s['change_order_number']]])->field('change_order_number,sum(change_area) as total_change_area,sum(change_rent) as total_change_rent,sum(change_use_area) as total_change_use_area,sum(change_oprice) as total_change_oprice')->group('change_order_number')->having('count(change_order_number) > 1')->find();
                if(!empty($table_data)){
                    $a['cancel_rent'] = $table_data['total_change_rent'];
                    $a['cancel_area'] = $table_data['total_change_area'];
                    $a['cancel_use_area'] = $table_data['total_change_use_area'];
                    $a['cancel_oprice'] = $table_data['total_change_oprice'];
                    
                }

                $total_cancel_rent = bcadd($total_cancel_rent, $a['cancel_rent'],2);
                $total_cancel_area = bcadd($total_cancel_area, $a['cancel_area'],2);
                $total_cancel_use_area = bcadd($total_cancel_use_area, $a['cancel_use_area'],2);
                $total_cancel_oprice = bcadd($total_cancel_oprice, $a['cancel_oprice'],2);

            }
            $data['total_cancel_rent'] = $total_cancel_rent;
            $data['total_cancel_area'] = $total_cancel_area;
            $data['total_cancel_use_area'] = $total_cancel_use_area;
            $data['total_cancel_oprice'] = $total_cancel_oprice;
            // $totalRow = Db::name('change_cancel')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(cancel_rent) as total_cancel_rent, sum(cancel_area) as total_cancel_area, sum(cancel_use_area) as total_cancel_use_area, sum(cancel_oprice) as total_cancel_oprice')->find();
            // if($totalRow){
            //     $data['total_cancel_rent'] = $totalRow['total_cancel_rent'];
            //     $data['total_cancel_area'] = $totalRow['total_cancel_area'];
            //     $data['total_cancel_use_area'] = $totalRow['total_cancel_use_area'];
            //     $data['total_cancel_oprice'] = $totalRow['total_cancel_oprice'];
            // }
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function record_old()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeCancelModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            $fields = "a.id,a.is_back,a.change_order_number,a.cancel_type,a.cancel_rent,a.cancel_area,a.cancel_use_area,a.cancel_oprice,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.entry_date,d.ban_address,d.ban_owner_id,d.ban_inst_id";
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