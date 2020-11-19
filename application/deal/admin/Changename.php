<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeName as ChangeNameModel;

/**
 * 别字更正
 */
class Changename extends Admin
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ChangeModel = new ChangeNameModel;
            $where = $ChangeModel->checkWhere($getData,'apply');
            $fields = "a.id,a.change_order_number,a.old_tenant_name,a.new_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,b.house_use_id,b.house_number,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('etime desc')->limit($limit)->select();
            $data['count'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
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
            $result = $this->validate($data, 'Changename.form');
            if($result !== true) {
                return $this->error($result);
            }

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title', 'eq', 'changename_file_upload']])->value('value');
            $file = [];
            if (isset($data['Houselease']) && $data['Houselease']) { // 计租表
                $file = array_merge($file, $data['Houselease']);
            } else {
                if (strpos($fileUploadConfig, 'Houselease') !== false) {
                    return $this->error('请上传计租表');
                }
            }
            if (isset($data['TenantReIDCard']) && $data['TenantReIDCard']) { // 身份证
                $file = array_merge($file, $data['TenantReIDCard']);
            } else {
                if (strpos($fileUploadConfig, 'TenantReIDCard') !== false) {
                    return $this->error('请上传身份证');
                }
            }
            if (isset($data['HouseApplicationForm']) && $data['HouseApplicationForm']) { // 租约
                $file = array_merge($file, $data['HouseApplicationForm']);
            } else {
                if (strpos($fileUploadConfig, 'HouseApplicationForm') !== false) {
                    return $this->error('请上传租约');
                }
            }
            if (isset($data['NameChangeApproval']) && $data['NameChangeApproval']) { // 审批表
                $file = array_merge($file, $data['NameChangeApproval']);
            } else {
                if (strpos($fileUploadConfig, 'NameChangeApproval') !== false) {
                    return $this->error('请上传审批表');
                }
            }
            if (isset($data['NameChangeApplication']) && $data['NameChangeApplication']) { // 申请报告
                $file = array_merge($file, $data['NameChangeApplication']);
            } else {
                if (strpos($fileUploadConfig, 'NameChangeApplication') !== false) {
                    return $this->error('请上传申请报告');
                }
            }
            $data['file'] = $file;

            $houseInfo = Db::name('house')->where([['house_id', 'eq', $data['house_id']]])->field('house_use_id')->find();
            if($houseInfo['house_use_id'] != 1 && $data['is_create_lease']){
                return $this->error('非住宅房屋无法自动发租约');
            }

            $ChangeModel = new ChangeNameModel;
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
            $result = $this->validate($data, 'Changename.edit');
            if ($result !== true) {
                return $this->error($result);
            }

            // 附件上传验证 S
            $fileUploadConfig = Db::name('config')->where([['title', 'eq', 'changename_file_upload']])->value('value');
            $file = [];
            if (isset($data['Houselease']) && $data['Houselease']) { // 计租表
                $file = array_merge($file, $data['Houselease']);
            } else {
                if (strpos($fileUploadConfig, 'Houselease') !== false) {
                    return $this->error('请上传计租表');
                }
            }
            if (isset($data['TenantReIDCard']) && $data['TenantReIDCard']) { // 身份证
                $file = array_merge($file, $data['TenantReIDCard']);
            } else {
                if (strpos($fileUploadConfig, 'TenantReIDCard') !== false) {
                    return $this->error('请上传身份证');
                }
            }
            if (isset($data['HouseApplicationForm']) && $data['HouseApplicationForm']) { // 租约
                $file = array_merge($file, $data['HouseApplicationForm']);
            } else {
                if (strpos($fileUploadConfig, 'HouseApplicationForm') !== false) {
                    return $this->error('请上传租约');
                }
            }
            if (isset($data['NameChangeApproval']) && $data['NameChangeApproval']) { // 审批表
                $file = array_merge($file, $data['NameChangeApproval']);
            } else {
                if (strpos($fileUploadConfig, 'NameChangeApproval') !== false) {
                    return $this->error('请上传审批表');
                }
            }
            if (isset($data['NameChangeApplication']) && $data['NameChangeApplication']) { // 申请报告
                $file = array_merge($file, $data['NameChangeApplication']);
            } else {
                if (strpos($fileUploadConfig, 'NameChangeApplication') !== false) {
                    return $this->error('请上传申请报告');
                }
            }
            $data['file'] = $file;

            $houseInfo = Db::name('house')->where([['house_id', 'eq', $data['house_id']]])->field('house_use_id')->find();
            if ($houseInfo['house_use_id'] != 1 && $data['is_create_lease']) {
                return $this->error('非住宅房屋无法自动发租约');


                $ChangeModel = new ChangeNameModel;
                // 数据过滤
                $filData = $ChangeModel->dataFilter($data, 'edit');
                if (!is_array($filData)) {
                    return $this->error($filData);
                }
                // 入库使用权变更表
                $row = $ChangeModel->allowField(true)->update($filData);
                if ($row === false) {
                    return $this->error('申请失败');
                }
                if ($data['save_type'] == 'submit') {
                    if (count($row['child_json']) == 1) {
                        // 入库审批表
                        $ProcessModel = new ProcessModel;
                        $filData['change_id'] = $row['id'];
                        $filData['change_order_number'] = $row['change_order_number'];
                        unset($filData['id']);
                        if (!$ProcessModel->allowField(true)->create($filData)) {
                            return $this->error('未知错误');
                        }
                    } elseif (count($row['child_json']) > 1) {
                        // 入库审批表
                        $ProcessModel = new ProcessModel;
                        $process = $ProcessModel->where([['change_type', 'eq', 17], ['change_id', 'eq', $row['id']]])->update(['curr_role' => 6, 'change_desc' => '待经租会计初审']);
                        if (!$process) {
                            return $this->error('未知错误');
                        }
                    }
                    $msg = '保存并提交成功';
                } else {
                    $msg = '保存成功';
                }
                return $this->success($msg, url('index'));
            }
            $id = $this->request->param('id');
            $ChangeModel = new ChangeNameModel;
            $row = $ChangeModel->detail($id);
            $this->assign('data_info', $row);
            return $this->fetch();
        }
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $ChangeModel = new ChangeNameModel;
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
            $ChangeModel = new ChangeNameModel;
            $where = $ChangeModel->checkWhere($getData,'record');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.old_tenant_name,a.new_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.entry_date,a.is_back,a.entry_date,b.house_use_id,b.house_number,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
            $data['count'] = Db::name('change_name')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    public function del()
    {
        $id = $this->request->param('id');       
        $row = ChangeNameModel::get($id);
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