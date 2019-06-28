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
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('dtime desc')->limit($limit)->select();
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

        // 工单状态
        //$current_nick = UserModel::where([['id','eq',$current_uid]])->value('nick');
        //halt($row);
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
        $this->assign('group',input('group','j'));
        $this->assign('current_uid',$current_uid);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     * 转交工单,完结工单
     * @return [type] [description]
     */
    public function affirm()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $OporderModel = new OporderModel();
            // 数据过滤
            $filData = $OporderModel->dataFilter($data,'affirm');   
            //halt($filData);
            if (!$OporderModel->allowField(true)->update($filData)) {
                return $this->error('确认失败');
            }
            return $this->success('确认成功',url('index'));
        }
    }
}