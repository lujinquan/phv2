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
use app\wechat\home\Base;
use app\system\model\SystemConfig as ConfigModel;
use app\common\model\SystemAnnex as AnnexModel;
use app\common\model\SystemAnnexType;


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
class Api extends Base
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
    	$file = AnnexModel::upload($from, $group, $water, $thumb, $thumb_type, $input);
    	if($file['code']){
    		$file['data']['file'] = $this->domain . $file['data']['file'];
        	return json($file);
    	}else{
			return json($file);
    	}
    	
    	
    }

}