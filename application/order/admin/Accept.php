<?php

// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\order\admin;

use app\order\model\OpType;
use app\system\admin\Admin;
use app\system\model\SystemAffiche;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 待受理工单，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Accept extends Admin 
{
    /**
     * 待受理工单列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index() 
    {

        // $OpOrderModel = new OpOrderModel;
        // halt($OpOrderModel->getAcceptCount());
        if ($this->request->isAjax()) {
            $page         = input('param.page/d', 1);
            $limit        = input('param.limit/d', 10);
            $getData      = $this->request->get();
            $OpOrderModel = new OpOrderModel;
            $where        = $OpOrderModel->checkWhere($getData,'accept');
            $data  = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->order('ctime desc')->select()->toArray();
            $inst_ids = explode(',',session('admin_user.inst_ids'));
            $opTypeModel = new OpType;
            $opTypeArr = $opTypeModel->column('id,title');
//halt($temps);
            //‘转交至’状态的数据排在前面,‘待处理’状态的数据排在后面，且同状态的按照创建时间倒序排列
            $i = 1;
            $j = 10000;
            $teamps_arr = [];
            foreach ($temps as $k => $v) {
                if(ADMIN_ROLE == 9 && $v['role_id'] == 9){
//                    unset($temps[$k]);
                    continue;
                }
//                dump($v['inst_id']);halt($inst_ids);
                if (strpos($v['duid'], ',') === false) {
                    if (!in_array($v['inst_id'],$inst_ids)) {
//                        unset($temps[$k]);
                        continue;
                    } else {   
                        $v['op_order_type_name'] = $opTypeArr[$v['op_order_type']];
                        $v['status_info'] = '待处理';
                        $v['order_sort'] = $j;
                        $j++;
                    }
                    $teamps_arr[] = $v;
                } else {
                    $uids = explode(',', $v['duid']);

                    $current_uid = array_pop($uids);
                    if ($current_uid != ADMIN_ID) { //保证是待受理的工单
//                        unset($temps[$k]);
                        continue;
                    } else {
                        $v['op_order_type_name'] = $opTypeArr[$v['op_order_type']];
                        $current_nick     = UserModel::where([['id', 'eq', $current_uid]])->value('nick');
                        $v['status_info'] = '转交至'. $current_nick;
                        $v['order_sort'] = $i;
                        $i++;
                    }
                    $teamps_arr[] = $v;
                }
//                dump($v);
            }
//            halt($teamps_arr);
            sort($teamps_arr);

            //二维数组冒泡排序
            $a = [];
            foreach($teamps_arr as $key=>$val){
                $a[] = $val['order_sort'];//$a是$sort的其中一个字段
            }
            $temps = bubble_sort($teamps_arr,$a,'asc');//正序

            $data['data']  = array_slice($teamps_arr, ($page- 1) * $limit, $limit);
            $data['count'] = count($teamps_arr);
            $data['code']  = 0;
            $data['msg']   = '';
            //halt($data);
            return json($data);
        }
        return $this->fetch();
    }

    public function turnForm()
    {
        $id = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);
        $this->assign('data_info', $row);
        return $this->fetch();
    }

    /**
     * 待受理工单列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add() 
    {
        
        // 【待解决问题，模糊查找whereOr如何实现】
        //$userRow = UserModel::whereOr([['role_id','eq',11],['inst_ids','like','%8,%']])->whereOr([['role_id','eq',11],['inst_ids','like','%,8%']])->select();
        //$userRow = Db::query("select * from system_user where role_id = 11 and ((inst_ids like %,8%) or (inst_ids like %8,%))");
        //halt($userRow);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //halt($data);
            // 数据验证
            $result = $this->validate($data, 'OpOrder.sceneForm');
            if ($result !== true) {
                return $this->error($result);
            }
            // 验证必传资料是否上传
            $opType = new OpType;
            $requireStr = $opType->where([['id','eq',$data['op_order_type']]])->value('filetypes');
            $annexTypeModel = new SystemAnnexType;
            $fileArr = $annexTypeModel->where([['id','in',$requireStr]])->field('file_type,file_name')->select();

            // $requireStr = Db::name('op_type')->where([['id','eq',$data['op_order_type']]])->value('filetypes');
            // $fileArr = Db::name('file_type')->where([['id','in',$requireStr]])->field('file_type,file_name')->select();
            $data['imgs'] = '';
            foreach($fileArr as $f){
                if($f['file_type'] != 'Extra'){
                    if(!isset($data[$f['file_type']])){
                        return $this->error('请提交资料“'.$f['file_name'].'”');
                    } 
                }
                if(isset($data[$f['file_type']])){
                    foreach($data[$f['file_type']] as $d){
                        $data['imgs'] .=  (','.$d);
                    }
                }
            }
            $data['imgs'] = $data['imgs']?substr($data['imgs'],1):'';
          
            // 数据过滤
            $OporderModel = new OporderModel();
            $filData = $OporderModel->dataFilter($data);

            //halt($filData);
            $row     = $OporderModel->allowField(true)->create($filData);
//            dump($row);
            if (!$row) {
                return $this->error('提交失败');
            }

            if(isset($filData['imgs'])){ //如果上传了附件，且提交成功，就修改附件的过期时间为0
                (new \app\common\model\SystemAnnex)->updateAnnexEtime($filData['imgs']);
            }
            // 【待解决问题，成功跳转后，菜单的高亮没有正确呈现】
            //return $this->success('提交成功',url('Myorder/index'));

            //$userRow                     = UserModel::where([['role_id', 'eq', 11], ['inst_ids', 'like', '%,' . $row['inst_id'] . ',%']])->find();
            $systemAffiche               = new SystemAffiche;
            $userArr                     = UserModel::where([['role_id', 'eq', 11],['status','eq',1]])->select();
//            halt($userArr);
            foreach($userArr as $users){
                $insts = explode(',',$users['inst_ids']);
                if(in_array($row['inst_id'],$insts)){
                    $userRow = $users;

                    
                    $systemAffiche->title        = '来自【' . session('admin_user.nick') . '】的工单待受理！';
                    $systemAffiche->content      = '您有一条来自【'. session('admin_user.nick') . '】的工单待受理！工单编号：' . $filData['op_order_number'] . '。请您尽快处理！';
                    $systemAffiche->from_user_id = '*';
                    $systemAffiche->url = '/admin.php/order/accept/index.html';
                    $systemAffiche->to_user_id   = '|' . $userRow['id'] . '|';
                    $systemAffiche->create_time  = time();
                    $systemAffiche->save();

                    break;
                }
            }
            return $this->success('提交成功');       
        }
        $opType = new OpType;
        $opTypesArr = $opType->where([['status','eq',1]])->order('sort')->column('id,title,pid,keyids,filetypes,remark');
        $annexTypeModel = new SystemAnnexType;
        $fileArr = $annexTypeModel->column('id,file_type,file_name');
        $opResultArr = [];
        $opAjaxTypeArr = [];
        $opFileArr = [];
        $i = 0;
        foreach($opTypesArr as $v){    
            $opFileArr[$v['id']] = $v['filetypes'];
            if($v['pid'] == 0){
                $opAjaxTypeArr[$i]['value'] = $v['id'];
                $opAjaxTypeArr[$i]['label'] = $v['title'];
                foreach($opTypesArr as $v1){
                    if($v1['pid'] == $v['id']){
                        $opAjaxTypeArr[$i]['children'][] = [
                            'value'=>$v1['id'],
                            'label'=>$v1['title'],
                        ];
                    }
                }
                $i++;
            }
        }
        $OpType = new OpType;
        $optypes = $OpType->where([['status','eq',1]])->order('sort')->select();
        $opTypesData = [];
        foreach($optypes as $op){
            if($op['pid'] == 0){
                
                $childs = [];
                foreach($optypes as $p){
                    if($p['pid'] == $op['id']){
                        $childs[] = $p['title'];
                    }
                }
                $opTypesData[] = [
                    'title' => $op['title'],
                    'childs'=> $childs,
                ];
            }
        }
        // halt($optypesData);
        //halt($opFileArr);
        $this->assign('opAjaxTypeArr',$opAjaxTypeArr);
        $this->assign('opTypesArr',$opTypesArr);
        $this->assign('opTypesData',$opTypesData);
        $this->assign('fileArr',$fileArr);
        $this->assign('opFileArr',$opFileArr);
        return $this->fetch();
    }

    // 待受理的详情
    public function detail() 
    {
        $id  = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);

        // 缺少一个判断，需要判断当前工单是否为当前角色待处理的工单【优化】
        $duid        = explode(',', $row['duid']);
        $current_uid = array_pop($duid);

        $temp            = $row['jsondata'];
        if ($temp) {
            foreach ($temp as &$v) {
                if ($v['Img']) {
                    $v['Img'] = SystemAnnex::changeFormat($v['Img']);
                }
            }
        }
        $row['jsondata'] = $temp;
        if ($row['dtime'] && !$row['ftime']) {
            $row['status_info'] = '待确认';
        } else if (!$row['dtime']) {
            $row['status_info'] = '处理中';
        } else {
            $row['status_info'] = '已完结';
        }
        //工单类型
        $opTypeModel = new OpType;
        $row['op_order_type_name'] = $opTypeModel->where([['id','eq',$row['op_order_type']]])->value('title');
        $row['imgs'] = SystemAnnex::changeFormat($row['imgs']);
        $this->assign('data_info', $row);
        return $this->fetch();
    }

    /**
     * 转交工单,完结工单
     * @return [type] [description]
     */
    public function transfer() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            if (isset($data['is_end'])) {
                $result = $this->validate($data,'OpOrder.sceneEnd');
            } else {
                $result = $this->validate($data,'OpOrder.sceneTransfer');
            }

            if ($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            // 数据过滤
            if (isset($data['is_end'])) {
                $filData = $OporderModel->dataFilter($data,'complete');
                $msg     = '完成';
            } else {
                $filData = $OporderModel->dataFilter($data,'transfer');
                $msg     = '转交';
            }
            if (!$OporderModel->allowField(true)->update($filData)){
                return $this->error($msg . '失败');
            }

            if(isset($filData['file'])){ //如果上传了附件，且提交成功，就修改附件的过期时间为0
                (new \app\common\model\SystemAnnex)->updateAnnexEtime($filData['file']);
            }
            //$userRow                     = UserModel::where([['id', 'eq', $data['thransfer_to']]])->find();
            
            if (isset($filData['dtime']) && $filData['dtime']) { //最终转给申请人的工单
                $contentMsg = '确认';
                $url = '/admin.php/order/myorder/index.html';
            } else {
                $contentMsg = '处理';
                $url = '/admin.php/order/accept/index.html';
            }
            $systemAffiche               = new SystemAffiche;
            $data['transfer_to'] = (isset($data['transfer_to']) && $data['transfer_to'])?$data['transfer_to']:$filData['transfer_to'];

            $systemAffiche->title        = '【' . session('admin_user.nick') . '】转交给您的工单待'.$contentMsg.'！';
            $systemAffiche->content      = '一条【'. session('admin_user.nick') . '】转交给您的工单待'.$contentMsg.'！工单编号：' . $filData['op_order_number'] . '。请您尽快处理！';
            $systemAffiche->from_user_id = '*';
            $systemAffiche->url = $url;
            $systemAffiche->to_user_id   = '|' . $data['transfer_to'] . '|';
            $systemAffiche->create_time  = time();
            $systemAffiche->save();
            return $this->success($msg . '成功', url('index'));
        }
    }

    /**
     * 退至发起人
     * @return [type] [description]
     */
    public function backToFirst() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data,'OpOrder.sceneBackToFirst');
            if ($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            $orderRow = $OporderModel->find($data['id']);
//            if($orderRow['back_times'] > 0){
//                return $this->error('您已退回过'.$orderRow['back_times'].'次！');
//            }else{
                $filData = $OporderModel->dataFilter($data,'back');

                if (!$OporderModel->allowField(true)->update($filData)){
                    return $this->error('退回失败');
                }

                if(isset($filData['file'])){ //如果上传了附件，且退回成功，就修改附件的过期时间为0
                    (new \app\common\model\SystemAnnex)->updateAnnexEtime($filData['file']);
                }

                $systemAffiche               = new SystemAffiche;
                $systemAffiche->title        = '【' . session('admin_user.nick') . '】退至给您的工单待补充资料！';
                $systemAffiche->content      = '一条【'. session('admin_user.nick') . '】退至给您的工单待补充资料！工单编号：' . $filData['op_order_number'] . '。请您尽快处理！';
                $systemAffiche->from_user_id = '*';
                $systemAffiche->url = '/admin.php/order/myorder/index.html';
                $systemAffiche->to_user_id   = '|' . $orderRow['cuid'] . '|';
                $systemAffiche->create_time  = time();
                $systemAffiche->save();

                return $this->success('退回成功',url('index'));
            }
            
//        }
    }

    
}
