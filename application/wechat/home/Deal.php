<?php

// +----------------------------------------------------------------------
// | 框架永久免费开源
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>
// +----------------------------------------------------------------------
// | Motto: There is only one kind of failure in the world is to give up .
// +----------------------------------------------------------------------

namespace app\wechat\home;

use think\Db;
use SendMessage\ServerCodeAPI;
use app\wechat\home\Base;
use app\common\model\SystemAnnex;
use app\common\model\SystemAnnexType;
use app\rent\model\Rent as RentModel;
use app\house\model\Ban as BanModel;
use app\wechat\model\WeixinSignRecord;
use app\deal\model\ChangeCut as ChangeCutModel;
use app\rent\model\Invoice as InvoiceModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
use app\common\model\Cparam as ParamModel;
use app\wechat\model\Weixin as WeixinModel;
use app\system\model\SystemUser as UserModel;
use app\system\model\SystemRole as RoleModel;
use app\deal\model\Process as ProcessModel;
use app\rent\model\Recharge as RechargeModel;
use app\system\model\SystemConfig as ConfigModel;
use app\rent\model\RentOrderChild as RentOrderChildModel;
use app\wechat\model\WeixinToken as WeixinTokenModel;
use app\wechat\model\WeixinGuide as WeixinGuideModel;
use app\wechat\model\WeixinColumn as WeixinColumnModel;
use app\wechat\model\WeixinNotice as WeixinNoticeModel;
use app\wechat\model\WeixinBanner as WeixinBannerModel;
use app\wechat\model\WeixinConfig as WeixinConfigModel;
use app\wechat\model\WeixinMember as WeixinMemberModel;
use app\wechat\model\WeixinOrder as WeixinOrderModel;
use app\wechat\model\WeixinReadRecord as WeixinReadRecordModel;
use app\wechat\model\WeixinOrderTrade as WeixinOrderTradeModel;
use app\wechat\model\WeixinOrderRefund as WeixinOrderRefundModel;
use app\wechat\model\WeixinMemberHouse as WeixinMemberHouseModel;

/**
 * 功能描述：用户版小程序
 * =====================================
 * @author  Lucas 
 * email:   598936602@qq.com 
 * Website  address:  www.mylucas.com.cn
 * =====================================
 * 创建时间: 2020-02-28 11:47:10
 * @example 
 * @link    文档参考地址：
 * @return  返回值  
 * @version 版本  1.0
 */
class Deal extends Base
{
    protected $debug = false;

    protected $domain = '';

    protected function initialize()
    {
        parent::initialize();
        $site_domain = ConfigModel::where([['name','eq','site_domain']])->value('value');
        $this->domain = 'https://'.$site_domain;
    }

    /**
     * 功能描述：租金减免点击申请跳转的列表页面
     * @author  Lucas 
     * 创建时间: 2020-11-18 15:10:11
     */
    public function get_member_house() 
    {
        // 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/get_member_house';
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        $result['data']['member'] = $member_info;
        $result['data']['role_type'] = $checkData['role_type'];
        $result['data']['member_extra_info'] = $member_extra_info;

        // 查找绑定的房屋
        $WeixinMemberHouseModel = new WeixinMemberHouseModel;
        // 获取关联的所有房屋
        $houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->column('house_id');
        // 获取关联的所有自己已认证的房屋
        $is_auth_houses = $WeixinMemberHouseModel->where([['member_id','eq',$member_info['member_id']],['is_auth','eq',1],['dtime','eq',0]])->column('house_id');

        $result['data']['tenant'] = TenantModel::where([['tenant_id','eq',$member_info['tenant_id']]])->find();
        // 去掉暂停计租的房子+已注销的房子
        $result['data']['house'] = HouseModel::with('ban,tenant')->where([['house_id','in',$houses],['house_is_pause','eq',0],['house_status','eq',1]])->field('house_id,house_use_id,house_balance,ban_id,tenant_id,house_unit_id,house_is_pause,house_pre_rent,house_status,house_floor_id,house_balance')->select()->toArray();
        $yue = 0;
        foreach ($result['data']['house'] as $k => &$v) {
            $row = Db::name('rent_order')->where([['house_id','eq',$v['house_id']]])->field('sum(rent_order_receive - rent_order_paid) as rent_order_unpaids,sum(rent_order_paid) as rent_order_paids')->find();
            // if($row['rent_order_unpaids'] == 0){
            //     unset($result['data']['house'][$k]);
            //     continue;
            // }
            
            // 是否可以申请租金减免，0可以，1审核中，2审核通过，3欠租
            $v['is_can_apply_cutrent'] = 0; 
            // 如果当前房屋正在减免生效中
            $table_info = Db::name('change_table')->where([['house_id','eq',$v['house_id']],['change_type','eq',1],['change_status','eq',1]])->field('end_date,order_date')->find();
            if($table_info){
            	if(date('Ym') < $table_info['end_date']){
            		$v['is_can_apply_cutrent'] = 2;
            	}
            }
            // 如果当前房屋已在会员申请减免列表中，且为审批中状态时
            $change_member_cut_info = Db::name('change_cut')->where([['house_id','eq',$v['house_id']]])->field('change_status,is_valid')->find();
            if($change_member_cut_info){
            	if($change_member_cut_info['change_status'] > 1){
            		$v['is_can_apply_cutrent'] = 1;
            	}
            	// if($change_member_cut_info['change_status'] == 1 && $change_member_cut_info['is_valid'] == 1){
            	// 	$v['is_can_apply_cutrent'] = 2;
            	// }
            }

            $cut_row = Db::name('rent_order')->where([['house_id','eq',$v['house_id']]])->field('rent_order_cut')->order('rent_order_date desc')->find();
            $v['rent_order_cut'] = $cut_row['rent_order_cut'];
            $v['is_auth'] = 0;
            $v['house_use_id'] = $v['house_use_id'];
            if(in_array($v['house_id'], $is_auth_houses)){
                $yue += $v['house_balance'];
                $v['is_auth'] = 1;
            }
            $v['rent_order_unpaids'] = $row['rent_order_unpaids']?$row['rent_order_unpaids']:0;
            if($v['rent_order_unpaids'] > 0){
            	$v['is_can_apply_cutrent'] = 3;
            }
            $v['rent_order_paids'] = $row['rent_order_paids']?$row['rent_order_paids']:0;
        }
        $result['data']['house'] = array_values($result['data']['house']);
        $result['data']['yue'] = $yue;
        $result['code'] = 1;
        $result['msg'] = '获取成功！';
        return json($result); 
    }

