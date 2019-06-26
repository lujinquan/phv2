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
use app\system\model\SystemNotice;

use think\Db;

/**
 * 系统公告控制器
 * @package app\system\admin
 */
class Notice extends Admin
{

    /**
     * 初始化方法
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            //$getData = $this->request->get();
            $SystemNotice = new SystemNotice;
            //$data['data'] = $SystemNotice->select();
            //halt($where);
            $data = [];
            $data['data'] = $SystemNotice->page($page)->order('sort asc')->limit($limit)->select();

            //$result = [];
            // foreach($temps as $k => &$v){
                
            // }
            //halt($temps);
            //$data['data'] = array_slice($temps->toArray(), ($page - 1) * $limit, $limit);
            //$data['count'] = $OpOrderModel->where($where)->count('id');
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
            // 数据验证
            $result = $this->validate($data, 'SystemNotice');
            if($result !== true) {
                return $this->error($result);
            }
            $systemNotice = new SystemNotice;
            $data['cuid'] = ADMIN_ID;
            
            $data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$systemNotice->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功');
        }
        return $this->fetch();
    }

    public function edit()
    {
        $systemNotice = new SystemNotice;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'SystemNotice');
            if($result !== true) {
                return $this->error($result);
            }
            
            //$data['cuid'] = ADMIN_ID;
            // 入库
            if (!$systemNotice->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $systemNotice->find($id);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    public function detail()
    {
        $systemNotice = new SystemNotice;
        $id = input('param.id/d');
        $row = $systemNotice->find($id);
        // 更新已读记录 
        $systemNotice->updateReads($id);
        $this->assign('data_info',$row);
        return $this->fetch();
    }

    public function del()
    {
        $ids = $this->request->param('id/a'); 
        $systemNotice = new SystemNotice;       
        $res = $systemNotice->where([['id','in',$ids]])->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}
