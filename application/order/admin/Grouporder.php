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

use app\system\admin\Admin;
use app\order\model\OpType;
use app\system\model\SystemAffiche;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\system\model\SystemUser as UserModel;
use app\system\model\SystemRole as RoleModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 组内待受理工单，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Grouporder extends Admin
{
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $OpOrderModel = new OpOrderModel;
            $where = $OpOrderModel->checkWhere($getData,'grouporder');  
            $opTypeModel = new OpType;
            $opTypeArr = $opTypeModel->column('id,title');
            $data = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            foreach($temps as $k => &$v){
                if(strpos($v['duid'],',') === false){
                    $v['status_info'] = '待处理';
                    $v['op_order_type_name'] = $opTypeArr[$v['op_order_type']];
                }else{
                    unset($temps[$k]);
                }
            }
            $data['data'] = array_slice($temps->toArray(), ($page - 1) * $limit, $limit);
            $data['count'] = $OpOrderModel->where($where)->count('id');
            $data['code'] = 0;
            $data['msg'] = '';
            
            return json($data);

        }
        return $this->fetch();
    }

    // public function add()
    // {
    //     if ($this->request->isPost()) {
    //         $data = $this->request->post();
    //         // 数据验证
    //         $result = $this->validate($data, 'OpOrder.sceneForm');
    //         if($result !== true) {
    //             return $this->error($result);
    //         }
    //         $OporderModel = new OporderModel();
    //         // 数据过滤
    //         $filData = $OporderModel->dataFilter($data);
    //         if (!$OporderModel->allowField(true)->create($filData)) {
    //             return $this->error('提交失败');
    //         }
    //         return $this->success('提交成功',url('Myorder/index'));
    //     }
    //     return $this->fetch();
    // }

    // 待受理的详情
    public function detail()
    {
        $id = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);
        // 缺少一个判断，需要判断当前工单是否为当前角色待处理的工单【优化】
        $duid = explode(',',$row['duid']);
        $current_uid = array_pop($duid);
        //$row['jsondata'] = json_decode($row['jsondata'],true);
        $temp = $row['jsondata'];
        if($temp){
           foreach($temp as &$v){
                if($v['Img']){
                    $v['Img'] = SystemAnnex::changeFormat($v['Img']);
                }
            } 
        }
        $row['jsondata'] = $temp;
        if($row['dtime'] && !$row['ftime']){
            $row['status_info'] = '待确认';
        }else if(!$row['dtime']){
            $row['status_info'] = '处理中';
        }else{
            $row['status_info'] = '已完结';
        }
        $opTypeModel = new OpType;
        $row['op_order_type_name'] = $opTypeModel->where([['id','eq',$row['op_order_type']]])->value('title');
        $row['imgs'] = SystemAnnex::changeFormat($row['imgs']);
        $this->assign('data_info',$row);
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
            if(isset($data['is_end'])){
                $result = $this->validate($data, 'OpOrder.sceneEnd');
            }else{
                $result = $this->validate($data, 'OpOrder.sceneTransfer');
            }    
            if($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            // 数据过滤
            if(isset($data['is_end'])){
                $filData = $OporderModel->dataFilter($data,'complete');
                $msg = '完结';
            }else{
                $filData = $OporderModel->dataFilter($data,'transfer');
                $msg = '转交';
            }
            //halt($filData);
            if (!$OporderModel->allowField(true)->update($filData)) {
                return $this->error($msg.'失败');
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
            $data['transfer_to'] = $data['transfer_to']?$data['transfer_to']:$filData['transfer_to'];

            $systemAffiche->title        = '【' . session('admin_user.nick') . '】转交给您的工单待'.$contentMsg.'！';
            $systemAffiche->content      = '一条【'. session('admin_user.nick') . '】转交给您的工单待'.$contentMsg.'！工单编号：' . $filData['op_order_number'] . '。请您尽快处理！';
            $systemAffiche->from_user_id = '*';
            $systemAffiche->url = $url;
            $systemAffiche->to_user_id   = '|' . $data['transfer_to'] . '|';
            $systemAffiche->create_time  = time();
            $systemAffiche->save();
            return $this->success($msg.'成功',url('index'));
        }
    }
    
}