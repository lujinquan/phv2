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
            $data = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            foreach($temps as $k => &$v){
                if(strpos($v['duid'],',') === false){
                    $v['status_info'] = '待处理';
                }else{
                    unset($temps[$k]);
                }
                // else{
                //     $uids = explode(',',$v['duid']);

                //     $current_uid = array_pop($uids);
                //     $find = UserModel::where([['id','eq',$current_uid]])->field('nick,role_id')->find();
                //     // 如果是运营中心
                //     if($find['role_id'] == 11 && ADMIN_ROLE == 11){
                //         $v['status_info'] = '转交至'.$find['nick'];
                //     }else{
                //         unset($temps[$k]);
                //     }
                    
                // }
            }
            $data['data'] = array_slice($temps->toArray(), ($page - 1) * $limit, $limit);
            $data['count'] = $OpOrderModel->where($where)->count('id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);

        }
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'OpOrder.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            // 数据过滤
            $filData = $OporderModel->dataFilter($data);
            if (!$OporderModel->allowField(true)->create($filData)) {
                return $this->error('提交失败');
            }
            return $this->success('提交成功',url('Myorder/index'));
        }
        return $this->fetch();
    }

    // 待受理的详情
    public function detail()
    {
        $id = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);
        // 缺少一个判断，需要判断当前工单是否为当前角色待处理的工单【优化】
        $duid = explode(',',$row['duid']);
        $current_uid = array_pop($duid);
        $row['jsondata'] = json_decode($row['jsondata'],true);
        $temp = $row['jsondata'];
        if($temp){
           foreach($temp as &$v){
                if($v['Img']){
                    $v['Img'] = explode(',',$v['Img']);
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
            return $this->success($msg.'成功',url('index'));
        }
    }
    
}