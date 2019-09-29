<?php

namespace app\deal\model;

use think\Db;
use app\system\model\SystemBase;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\house\model\Ban as BanModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
include EXTEND_PATH.'phpqrcode/phpqrcode.php';

class ChangeLease extends SystemBase
{
    // 设置模型名称
    protected $name = 'change_lease';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $type = [
        'last_print_time' => 'timestamp:Y-m-d H:i:s',
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'child_json' => 'json',
        'data_json' => 'json',
    ];

    protected $processAction = ['审批不通过','审批成功','打回','初审通过','审批通过','审批通过','发证','提交签字'];

    protected $processDesc = ['失败','成功','打回','待资料员补充初审','待经管所长审批','待经管科长审批','待经租会计发证','待房管员提交签字'];

    protected $processRole = ['2'=>4,'3'=>5,'4'=>8,'5'=>9,'6'=>6,'7'=>4];

    public function checkWhere($data,$type)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
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
        // 检索原租户
        if(isset($data['tenant_name']) && $data['tenant_name']){
            $where[] = ['a.tenant_name','like','%'.$data['tenant_name'].'%'];
        }
        // 检索楼栋地址
        if(isset($data['ban_address']) && $data['ban_address']){
            $where[] = ['d.ban_address','like','%'.$data['ban_address'].'%'];
        }
        // 检索楼栋产别
        if(isset($data['ban_owner_id']) && $data['ban_owner_id']){
            $where[] = ['d.ban_owner_id','eq',$data['ban_owner_id']];
        }
        // 检索申请时间(按天搜索)
        if(isset($data['ctime']) && $data['ctime']){
            $startTime = strtotime($data['ctime']);
            $where[] = ['a.ctime','between time',[$startTime,$startTime+3600*24]];
        }
        // 检索申请时间(按天搜索)
        if(isset($data['ftime']) && $data['ftime']){
            $startFilishTime = strtotime($data['ftime']);
            $where[] = ['a.ftime','between time',[$startFilishTime,$startFilishTime+3600*24]];
        }
        // 检索楼栋机构
        if(isset($data['ban_inst_id']) && $data['ban_inst_id']){
            $where[] = ['d.ban_inst_id','eq',$data['ban_inst_id']];
        }else{
            //检索管段
            $insts = config('inst_ids');
            $instid = (isset($data['ban_inst_id']) && $data['ban_inst_id'])?$data['ban_inst_id']:INST;
            $where[] = ['d.ban_inst_id','in',$insts[$instid]];
        }
        
