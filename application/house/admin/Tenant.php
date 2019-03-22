<?php
namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\Tenant as TenantModel;

class Tenant extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $keywords = input('param.keywords');
            
            $where = [
                ['is_show','eq',1],
            ];
            if($keywords){
                $where[] = ['keywords','like','%'.$keywords.'%'];
            }
            
            $fields = 'tenant_id,tenant_inst_id,tenant_inst_pid,tenant_number,tenant_name,tenant_tel,tenant_card';
            $data['data'] = TenantModel::field($fields)->where($where)->page($page)->order('tenant_ctime desc')->limit($limit)->select();
            $data['count'] = TenantModel::where($where)->count('tenant_id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }
}