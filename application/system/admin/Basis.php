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

namespace app\system\admin;

use Env;
use hisi\Dir;
use think\Db;
use app\system\model\SystemBasis;
use app\system\model\SystemConfig;

/**
 * 基础配置控制器
 * @package app\system\admin
 */
class Basis extends Admin
{
	public function index()
	{

		if ($this->request->isAjax()) {
			$page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $group = isset($getData['group'])?$getData['group']:'y';
            if($group == 'y'){
                $SystemBasis = new SystemBasis;
                // $where = $SystemNotice->checkWhere($getData);
                $data = $where = [];
                $where[] = ['delete_time','eq',0];
                $data['data'] = $SystemBasis->where($where)->page($page)->order('create_time desc')->limit($limit)->select();
                $data['count'] = $SystemBasis->where($where)->count();
                $data['code'] = 0;
                $data['msg'] = '';
                return json($data);
            }else{

            }  
        }
        $group = input('group','y');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '版本管理',
                'url' => '?group=y',
            ],
            [
                'title' => '版权设置',
                'url' => '?group=x',
            ]
        ];
        $SystemConfig = new SystemConfig;
        $row = $SystemConfig->where([['name','eq','copy_right']])->find();
        $this->assign('data_info',$row);

        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch('index_'.$group);
	}

	public function version_add()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'SystemBasis');
            if($result !== true) {
                return $this->error($result);
            }
            $SystemBasis = new SystemBasis;
            $data['cuid'] = ADMIN_ID;
            $data['content'] = $data['content'];
            // 入库
            if (!$SystemBasis->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功','','index');
        }
        return $this->fetch();
	}

	public function version_edit()
	{
        $SystemBasis = new SystemBasis;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'SystemBasis');
            if($result !== true) {
                return $this->error($result);
            }
            // 入库
            if (!$SystemBasis->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $SystemBasis->find($id);
        //halt($row);
        $this->assign('data_info',$row);
		return $this->fetch();
	}

	public function copyright_edit()
	{
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $SystemConfig = new SystemConfig;
            $row = $SystemConfig->where([['name','eq','copy_right']])->find();
            $row->options = $data['content'];
            $row->save();
            return $this->success('编辑成功');
        }
		return $this->fetch();
	}

	public function version_del()
	{
        $id = input('id');
        $SystemBasis = new SystemBasis;       
        $res = $SystemBasis->where([['id','eq',$id]])->update(['delete_time'=>time()]);
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
	}

}