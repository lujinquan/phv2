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
use app\deal\model\ChangeInst as ChangeInstModel;
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
            $fields = "a.id,a.change_id,a.change_type,a.change_order_number,from_unixtime(a.ctime, '%Y-%m-%d') as ctime,a.change_desc,a.curr_role,d.ban_address,d.ban_owner_id,d.ban_inst_id";
            $data = [];
            $data['data'] = [];
            $temps = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->page($page)->limit($limit)->order('a.ctime asc')->select();
            foreach($temps as $k => $v){
                if($v['curr_role'] == ADMIN_ROLE){
                    $v['is_process'] = 1;
                    array_unshift($data['data'],$v);
                }else{
                    $v['is_process'] = 0;
                    array_push($data['data'],$v);
                }
            }
            $data['count'] = Db::name('change_process')->alias('a')->join('ban d','a.ban_id = d.ban_id','left')->field($fields)->where($where)->count('a.id');
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
            $res = $PorcessModel->process($change_type,$data); //$data必须包含子表的id
            if (!$res) {
                return $this->error('审批失败');
            }
            return $this->success('审批成功',url('index'));
        }

        switch ($change_type) {
            case '1': // 租金减免
                $ChangeModel = new ChangeCutModel;
                $template = 'change_cut_process';
                break;
            case '2': // 空租
                # code...
                break;
            case '3': //暂停计租
                $ChangeModel = new ChangePauseModel;
                $template = 'change_pause_process';
                break;
            case '4': // 陈欠核销
                $ChangeModel = new ChangeOffsetModel;
                $template = 'change_offset_process';
                break;
            case '5': // 房改
                # code...
                break;
            case '6': // 维修
                # code...
                break;
            case '7': // 新发租
                $ChangeModel = new ChangeNewModel;
                $template = 'change_new_process';
                break;
            case '8': //注销
                $ChangeModel = new ChangeCancelModel;
                $template = 'change_cancel_process';
                break;
            case '9': // 房屋调整
                $ChangeModel = new ChangeHouseModel;
                $template = 'change_house_process';
                break;
            case '10': // 管段调整
                $ChangeModel = new ChangeInstModel;
                $template = 'change_inst_process';
                break;
            case '11': // 租金追加调整
                $ChangeModel = new ChangeRentAddModel;
                $template = 'change_rentadd_process';
                break;
            case '12': //租金调整
                # code...
                break;
            case '13': //使用权变更
                $ChangeModel = new ChangeUseModel;
                $template = 'change_use_process';
                break;
            case '14': // 楼栋调整
                $ChangeModel = new ChangeBanModel;
                $template = 'change_ban_process';
                break;
            case '16': // 租金减免年审
                $ChangeModel = new ChangeCutYearModel;
                $template = 'change_cut_year_process';
                break;
            case '17': // 别字更正
                $ChangeModel = new ChangeNameModel;
                $template = 'change_name_process';
                break;
            case '18': // 租约管理
                $ChangeModel = new ChangeLeaseModel;
                $template = 'change_lease_process';
                break;
            default:
                # code...
                break;
        }
        $row = $ChangeModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch($template);

    }

    /**
     * 租约打印
     * @return [type] [description]
     */
    public function printout()
    {

        if ($this->request->isAjax()) {
            
        }

        $id = $this->request->param('id');
        $ChangeLeaseModel = new ChangeLeaseModel;
        $findRow = $ChangeLeaseModel->get($id);
        if($findRow['qrcode']){   
            @unlink($_SERVER['DOCUMENT_ROOT'].$findRow['qrcode']); //删除过期的二维码
        }
        $qrcodeUrl = $ChangeLeaseModel->makeQrcode(); //生成新的二维码
        Db::name('system_config')->where('name','szno')->setInc('value',1); //更新计数器
        $findRow->update(['id'=>$id,'last_print_time'=>time(),'print_times'=>Db::raw('print_times+1'),'qrcode'=>$qrcodeUrl]);
  
        $row = $ChangeLeaseModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch('changelease/printout');
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
                $ChangeModel = new ChangeCutModel;
                $template = 'Changecut/detail_x';              
                break;
            case '2': // 空租
                # code...
                break;
            case '3': //暂停计租
                $ChangeModel = new ChangePauseModel;
                $template = 'Changepause/detail'; 
                break;
            case '4': // 陈欠核销
                $ChangeModel = new ChangeOffsetModel;
                $template = 'Changeoffset/detail';
                break;
            case '5': // 房改
                # code...
                break;
            case '6': // 维修
                # code...
                break;
            case '7': // 新发租
                $ChangeModel = new ChangeNewModel;
                $template = 'Changenew/detail';
                break;
            case '8': //注销
                $ChangeModel = new ChangeCancelModel;
                $template = 'Changecancel/detail';
                break;
            case '9': // 房屋调整
                $ChangeModel = new ChangeHouseModel;
                $template = 'Changehouse/detail';
                break;
            case '10': // 管段调整
                $ChangeModel = new ChangeInstModel;
                $template = 'Changeinst/detail';
                break;
            case '11': // 租金追加调整
                $ChangeModel = new ChangeRentAddModel;
                $template = 'Changerentadd/detail';
                break;
            case '12': //租金调整
                # code...
                break;
            case '13': //使用权变更
                $ChangeModel = new ChangeUseModel;
                $template = 'Changeuse/detail';
                break;
            case '14': //楼栋调整
                $ChangeModel = new ChangeBanModel;
                $template = 'Changeban/detail';
                break;
            case '16': // 租金减免年审
                $ChangeModel = new ChangeCutYearModel;
                $template = 'Changecutyear/detail';
                break;
            case '17': // 别字更正
                $ChangeModel = new ChangeNameModel;
                $template = 'Changename/detail';
                break;
            case '18': // 租约管理
                $ChangeModel = new ChangeLeaseModel;
                $template = 'Changelease/detail';
                break;
            default:
                # code...
                break;
        }
        $row = $ChangeModel->detail($id);
        $this->assign('data_info',$row);
        return $this->fetch($template);
    }

}