    /**
     * 租金减免申请提交
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-11-18 18:13:47
     * @return  返回值  
     * @version 版本  1.0
     */
    public function apply_change_cut()
    {
    	// 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/get_member_house';
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
  
        $data = $this->request->param();

        $data['member_id'] = $member_info['member_id'];
        $data['save_type'] = 'save';

        // 数据验证
        $result = $this->validate($data, 'app\deal\validate\Changecut.form');
        if($result !== true) {
            return json(array('code' => 10030, 'msg'=>$result));
        }
		$house_admin_info = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('system_user c','b.ban_inst_id = c.inst_id')->where([['c.status','eq',1]])->field('c.id,a.ban_id')->find();
		define('ADMIN_ID', $house_admin_info['id']);
		$data['ban_id'] = $house_admin_info['ban_id'];
        // 附件上传验证 S
        $fileUploadConfig = Db::name('config')->where([['title','eq','changecut_file_upload']])->value('value');
        $file = [];
        if(isset($data['TenantReIDCard']) && $data['TenantReIDCard']){ // 身份证  
            $file = array_merge($file,$data['TenantReIDCard']);
        }else{
            if(strpos($fileUploadConfig, 'TenantReIDCard') !== false){
                return json(array('code' => 10030, 'msg'=>'请上传身份证'));
            }
        }
        if(isset($data['Residence']) && $data['Residence']){ // 户口本
            $file = array_merge($file,$data['Residence']);
        }else{
            if(strpos($fileUploadConfig, 'Residence') !== false){
            	return json(array('code' => 10030, 'msg'=>'请上传户口本'));
            }
        }
        if(isset($data['HouseForm']) && $data['HouseForm']){ // 租约
            $file = array_merge($file,$data['HouseForm']);
        }else{
            if(strpos($fileUploadConfig, 'HouseForm') !== false){
            	return json(array('code' => 10030, 'msg'=>'请上传租约'));
            }
        }
        if(isset($data['Lowassurance']) && $data['Lowassurance']){ // 低保证
            $file = array_merge($file,$data['Lowassurance']);
        }else{
            if(strpos($fileUploadConfig, 'Lowassurance') !== false){
            	return json(array('code' => 10030, 'msg'=>'请上传低保证'));
            }
        }
        if(isset($data['Housingsecurity']) && $data['Housingsecurity']){ // 租房保障申请表
            $file = array_merge($file,$data['Housingsecurity']);
        }else{
            if(strpos($fileUploadConfig, 'Housingsecurity') !== false){
            	return json(array('code' => 10030, 'msg'=>'请上传租房保障申请表'));
            }
        }
        $data['file'] = $file;
        // 附件上传验证 E
        
        $ChangeCutModel = new ChangeCutModel;
        // 数据过滤
        $filData = $ChangeCutModel->dataFilter($data,'add');
        if(!is_array($filData)){
            // return $this->error($filData);
            return json(array('code' => 10030, 'msg'=>filData));
        }
    
        // 入库使用权变更表
        unset($filData['id']);
        $row = $ChangeCutModel->allowField(true)->create($filData);
        if (!$row) {
            return json(array('code' => 10030, 'msg'=>'申请失败'));
        }else{
        	return json(array('code' => 1, 'msg'=>'申请成功'));
        }     
        
    }

