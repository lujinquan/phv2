<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\deal\model\ChangeCutCancel as ChangeCutCancelModel;
use app\deal\model\ChangeCutYear as ChangeCutYearModel;

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
            if($group == 'x'){
                $ChangeCutModel = new ChangeCutModel;
                $where = $ChangeCutModel->checkWhere($getData,'apply');
                //halt($where);
                $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id";
                $data = [];
                $data['data'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->order('etime desc')->where($where)->page($page)->limit($limit)->select();
                $data['count'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
                $totalRow = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(cut_rent) as total_cut_rent')->find();
                if($totalRow){
                    $data['total_cut_rent'] = $totalRow['total_cut_rent'];
                }
            }else{
                $ChangeCutYearModel = new ChangeCutYearModel;
                $where = $ChangeCutYearModel->checkWhere($getData,'apply');
                //halt($where);
                $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_status,a.is_back,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id";
                $data = [];
                $data['data'] = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->order('etime desc')->where($where)->page($page)->limit($limit)->select();
                $data['count'] = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
                $totalRow = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(cut_rent) as total_cut_rent')->find();
                if($totalRow){
                    $data['total_cut_rent'] = $totalRow['total_cut_rent'];
                }
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
            if($data['group'] == 'x'){
                // 数据验证
                $result = $this->validate($data, 'Changecut.form');
                if($result !== true) {
                    return $this->error($result);
                }

                // 附件上传验证 S
                $fileUploadConfig = Db::name('config')->where([['title','eq','changecut_file_upload']])->value('value');
                $file = [];
                if(isset($data['TenantReIDCard']) && $data['TenantReIDCard']){ // 身份证  
                    $file = array_merge($file,$data['TenantReIDCard']);
                }else{
                    if(strpos($fileUploadConfig, 'TenantReIDCard') !== false){
                        return $this->error('请上传身份证');
                    }
                }
                if(isset($data['Residence']) && $data['Residence']){ // 户口本
                    $file = array_merge($file,$data['Residence']);
                }else{
                    if(strpos($fileUploadConfig, 'Residence') !== false){
                        return $this->error('请上传户口本');
                    }
                }
                if(isset($data['HouseForm']) && $data['HouseForm']){ // 租约
                    $file = array_merge($file,$data['HouseForm']);
                }else{
                    if(strpos($fileUploadConfig, 'HouseForm') !== false){
                        return $this->error('请上传租约');
                    }
                }
                if(isset($data['Lowassurance']) && $data['Lowassurance']){ // 低保证
                    $file = array_merge($file,$data['Lowassurance']);
                }else{
                    if(strpos($fileUploadConfig, 'Lowassurance') !== false){
                        return $this->error('请上传低保证');
                    }
                }
                if(isset($data['Housingsecurity']) && $data['Housingsecurity']){ // 租房保障申请表
                    $file = array_merge($file,$data['Housingsecurity']);
                }else{
                    if(strpos($fileUploadConfig, 'Housingsecurity') !== false){
                        return $this->error('请上传租房保障申请表');
                    }
                }
                $data['file'] = $file;
                // 附件上传验证 E
                
                $ChangeCutModel = new ChangeCutModel;
                // 数据过滤
                $filData = $ChangeCutModel->dataFilter($data,'add');
                if(!is_array($filData)){
                    return $this->error($filData);
                }
            
                // 入库使用权变更表
                unset($filData['id']);
                $useRow = $ChangeCutModel->allowField(true)->create($filData);
                if (!$useRow) {
                    return $this->error('申请失败');
                }
                if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $useRow['id'];
                    unset($filData['id']);
                    if (!$ProcessModel->allowField(true)->create($filData)) {
                        return $this->error('未知错误');
                    }
                    $msg = '保存并提交成功';
                }else{
                    $msg = '保存成功';
                }

            // 年审
            }else{
                // 数据验证
                $result = $this->validate($data, 'Changecutyear.form');
                if($result !== true) {
                    return $this->error($result);
                }
                $ChangeCutYearModel = new ChangeCutYearModel;
                // 数据过滤
                $filData = $ChangeCutYearModel->dataFilter($data,'add');
                if(!is_array($filData)){
                    return $this->error($filData);
                }
                // 入库使用权变更表
                unset($filData['id']);
                $useRow = $ChangeCutYearModel->allowField(true)->create($filData);
                if (!$useRow) {
                    return $this->error('申请失败');
                }
                if($data['save_type'] == 'submit'){ //如果是保存并提交，则入库审批表
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $useRow['id'];
                    unset($filData['id']);
                    if (!$ProcessModel->allowField(true)->create($filData)) {
                        return $this->error('未知错误');
                    }
                    $msg = '保存并提交成功';
                }else{
                    $msg = '保存成功';
                }      

            }
            
            return $this->success($msg,'index?group='.$data['group']); 
        }       
        return $this->fetch('apply_'.$group);
    }

    public function edit()
    {
        $group = input('group','x');
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            if($data['group'] == 'x'){
                // 数据验证
                $result = $this->validate($data, 'Changecut.edit');
                if($result !== true) {
                    return $this->error($result);
                }
                
                // 附件上传验证 S
                $fileUploadConfig = Db::name('config')->where([['title','eq','changecut_file_upload']])->value('value');
                $file = [];
                if(isset($data['TenantReIDCard']) && $data['TenantReIDCard']){ // 身份证  
                    $file = array_merge($file,$data['TenantReIDCard']);
                }else{
                    if(strpos($fileUploadConfig, 'TenantReIDCard') !== false){
                        return $this->error('请上传身份证');
                    }
                }
                if(isset($data['Residence']) && $data['Residence']){ // 户口本
                    $file = array_merge($file,$data['Residence']);
                }else{
                    if(strpos($fileUploadConfig, 'Residence') !== false){
                        return $this->error('请上传户口本');
                    }
                }
                if(isset($data['HouseForm']) && $data['HouseForm']){ // 租约
                    $file = array_merge($file,$data['HouseForm']);
                }else{
                    if(strpos($fileUploadConfig, 'HouseForm') !== false){
                        return $this->error('请上传租约');
                    }
                }
                if(isset($data['Lowassurance']) && $data['Lowassurance']){ // 低保证
                    $file = array_merge($file,$data['Lowassurance']);
                }else{
                    if(strpos($fileUploadConfig, 'Lowassurance') !== false){
                        return $this->error('请上传低保证');
                    }
                }
                if(isset($data['Housingsecurity']) && $data['Housingsecurity']){ // 租房保障申请表
                    $file = array_merge($file,$data['Housingsecurity']);
                }else{
                    if(strpos($fileUploadConfig, 'Housingsecurity') !== false){
                        return $this->error('请上传租房保障申请表');
                    }
                }
                $data['file'] = $file;
                // 附件上传验证 E
                
                $ChangeCutModel = new ChangeCutModel;
                // 数据过滤
                $filData = $ChangeCutModel->dataFilter($data,'edit');
                if(!is_array($filData)){
                    return $this->error($filData);
                }

                // 入库使用权变更表
                $useRow = $ChangeCutModel->allowField(true)->update($filData);
                if ($useRow === false) {
                    return $this->error('申请失败');
                }
                //halt($useRow);
                if($data['save_type'] == 'submit' && count($useRow['child_json']) == 1){ //如果是保存并提交，则入库审批表
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $useRow['id'];
                    unset($filData['id']);
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
                // 数据验证
                $result = $this->validate($data, 'Changecutyear.edit');
                if($result !== true) {
                    return $this->error($result);
                }
                $ChangeCutYearModel = new ChangeCutYearModel;
                // 数据过滤
                $filData = $ChangeCutYearModel->dataFilter($data,'edit');
                if(!is_array($filData)){
                    return $this->error($filData);
                }
                //halt($filData);
                // 入库使用权变更表
                $useRow = $ChangeCutYearModel->allowField(true)->update($filData);
                if (!$useRow) {
                    return $this->error('申请失败');
                }
                //halt($useRow);
                if($data['save_type'] == 'submit' && count($useRow['child_json']) == 1){ //如果是保存并提交，则入库审批表
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $filData['change_id'] = $useRow['id'];
                    unset($filData['id']);
                    if (!$ProcessModel->allowField(true)->create($filData)) {
                        return $this->error('未知错误');
                    }
                    $msg = '保存并提交成功';
                }elseif($data['save_type'] == 'submit' && count($useRow['child_json']) > 1){ 
                    // 入库审批表
                    $ProcessModel = new ProcessModel;
                    $process = $ProcessModel->where([['change_type','eq',16],['change_id','eq',$useRow['id']]])->update(['curr_role'=>6,'change_desc'=>'待经租会计初审']);
                    if (!$process) {
                        return $this->error('未知错误');
                    }
                    $msg = '保存并提交成功';
                }else{
                    $msg = '保存成功';
                }
            }
            
            return $this->success($msg,'index?group='.$data['group']);
        }
        $id = $this->request->param('id');
        if($group == 'x'){
            $ChangeCutModel = new ChangeCutModel;
            $row = $ChangeCutModel->detail($id);
            $this->assign('data_info',$row);
        }else{
            $ChangeCutYearModel = new ChangeCutYearModel;
            $row = $ChangeCutYearModel->detail($id);
            $this->assign('data_info',$row);

            $ChangeCutModel = new ChangeCutModel;
            $cutRow = $ChangeCutModel->where([['house_id','eq',$row['house_id']],['change_status','eq',1]])->order('ftime desc')->find();
            $oldRow = $ChangeCutModel->detail($cutRow['id']);

            $this->assign('old_data_info',$oldRow);

        }
        //halt($row);
        return $this->fetch('edit_'.$group);
    }

    public function detail()
    {
        $group = input('group','x');
        $id = $this->request->param('id');
        if($group == 'x'){
            $ChangeCutModel = new ChangeCutModel;           
            $row = $ChangeCutModel->detail($id);
        }else{
            $ChangeCutYearModel = new ChangeCutYearModel;
            $row = $ChangeCutYearModel->detail($id); 

            $ChangeCutModel = new ChangeCutModel;
            $cutRow = $ChangeCutModel->where([['house_id','eq',$row['house_id']],['change_status','eq',1]])->order('ftime desc')->find();
            $oldRow = $ChangeCutModel->detail($cutRow['id']);
            $this->assign('old_data_info',$oldRow);
        }     
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
            if(!$data['change_remark']){
                return $this->error('请填写取消原因！');
            }
            if(isset($data['file']) && $data['file']){
                $data['change_imgs'] = implode(',',$data['file']);
            }else{
               return $this->error('请上传取消报告！'); 
            }
            
            //halt($data);
            $filData = [
                'change_cut_id' => $data['id'],
                'change_remark' => $data['change_remark'],
                'change_imgs' => $data['change_imgs'],
                'change_status' => 1,
            ];
            // 入库减免取消
            $ChangeCutCancelModel = new ChangeCutCancelModel;
            if (!$ChangeCutCancelModel->allowField(true)->create($filData)) {
                return $this->error('未知错误！');
            }

            // 将减免异动的减免结束时间改成当月
            $ChangeCutModel = new ChangeCutModel;
            $row = $ChangeCutModel->get($data['id']);
            $row->end_date = date('Ym');
            $row->is_valid = 0;
            $row->save();
            //$ChangeCutModel->where([['id','eq',$data['id']]])->update(['end_date'=>date('Ym')]);
            // 将异动统计表的该减免结束时间改成当月
            Db::name('change_table')->where([['change_order_number','eq',$row['change_order_number']]])->update(['end_date'=>date('Ym')]);
            //halt($data);
            return $this->success('取消成功！',url('index'));
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
        $group = input('group','x');
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            if($group == 'x'){
                $ChangeCutModel = new ChangeCutModel;
                $where = $ChangeCutModel->checkWhere($getData,'record');
                //halt($where);
                $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.is_back,a.end_date,a.entry_date,a.is_valid,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id,e.change_status as change_cut_status";
                //,CONCAT(left(a.end_date,4),'-',right(end_date,2)) as end_date
                $data = [];
                $data['data'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->join('change_cut_cancel e','a.id = e.change_cut_id','left')->field($fields)->where($where)->order('a.change_status desc,ftime desc')->page($page)->limit($limit)->select();
                //halt($data['data']);
                $data['count'] = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
                $totalRow = Db::name('change_cut')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(cut_rent) as total_cut_rent')->find();
                if($totalRow){
                    $data['total_cut_rent'] = $totalRow['total_cut_rent'];
                }
            }else{
                $ChangeCutModel = new ChangeCutModel;
                $where = $ChangeCutModel->checkWhere($getData,'record');
                //halt($where);
                $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,from_unixtime(a.ftime, '%Y-%m-%d') as fdate,a.ftime,a.change_status,a.is_back,a.entry_date,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id";
                $data = [];
                $data['data'] = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->order('a.change_status desc,ftime desc')->limit($limit)->select();
                $data['count'] = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
                $totalRow = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->field('sum(cut_rent) as total_cut_rent')->find();
                if($totalRow){
                    $data['total_cut_rent'] = $totalRow['total_cut_rent'];
                }
                // $fields = "a.id,a.change_order_number,a.cut_type,a.cut_rent,a.cut_rent_number,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,a.is_back,b.house_use_id,d.ban_address,c.tenant_name,d.ban_owner_id,d.ban_inst_id";
                // $data = [];
                // $data['data'] = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
                // //halt($data['data']);
                // $data['count'] = Db::name('change_cut_year')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            }
            
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);        
        }
        
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
        $group = input('group','x');
        if($group == 'x'){
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
        }else{
            $row = ChangeCutYearModel::get($id);
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
}