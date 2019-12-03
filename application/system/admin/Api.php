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

namespace app\system\admin;
use app\common\controller\Common;
use app\system\model\SystemAffiche as AfficheModel;
use app\common\model\SystemAnnex as AnnexModel;
use app\common\model\Cparam as CparamModel;
use app\house\model\House as HouseModel;
use app\house\model\Room as RoomModel;
use app\house\model\FloorPoint as FloorPointModel;
use app\deal\model\ChangeLease as ChangeLeaseModel;
use app\system\model\SystemNotice;
use app\system\model\SystemHelp;
use think\Db;

/**
 * 系统API控制器
 */
class Api extends Common 
{

    public function synAllHouses()
    {
        $houseids = Db::name('house')->cloumn('house_id');
        $HouseModel = new HouseModel;
        foreach ($houseids as $k => $v) {
            $houseRent = $HouseModel->count_house_rent($v);
            Db::name('house')->where([['house_id','eq',$v]])->update(['house_cou_rent'=>$houseRent]);
        }
        halt('处理完成！');
    }


    /**
     * 获取 一条消息提醒数据
     * @param id 消息id
     * @return json
     */
    public function getAfficheRow() 
    {
        if ($this->request->isAjax()) {
            $id = input('param.id/d', '');
            if ($id) {
                $afficheModel = new AfficheModel;
                $row = $afficheModel->get($id); //halt(session('admin_user.uid'));
                if ($row) {
                    //将阅读的人加入到已读行列中
                    if ($row['read_users']) {
                        $arrTemp = explode('|', $row['read_users']);
                        if (!in_array(session('admin_user.uid') , $arrTemp)) {
                            $arrTemp[] = session('admin_user.uid');
                            $readUsers = array_filter($arrTemp);
                            $readUsers = '|' . implode('|', $readUsers) . '|';
                            $afficheModel->where([['id', 'eq', $id]])->update(['read_users' => $readUsers]);
                        }
                    } else {
                        $readUsers = '|' . session('admin_user.uid') . '|';
                        $afficheModel->where([['id', 'eq', $id]])->update(['read_users' => $readUsers]);
                    }
                    $data = [];
                    $row['create_time'] = tranTime($row['create_time']);
                    $data['data'] = $row;
                    $data['msg'] = '';
                    $data['code'] = 0;
                    return json($data); 
                }
            } else {
                return $this->error('未知消息ID');
            }
        }
    }



    /**
     * 获取帮助文档数据
     * @return json
     */
    public function helpdoc() 
    {
        $systemHelp = new SystemHelp;
        $docs = $systemHelp->select();
        $nodes = [];
        $types = CparamModel::getCparams('help_type');
        foreach ($docs as $d) {
            $nodes[$d['type'] - 1]['name'] = $types[$d['type']];
            $nodes[$d['type'] - 1]['spread'] = true;
            $nodes[$d['type'] - 1]['id'] = $d['type'];
            $nodes[$d['type'] - 1]['alias'] = $d['type'];
            $nodes[$d['type'] - 1]['name'] = $types[$d['type']];
            $nodes[$d['type'] - 1]['children'][] = [
                'name' => $d['title'], 
                'id' => $d['id'], 
                'alias' => $d['type'] . $d['id'], 
                'content' => htmlspecialchars_decode($d['content'])
            ];
        }
        $data = [];
        $data['data'] = $nodes;
        // 模板实例如下：
        // $data['data']  =  [
        //                         [
        //                               'name'=> '常见问题',
        //                               'spread'=>true,
        //                               'id'=> 1,
        //                               'alias'=> 'changjianwentyi',
        //                               'children'=> [
        //                               [
        //                                 'name'=> '问题1（设置跳转）',
        //                                 'id'=> 11,
        //                                 'alias'=> 'wenti1',
        //                                 'content'=> 'content1'
        //                               ],
        //                               [
        //                                 'name'=> '问题2',
        //                                 'id'=> 12,
        //                                 'alias'=> 'wenti2',
        //                                 'content'=> 'content2'
        //                               ]
        //                             ],
        //                         ],
        //                         [
        //                               'name'=> '产品使用',
        //                               'spread'=>true,
        //                               'id'=> 2,
        //                               'alias'=> 'changjianwentyi',
        //                               'children'=> [
        //                               [
        //                                 'name'=> '产品使用1',
        //                                 'id'=> 13,
        //                                 'alias'=> 'wenti1',
        //                                 'content'=> 'content3'
        //                               ],
        //                               [
        //                                 'name'=> '产品使用2',
        //                                 'id'=> 14,
        //                                 'alias'=> 'wenti2',
        //                                 'content'=> 'content4'
        //                               ]
        //                             ],
        //                         ],
        //                   ];
        $data['msg'] = '';
        $data['code'] = 0;
        return json($data);
    }

    /**
     * 更新公告阅读记录
     * @param id 公告id
     * @return string 提示信息
     */
    public function update_notice_reads() 
    {
        $id = input('param.id/d');
        $systemNotice = new SystemNotice;
        $result = $systemNotice->updateReads($id);
        return $result;
    }

