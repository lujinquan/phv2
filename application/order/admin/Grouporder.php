<?php

namespace app\order\admin;
use app\system\admin\Admin;
use app\system\model\SystemUser as UserModel;
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
            //$result = [];
            foreach($temps as $k => &$v){
                if(strpos($v['duid'],',') === false){
                    $v['status_info'] = '待处理';
                }else{
                    $uids = explode(',',$v['duid']);

                    $current_uid = array_pop($uids);
                    if($current_uid != ADMIN_ID){ //保证是待受理的工单
                       unset($temps[$k]); 
                    }else{
                        $current_nick = UserModel::where([['id','eq',$current_uid]])->value('nick');
                        $v['status_info'] = '转交至'.$current_nick;
                    }
                    
                }
            }
            //halt($temps);
            $data['data'] = array_slice($temps->toArray(), ($page - 1) * $limit, $limit);
            $data['count'] = $OpOrderModel->where($where)->count('id');
            $data['code'] = 0;
            $data['msg'] = '';
            //halt($data);
            return json($data);

        }
    	return $this->fetch();
    }

    public function add()
    {
    	if ($this->request->isPost()) {
            $data = $this->request->post();
            //halt($data);
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

    public function detail()
    {
        $id = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);
        $duid = explode(',',$row['duid']);
        $current_uid = array_pop($duid);

        // 如果是当前用户处理，或者是运营中心的人，就打开回复框
        if(ADMIN_ID == $current_uid || (ADMIN_ROLE == 11 && !$duid)){
            $row['is_current'] = true;
        }else{
            $row['is_current'] = false;
        }
        // 工单状态
        $current_nick = UserModel::where([['id','eq',$current_uid]])->value('nick');
        //halt($row);
        $row['jsondata'] = json_decode($row['jsondata'],true);

        if($duid){
            $row['status_info'] = '待确认';
            //$row['status_info'] = '转交给'.$current_nick;
        }else{
            $row['status_info'] = '处理中';
            //$row['status_info'] = $current_nick.'提交工单编号：'.$row['op_order_number'];
        }
    

        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     * 转交工单
     * @return [type] [description]
     */
    public function transfer()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // halt($data);
            // 数据验证
            $result = $this->validate($data, 'OpOrder.sceneTransfer');
            if($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            // 数据过滤
            $filData = $OporderModel->dataFilter($data,'transfer');
            if (!$OporderModel->allowField(true)->update($filData)) {
                return $this->error('转交失败');
            }
            return $this->success('转交成功',url('index'));
        }
    }
}