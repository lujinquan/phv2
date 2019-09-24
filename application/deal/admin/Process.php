<?php

namespace app\deal\admin;

use think\Db;
use app\system\admin\Admin;
use app\deal\model\Process as ProcessModel;
use app\deal\model\ChangeBan as ChangeBanModel;
use app\deal\model\ChangeCancel as ChangeCancelModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;
use app\deal\model\ChangeName as ChangeNameModel;
use app\deal\model\ChangeNew as ChangeNewModel;
use app\deal\model\ChangeOffset as ChangeOffsetModel;
use app\deal\model\ChangePause as ChangePauseModel;
use app\deal\model\ChangeRentAdd as ChangeRentAddModel;
use app\deal\model\ChangeUse as ChangeUseModel;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\deal\model\ChangeCutYear as ChangeCutYearModel;

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
            $where = $ProcessModel->checkWhere($getData);
            //halt($where);
            $fields = "a.id,a.change_id,a.change_type,a.change_order_number,from_unixtime(a.ctime, '%Y-%m-%d %H:%i:%S') as ctime,a.change_desc,a.curr_role,d.ban_address,c.nick,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = [];
            $temps = Db::name('change_process')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.ctime asc')->select();
            foreach($temps as $k => $v){
                if($v['curr_role'] == ADMIN_ROLE){
                    $v['is_process'] = 1;
                    array_unshift($data['data'],$v);
                }else{
                    $v['is_process'] = 0;
                    array_push($data['data'],$v);
                }
            }
           
            //dump($where);halt($data['data']);
            $data['count'] = Db::name('change_process')->alias('a')->join('system_user c','a.cuid = c.id','left')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->count('a.id');
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
        // 显示对应的审批页面
        $id = input('param.id/d');
        $change_type = input('param.change_type/d');
        
        if(!$change_type || !$id){
            return $this->error('参数错误！');
        }

        //检查当前页面或当前表单，是否允许被请求？
        

        // 提交审批表单
        if($this->request->isPost()) {
            $data = $this->request->post();
            $PorcessModel = new ProcessModel;
            //halt($data);
            $res = $PorcessModel->process($change_type,$data);
            if (!$res) {
                return $this->error('审批失败');
            }
            return $this->success('审批成功',url('index'));
        }


        switch ($change_type) {
            case '1': // 租金减免
                $ChangeCutModel = new ChangeCutModel;
                $row = $ChangeCutModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_cut_process');
                break;
            case '2': // 空租
                # code...
                break;
            case '3': //暂停计租
                $ChangePauseModel = new ChangePauseModel;
                $row = $ChangePauseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_pause_process');
                break;
            case '4': // 陈欠核销
                $ChangeOffsetModel = new ChangeOffsetModel;
                $row = $ChangeOffsetModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_offset_process');
                break;
            case '5': // 房改
                # code...
                break;

            case '6': // 维修
                # code...
                break;
            case '7': // 新发租
                $ChangeNewModel = new ChangeNewModel;
                $row = $ChangeNewModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_new_process');
                break;

            case '8': //注销
                $ChangeCancelModel = new ChangeCancelModel;
                $row = $ChangeCancelModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_cancel_process');
                break;
            case '9': // 房屋调整
                $ChangeHouseModel = new ChangeHouseModel;
                $row = $ChangeHouseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_house_process');
                break;
            case '10': // 管段调整
                
                break;

            case '11': // 租金追加调整
                $ChangeRentAddModel = new ChangeRentAddModel;
                $row = $ChangeRentAddModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_rentadd_process');
                break;
            case '12': //租金调整
                # code...
                break;

            case '13': //使用权变更
                $ChangeUseModel = new ChangeUseModel;
                $row = $ChangeUseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_use_process');
                break;
            case '14': // 楼栋调整
                $ChangeBanModel = new ChangeBanModel;
                $row = $ChangeBanModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_ban_process');
                break;
            case '16': // 租金减免年审
                $ChangeCutYearModel = new ChangeCutYearModel;
                $row = $ChangeCutYearModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_cut_year_process');
                break;
            case '17': // 别字更正
                $ChangeNameModel = new ChangeNameModel;
                $row = $ChangeNameModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('change_name_process');
                break;
            default:
                # code...
                break;
        }
    }

    public function detail()
    {
        // 显示对应的审批页面
        $id = input('param.id/d');
        $change_type = input('param.change_type/d');
        
        if(!$change_type || !$id){
            return $this->error('参数错误！');
        }

        switch ($change_type) {
            case '1': // 租金减免
                $ChangeCutModel = new ChangeCutModel;
                $row = $ChangeCutModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changecut/detail');
                break;
            case '2': // 空租
                # code...
                break;
            case '3': //暂停计租
                $ChangePauseModel = new ChangePauseModel;
                $row = $ChangePauseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changepause/detail');
                break;
            case '4': // 陈欠核销
                $ChangeOffsetModel = new ChangeOffsetModel;
                $row = $ChangeOffsetModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changeoffset/detail');
                break;
            case '5': // 房改
                # code...
                break;

            case '6': // 维修
                # code...
                break;
            case '7': // 新发租
                $ChangeNewModel = new ChangeNewModel;
                $row = $ChangeNewModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changenew/detail');
                break;

            case '8': //注销
                $ChangeCancelModel = new ChangeCancelModel;
                $row = $ChangeCancelModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changecancel/detail');
                break;
            case '9': // 房屋调整
                $ChangeHouseModel = new ChangeHouseModel;
                $row = $ChangeHouseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changehouse/detail');
                break;
            case '10': // 管段调整
                # code...
                break;

            case '11': // 租金追加调整
                $ChangeRentAddModel = new ChangeRentAddModel;
                $row = $ChangeRentAddModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changerentadd/detail');
                break;
            case '12': //租金调整
                # code...
                break;

            case '13': //使用权变更
                $ChangeUseModel = new ChangeUseModel;
                $row = $ChangeUseModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changeuse/detail');
                break;
            case '14': //楼栋调整
                $ChangeBanModel = new ChangeBanModel;
                $row = $ChangeBanModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changeuse/detail');
                break;
            case '16': // 租金减免年审
                $ChangeCutYearModel = new ChangeCutYearModel;
                $row = $ChangeNameModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changecutyear/detail');
                break;
            case '17': // 别字更正
                $ChangeNameModel = new ChangeNameModel;
                $row = $ChangeNameModel->detail($id);
                $this->assign('data_info',$row);
                return $this->fetch('Changename/detail');
                break;
            default:
                # code...
                break;
        }
    }

}