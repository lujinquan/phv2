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

namespace app\order\admin;
use app\system\admin\Admin;
use app\system\model\SystemUser as UserModel;
use app\order\model\OpOrder as OpOrderModel;

/**
 * 已受理工单，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Filished extends Admin 
{
    
    public function index() 
    {
        if ($this->request->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $OpOrderModel = new OpOrderModel;
            $where = $OpOrderModel->checkWhere($getData, 'filished');
            $data = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('ctime desc')->limit($limit)->select()->toArray();
            $i = 1;
            $j = 1000;
            $p = 10000;
            foreach ($temps as $k => & $v) {
                if(ADMIN_ROLE == 9 && $v['role_id'] == 9){
                    unset($temps[$k]);
                }
                if (strpos($v['duid'], ',') === false) {
                    $v['status_info'] = '待运营中心处理';
                    $v['order_sort'] = $j;
                    $j++;
                } else {
                    $uids = explode(',', $v['duid']);
                    $current_uid = array_pop($uids);
                    if ($current_uid == ADMIN_ID) { //保证是待受理的工单
                        unset($temps[$k]);
                    } else {
                        $current_nick = UserModel::where([['id', 'eq', $current_uid]])->value('nick');
                        if ($v['ftime']) { // 如果方管员已确认表示已完结
                            $v['status_info'] = '已完结';
                            $v['order_sort'] = $p;
                            $p++;
                        } else {
                            $v['status_info'] = '转交至' . $current_nick;
                            $v['order_sort'] = $i;
                            $i++;
                        }
                    }
                }
            }

            sort($temps);

            //二维数组冒泡排序
            $a = [];
            foreach($temps as $key=>$val){
                $a[] = $val['order_sort'];//$a是$sort的其中一个字段
            }
            $temps = bubble_sort($temps,$a,'asc');//正序
            //halt($temps);
            $data['data'] = array_slice($temps , ($page - 1) * $limit, $limit);
            $data['count'] = $OpOrderModel->where($where)->count('id');
            $data['code'] = 0;
            $data['msg'] = '';
            //halt($data);
            return json($data);
        }
        return $this->fetch();
    }
    public function detail() 
    {
        $id = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);
        $duid = explode(',', $row['duid']);
        $current_uid = array_pop($duid);
        // 如果是当前用户处理，或者是运营中心的人，就打开回复框
        if (ADMIN_ID == $current_uid || (ADMIN_ROLE == 11 && !$duid)) {
            $row['is_current'] = true;
        } else {
            $row['is_current'] = false;
        }
        // 工单状态
        $current_nick = UserModel::where([['id', 'eq', $current_uid]])->value('nick');
        //halt($row);
        $row['jsondata'] = json_decode($row['jsondata'], true);
        $temp = $row['jsondata'];
        if ($temp) {
            foreach ($temp as & $v) {
                if ($v['Img']) {
                    $v['Img'] = explode(',', $v['Img']);
                }
            }
        }
        $row['jsondata'] = $temp;
        //halt($temp);
        if ($row['dtime'] && !$row['ftime']) {
            $row['status_info'] = '待确认';
        } else if (!$row['dtime']) {
            $row['status_info'] = '处理中';
        } else {
            $row['status_info'] = '已完结';
        }
        //halt($row);
        $this->assign('data_info', $row);
        return $this->fetch();
    }

}