    /**
     * 附件上传
     * @param string $from 来源
     * @param string $group 附件分组,默认sys[系统]，模块格式：m_模块名，插件：p_插件名
     * @param string $water 水印，参数为空默认调用系统配置，no直接关闭水印，image 图片水印，text文字水印
     * @param string $thumb 缩略图，参数为空默认调用系统配置，no直接关闭缩略图，如需生成 500x500 的缩略图，则 500x500多个规格请用";"隔开
     * @param string $thumb_type 缩略图方式
     * @param string $input 文件表单字段名
     * @author Lucas <598936602@qq.com>
     * @return json
     */
    public function upload($from = 'input', $group = 'sys', $water = '', $thumb = '', $thumb_type = '', $input = 'file')
    {
        return json(AnnexModel::upload($from, $group, $water, $thumb, $thumb_type, $input));
    }

    public function lease_house_info()
    {
        $id = input('param.id/d');

        $result = [];

        $result['house'] = HouseModel::with(['tenant','ban'])->get($id);

        if(empty($result['house'])){
            return jsons('4000','参数错误');
        }

        $val = Db::name('system_config')->where([['name','eq','szno']])->value('value');

        $result['house']['house_szno'] = $result['house']['house_szno'].$val;
        
        $result['house']['total_use_area'] = 0;
        $result['house']['total_lease_area'] = 0;
        $result['house']['total_room_month'] = 0;
        $result['house']['hall_rent'] = 0;
        $result['house']['toilet_rent'] = 0;
        $result['house']['aisle_rent'] = 0;
        $result['house']['kitchen_rent'] = 0;
        $result['house']['below_five_num_rent'] = 0.5 * $result['house']['house_below_five_num'];
        $result['house']['more_five_num_rent'] = 1 * $result['house']['house_more_five_num'];

        $result['house']['change_remark'] = ChangeLeaseModel::where([['house_id','eq',$id],['change_status','eq',1]])->order('ctime desc')->value('change_remark');

        //获取当前房屋的房间
        $roomids = $result['house']->house_room()->where([['house_room_status','<=',1]])->column('room_id'); 
        $rooms = RoomModel::where([['room_id','in',$roomids]])->select();

        //halt($rooms);

        if(empty($rooms)){
            $rooms = array();
        }else{
            $i = 0;
            $j = 0;
            $k = 0;
            $result['house']['hall'] = 0;
            $result['house']['toilet'] = 0;
            $result['house']['inner_aisle'] = 0;
            $result['house']['kitchen'] = 0;
            foreach($rooms as &$v){
                //$row = Db::name('room_amend')->where(['RoomID'=>$v['RoomID'],'HouseID'=>$houseid])->find();
                //if($row){
                $v['room_lease_area'] = round($v['room_lease_area'],2);
                   //$v['RoomRentMonth'] = $row['RoomRentMonth'];
                //}
                switch ($v['room_pub_num']) {
                    case 1:
                        $v['room_pub_num'] = '独';
                        $i += $v['room_use_area'];
                        $j += $v['room_lease_area'];
                        $k += $v['room_cou_rent'];
                        $room[] = $v;
                        break;
                    case 2:
                        $v['room_pub_num'] = '共';
                        $i += $v['room_use_area'];
                        $j += $v['room_lease_area'];
                        $k += $v['room_cou_rent'];
                        $room[] = $v;
                        break;
                    default:
                        if($v['room_type'] == 5){ //三户共用厅堂
                            $result['house']['hall'] += 1;
                        }elseif($v['room_type'] == 2){ //三户共用卫生间
                            $result['house']['toilet'] += 1;
                        }elseif($v['room_type'] == 3){ //三户共用室内走道
                            $result['house']['inner_aisle'] += 1;
                        }elseif($v['room_type'] == 6){ //三户共用厨房
                            $result['house']['kitchen'] += 1;
                        }
                    break;
                }
             
            }

            $result['house']['hall_rent'] = 0.5 * $result['house']['hall'];
            $result['house']['toilet_rent'] = 0.5 * $result['house']['toilet'];
            $result['house']['inner_aisle_rent'] = 0.5 * $result['house']['inner_aisle'];
            $result['house']['kitchen_rent'] = 0.5 * $result['house']['kitchen'];

            $result['house']['kotal_use_area'] = $i;
            $result['house']['total_lease_area'] = $j;
            $s = $result['house']['hall_rent'] + $result['house']['toilet_rent'] + $result['house']['inner_aisle_rent'] + $result['house']['kitchen_rent'] + $k + $result['house']['below_five_num_rent'] + $result['house']['more_five_num_rent'];
            $result['house']['total_room_month'] = round($s,1);
            $result['house']['heding_room_month'] = round($s,1);
        }

        $result['house']['pump_cost'] = $result['house']['house_pump_rent'];
        $result['house']['heding_room_month'] = round(($result['house']['heding_room_month'] + $result['house']['house_pump_rent'] + $result['house']['house_diff_rent']),1);

        $result['room'] = isset($room)?$room:array();

        $data = [];
        $data['data'] = $result;
        $data['code'] = 0;
        $data['msg'] = '获取成功！';
        return json($data);
    }

    /**
     * 同步楼栋临时表，同步房屋临时表，同步房屋房间临时表，同步房间临时表
     * @param  [type] $queryWhere [description]
     * @return [type]             [description]
     */
    // public function synTempTable()
    // {
    //     $res = Db::query('call syn_temp_table');
    // }

}