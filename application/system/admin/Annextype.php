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

use app\common\model\SystemAnnexType;

/**
 * 附件控制器
 * @package app\system\admin
 */

class Annextype extends Admin
{

    /**
     * 附件管理
     * @return mixed
     */
    public function index() 
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $systemAnnexTypeModel = new SystemAnnexType;
            $data = [];
            $data['data'] = $systemAnnexTypeModel->where([['status','eq',1]])->page($page)->limit($limit)->select();
            $data['count'] = $systemAnnexTypeModel->where([['status','eq',1]])->count('id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 新增附件分类
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            $result = $this->validate($data, 'SystemAnnexType');
            if($result !== true) {
                return $this->error($result);
            }
            $systemAnnexTypeModel = new SystemAnnexType;
           
            // 入库
            if (!$systemAnnexTypeModel->allowField(true)->create($data)) {
                return $this->error('新增失败');
            }
            return $this->success('新增成功');
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
        $systemAnnexTypeModel = new SystemAnnexType;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            // $result = $this->validate($data, 'SystemAnnexType');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            // 入库
            if (!$systemAnnexTypeModel->allowField(true)->update($data)) {
                return $this->error('编辑失败');
            }
            return $this->success('编辑成功');
        }
        $id = input('param.id/d');
        $row = $systemAnnexTypeModel->find($id);
        $this->assign('data_info',$row);
        return $this->fetch('form');
    }

    /**
     * 删除帮助文档
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function del()
    {
        $ids = $this->request->param('id/a'); 
        $systemAnnexTypeModel = new SystemAnnexType;       
        $res = $systemAnnexTypeModel->where([['id','in',$ids]])->setField('status',0);
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
  
}
