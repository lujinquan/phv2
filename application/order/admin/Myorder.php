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
}