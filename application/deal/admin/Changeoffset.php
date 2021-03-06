<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeOffset as ChangeOffsetModel;

/**
 * 陈欠核销
 */
class Changeoffset extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeOffsetModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.is_back,a.before_year_rent,a.before_month_rent,a.this_month_rent,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,b.house_number,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_offset')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->order('etime desc')->page($page)->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_offset')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_offset')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(before_month_rent) as total_before_month_rent,sum(before_year_rent) as total_before_year_rent')->find();
            if($totalRow){
                $data['total_before_month_rent'] = $totalRow['total_before_month_rent'];
                $data['total_before_year_rent'] = $totalRow['total_before_year_rent'];
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
            $result = $this->validate($data, 'Changeoffset.form');
            if($result !== true) {
                return $this->error($result);
            }

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changeoffset_file_upload']])->value('value');
            $file = [];
            if(isset($data['HouseForm']) && $data['HouseForm']){ // 其他  
                $file = array_merge($file,$data['HouseForm']);
            }else{
                if(strpos($fileUploadConfig, 'HouseForm') !== false){
                    return $this->error('请上传附件其他');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E
            
            $ChangeModel = new ChangeOffsetModel;
            // 数据过滤
            $filData = $ChangeModel->dataFilter($data,'add');
            if(!is_array($filData)){
                return $this->error($filData);
            }

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
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'Changeoffset.edit');
            if($result !== true) {
                return $this->error($result);
            }
            
            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title','eq','changeoffset_file_upload']])->value('value');
            $file = [];
            if(isset($data['HouseForm']) && $data['HouseForm']){ // 其他  
                $file = array_merge($file,$data['HouseForm']);
            }else{
                if(strpos($fileUploadConfig, 'HouseForm') !== false){
                    return $this->error('请上传附件其他');
                }
            }
            $data['file'] = $file;
            // 附件上传验证 E
            
            $ChangeModel = new ChangeOffsetModel;
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
                $filData['change_order_number'] = $row['change_order_number'];
                unset($filData['id']);
                //halt($filData);
                if (!$ProcessModel->allowField(true)->create($filData)) {
                    return $this->error('未知错误');
                }
                $msg = '保存并提交成功';
            }elseif($data['save_type'] == 'submit' && count($row['child_json']) > 1){ 
                // 入库审批表
                $ProcessModel = new ProcessModel;
                $process = $ProcessModel->where([['change_type','eq',4],['change_id','eq',$row['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
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
        $ChangeModel = new ChangeOffsetModel;
        $row = $ChangeModel->detail($id);
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeOffsetModel;
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
            $ChangeModel = new ChangeOffsetModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            $fields = "a.id,a.change_order_number,a.before_year_rent,a.before_month_rent,a.this_month_Rent,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.entry_date,b.house_number,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_offset')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
            //halt($data['data']);
            $data['count'] = Db::name('change_offset')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $totalRow = Db::name('change_offset')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','a.ban_id = d.ban_id','left')->where($where)->field('sum(before_month_rent) as total_before_month_rent,sum(before_year_rent) as total_before_year_rent')->find();
            if($totalRow){
                $data['total_before_month_rent'] = $totalRow['total_before_month_rent'];
                $data['total_before_year_rent'] = $totalRow['total_before_year_rent'];
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
        $row = ChangeOffsetModel::get($id);
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