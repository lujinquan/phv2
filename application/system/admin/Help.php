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

use think\Db;

/**
 * 系统帮助文档控制器
 * @package app\system\admin
 */
class Help extends Admin
{

    /**
     * 初始化方法
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $SystemHelp = new SystemHelp;
            $data = [];
            $data['data'] = $SystemHelp->page($page)->order('sort asc')->limit($limit)->select();
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
            $result = $this->validate($data, 'SystemHelp');
            if($result !== true) {
                return $this->error($result);
            }
            $SystemHelp = new SystemHelp;
            $data['cuid'] = ADMIN_ID;
            
            $data['content'] = htmlspecialchars($data['content']);
            // 入库
            if (!$SystemHelp->allowField(true)->create($data)) {
                return $this->error('发布失败');
            }
            return $this->success('发布成功');
        }
        return $this->fetch();
    }

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

    public function detail()
    {
        $SystemHelp = new SystemHelp;
        
        $id = input('param.id/d');
        $row = $SystemHelp->find($id);
        $reads = [];
        $i = true;
        if($row['reads']){
            $reads = json_decode($row['reads'],true);
            foreach($reads as $r){
                if($r['uid'] == ADMIN_ID){
                    $i = false;
                    break;
                }
            } 
        }
        if($i){
            $tempArr = [
                'uid' => ADMIN_ID,
                'time' => time()
            ];
            array_unshift($reads,$tempArr);
            $SystemHelp->where([['id','eq',$id]])->update(['reads'=>json_encode($reads)]);
        }
        $this->assign('data_info',$row);
        return $this->fetch();
    }

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
