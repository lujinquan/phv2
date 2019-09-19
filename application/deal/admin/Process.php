<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeUse as ChangeUseModel;

/**
 * 审核
 */
class Process extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $ProcessModel = new ProcessModel;
            $where = $ProcessModel->checkWhere($getData,'apply');
            //halt($where);
            $fields = "a.id,a.change_order_number,a.old_tenant_name,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_status,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $temp = Db::name('change_use')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('system_user c','a.cuid = c.id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->select();
            foreach ($temp as $k => &$v) {
                $v['change_type'] = 13;
            }
            $data['data'] = $temp;
            $data['count'] = Db::name('change_use')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->where($where)->count('a.id');
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @title 审核(此处的审核有别与补充资料)
     * @author Mr.Lu
     * @description
     */
    public function process()
    {
        $id = input('param.id/d');
        $change_type = input('param.change_type/d');
        
        if(!$change_type || !$id){
            return $this->error('参数错误！');
        }

        switch ($change_type) {
            case '1':
                
                break;
            case '2':
                # code...
                break;
            case '3': //暂停计租
                return $this->fetch('change_pause_process');
                break;
            case '4':
                # code...
                break;
            case '5':
                # code...
                break;

            case '6':
                # code...
                break;
            case '7':
                # code...
                break;

            case '8': //注销
                return $this->fetch('change_cancel_process');
                break;
            case '9':
                # code...
                break;
            case '10':
                # code...
                break;

            case '11':
                # code...
                break;
            case '12':
                # code...
                break;

            case '13': //使用权变更
                $ChangeUseModel = new ChangeUseModel;
                $row = $ChangeUseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_use_process');
                break;
            default:
                # code...
                break;
        }
   



        if($this->request->isPost()) {
            
            $data = $this->request->post();
            
            switch ($data['change_type']) {
                case '1':
                    # code...
                    break;
                case '2':
                    # code...
                    break;
                case '3':
                    # code...
                    break;
                case '4':
                    # code...
                    break;
                case '5':
                    # code...
                    break;

                case '6':
                    # code...
                    break;
                case '7':
                    # code...
                    break;

                case '8':
                    # code...
                    break;
                case '9':
                    # code...
                    break;
                case '10':
                    # code...
                    break;

                case '11':
                    # code...
                    break;
                case '12':
                    # code...
                    break;

                case '13':
                    # code...
                    break;
                default:
                    # code...
                    break;
            }
  
        }

    }


}