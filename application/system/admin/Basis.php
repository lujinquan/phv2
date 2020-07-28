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

use hisi\Dir;
use think\Db;
use Env;

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

            switch ($group) {
                case 'y':
                    $order = 'house_ctime desc';
                    break;
                case 'x':
                    $order = 'house_ctime desc';
                    break;
                case 'z':
                    $order = 'house_dtime desc';
                    break;
                default:
                    $order = 'house_ctime desc';
                    break;
            }

            $where = 1;
            if(isset($getData['table_name']) && $getData['table_name']){
            	$where .= " AND NAME LIKE '%".$getData['table_name']."%'";
            }
            if(isset($getData['table_remark']) && $getData['table_remark']){
            	$where .= " AND Comment LIKE '%".$getData['table_remark']."%'";
            }
            $data = [];
            $sql = "SHOW TABLE STATUS WHERE ".$where;
            $tables = Db::query($sql);
            foreach ($tables as $k => &$v) {
                if(strpos($v['Name'], '_back') !== false || strpos($v['Name'], '_copy') !== false){
                    unset($tables[$k]);
                }else{
                    $v['id'] = $v['Name'];
                }
                $v['id'] = $v['Name'];
            }
            $data['data'] = array_slice($tables, ($page- 1) * $limit, $limit);
            $data['count'] = count($tables);
            $data['code'] = 0;

            return json($data);
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
        $tabData['current'] = url('?group='.$group);
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
		return $this->fetch('index_'.$group);
	}

	public function version_add()
	{
		return $this->fetch();
	}

	public function version_edit()
	{
		return $this->fetch();
	}

	public function copyright_edit()
	{
		return $this->fetch();
	}

	public function version_del()
	{

	}

}