        return $where;
    }

    //不需要调试模式，ob_end_clean() 不能去掉否则乱码
    public function makeQrcode()
    {
        ob_end_clean();

        $code = substr(md5(substr(uniqid(),-6)),6).substr(uniqid(),-6);

        $value = 'https://www.mylucas.com.cn';          //二维码内容
        $errorCorrectionLevel = 'L';    //容错级别 
        $matrixPointSize = 6;           //生成图片大小
        $url = '/upload/qrcode/'.$code.'.png';
        $filename = $_SERVER['DOCUMENT_ROOT'].$url;

        $qrcode = new \QRcode;

        $qrcodeUrl = $qrcode::png($value,$filename,$errorCorrectionLevel, $matrixPointSize, 2);

        return $url;
    }

    /**
     * 数据过滤
     * @param  [type] $data [传入数据]
     * @return [type]
     */
    public function dataFilter($data)
    {

        if(isset($data['file']) && $data['file']){
            $data['change_imgs'] = implode(',',$data['file']);
        }
        if(isset($data['id'])){
            $row = $this->get($data['id']); 
            if($row['is_back']){ //如果打回过
                $data['child_json'] = $row['child_json'];
            }
            
        }
        $data['data_json'] = [
            'applyAddress' => $data['applyAddress'],
            'applyStruct' => $data['applyStruct'],
            'applyHouseFloor' => $data['applyHouseFloor'],
            'applyLiveFloor' => $data['applyLiveFloor'],
            'applyRentName' => $data['applyRentName'],
            'applyRentNumber' => $data['applyRentNumber'],
            'applyRentTel' => $data['applyRentTel'],
            'applyRentName1' => $data['applyRentName1'],
            'applyRentNumber1' => $data['applyRentNumber1'],
            'applyRepresent' => $data['applyRepresent'],
            'applyYear' => $data['applyYear'],
            'applyMonth' => $data['applyMonth'],
            'applyDay' => $data['applyDay'],
            'applyRoom1_data1' => $data['applyRoom1_data1'],
            'applyRoom1_data2' => $data['applyRoom1_data2'],
            'applyRoom1_data3' => $data['applyRoom1_data3'],
            'applyRoom1_data4' => $data['applyRoom1_data4'],
            'applyRoom1_data5' => $data['applyRoom1_data5'],
            'applyRoom1_data6' => $data['applyRoom1_data6'],
            'applyRoom2_data1' => $data['applyRoom2_data1'],
            'applyRoom2_data2' => $data['applyRoom2_data2'],
            'applyRoom2_data3' => $data['applyRoom2_data3'],
            'applyRoom2_data4' => $data['applyRoom2_data4'],
            'applyRoom2_data5' => $data['applyRoom2_data5'],
            'applyRoom2_data6' => $data['applyRoom2_data6'],
            'applyRoom3_data1' => $data['applyRoom3_data1'],
            'applyRoom3_data2' => $data['applyRoom3_data2'],
            'applyRoom3_data3' => $data['applyRoom3_data3'],
            'applyRoom3_data4' => $data['applyRoom3_data4'],
            'applyRoom3_data5' => $data['applyRoom3_data5'],
            'applyRoom3_data6' => $data['applyRoom3_data6'],
            'applyRoom4_data1' => $data['applyRoom4_data1'],
            'applyRoom4_data2' => $data['applyRoom4_data2'],
            'applyRoom4_data3' => $data['applyRoom4_data3'],
            'applyRoom4_data4' => $data['applyRoom4_data4'],
            'applyRoom4_data5' => $data['applyRoom4_data5'],
            'applyRoom4_data6' => $data['applyRoom4_data6'],
            'applyRoom5_data1' => $data['applyRoom5_data1'],
            'applyRoom5_data2' => $data['applyRoom5_data2'],
            'applyRoom5_data3' => $data['applyRoom5_data3'],
            'applyRoom5_data4' => $data['applyRoom5_data4'],
            'applyRoom5_data5' => $data['applyRoom5_data5'],
            'applyRoom5_data6' => $data['applyRoom5_data6'],
            'applyRoom5_data7' => $data['applyRoom5_data7'],
            'applyRoom6_data1' => $data['applyRoom6_data1'],
            'applyRoom6_data2' => $data['applyRoom6_data2'],
            'applyRoom6_data3' => $data['applyRoom6_data3'],
            'applyRoom6_data4' => $data['applyRoom6_data4'],
            'applyRoom6_data5' => $data['applyRoom6_data5'],
            'applyRoom6_data6' => $data['applyRoom6_data6'],
            'applyRoom6_data7' => $data['applyRoom6_data7'],
            'applyRoom7_data1' => $data['applyRoom7_data1'],
            'applyRoom7_data2' => $data['applyRoom7_data2'],
            'applyRoom7_data3' => $data['applyRoom7_data3'],
            'applyRoom7_data4' => $data['applyRoom7_data4'],
            'applyRoom7_data5' => $data['applyRoom7_data5'],
            'applyRoom7_data8' => $data['applyRoom7_data8'],
            'applyRoom7_data9' => $data['applyRoom7_data9'],
            'applyRoom8_data1' => $data['applyRoom8_data1'],
            'applyRoom8_data2' => $data['applyRoom8_data2'],
            'applyRoom8_data3' => $data['applyRoom8_data3'],
            'applyRoom8_data4' => $data['applyRoom8_data4'],
            'applyRoom8_data5' => $data['applyRoom8_data5'],
            'applyRoom8_data6' => $data['applyRoom8_data6'],
            'applyRoom8_data7' => $data['applyRoom8_data7'],
            'applyRoom8_data8' => $data['applyRoom8_data8'],
            'applyRoom8_data9' => $data['applyRoom8_data9'],
            'applyRoom9_data1' => $data['applyRoom9_data1'],
            'applyRoom9_data2' => $data['applyRoom9_data2'],
            'applyRoom9_data3' => $data['applyRoom9_data3'],
            'applyRoom9_data4' => $data['applyRoom9_data4'],
            'applyRoom9_data5' => $data['applyRoom9_data5'],
            'applyRoom10_data1' => $data['applyRoom10_data1'],
            'applyRoom10_data2' => $data['applyRoom10_data2'],
            'applyRoom10_data3' => $data['applyRoom10_data3'],
            'applyRoom10_data4' => $data['applyRoom10_data4'],
            'applyRoom10_data5' => $data['applyRoom10_data5'],
            'applyRoom11_data1' => $data['applyRoom11_data1'],
            'applyRoom11_data2' => $data['applyRoom11_data2'],
            'applyRoom11_data3' => $data['applyRoom11_data3'],
            'applyRoom11_data4' => $data['applyRoom11_data4'],
            'applyRoom11_data5' => $data['applyRoom11_data5'],
            'applyRoom12_data1' => $data['applyRoom12_data1'],
            'applyRoom12_data2' => $data['applyRoom12_data2'],
            'applyRoom12_data3' => $data['applyRoom12_data3'],
            'applyRoom12_data4' => $data['applyRoom12_data4'],
            'applyRoom12_data5' => $data['applyRoom12_data5'],
            'applyRoom12_data6' => $data['applyRoom12_data6'],
            'applyRoom12_data7' => $data['applyRoom12_data7'],
            'applyRoom12_data8' => $data['applyRoom12_data8'],
            'applyRoom12_data9' => $data['applyRoom12_data9'],
            'applyRoom13_data1' => $data['applyRoom13_data1'],
            'applyRoom13_data2' => $data['applyRoom13_data2'],
            'applyRoom13_data3' => $data['applyRoom13_data3'],
            'applyRoom13_data4' => $data['applyRoom13_data4'],
            'applyRoom13_data5' => $data['applyRoom13_data5'],
            'applyRoom13_data6' => $data['applyRoom13_data6'],
            'applyRoom13_data7' => $data['applyRoom13_data7'],
            'applyRoom13_data8' => $data['applyRoom13_data8'],
            'applyRoom14_data1' => $data['applyRoom14_data1'],
            'applyRoom14_data2' => $data['applyRoom14_data2'],
            'applyRoom14_data3' => $data['applyRoom14_data3'],
            'applyRoom14_data4' => $data['applyRoom14_data4'],
            'applyRoom14_data5' => $data['applyRoom14_data5'],
            'applyRoom14_data6' => $data['applyRoom14_data6'],
            'applyRoom14_data7' => $data['applyRoom14_data7'],
            'applyRoom14_data8' => $data['applyRoom14_data8'],
            'applyRoom15_data1' => $data['applyRoom15_data1'],
            'applyRoom15_data2' => $data['applyRoom15_data2'],
            'applyRoom15_data3' => $data['applyRoom15_data3'],
            'applyRoom15_data4' => $data['applyRoom15_data4'],
            'applyRoom15_data5' => $data['applyRoom15_data5'],
            'applyRoom15_data6' => $data['applyRoom15_data6'],
            'applyRoom15_data7' => $data['applyRoom15_data7'],
            'applyRoom15_data8' => $data['applyRoom15_data8'],
            'applyRoom16_data1' => $data['applyRoom16_data1'],
            'applyRoom16_data2' => $data['applyRoom16_data2'],
            'applyRoom16_data3' => $data['applyRoom16_data3'],
            'applyRoom16_data4' => $data['applyRoom16_data4'],
            'applyRoom16_data5' => $data['applyRoom16_data5'],
            'applyRoom16_data6' => $data['applyRoom16_data6'],
            'applyRoom16_data7' => $data['applyRoom16_data7'],
            'applyRoom16_data8' => $data['applyRoom16_data8'],
            'applyRoom17_data1' => $data['applyRoom17_data1'],
            'applyRoom17_data2' => $data['applyRoom17_data2'],
            'applyRoom17_data3' => $data['applyRoom17_data3'],
            'applyRoom17_data4' => $data['applyRoom17_data4'],
            'applyRoom17_data5' => $data['applyRoom17_data5'],
            'applyRoom18_data1' => $data['applyRoom18_data1'],
            'applyRoom18_data2' => $data['applyRoom18_data2'],
            'applyRoom18_data3' => $data['applyRoom18_data3'],
            'applyRoom18_data4' => $data['applyRoom18_data4'],
            'applyRoom18_data5' => $data['applyRoom18_data5'],
            'applyRoom19_data1' => $data['applyRoom19_data1'],
            'applyRoom19_data2' => $data['applyRoom19_data2'],
            'applyRoom19_data3' => $data['applyRoom19_data3'],
            'applyRoom19_data4' => $data['applyRoom19_data4'],
            'applyRoom19_data5' => $data['applyRoom19_data5'],
            'applyRoom20_data1' => $data['applyRoom20_data1'],
            'applyRoom20_data2' => $data['applyRoom20_data2'],
            'applyRoom20_data3' => $data['applyRoom20_data3'],
            'applyRoom20_data4' => $data['applyRoom20_data4'],
            'applyRoom20_data5' => $data['applyRoom20_data5'],
            'applyRoom21_data1' => $data['applyRoom21_data1'],
            'applyRoom21_data2' => $data['applyRoom21_data2'],
            'applyRoom21_data3' => $data['applyRoom21_data3'],
            'applyDev1_data1' => $data['applyDev1_data1'],
            'applyDev1_data2' => $data['applyDev1_data2'],
            'applyDev1_data3' => $data['applyDev1_data3'],
            'applyDev1_data4' => $data['applyDev1_data4'],
            'applyDev1_data5' => $data['applyDev1_data5'],
            'applyDev1_data6' => $data['applyDev1_data6'],
            'applyDev2_data1' => $data['applyDev2_data1'],
            'applyDev2_data2' => $data['applyDev2_data2'],
            'applyDev2_data3' => $data['applyDev2_data3'],
            'applyDev2_data4' => $data['applyDev2_data4'],
            'applyDev2_data5' => $data['applyDev2_data5'],
            'applyDev2_data6' => $data['applyDev2_data6'],
            'applyDev3_data1' => $data['applyDev3_data1'],
            'applyDev3_data2' => $data['applyDev3_data2'],
            'applyDev3_data3' => $data['applyDev3_data3'],
            'applyDev3_data4' => $data['applyDev3_data4'],
            'applyDev3_data5' => $data['applyDev3_data5'],
            'applyDev3_data6' => $data['applyDev3_data6'],
            'applyDev4_data1' => $data['applyDev4_data1'],
            'applyDev4_data2' => $data['applyDev4_data2'],
            'applyDev4_data3' => $data['applyDev4_data3'],
            'applyDev4_data4' => $data['applyDev4_data4'],
            'applyDev4_data5' => $data['applyDev4_data5'],
            'applyDev4_data6' => $data['applyDev4_data6'],
            'applyDev5_data1' => $data['applyDev5_data1'],
            'applyDev5_data2' => $data['applyDev5_data2'],
            'applyDev5_data3' => $data['applyDev5_data3'],
            'applyDev5_data4' => $data['applyDev5_data4'],
            'applyDev5_data5' => $data['applyDev5_data5'],
            'applyDev5_data6' => $data['applyDev5_data6'],
            'applyReason' => $data['applyReason'],
            'applyType' => $data['applyType'],            
        ];
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
        $data['change_order_number'] = date('Ym').'18'.random(14); 

        // 审批表数据
        $processRoles = $this->processRole;
        $processDescs = $this->processDesc;
        $data['change_desc'] = $processDescs[3];
        $data['curr_role'] = $processRoles[3];
        
        return $data; 
    }

    public function detail($id)
    {
        $row = self::get($id);
        $row['change_imgs'] = SystemAnnex::changeFormat($row['change_imgs']);
        $row['ban_info'] = BanModel::get($row['ban_id']);
        $row['house_info'] = HouseModel::get($row['house_id']);
        $row['tenant_info'] = TenantModel::get($row['tenant_id']);
        //halt($row);
        return $row;
    }

    public function process($data)
    {
        // 判断是否通过
        $changeoffsetRow = self::get($data['id']);

        // 获取最后一步的step
        $processRoles = $this->processRole;
        $steps = array_keys($processRoles);
        $finalStep = array_pop($steps);
        // 获取审批动作
        $processActions = $this->processAction;
        // 获取审批描述
        $processDescs = $this->processDesc;

        $changeOffsetUpdateData = $processUpdateData = [];

        /*  如果是打回  */
        if(isset($data['back_reason'])){
            $changeOffsetUpdateData['change_status'] = 2;
            $changeOffsetUpdateData['is_back'] = 1;
            $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
            $changeOffsetUpdateData['child_json'][] = [
                'success' => 1,
                'action' => $processActions[2].'，原因：'.$data['back_reason'],
                'time' => date('Y-m-d H:i:s'),
                'uid' => ADMIN_ID,
                'img' => '',
            ];

            // 更新使用权变更表
            $changeoffsetRow->allowField(['child_json','is_back','change_status'])->save($changeOffsetUpdateData, ['id' => $data['id']]);;
            // 更新审批表
            $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
            $processUpdateData['curr_role'] = $processRoles[$changeOffsetUpdateData['change_status']];
        }else{
            /* 如果审批通过，且非终审：更新使用权变更表的child_json、change_status，更新审批表change_desc、curr_role */
            if(!isset($data['change_reason']) && ($changeoffsetRow['change_status'] < $finalStep)){
                $changeOffsetUpdateData['change_status'] = $changeoffsetRow['change_status'] + 1;
                $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
                $changeOffsetUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeOffsetUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                if(isset($data['file']) && $data['file']){
                    $changeOffsetUpdateData['change_imgs'] = implode(',',$data['file']);
                }
                // 更新使用权变更表
                $changeoffsetRow->allowField(['child_json','change_imgs','change_status'])->save($changeOffsetUpdateData, ['id' => $data['id']]);;
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
                $processUpdateData['curr_role'] = $processRoles[$changeOffsetUpdateData['change_status']];

            /* 如果审批通过，且为终审：更新暂停计租表的child_json、change_status，更新审批表change_desc、curr_role、ftime、status，同时更新异动统计表 */
            }else if(!isset($data['change_reason']) && ($changeoffsetRow['change_status'] == $finalStep)){

                $changeOffsetUpdateData['change_status'] = 1;
                $changeOffsetUpdateData['ftime'] = time();
                $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
                $changeOffsetUpdateData['child_json'][] = [
                    'success' => 1,
                    'action' => $processActions[$changeOffsetUpdateData['change_status']],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                //终审成功后的数据处理
                try{$this->finalDeal($changeoffsetRow);}catch(\Exception $e){return false;}
                // 更新暂停计租表
                $changeoffsetRow->allowField(['child_json','change_status','ftime'])->save($changeOffsetUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeOffsetUpdateData['ftime'];
                $processUpdateData['status'] = 0;

            /* 如果审批不通过：更新暂停计租的child_json、change_status，更新审批表change_desc、curr_role */
            }else if (isset($data['change_reason'])){
                $changeOffsetUpdateData['change_status'] = 0;
                $changeOffsetUpdateData['ftime'] = time();
                $changeOffsetUpdateData['child_json'] = $changeoffsetRow['child_json'];
                $changeOffsetUpdateData['child_json'][] = [
                    'success' => 0,
                    'action' => $processActions[$changeOffsetUpdateData['change_status']].'，原因：'.$data['change_reason'],
                    'time' => date('Y-m-d H:i:s'),
                    'uid' => ADMIN_ID,
                    'img' => '',
                ];
                // 更新暂停计租表
                $changeoffsetRow->allowField(['child_json','change_status','ftime'])->save($changeOffsetUpdateData, ['id' => $data['id']]);
                // 更新审批表
                $processUpdateData['change_desc'] = $processDescs[$changeOffsetUpdateData['change_status']];
                $processUpdateData['ftime'] = $changeOffsetUpdateData['ftime'];
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
        // 将涉及的所有房屋，设置成暂停计租状态
        //HouseModel::where([['house_id','in',$finalRow['house_id']]])->update(['house_status'=>2]);
        
    }
}