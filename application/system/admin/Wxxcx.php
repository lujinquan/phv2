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

use think\Db;
use think\Controller;
use app\weichat\model\Weixin as WeixinModel;

/**
 * 微信小程序用户版接口
 */
class Wxxcx extends Controller 
{
	protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取微信用户openid
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-02-20 11:20:40
     * @link  参考地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/login/auth.code2Session.html
     * @return  返回值  
     * @version 版本  1.0
     */
	public function getOpenid()
	{
	    $code = input('code');//小程序传来的code值
	    $WeixinModel = new WeixinModel;
	    $res = $WeixinModel->getOpenid($code);
	    
	}






}
