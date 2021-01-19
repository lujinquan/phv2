<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\common\model\Cparam as ParamModel;
use app\house\model\Tenant as TenantModel;
use app\house\model\HouseTai as HouseTaiModel;
use app\house\model\TenantTai as TenantTaiModel;
use app\deal\model\ChangeRecord as ChangeRecordModel;
include EXTEND_PATH.'phpqrcode/phpqrcode.php';

class ChangeLease extends SystemBase
{
    // 设置模型名称
    protected $name = 'change_lease';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'etime';

    protected $type = [
        'last_print_time' => 'timestamp:Y-m-d H:i:s',
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'etime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
        'data_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回给房管员','初审通过','审批通过','审批通过','发证','提交签字'];

    protected $processDesc = ['失败','成功','打回给房管员','待资料员初审','待经管所长审批','待经管科长审批','待经租会计发证','待房管员提交签字'];

    protected $processRole = ['2'=>4,'3'=>5,'4'=>8,'5'=>9,'6'=>6,'7'=>4];

    public function checkWhere($data,$type)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
        $where[] = ['a.dtime','eq',0];
        switch ($type) {
            // 申请列表
            case 'apply':
                $where[] = ['a.change_status','>',1];
                break;
            // 记录列表
            case 'record':
                $where[] = ['a.change_status','<',2];
                break;

            default:
                # code...
                break;
        }
        // 检索租约编号
        if(isset($data['szno']) && $data['szno']){
            $where[] = ['a.szno','like','%'.$data['szno'].'%'];
        }
        // 检索异动单号
        if(isset($data['change_order_number']) && $data['change_order_number']){
            $where[] = ['a.change_order_number','like','%'.$data['change_order_number'].'%'];
        }
        // 检索房屋编号
        if(isset($data['house_number']) && $data['house_number']){
            $where[] = ['b.house_number','like','%'.$data['house_number'].'%'];
        }
        // 检索审核状态
        if(isset($data['change_status']) && $data['change_status'] !== ''){
            $where[] = ['a.change_status','eq',$data['change_status']];
        }
        // 检索是否有效
        if(isset($data['is_valid']) && $data['is_valid'] !== ''){
            $where[] = ['a.is_valid','eq',$data['is_valid']];
        }
        // 检索租户姓名
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['a.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索租户手机号
        if(isset($data['tenant_tel']) && $data['tenant_tel']){
            $where[] = ['c.tenant_tel','like','%'.$data['tenant_tel'].'%'];
        }
        // 检索租户身份证号
        if(isset($data['tenant_card']) && $data['tenant_card']){
            $where[] = ['c.tenant_card','like','%'.$data['tenant_card'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','in',explode(',',$data['ban_owner_id'])];
        }
        // 检索使用性质
        if(isset($data['house_use_id']) && $data['house_use_id']){
            $where[] = ['b.house_use_id','in',explode(',',$data['house_use_id'])];
        }
        // 检索申请时间(按月搜索)
        if(isset($data['ctime']) && $data['ctime']){
            $endTime = date('Y-m',strtotime('+1 month',strtotime($data['ctime'])));
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ctime','between time',[$data['ctime'],$endTime]];
        }
        // 检索申请时间(按月搜索)
        if(isset($data['ftime']) && $data['ftime']){
            $endFtime = date('Y-m',strtotime('+1 month',strtotime($data['ftime'])));
            //$where[] = ['a.ctime','BETWEEN TIME',['2019-09-01','2019-09-21']];
            $where[] = ['a.ftime','between time',[$data['ftime'],$endFtime]];
        }
        // 检索生效时间(按月搜索)
        if(isset($data['effecttime']) && $data['effecttime']){ 
            $where[] = ['a.entry_date','eq',$data['effecttime']];
        }
        // 检索楼栋机构
        // $insts = config('inst_ids');
        // if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
        //     $where[] = ['d.ban_inst_id','in',$insts[$data['ban_inst_id']]];
        // }else{
        //     $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
        //     $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        // }
        // 检索机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $insts = explode(',',$data['ban_inst_id']);
            $instid_arr = [];
            foreach ($insts as $inst) {
                foreach (config('inst_ids')[$inst] as $instid) {
                    $instid_arr[] = $instid;
                }
            }
            $where[] = ['d.ban_inst_id','in',array_unique($instid_arr)];
        }else{
            $where[] = ['d.ban_inst_id','in',config('inst_ids')[INST]];
        }
        return $where;
    }

    //不需要调试模式，ob_end_clean() 不能去掉否则乱码
    public function makeQrcode()
    {
        ob_end_clean();

        $code = substr(md5(substr(uniqid(),-6)),6).substr(uniqid(),-6);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        //dump($http_type);dump($_SERVER['HTTP_HOST']);halt($_SERVER['SERVER_NAME']);
        $value = $http_type.$_SERVER['HTTP_HOST'].'/erweima/'.$code;          //二维码内容
        //halt($value);
        $errorCorrectionLevel = 'L';    //容错级别 
        $matrixPointSize = 6;           //生成图片大小
        $url = '/upload/qrcode/'.$code.'.png';
        $filename = $_SERVER['DOCUMENT_ROOT'].$url;

        $qrcode = new \QRcode;

        $qrcode::png($value,$filename,$errorCorrectionLevel, $matrixPointSize, 2);

        return $url;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data,$flag = 'add')
    {

        if(($flag === 'add' && isset($data['file']) && $data['file']) || ($flag === 'edit' && isset($data['file']))){
            $data['change_imgs'] = trim(implode(',',$data['file']),',');
        }
        if($flag === 'edit' && !isset($data['file'])){
            $data['change_imgs'] = '';
        }
        if(isset($data['id'])){
            $row = $this->get($data['id']); 
            if($row['is_back']){ //如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }

        // 重组applyText_other数据
        $change_remark = self::where([['house_id','eq',$data['house_id']],['change_status','eq',1]])->order('ctime desc')->value('change_remark');
        if(!$change_remark){
            $change_remark = '';
        }

        // if($data['applyType'] < 7){
        //     $data['applyType'] = 
        // }
        if(!isset($data['applyReason'])){
            $data['applyReason'] = '';
        }
        $data['change_remark'] = date('Y年m月d日'). ' '.$data['applyType'].' '.$data['applyReason'].';'.$change_remark;
            
//halt(date('Y年m月d日'). ' '.$data['applyType'].';'.$change_remark);
        $applyColumns = config('apply_columns');
        foreach($applyColumns as $c){
            // if($c == 'applyText_other'){
            //     $data['data_json']['applyText_other'] = date('Y年m月d日'). ' '.$data['applyType'].';'.$change_remark;
            // }else{
                $data['data_json'][$c] = $data[$c];
            //}
            
            unset($data[$c]);
        }
        $data['data_json']['applyText_other'] = $data['change_remark'];

        if($data['save_type'] == 'save'){ //保存
            $data['change_status'] = 2;
        }else{ //保存并提交
            $data['change_status'] = 3;
            $data['child_json'][] = [
                'step' => 1,
                'action' => '提交申请',
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];
        }
        $data['cuid'] = ADMIN_ID;
        $data['change_type'] = 18; //租约管理
        if($flag === 'add'){
            $data['change_order_number'] = date('Ym').'18'.random(14);   
        }

        // 审批表数据
        $processRoles = $this->processRole;
        $processDescs = $this->processDesc;
        $data['change_desc'] = $processDescs[3];
        $data['curr_role'] = $processRoles[3];

        // 如果是自动生成租约的，直接跳过中间环节，直接到会计审批
        if($data['save_type'] === 'submit' && isset($data['id'])){
            $process_id = self::where([['id','eq',$data['id']]])->value('process_id');
            //halt($process_id);
            // 如果是自动生成租约的，直接跳过中间环节，直接到会计审批
            if($process_id == 2){ 
                $data['change_status'] = 6;   
                $data['change_desc'] = $processDescs[6];
                $data['curr_role'] = $processRoles[6];
            }

        }
        
        return $data; 
    }

    /**
     * 某些异动完成后，自动生成租约的功能
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-08-25 11:02:33
     * @return  返回值  
     * @version 版本  1.0
     */
    public function auto_create_changelease($data = [])
    {
        //halt($data);
        $form = [];
        $form['change_order_number'] = date('Ym').'18'.random(14);
        $form['change_type'] = 18;
        $form['ban_id'] = $data['ban_id'];
        $form['house_id'] = $data['house_id'];
        $form['tenant_id'] = $data['tenant_id'];
        $form['cuid'] = $data['cuid'];
        $form['change_imgs'] = $data['change_imgs'];
        $form['tenant_name'] = $data['tenant_name'];
        $form['change_status'] = 2;
        $form['extra'] = $data['extra'];
        $form['process_id'] = 2; //代表是自动生成租约的

        $val = Db::name('system_config')->where([['name','eq','szno']])->value('value');

        //$result['house']['house_szno'] = $result['house']['house_szno'].$val;

        $form['szno'] = $data['szno'].$val;
        $this->save($form);
    }

    public function detail($id,$change_order_number = '')
    {
        $changeWhere = [];
        if($id){
            $row = self::get($id)->toArray();
        }else{
            $row = self::where([['change_order_number','eq',$change_order_number]])->find()->toArray(); 
        }

        if($row['change_status'] == 6){
            $row['change_remark'] = preg_replace("/\(.*\)/", '', $row['change_remark']);
        }

        // if($row['change_status'] > 1){
        //     $row['change_remark'] = self::where([['house_id','eq',$row['house_id']],['change_status','eq',1],['id','<',$id]])->order('ctime desc')->value('change_remark');
        // }
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['ban_info'] = BanModel::get($row['ban_id']);
        $row['house_info'] = HouseModel::get($row['house_id']);
        $row['tenant_info'] = TenantModel::get($row['tenant_id']);
        // halt($row['ban_info']['ban_inst_pid']);
        if($row['ban_info']['ban_inst_pid'] == 2){
            $row['ziliaoyuan'] = '段芸菲';
            $row['jingguansuozhang'] = '冯霞';
            $row['jingguankezhang'] = '魏斌成';
        }
        if($row['ban_info']['ban_inst_pid'] == 3){
            $row['ziliaoyuan'] = '张润';
            $row['jingguansuozhang'] = '袁玲';
            $row['jingguankezhang'] = '魏斌成';
        }
        return $row;
    }

    public function process($data)
    {
        // 判断是否通过
        $changeRow = self::get($data['id']);

        // 获取最后一步的step
        $processRoles = $this->processRole;
        $steps = array_keys($processRoles);
        $finalStep = array_pop($steps);
        // 获取审批动作
        $processActions = $this->processAction;
        // 获取审批描述
        $processDescs = $this->processDesc;
        $params = ParamModel::getCparams();
        $changeUpdateData = $processUpdateData = [];

        /*  如果是打回  */
        if($data['flag'] === 'back'){
            if($data['back_reason']){
                $backReason = $data['back_reason'];
            }else{
                $backReason = $params['back_reason_type'][$data['back_reason_type']];
            }
            $changeUpdateData['change_status'] = 2;
            $changeUpdateData['is_back'] = 1;
            $changeUpdateData['child_json'] = $changeRow['child_json'];
            $changeUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[2].'，原因：'.$backReason,
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];

            // 更新使用权变更表
            $changeRow->allowField(['child_json','is_back','change_status'])->save($changeUpdateData, ['id' => $data['id']]);;
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
            $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];
        }else{
            // dump($changeRow['change_status']);halt($finalStep);
            /* 如果审批通过，且非终审：更新使用权变更表的child_json、change_status，更新审批表change_desc、curr_role */
            //if(!isset($data['change_reason']) && ($changeRow['change_status'] < $finalStep)){
            if(($data['flag'] === 'passed') && ($changeRow['change_status'] < $finalStep)){
                //dump($changeRow['change_status']);halt($finalStep);
                $changeUpdateData['change_status'] = $changeRow['change_status'] + 1;
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeRow['change_status']], //用没递增的状态id来记录
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                if(isset($data['file']) && $data['file']){
                    $changeUpdateData['change_imgs'] = implode(',',$data['file']);
                }
                // 更新使用权变更表
                $changeRow->allowField(['child_json','change_imgs','change_status'])->save($changeUpdateData, ['id' => $data['id']]);
                //halt($data);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新暂停计租表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            //}else if(!isset($data['change_reason']) && ($changeRow['change_status'] == $finalStep)){
            }else if(($data['flag'] === 'passed') && ($changeRow['change_status'] == $finalStep)){
                $changeUpdateData['change_status'] = 1;
                $changeUpdateData['is_valid'] = 1;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['entry_date'] = date('Y-m');
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeRow['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                if(isset($data['file']) && $data['file']){
                    $files = implode(',',$data['file']);
                    if($changeRow['change_imgs']){
                        $changeUpdateData['change_imgs'] = $changeRow['change_imgs'].','.$files;
                    }else{
                        $changeUpdateData['change_imgs'] = $files;
                    }
                   
                }
                //终审成功后的数据处理
                $this->finalDeal($changeRow);
                //try{$this->finalDeal($changeRow);}catch(\Exception $e){return false;}
                // 更新暂停计租表
                $changeRow->allowField(['child_json','change_status','entry_date','is_valid','change_imgs','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新暂停计租的child_json、change_status，更新审批表change_desc、curr_role */
            //}else if (isset($data['change_reason'])){
            }else if ($data['flag'] === 'change'){
                if($data['change_reason']){
                    $changeReason = $data['change_reason'];
                }else{
                    $changeReason = $params['change_reason_type'][$data['change_reason_type']];
                }
                $changeUpdateData['change_status'] = 0;
                $changeUpdateData['ftime'] = time();
                $changeUpdateData['child_json'] = $changeRow['child_json'];
                $changeUpdateData['child_json'][] = [
                    'success' => 0,
                    'action' => $processActions[$changeUpdateData['change_status']].'，原因：'.$changeReason,
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
                $changeRow->allowField(['child_json','change_status','ftime'])->save($changeUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeUpdateData['ftime'];
                $processUpdateData['status'] = 0;                
            }

        }

        return $processUpdateData;
    }

    /**
     * 终审审核成功后的数据处理
     * @return [type] [description]
     */
    private function finalDeal($finalRow)
    {
        // 异动记录
        $ChangeRecordModel = new ChangeRecordModel;
        $ChangeRecordModel->save([
            'change_type' => 18,
            'change_order_number' => $finalRow['change_order_number'],
            'ban_id' => $finalRow['ban_id'],
            'ctime' => $finalRow->getData('ctime'),
            'ftime' => time(),
            'change_status' => 1,
        ]);

        // 1、添加房屋台账
        $taiHouseData = [];
        $taiHouseData['house_id'] = $finalRow['house_id'];
        $taiHouseData['tenant_id'] = $finalRow['tenant_id'];
        $taiHouseData['house_tai_type'] = 13;
        $taiHouseData['cuid'] = $finalRow['cuid'];
        $taiHouseData['house_tai_remark'] = '租约管理异动单号：'.$finalRow['change_order_number'];
        $taiHouseData['data_json'] = [];
        $taiHouseData['change_type'] = 18;
        $taiHouseData['change_id'] = $finalRow['id'];
        $HouseTaiModel = new HouseTaiModel;
        $HouseTaiModel->allowField(true)->create($taiHouseData);

        // 2、添加租户台账
        $taiData = [];
        $taiData['tenant_id'] = $finalRow['tenant_id'];
        $taiData['cuid'] = $finalRow['cuid'];
        $taiData['tenant_tai_type'] = 3;
        $taiData['tenant_tai_remark'] = '租约管理异动单号：'.$finalRow['change_order_number'];
        $taiData['data_json'] = [];
        $taiData['change_type'] = 18;
        $taiData['change_id'] = $finalRow['id'];

        $TenantTaiModel = new TenantTaiModel;
        $TenantTaiModel->allowField(true)->create($taiData);
    }
}