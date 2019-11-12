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

namespace app\index\admin;

use think\Db;
use app\common\controller\Common;
use app\system\model\SystemNotice;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\house\model\Tenant as TenantModel;
//use app\system\model\SystemUser as UserModel;


/**
 * 微信小程序用户版接口
 */
class Weixin extends Common 
{

	/**
	 * [signin description]
	 * @return [type] [description]
	 */
	public function signin()
    {
        if ($this->request->isPost()) {
            // 获取post数据
            $data = $this->request->post();
            // 验证数据合法性
            if(!$data['username']){
            	return $this->error('请输入登录用户名！');
            }
            // 如果有重复的手机号，会只取第一条
            $id = TenantModel::where([['tenant_tel','eq',$data['username']],['tenant_status','eq',1]])->find();
            if(!$id){
            	return $this->error('用户名错误或被禁用！');
            } 
			$key = str_coding($id,'ENCODE');
			// 更新用户登录的信息
			TenantModel::where([['tenant_id','eq',$id],['tenant_status','eq',1]])->update(['tenant_key'=>$key,'tenant_weixin_ctime'=>time()]);
            $systemNotice = new SystemNotice;
            $where = $systemNotice->checkWhere($getData);
            $data['data'] = $systemNotice->field('id,title,type,content,cuid,reads,create_time')->where($where)->page($page)->order('sort asc')->limit($limit)->select();
            $data['key'] = $key;
            $data['code'] = 0;
            $data['msg'] = '登录成功！';
            return json($data);    
            //return $this->success('登录成功','',['key'=>$key]);
        }
    }

    /**
     * 
     * @param id 消息id
     * @return json
     */
    public function getTenantInfo() 
    {
    	$key = input('key');
    	$key = str_replace(" ","+",$key); //加密过程中可能出现“+”号，在接收时接收到的是空格，需要先将空格替换成“+”号
    	halt(str_coding($key,'DECODE'));
    }

}