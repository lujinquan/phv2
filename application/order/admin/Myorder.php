<?php

namespace app\order\admin;
use app\system\admin\Admin;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

class Myorder extends Admin
{
    public function index()
    {
    	$group = input('group','j');
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $OpOrderModel = new OpOrderModel;
            $where = $OpOrderModel->checkWhere($getData,'myorder');
            
            $data = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            if($getData['group'] == 'j'){
            	foreach($temps as &$v){
	            	if($v['dtime'] && !$v['ftime']){
						$v['status_info'] = '待确认';
	            	}
	            	if(!$v['dtime']){
						$v['status_info'] = '处理中';
	            	}
	            }
            }else{
				foreach($temps as &$v){
					$uids = explode(',',$v['duid']);
                    $yunyin_uid = $uids[1];
					$v['nick'] = UserModel::where([['id','eq',$yunyin_uid]])->value('nick');
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
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '进行中',
                'url' => '?group=j',
            ],
            [
                'title' => '已完结',
                'url' => '?group=w',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);

    	return $this->fetch('index_'.$group);
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
}