    /**
     * 租金减免异动详情
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-11-18 18:12:02
     * @return  返回值  
     * @version 版本  1.0
     */
    /*public function change_cut_detail()
    {
    	// 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/get_member_house';
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }
        
        $id = $this->request->param('id');
        
        $ChangeCutModel = new ChangeCutModel;           
        $row = $ChangeCutModel->detail($id);
        {
		    "code": 1,
		    "data": {
		        "id": 221,
		        "change_order_number": "2020110163420320600588",
		        "change_type": 1,
		        "process_id": 0,
		        "cut_type": 1,
		        "cut_rent": "1.00",
		        "cut_rent_number": "2343243",
		        "ban_id": 0,
		        "house_id": "18",
		        "tenant_id": 18726,
		        "member_id": 2824,
		        "child_json": null,
		        "change_reason": "",
		        "change_remark": "异动原因",
		        "change_imgs": "",
		        "is_back": 0,
		        "is_valid": 0,
		        "change_status": 2,
		        "end_date": 0,
		        "entry_date": "",
		        "cuid": 79,
		        "ctime": "2020-11-18 17:49:58",
		        "etime": "2020-11-18 17:49:58",
		        "dtime": 0,
		        "ftime": 0,
		        "house_number": "10020120070008",
		        "house_use_area": "52.71",
		        "house_lease_area": "50.49",
		        "house_pre_rent": "74.60",
		        "house_cou_rent": "74.60",
		        "house_use_id": 1,
		        "tenant_number": 1314877,
		        "tenant_tel": "15623022843",
		        "tenant_card": "420109195311170061",
		        "tenant_name": "彭永红",
		        "ban_info": null
		    }
		}
        return json(array('code' => 1, 'data'=>$row));
    }*/

    /**
     * 获取租金减免申请页面数据
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-11-18 18:17:42
     * @return  返回值  
     * @version 版本  1.0
     */
    public function get_apply_change_cut()
    {
    	// 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/get_member_house';
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }

        $house_id = $this->request->param('house_id');

        $fields = 'a.house_id,a.house_number,a.house_pre_rent,a.house_cou_rent,b.ban_id,b.ban_owner_id,b.ban_number,b.ban_address,a.house_use_id,d.tenant_id,d.tenant_name,d.tenant_card,d.tenant_tel';

        $row = Db::name('house')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('tenant d','a.tenant_id = d.tenant_id')->where([['a.house_id','eq',$house_id]])->field($fields)->find();

        $params = ParamModel::getCparams();

        $row['house_use_id'] = $params['uses'][$row['house_use_id']];
        $row['ban_owner_id'] = $params['owners'][$row['ban_owner_id']];

        return json(array('code' => 1, 'data' => $row, 'params' =>  $params));
    }

    /**
     * 获取租金减免审核列表数据
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-11-18 18:34:40
     * @return  返回值  
     * @version 版本  1.0
     */
    public function change_cut_list()
    {
    	// 验证令牌
        $result = [];
        $result['code'] = 0;
        $result['action'] = 'wechat/weixin/change_cut_list';
        $checkData = $this->check_user_token();
        if($checkData['error_code']){ // 如果有错误码
            $result['code'] = $checkData['error_code'];
            $result['msg'] = $checkData['error_msg'];
            return json($result);
        }else{ // 验证成功
            $member_info = $checkData['member_info']; //微信用户基础数据
            $member_extra_info = $checkData['member_extra_info'];
        }
        if($member_info['is_show'] == 2){
            $result['code'] = 10011;
            $result['msg'] = '用户已被禁止访问';
            $result['en_msg'] = 'The user has been denied access';
            return json($result);
        }

        $change_member_cut_data = Db::name('change_cut')->alias('a')->join('ban b','a.ban_id = b.ban_id')->join('house c','a.house_id = c.house_id')->join('tenant d','a.tenant_id = d.tenant_id')->where([['member_id','eq',$member_info['member_id']]])->field('a.id,a.house_id,a.tenant_id,a.ban_id,a.cut_rent,a.change_status,c.house_number,from_unixtime(a.ctime, \'%Y-%m-%d %H:%i\') as ctime,b.ban_address,d.tenant_name')->select();
        if (empty($change_member_cut_data)) {
        	$change_member_cut_data = array();
        }

        $bind_house_info = WeixinMemberHouseModel::where([['member_id','eq',$member_info['member_id']],['dtime','eq',0]])->field('count(house_id) as house_ids')->find();
        if(isset($bind_house_info['house_ids'])){
            $bind_houses = $bind_house_info['house_ids'];
        }else{
            $bind_houses = 0;
        }

        return json(array('code' => 1, 'data' => $change_member_cut_data ,'bind_houses' => $bind_houses));
    }




}