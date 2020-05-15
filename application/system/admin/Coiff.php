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

use think\Db;
use app\common\model\Cparam as CparamModel;
use app\system\model\SystemConfig as ConfigModel;

/**
 * 配置管理控制器
 * @package app\system\admin
 */

class Coiff extends Admin
{
	protected $hisiTable = 'Cparam';

	public function index($group = 'record')
    {
        if ($this->request->isAjax()) {
            $where  = $data = [];
            $page   = $this->request->param('page/d', 1);
            $limit  = $this->request->param('limit/d', 15);

            if ($group) {
                $where['group'] = $group;
            }

            $data['data']   = CparamModel::where($where)->page($page)->limit($limit)->order('sort,id')->select();
            $data['count']  = CparamModel::where($where)->count('id');
            $data['code']   = 0;
            return json($data);
        }

        $tabData = [];

        foreach (config('hs_system.cparam_group') as $key => $value) {
            $arr                = [];
            $arr['title']       = $value;
            $arr['url']         = '?group='.$key;
            $tabData['menu'][]  = $arr;
        }

        $tabData['current'] = url('?group='.$group);

        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    public function search()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            $flag = 0;
            foreach ($data as $k => $v) {
                if(count($v) > 0){
                    Db::name('config')->where([['title','eq',$k]])->update(['value'=>implode(',',$v)]);
                }else{
                    $flag = 1;  
                }
            }
            if($flag){
                return $this->error('请至少选一个搜索选项'); 
            }else{
                return $this->success('编辑成功');
            }
           
            
        }
        return $this->fetch();
    }

    /**
     * 添加配置
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            switch ($data['type']) {
                case 'switch':
                case 'radio':
                case 'checkbox':
                case 'select':
                    if (!$data['options']) {
                        return $this->error('请填写配置选项');
                    }
                    break;
                default:
                    break;
            }

            // 验证
            $result = $this->validate($data, 'Cparam');
            if($result !== true) {
                return $this->error($result);
            }

            if (!CparamModel::create($data)) {
                return $this->error('添加失败');
            }

            // 更新配置缓存
            //CparamModel::getConfig('', true);
            return $this->success('添加成功');
        }
        return $this->fetch('form');
    }

    /**
     * 修改配置
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function edit($id = 0)
    {
        $row = CparamModel::where('id', $id)->field('id,group,title,name,value,type,options,status,system')->find();

        if ($row['system'] == 1) {
            return $this->error('禁止编辑此配置');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'SystemConfig');

            if($result !== true) {
                return $this->error($result);
            }

            if (!CparamModel::update($data)) {
                return $this->error('保存失败');
            }

            // 更新配置缓存
            //CparamModel::getConfig('', true);
            return $this->success('保存成功');
        }

        //$row['tips'] = htmlspecialchars_decode($row['tips']);
        $row['value'] = htmlspecialchars_decode($row['value']);
        $this->assign('formData', $row);
        return $this->fetch('form');
    }

    /**
     * 删除配置
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function del()
    {
        $id = $this->request->param('id/a');
        $model = new CparamModel();
        
        if ($model->del($id)) {
            return $this->success('删除成功');
        }
        // 更新配置缓存
        //CparamModel::getConfig('', true);
        return $this->error($model->getError());
    }

}