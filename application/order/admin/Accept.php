<?php

namespace app\order\admin;
use app\system\admin\Admin;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 待受理工单，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Accept extends Admin
{
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $OpOrderModel = new OpOrderModel;
            $where = $OpOrderModel->checkWhere($getData,'accept');
            
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

    // 待受理的详情
    public function detail()
    {
        $id = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);

        // 缺少一个判断，需要判断当前工单是否为当前角色待处理的工单【优化】
        $duid = explode(',',$row['duid']);
        $current_uid = array_pop($duid);


        $row['jsondata'] = json_decode($row['jsondata'],true);
        //如果改订单待处理的当前人和提交人相同，表示“待确认”，否则即为处理中
        if(count($duid) > 1 &&($duid[0] == $current_uid)){
            $row['status_info'] = '待确认';
        }else{
            $row['status_info'] = '处理中';
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
            
            if (!$OporderModel->allowField(true)->update($filData)) {
                return $this->error($msg.'失败');
            }
            return $this->success($msg.'成功',url('index'));
        }
    }
}