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
use think\Db;
use app\order\model\OpType;
use app\system\model\SystemAffiche;
use app\common\model\SystemAnnex;
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
            $opTypeModel = new OpType;
            $opTypeArr = $opTypeModel->column('id,title');
            $data = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('dtime desc,ctime desc')->limit($limit)->select();
            if($getData['group'] == 'j'){
            	foreach($temps as &$v){
	            	if($v['dtime'] && !$v['ftime']){
						$v['status_info'] = '待确认';
	            	}
	            	if(!$v['dtime']){
						$v['status_info'] = '处理中';
	            	}
                    $duidArr = explode(',',$v['duid']);
                    $currid = end($duidArr);
                    //halt($currid);
                    if(!$v['dtime'] && ($currid == ADMIN_ID) && (count($duidArr )>1)){
                        $v['status_info'] = '补充资料';
                        $v['ifadd'] = ''; 
                    }else{
                        $v['ifadd'] = 'j-visibility';
                    }
                    $v['op_order_type_name'] = $opTypeArr[$v['op_order_type']];
	            }

            }else{
				foreach($temps as &$v){
					$uids = explode(',',$v['duid']);
                    $yunyin_uid = $uids[1];
					$v['nick'] = UserModel::where([['id','eq',$yunyin_uid]])->value('nick');
                    $v['op_order_type_name'] = $opTypeArr[$v['op_order_type']];
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

        $row['jsondata'] = json_decode($row['jsondata'],true);
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
        $row['imgs'] = SystemAnnex::changeFormat($row['imgs']);

        //工单类型
        $opTypeModel = new OpType;
        $row['op_order_type_name'] = $opTypeModel->where([['id','eq',$row['op_order_type']]])->value('title');
        //halt($row);
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

    public function edit() 
    {
        $id = input('id/d');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $OporderModel = new OporderModel();
            $row = $OporderModel->get($data['id']);
            
            if(!isset($data['file'])){
                return $this->error('请补充资料!');
            }
            
            $filData = $OporderModel->dataFilter($data,'addfiles');
            
            //dump($row);halt($filData);
            if (!$OporderModel->allowField(true)->update($filData)) {
                return $this->error('补充失败');
            }
            
            $systemAffiche               = new SystemAffiche;
            $duidArr = explode(',',$row['duid']);

            $systemAffiche->title        = '【' . session('admin_user.nick') . '】已补充完附件，待您处理！';
            $systemAffiche->content      = '一条【'. session('admin_user.nick') . '】已补充完附件，待您处理！工单编号：' . $filData['op_order_number'] . '。请您尽快处理！';
            $systemAffiche->from_user_id = '*';
            $systemAffiche->url = '/admin.php/order/accept/index.html';
            $systemAffiche->to_user_id   = '|' . $duidArr[1] . '|';
            $systemAffiche->create_time  = time();
            $systemAffiche->save();
            return $this->success('补充成功',url('index'));
        }
        $OporderModel = new OporderModel();
        $row = $OporderModel->get($id);
        $opType = new OpType;
        $opTypesArr = $opType->where([['status','eq',1]])->order('sort')->select()->toArray();
        $fileArr = Db::name('file_type')->column('id,file_type,file_name');
        $opResultArr = [];
        $opFileArr = [];
        foreach($opTypesArr as $op){
            $opFileArr[$op['id']] = $op['filetypes'];
            if($op['pid'] === 0){ //顶级
                $opResultArr[$op['id']] = $op;
            }else{
               $opResultArr[$op['pid']]['children'][] = $op; 

            }

        }
        $row['imgs'] = SystemAnnex::changeFormat($row['imgs']);
        //halt($row);
        $this->assign('data_info',$row);
        $this->assign('fileArr',$fileArr);
        $this->assign('opFileArr',$opFileArr);
        $this->assign('opResultArr',$opResultArr);
        return $this->fetch();
    }
}