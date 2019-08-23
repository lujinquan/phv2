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

namespace app\rent\admin;

use think\Db;
use app\system\admin\Admin;
use app\rent\model\Recharge as RechargeModel;

/**
 * 账户充值
 */
class Recharge extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $RechargeModel = new RechargeModel;
            $where = $RechargeModel->checkWhere($getData);

            $fields = "a.house_id,a.tenant_id,a.pay_rent,a.pay_way,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,b.house_use_id,b.house_number,c.tenant_name,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('ctime desc')->select();
            $data['count'] = Db::name('rent_recharge')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('tenant c','a.tenant_id = c.tenant_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');

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
            //halt($data);
            // 数据验证
            $result = $this->validate($data, 'Recharge.add');
            if($result !== true) {
                return $this->error($result);
            }
            $RechargeModel = new RechargeModel;
            // 数据过滤
            $filData = $RechargeModel->dataFilter($data);
            if(!is_array($filData)){
                return $this->error($filData);
            }
            // 入库
            if (!$RechargeModel->allowField(true)->create($filData)) {
                return $this->error('充值失败');
            }
            return $this->success('充值成功');
        }
        return $this->fetch();
    }
}