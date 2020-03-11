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

namespace app\wechat\admin;

use think\Db;
use app\system\admin\Admin;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\wechat\model\Weixin as WeixinModel;
use app\wechat\model\WeixinColumn as WeixinColumnModel;

/**
 * 微信小程序用户版
 */
class Wecolumn extends Admin
{
	public function index()
	{
		if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $WeixinColumnModel = new WeixinColumnModel;
            $where = $WeixinColumnModel->checkWhere($getData);
            $data = [];
            $temp = WeixinColumnModel::where($where)->page($page)->order('is_top desc,sort desc')->limit($limit)->select();
            foreach ($temp as $k => $v) {
            	$v['file'] = SystemAnnex::where([['id','eq',$v['col_icon']]])->value('file');
            }
            $data['data'] = $temp;
            $data['count'] = WeixinColumnModel::where($where)->count('col_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
		return $this->fetch();
	}

	public function edit()
	{
		$id = input('id');
		if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            // $result = $this->validate($data, 'Wecolumn.edit');
            // if($result !== true) {
            //     return $this->error($result);
            // }
            if(isset($data['file']) && $data['file']){
                $data['col_icon'] = implode(',',$data['file']);
            }else{
                $data['col_icon'] = '';
            }

            $WeixinColumnModel = new WeixinColumnModel;
            unset($data['file']);
            //halt($data);
            // 入库
            if (!$WeixinColumnModel->allowField(true)->where([['col_id','eq',$id]])->update($data)) {
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
		
		$WeixinColumnModel = new WeixinColumnModel;
		$row = $WeixinColumnModel->find($id);
		$row['col_icon'] = SystemAnnex::changeFormat($row['col_icon']);
		//halt($row);
		$this->assign('data_info',$row);
		return $this->fetch();
	}

	public function detail()
	{
		$id = input('id');
		$WeixinColumnModel = new WeixinColumnModel;
		$row = $WeixinColumnModel->find($id);
		$row['col_icon'] = SystemAnnex::changeFormat($row['col_icon']);
		//halt($row);
		$this->assign('data_info',$row);
		return $this->fetch();
	}

	/**
	 * 功能描述：启用禁用状态切换
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:30:34
	 */
	public function isTop()
	{
		$id = input('id');
		$WeixinColumnModel = new WeixinColumnModel;
		$info = $WeixinColumnModel->find($id);
		if($info->is_top == 1){
			$info->is_top = 0;
			$msg = '取消成功！';
		}else{
			$info->is_top = 1;
			$msg = '置顶成功！';
		}
		$result = $info->save();
		if ($result === false) {
            return $this->error('置顶设置失败');
        }
        $WeixinColumnModel->where([['col_id','neq',$id]])->update(['is_top'=>0]);
        return $this->success($msg);
	}

	/**
	 * 功能描述：启用禁用状态切换
	 * @author  Lucas 
	 * 创建时间: 2020-03-09 16:30:34
	 */
	public function isShow()
	{
		$id = input('id');
		$WeixinColumnModel = new WeixinColumnModel;
		$info = $WeixinColumnModel->find($id);
		if($info->is_show == 1){
			$info->is_show = 0;
			$info->is_top = 0;
			$msg = '禁用成功！';
		}else{
			$info->is_show = 1;
			$msg = '启用成功！';
		}
		$result = $info->save();
		if ($result === false) {
            return $this->error('状态设置失败');
        }

        return $this->success($msg);
	}

}