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

namespace app\deal\admin;

use think\Db;
use think\Debug;
use app\common\controller\Common;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\report\model\Report as ReportModel;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 处理异动数据【预计耗时s】
     * @return [type] [description]
     */
    public function deal_change()
    {
        set_time_limit(0);

        debug('begin');
        
        // 1、处理 child_json 数据
        $allChildData = Db::name('json_child')->field("change_order_number,success,step,uid,from_unixtime(time,'%Y-%m-%d %H:%i:%s') as time,CONCAT(action,remark) as action,img")->select();
        $result = [];
        foreach($allChildData as $k => $v){
            $number = $v['change_order_number'];
            unset($v['change_order_number']);
            $result[$number][] = $v;
        }
//halt($allChildData);
        switch ((int)input('type')) {
            
            case 1:
                $msg = $this->deal_change_cut($result);
                break;
            case 3:
                $msg = $this->deal_change_pause($result);
                break;
            case 4:
                $msg = $this->deal_change_offset($result);
                break;
            case 7:
                $msg = $this->deal_change_new($result);
                break;
            case 8:
                $msg = $this->deal_change_cancel($result);
                break;
            case 9:
                $msg = $this->deal_change_house($result);
                break;
            case 10:
                $msg = $this->deal_change_inst($result);
                break;
            case 11:
                $msg = $this->deal_change_add($result);
                break;
            case 13:
                $msg = $this->deal_change_use($result);
                break;
            case 14:
                $msg = $this->deal_change_ban($result);
                break;
            case 17:
                $msg = $this->deal_change_name($result);
                break;
            case 18:
                $msg = $this->deal_change_lease($result);
                break;
            case 20: //月报表数据同步
                $ReportModel = new ReportModel;
                $tempData = $ReportModel->where([['type','eq','RentReport']])->column('date,data');
                foreach ($tempData as $k => $v) {
                    file_put_contents(ROOT_PATH.'file/report/rent/'.$k.'.txt', $v);
                }
                $msg = '同步成功';
                break;
            case 21: //房屋统计数据同步
                $ReportModel = new ReportModel;
                $tempData = $ReportModel->where([['type','eq','HouseReport']])->column('date,data');
                foreach ($tempData as $k => $v) {
                    file_put_contents(ROOT_PATH.'file/report/house/'.$k.'.txt', $v);
                }
                $msg = '同步成功';
                break;
            case 22: //产权统计数据同步
                $ReportModel = new ReportModel;
                $tempData = $ReportModel->where([['type','eq','PropertyReport']])->column('date,data');
                foreach ($tempData as $k => $v) {
                    file_put_contents(ROOT_PATH.'file/report/property/'.$k.'.txt', $v);
                }
                $msg = '同步成功';
                break;
            case 1000:
                $msg = $this->deal_data($result);
                break;
            default:
                return $this->error('暂未开发！');
                break;
        }
        debug('end');
        $time = floor(debug('begin','end')).'s';
        return $this->success($msg.'，耗时：'.$time);
    }

    /**
     * 租金减免，预计耗时s
     */
    public function deal_data($result)
    {

        $curDate = date('Ym');
        // 1、租金减免
        $allCutData = Db::name('change_cut')->where(1)->column('change_order_number');

        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_cut')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        Db::name('change_cut')->where([['change_status','eq',1],['end_date','>',$curDate]])->update(['is_valid'=>1]);
        // 2、别字更正
        $allCutData = Db::name('change_name')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_name')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        // 3、使用权变更
        $allCutData = Db::name('change_use')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_use')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        // 4、房屋调整
        $allCutData = Db::name('change_house')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_house')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        // 5、陈欠核销
        $allCutData = Db::name('change_offset')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_offset')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        // 6、新发租
        $allCutData = Db::name('change_new')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_new')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        // 7、注销
        $allCancelData = Db::name('change_cancel')->where(1)->column('change_order_number'); 
        foreach($allCancelData as $a){
            if(isset($result[$a])){
                Db::name('change_cancel')->where([['change_order_number','eq',$a]])->update(['process_id'=>1,'child_json'=>json_encode($result[$a])]);
            }
        }
        $jsonData = Db::name('json_data')->field('change_order_number,house_id,house_use_id,house_number,tenant_id,tenant_name,house_oprice,house_area,house_pre_rent,house_use_area,house_lease_area,house_diff_rent,house_pump_rent')->where([['changetype','eq','注销']])->select(); 
        $jsonArr = []; 
        foreach($jsonData as $d){
            $jsonArr[$d['change_order_number']][] = $d;
        }
        foreach ($jsonArr as $k => $v) {
            $res = Db::name('change_cancel')->where([['change_order_number','eq',$k]])->update(['process_id'=>1,'data_json'=>json_encode($v)]);
        }
        // 8、暂停计租
        $allPauseData = Db::name('change_pause')->where(1)->column('change_order_number');
        foreach($allPauseData as $a){
            if(isset($result[$a])){
                Db::name('change_pause')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        $users = Db::name('system_user')->column('number,id');
        $houses = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->column('a.house_number,a.house_id,a.tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent,b.tenant_name,b.tenant_number,b.tenant_card,b.tenant_tel');
        $housesss = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->column('a.house_id,a.house_number,a.tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent,b.tenant_name,b.tenant_number,b.tenant_card,b.tenant_tel');
        $steps = [1=>'提交申请',2=>'审批',3=>'审批',4=>'终审',5=>'发证',6=>'提交签字'];
        $data = Db::name('change_pause')->where([['house_id','neq','']])->field('id,house_id,child_json')->select();
        foreach($data as $d){
            $housearr = explode(',', $d['house_id']);
            $datajson = [];
            if(count($housearr) == 1){
                $datajson[0] = $housesss[$housearr[0]];
                $implodeHouses = $housearr[0];
            }else{
                $h = [];
                foreach ($housearr as $v) {
                    if(strlen($v) == 14){
                        $h[] = $houses[$v]['house_id'];
                        if(isset($houses[$v])){
                            $datajson[] = $houses[$v];
                        }
                    }else{
                        $h[] = $v;
                        if(isset($housesss[$v])){
                            $datajson[] = $housesss[$v];
                        }
                    }
                }
                $implodeHouses = implode(',', $h);
            }
            Db::name('change_pause')->where([['id','eq',$d['id']]])->update(['process_id'=>1,'house_id'=>$implodeHouses,'data_json'=>json_encode($datajson)]);
        }
        // 8、租约管理
        Db::execute('update ph_json_data as a left join ph_house as b on a.house_number = b.house_number left join ph_tenant as c on a.tenant_number = c.tenant_number set a.house_id = b.house_id,a.tenant_id = c.tenant_id');
        $users = Db::name('system_user')->column('number,id');
        $steps = [1=>'提交申请',2=>'审批',3=>'审批',4=>'终审',5=>'发证',6=>'提交签字'];
        $leaseJsonChild = Db::name('change_lease')->where([['process_id','neq',1]])->field('id,child_json')->select();
        foreach ($leaseJsonChild as $lease) {
            $child = json_decode($lease['child_json'],true);
            $a = [];
            foreach ($child as $k => $v) {
                $ftime = 0;
                if($k == 0){
                    $ftime = $v['CreateTime'];
                }
                $temp = [
                    'reason' => $v['Reson'],
                    'success' => $v['IfValid'],
                    'step' => $v['Step'],
                    'action' => $steps[$v['Step']],
                    'time' => date('Y-m-d H:i:s',$v['CreateTime']),
                    'uid' => isset($users[$v['UserNumber']])?$users[$v['UserNumber']]:1,
                ];
                array_unshift($a, $temp);
            }
            Db::name('change_lease')->where([['id','eq',$lease['id']]])->update(['process_id'=> 1,'ftime'=>$ftime,'entry_date'=>date('Y-m',$ftime),'child_json'=>json_encode($a)]);
        }
        Db::name('change_cut')->where([['change_status','eq',1]])->update(['is_valid'=>1]);
        // 9、管段调整
        $allCutData = Db::name('change_inst')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_inst')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }


        return '同步完成！';
    }

    /**
     * 租金减免，预计耗时s
     */
    public function deal_change_cut($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_cut')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_cut')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }

        }
        $curDate = date('Ym');
        // 2、标记仍然生效的减免异动
        Db::name('change_cut')->where([['change_status','eq',1],['end_date','>',$curDate]])->update(['is_valid'=>1]);
        return '租金减免同步完成！';
    }

    /**
     * 别字更正，预计耗时s
     */
    public function deal_change_name($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_name')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_name')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        return '别字更正同步完成！';
    }

    /**
     * 使用权变更，预计耗时s
     */
    public function deal_change_use($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_use')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_use')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        return '使用权变更同步完成！';
    }

    /**
     * 房屋调整，预计耗时s
     */
    public function deal_change_house($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_house')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_house')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        return '房屋调整同步完成！';
    }

    /**
     * 楼栋调整，预计耗时s
     */
    public function deal_change_ban($result)
    {
        // 1、处理 child_json 数据
        // $allCutData = Db::name('change_ban')->where(1)->column('change_order_number');
        // foreach($allCutData as $a){
        //     if(isset($result[$a])){
        //         Db::name('change_house')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
        //     }
        // }

        return '楼栋调整同步完成！';
    }

    /**
     * 租金追加调整，预计耗时s
     */
    public function deal_change_add($result)
    {
        // 1、处理 child_json 数据
        // $allCutData = Db::name('change_ban')->where(1)->column('change_order_number');
        // foreach($allCutData as $a){
        //     if(isset($result[$a])){
        //         Db::name('change_house')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
        //     }
        // }

        return '租金追加调整同步完成！';
    }

    /**
     * 陈欠核销，预计耗时s
     */
    public function deal_change_offset($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_offset')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_offset')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        return '陈欠核销同步完成！';
    }

    /**
     * 租约管理，预计耗时500s
     */
    public function deal_change_lease()
    {
        
        // 1、同步house_id，和tenant_id
        Db::execute('update ph_json_data as a left join ph_house as b on a.house_number = b.house_number left join ph_tenant as c on a.tenant_number = c.tenant_number set a.house_id = b.house_id,a.tenant_id = c.tenant_id');
        // 2、处理change_lease的child_json,data_json数据
        $users = Db::name('system_user')->column('number,id');
        $steps = [1=>'提交申请',2=>'审批',3=>'审批',4=>'终审',5=>'发证',6=>'提交签字'];
        $leaseJsonChild = Db::name('change_lease')->where([['process_id','neq',1]])->field('id,child_json')->select();
        foreach ($leaseJsonChild as $lease) {
            $child = json_decode($lease['child_json'],true);
            $a = [];
            foreach ($child as $k => $v) {
                $ftime = 0;
                if($k == 0){
                    $ftime = $v['CreateTime'];
                }
                $temp = [
                    'reason' => $v['Reson'],
                    'success' => $v['IfValid'],
                    'step' => $v['Step'],
                    'action' => $steps[$v['Step']],
                    'time' => date('Y-m-d H:i:s',$v['CreateTime']),
                    'uid' => isset($users[$v['UserNumber']])?$users[$v['UserNumber']]:1,
                ];
                array_unshift($a, $temp);
            }
            Db::name('change_lease')->where([['id','eq',$lease['id']]])->update(['process_id'=> 1,'ftime'=>$ftime,'entry_date'=>date('Y-m',$ftime),'child_json'=>json_encode($a)]);
        }

        // 2、标记仍然生效的租约管理异动
        Db::name('change_lease')->where([['change_status','eq',1]])->update(['is_valid'=>1]);

        return '租约管理同步完成！';
    }

    /**
     * 暂停计租，预计耗时s
     */
    public function deal_change_pause($result)
    {
        //['process_id'=>0]
        // 1、处理 child_json 数据
        $allPauseData = Db::name('change_pause')->where(['process_id'=>0])->column('change_order_number');
        foreach($allPauseData as $a){
            if(isset($result[$a])){
                Db::name('change_pause')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }

        }

        // 2、处理 json_data 数据
        $users = Db::name('system_user')->column('number,id');
        $houses = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->column('a.house_number,a.house_id,a.tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent,b.tenant_name,b.tenant_number,b.tenant_card,b.tenant_tel');
        $housesss = Db::name('house')->alias('a')->join('tenant b','a.tenant_id = b.tenant_id','left')->column('a.house_id,a.house_number,a.tenant_id,house_use_id,house_pre_rent,house_pump_rent,house_diff_rent,house_protocol_rent,b.tenant_name,b.tenant_number,b.tenant_card,b.tenant_tel');
        $steps = [1=>'提交申请',2=>'审批',3=>'审批',4=>'终审',5=>'发证',6=>'提交签字'];
        $data = Db::name('change_pause')->where([['house_id','neq','']])->field('id,house_id,child_json')->select();
        foreach($data as $d){
            $housearr = explode(',', $d['house_id']);
            $datajson = [];
            if(count($housearr) == 1){
                if(!isset($housesss[$housearr[0]])){
                    continue;
                }
                $datajson[0] = $housesss[$housearr[0]];
                $implodeHouses = $housearr[0];
            }else{
                $h = [];
                foreach ($housearr as $v) {
                    if(strlen($v) == 14){
                        $h[] = $houses[$v]['house_id'];
                        if(isset($houses[$v])){
                            $datajson[] = $houses[$v];
                        }
                    }else{
                        $h[] = $v;
                        if(isset($housesss[$v])){
                            $datajson[] = $housesss[$v];
                        }
                    }
                }
                $implodeHouses = implode(',', $h);
            }
            Db::name('change_pause')->where([['id','eq',$d['id']]])->update(['process_id'=>1,'house_id'=>$implodeHouses,'data_json'=>json_encode($datajson)]);
        }

        // 2、标记仍然生效的暂停计租异动
        Db::name('change_pause')->where([['change_status','eq',1]])->update(['is_valid'=>1]);
        $houseArr = Db::name('house')->column('house_id,house_status');
        $changepauses = Db::name('change_pause')->field('id,house_id')->select();
        foreach($changepauses as $ro){
            $flags = false;
            $temp_houses = explode(',',$ro['house_id']);
            foreach ($temp_houses as $temp_h) {
                if(isset($houseArr[$temp_h]) && $houseArr[$temp_h] == 1){
                    $flags = true;
                }
            }
            if(!$flags){
                Db::name('change_pause')->where([['id','eq',$ro['id']]])->update(['is_valid'=>0]);
            }
        }
        return '暂停计租同步完成！';
    }

    /**
     * 新发租，预计耗时s
     */
    public function deal_change_new($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_new')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_new')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        return '新发租同步完成！';
    }

    /**
     * 注销异动，预计耗时30s
     */
    public function deal_change_cancel($result)
    {
        // 1、处理 child_json 数据
        $allCancelData = Db::name('change_cancel')->where(1)->column('change_order_number'); 
        foreach($allCancelData as $a){
            if(isset($result[$a])){
                Db::name('change_cancel')->where([['change_order_number','eq',$a]])->update(['process_id'=>1,'child_json'=>json_encode($result[$a])]);
            }
        }

        // 2、处理 json_data 数据
        $jsonData = Db::name('json_data')->field('change_order_number,house_id,house_use_id,house_number,tenant_id,tenant_name,house_oprice,house_area,house_pre_rent,house_use_area,house_lease_area,house_diff_rent,house_pump_rent')->where([['changetype','eq','注销']])->select(); 
        $jsonArr = []; 
        foreach($jsonData as $d){
            $jsonArr[$d['change_order_number']][] = $d;
        }
        foreach ($jsonArr as $k => $v) {
            $res = Db::name('change_cancel')->where([['change_order_number','eq',$k]])->update(['process_id'=>1,'data_json'=>json_encode($v)]);
        }
        return '注销同步完成！'; 
    }

    /**
     * 租金减免，预计耗时s
     */
    public function deal_change_inst($result)
    {
        // 1、处理 child_json 数据
        $allCutData = Db::name('change_inst')->where(1)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_inst')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }
        return '管段调整同步完成！';
    }

    /**
     * 数据处理
     * @param id 消息id
     * @return json
     */
    public function dealChildJson()
    {
        //halt('确认处理child_json数据？会造成导入的数据详情页无法正常打开，如果确认处理，请在程序中注释改代码！');
        set_time_limit(0);
        //将字表中的数据json化，写入到对应的异动表中
        $allChildData = Db::name('json_child')->column("change_order_number,success,step,uid,from_unixtime(time,'%Y-%m-%d %H:%i:%s') as time,action,img");
//halt($allChildData);
        $result = [];
        foreach($allChildData as $k => &$v){
            unset($v['change_order_number']);
            $result[$k][] = $v;
        }

        $where = 1;
        

        // 处理新发租异动
        $allNewData = Db::name('change_new')->where($where)->column('change_order_number');
        foreach($allNewData as $a){
            if(isset($result[$a])){
                Db::name('change_new')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理租金减免异动
        $allCutData = Db::name('change_cut')->where($where)->column('change_order_number');
        foreach($allCutData as $a){
            if(isset($result[$a])){
                Db::name('change_cut')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理注销+房改异动
        $allCancelData = Db::name('change_cancel')->where($where)->column('change_order_number');
        foreach($allCancelData as $a){
            if(isset($result[$a])){
                // dump($a);
                // halt($result);
                Db::name('change_cancel')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理房屋调整异动
        $allHouseData = Db::name('change_house')->where($where)->column('change_order_number');
        foreach($allHouseData as $a){
            if(isset($result[$a])){
                Db::name('change_house')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理陈欠核销异动
        $allOffsetData = Db::name('change_offset')->where($where)->column('change_order_number');
        foreach($allOffsetData as $a){
            if(isset($result[$a])){
                Db::name('change_offset')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        // 处理租金追加调整异动
        $allRentaddData = Db::name('change_rentadd')->where($where)->column('change_order_number');
        foreach($allRentaddData as $a){
            if(isset($result[$a])){
                Db::name('change_rentadd')->where([['change_order_number','eq',$a]])->update(['child_json'=>json_encode($result[$a])]);
            }
        }

        halt('ok');
    }

    /**
     * 
     * @param id 消息id
     * @return json
     */
    public function getChangeCutRow() 
    {
        if ($this->request->isAjax()) {
            $house_id = $this->request->param('house_id');
            $data = [];
            $ChangeCutModel = new ChangeCutModel;
            if($house_id){
                $changecutid = ChangeCutModel::where([['house_id','eq',$house_id]])->order('ctime desc')->value('id');
                $row = $ChangeCutModel->detail($changecutid);
                $data['data'] = $row->toArray();
                $data['msg'] = '获取成功';
                $data['code'] = 1;
            }else{
                $data['msg'] = '参数错误';
                $data['code'] = 0;
            }
            return json($data);
        }
    }

    /**
     * @param ban_number 楼栋编号
     * @param ban_floors 楼栋总楼层数
     * @return json
     */
    public function getChangeBan()
    {
        if ($this->request->isAjax()) {
            $ban_id = input('param.ban_id/s');
            $ban_floors = input('param.ban_floors/d');
            $oldFloors = BanModel::where([['ban_id','eq',$ban_id]])->value('ban_floors');
            
            if(!$ban_floors){
                return $this->error('参数错误！');
            }
            // 检测传入的楼层号是否符合规范，是否高于已有的最高居住层
            $maxHouseFloorID = HouseModel::where([['ban_id','eq',$ban_id]])->max('house_floor_id');
            if(!$maxHouseFloorID || $maxHouseFloorID > $ban_floors){
                return $this->error('总楼层不能小于居住层！');
            }

            BanModel::where([['ban_id','eq',$ban_id]])->update(['ban_temp_floors'=>$ban_floors]);

            // 获取该楼栋下所有房间
            $RoomModel = new RoomModel;
            //$roomids = $RoomModel->where([['ban_id','eq',$ban_id]])->column('room_id');
            // 更新所有房间的计算租金
            // foreach($roomids as $roomid){
            //     $room_rent = $RoomModel->count_room_rent($roomid,'temp');
            //     RoomModel::where([['room_id','eq',$roomid]])->update(['room_temp_cou_rent'=>$room_rent]);
            // }
            // 获取该楼栋下所有房屋
            $houseOldArr = HouseModel::with('tenant')->where([['house_status','eq',1],['ban_id','eq',$ban_id],['tenant_id','>',0]])->field('house_id,house_number,tenant_id,house_pre_rent,house_floor_id,house_cou_rent')->select()->toArray();
            $HouseModel = new HouseModel;
            $result = [];
            $result['data'] = [];
            // 更新所有房屋的计算租金
            if($houseOldArr){
                foreach($houseOldArr as $h){
                    // 更新所有房间的计算租金
                    $roomids = Db::name('house_room')->where([['house_id','eq',$h['house_id']]])->column('room_id');
                    foreach($roomids as $roomid){
                        $room_rent = $RoomModel->count_room_rent($roomid,'temp');
                        RoomModel::where([['room_id','eq',$roomid]])->update(['room_temp_cou_rent'=>$room_rent]);
                    }
                    
                    $house_rent = $HouseModel->count_house_rent($h['house_id'],'temp');
                    HouseModel::where([['house_id','eq',$h['house_id']]])->update(['house_temp_cou_rent'=>$house_rent]);
                    //halt('房屋编号：'.$h['house_number'].',临时计算租金是'.$house_rent);
                }
                $houseNewArr = HouseModel::where([['ban_id','eq',$ban_id]])->column('house_id,house_temp_cou_rent');
                
                $oldCouRents = $newCouRents = 0;
                foreach ($houseOldArr as $k => $v) {
                    if($ban_floors < $v['house_floor_id']){
                        return $this->error('总楼层不能小于居住层！');
                        break;
                    }
                    $oldCouRents = bcadd($oldCouRents,$v['house_cou_rent'],2); //统计所有房屋原计算租金之和
                    $newCouRents = bcadd($newCouRents,$houseNewArr[$v['house_id']],2); //统计所有房屋现计算租金之和
                    $result['data'][$k]['house_number'] = $v['house_number'];
                    $result['data'][$k]['tenant_name'] = $v['tenant_name'];
                    $result['data'][$k]['old_floor'] = $v['house_floor_id'].'/'.$oldFloors;
                    $result['data'][$k]['new_floor'] = $v['house_floor_id'].'/'.$ban_floors;
                    $result['data'][$k]['house_pre_rent'] = $v['house_pre_rent'];
                    $result['data'][$k]['house_old_cou_rent'] = $v['house_cou_rent'];
                    $result['data'][$k]['house_new_cou_rent'] = $houseNewArr[$v['house_id']];
                    $result['data'][$k]['diff_cou_rent'] = bcsub($houseNewArr[$v['house_id']],$v['house_cou_rent'] ,2);
                    //$v['house_cou_rent'];
                }               
            }

            $result['count'] = count($result['data']);
            $result['msg'] = '获取成功！';
            $result['code'] = 1;
            return json($result);
            //halt(json($result));
        }
    }

    

}