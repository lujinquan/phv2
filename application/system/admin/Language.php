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

use app\common\model\SystemLanguage as LanguageModel;

/**
 * 语言包管理控制器
 * @package app\system\admin
 */
class Language extends Admin
{
    /**
     * 语言包管理首页
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $data           = [];
            $data['data']   = LanguageModel::order('sort asc')->select();
            $data['code']   = 0;
            return json($data);
        }

        return $this->fetch();
    }

    /**
     * 添加语言包
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $model = new LanguageModel();
            if (!$model->storage()) {
                return $this->error($model->getError());
            }
            return $this->success('保存成功');
        }

        return $this->fetch('form');
    }

    /**
     * 修改语言包
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function edit()
    {
        $id = get_num();
        if ($this->request->isPost()) {
            $model = new LanguageModel();
            if (!$model->storage()) {
                return $this->error($model->getError());
            }
            return $this->success('保存成功');
        }
        $dataInfo = LanguageModel::get($id);
        $this->assign('formData', $dataInfo);
        return $this->fetch('form');
    }

    /**
     * 删除语言包
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function del()
    {
        $id = get_num();
        $model = new LanguageModel(); 
        if ($model->del($id) === false) {
            return $this->error('删除失败');
        }
        return $this->success('删除成功');
    }
}
