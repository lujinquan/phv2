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
use app\common\model\SystemAnnexType;
use app\order\model\OpOrder as OpOrderModel;
use app\system\admin\Admin;
use app\system\model\SystemAffiche;
use app\system\model\SystemUser as UserModel;

/**
 * 工单分类，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Classify extends Admin 
{
    /**
     * 工单分类设置
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index() 
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $opTypeModel = new OpType;
            $where = $opTypeModel->checkWhere($getData);
            $data = [];
            $temps = $opTypeModel->where($where)->order('ctime desc')->select()->toArray();
            $titles = $opTypeModel->column('id,title');
            $annexTypeModel = new SystemAnnexType;
            $files = $annexTypeModel->column('id,file_name');
			foreach ($temps as $k => &$v) {
				if($v['filetypes']){
					$filetyps = explode(',',$v['filetypes']);
					$v['filetypes'] = '';
					foreach($filetyps as $f){
						$v['filetypes'] .= ('，'.$files[$f]);
					}
					$v['filetypes'] = trim($v['filetypes'],'，');
				}
				if($v['pid'] == 0){
					$v['pid'] = '顶级分类';
				}else{
					$v['pid'] = $titles[$v['pid']];
				}
			}
            $data['data'] = array_slice($temps , ($page - 1) * $limit, $limit);
            $data['count'] = $opTypeModel->where($where)->count('id');
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
            $result = $this->validate($data, 'OpType.sceneForm');
            if($result !== true) {
                return $this->error($result);
            }
            $opTypeModel = new OpType;
            // 数据过滤
            $filData = $opTypeModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$opTypeModel->allowField(true)->create($filData)) {
                return $this->error('新增失败');
            }
            return $this->success('新增成功');
        }
        $annexTypeModel = new SystemAnnexType;
        $files = $annexTypeModel->column('id,file_name');
        $opTypeModel = new OpType;
        $titlesFirstClass = $opTypeModel->where([['pid','eq',0]])->column('id,title');
        $this->assign('files',$files);
		$this->assign('titlesFirstClass',$titlesFirstClass);
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'OpType.sceneEdit');
            if($result !== true) {
                return $this->error($result);
            }
            $opTypeModel = new OpType;
            // 数据过滤
            $filData = $opTypeModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$opTypeModel->allowField(true)->update($filData)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $annexTypeModel = new SystemAnnexType;
        $files = $annexTypeModel->column('id,file_name');
        $opTypeModel = new OpType;
        $titlesFirstClass = $opTypeModel->where([['pid','eq',0]])->column('id,title');
        $this->assign('files',$files);
		$this->assign('titlesFirstClass',$titlesFirstClass);
        $id = input('param.id/d');
        $row = $opTypeModel->find($id);
        $row['filetypes'] = $row['filetypes']?explode(',',$row['filetypes']):[];
        $row['keyids'] = $row['keyids']?explode(',',$row['keyids']):[];
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    // public function detail()
    // {
    //     $id = input('param.id/d');
    //     $row = TenantModel::get($id);
    //     $this->assign('data_info',$row);
    //     return $this->fetch();
    // }

    public function del()
    {
        $id = $this->request->param('id'); 
        $opTypeModel = new OpType;       
        $res = $opTypeModel->where([['id','eq',$id]])->setField('status',0);
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }  
    }


}