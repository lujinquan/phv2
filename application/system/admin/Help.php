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

use app\common\controller\Common;
use app\system\model\SystemHelp;
use app\system\model\SystemHelpType;
use think\Db;

/**
 * 系统帮助文档控制器
 * @package app\system\admin
 */
class Help extends Admin
{

    /**
     * 帮助文档列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();

            $group = isset($getData['group'])?$getData['group']:'cate';

            if($group == 'index'){
            	$where = [];
	            //标题筛选
	            if(isset($getData['title']) && $getData['title']){
	                $where[] = ['title','like','%'.$getData['title'].'%'];
	            }
	            //类型筛选
	            if(isset($getData['type']) && $getData['type']){
	                $where[] = ['type','eq',$getData['type']];
	            }
	            
	            $SystemHelp = new SystemHelp;
	            $data = [];
	            $data['data'] = $SystemHelp->where($where)->page($page)->order('sort asc,update_time desc')->limit($limit)->select();
	            $data['count'] = $SystemHelp->where($where)->count();
	            $data['code'] = 0;
	            $data['msg'] = '';
            }else{
            	$SystemHelpType = new SystemHelpType;
            	$where = [];
	            //标题筛选
	            if(isset($getData['type_name']) && $getData['type_name']){
	                $where[] = ['type_name','like','%'.$getData['type_name'].'%'];
	            }
	            
	          
	            $data = [];
	            $data['data'] = $SystemHelpType->where($where)->page($page)->order('sort asc,id asc')->limit($limit)->select();
	            $data['count'] = $SystemHelpType->where($where)->count();
	            $data['code'] = 0;
	            $data['msg'] = '';
            }	
            
            return json($data);
        }
        $group = input('group','index');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '文档',
                'url' => '?group=index',
            ],
            [
                'title' => '分类',
                'url' => '?group=cate',
            ],
           
        ];
        $tabData['current'] = url('?group='.$group);
        $this->assign('ban_number',input('param.ban_number',''));
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
    	return $this->fetch($group);
    }

    /**
     * 新增帮助文档
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'SystemHelp');
            if($result !== true) {
                return $this->error($result);
            }
            $SystemHelp = new SystemHelp;
            $data['cuid'] = ADMIN_ID;
            // $data['content'] = $_POST['content'];
            $data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$SystemHelp->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功');
        }
        return $this->fetch();
    }

    /**
     * 修改帮助文档
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function edit()
    {
        $SystemHelp = new SystemHelp;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'SystemHelp');
            if($result !== true) {
                return $this->error($result);
            }
            $data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$SystemHelp->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $SystemHelp->find($id);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    /**
     * 帮助文档详情
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function detail()
    {
        $SystemHelp = new SystemHelp;
        
        $id = input('param.id/d');
        $row = $SystemHelp->find($id);
        //halt($row);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    /**
     * 删除帮助文档
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function del()
    {
        $ids = $this->request->param('id/a'); 
        $SystemHelp = new SystemHelp;       
        $res = $SystemHelp->where([['id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}
