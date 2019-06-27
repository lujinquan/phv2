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
use app\order\model\OpOrder as OpOrderModel;
use app\system\admin\Admin;
use app\system\model\SystemAffiche;
use app\system\model\SystemUser as UserModel;

/**
 * 待受理工单，权限限开放给【运营中心 + 技术部 + 经管科】
 */
class Accept extends Admin 
{
    /**
     * 待受理工单列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index() 
    {
        // $OpOrderModel = new OpOrderModel;
        // halt($OpOrderModel->getAcceptCount());
        if ($this->request->isAjax()) {
            $page         = input('param.page/d', 1);
            $limit        = input('param.limit/d', 10);
            $getData      = $this->request->get();
            $OpOrderModel = new OpOrderModel;
            $where        = $OpOrderModel->checkWhere($getData,'accept');
            $data  = [];
            $temps = $OpOrderModel->with('SystemUser')->where($where)->page($page)->order('ctime desc')->select(); //halt($temps);
            $inst_ids = explode(',',session('admin_user.inst_ids'));
            foreach ($temps as $k => &$v) {
                if (strpos($v['duid'], ',') === false) {
                    if (!in_array($v['inst_id'],$inst_ids)) {
                        unset($temps[$k]);
                    } else {
                        $v['status_info'] = '待处理';
                    }
                    
                } else {
                    $uids = explode(',', $v['duid']);

                    $current_uid = array_pop($uids);
                    if ($current_uid != ADMIN_ID) { //保证是待受理的工单
                        unset($temps[$k]);
                    } else {
                        $current_nick     = UserModel::where([['id', 'eq', $current_uid]])->value('nick');
                        $v['status_info'] = '转交至'. $current_nick;
                    }
                }
            }
            $data['data']  = array_slice($temps->toArray(), ($page- 1) * $limit, $limit);
            $data['count'] = $OpOrderModel->where($where)->count('id');
            $data['code']  = 0;
            $data['msg']   = '';
            //halt($data);
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 待受理工单列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function add() 
    {
        // 【待解决问题，模糊查找whereOr如何实现】
        //$userRow = UserModel::whereOr([['role_id','eq',11],['inst_ids','like','%8,%']])->whereOr([['role_id','eq',11],['inst_ids','like','%,8%']])->select();
        //$userRow = Db::query("select * from system_user where role_id = 11 and ((inst_ids like %,8%) or (inst_ids like %8,%))");
        //halt($userRow);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //halt($data);
            // 数据验证
            $result = $this->validate($data, 'OpOrder.sceneForm');
            if ($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            // 数据过滤
            $filData = $OporderModel->dataFilter($data);
            $row     = $OporderModel->allowField(true)->create($filData);
            if (!$row) {
                return $this->error('提交失败');
            }

            // 【待解决问题，成功跳转后，菜单的高亮没有正确呈现】
            //return $this->success('提交成功',url('Myorder/index'));

            $userRow                     = UserModel::where([['role_id', 'eq', 11], ['inst_ids', 'like', '%' . $row['inst_id'] . ',%']])->find();
            $systemAffiche               = new SystemAffiche;
            $systemAffiche->title        = '来自【' . session('admin_user.nick') . '】的工单待受理！';
            $systemAffiche->content      = '您有一条来自【'. session('admin_user.nick') . '】的工单待受理！工单编号：' . $filData['op_order_number'] . '。请您尽快处理！';
            $systemAffiche->from_user_id = '*';
            $systemAffiche->to_user_id   = '|' . $userRow['id'] . '|';
            $systemAffiche->create_time  = time();
            $systemAffiche->save();

            return $this->success('提交成功');
        }
        return $this->fetch();
    }

    // 待受理的详情
    public function detail() 
    {
        $id  = input('param.id/d');
        $row = OpOrderModel::with(['SystemUser'])->get($id);

        // 缺少一个判断，需要判断当前工单是否为当前角色待处理的工单【优化】
        $duid        = explode(',', $row['duid']);
        $current_uid = array_pop($duid);

        $row['jsondata'] = json_decode($row['jsondata'], true);
        $temp            = $row['jsondata'];
        if ($temp) {
            foreach ($temp as &$v) {
                if ($v['Img']) {
                    $v['Img'] = explode(',', $v['Img']);
                }
            }
        }
        $row['jsondata'] = $temp;
        if ($row['dtime'] && !$row['ftime']) {
            $row['status_info'] = '待确认';
        } else if (!$row['dtime']) {
            $row['status_info'] = '处理中';
        } else {
            $row['status_info'] = '已完结';
        }

        $this->assign('data_info', $row);
        return $this->fetch();
    }

    /**
     * 转交工单,完结工单
     * @return [type] [description]
     */
    public function transfer() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 数据验证
            if (isset($data['is_end'])) {
                $result = $this->validate($data,'OpOrder.sceneEnd');
            } else {
                $result = $this->validate($data,'OpOrder.sceneTransfer');
            }

            if ($result !== true) {
                return $this->error($result);
            }
            $OporderModel = new OporderModel();
            // 数据过滤
            if (isset($data['is_end'])) {
                $filData = $OporderModel->dataFilter($data,'complete');
                $msg     = '完成';
            } else {
                $filData = $OporderModel->dataFilter($data,'transfer');
                $msg     = '转交';
            }
            if (!$OporderModel->allowField(true)->update($filData)){
                return $this->error($msg . '失败');
            }
            return $this->success($msg . '成功', url('index'));
        }
    }
}
