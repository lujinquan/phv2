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

namespace app\wechat\validate;

use think\Validate;

/**
 * 公告验证器
 * @package app\system\validate
 */
class WeixinBanner extends Validate
{
	//定义验证规则
    protected $rule = [
        'banner_title|幻灯片名称'       => 'require|max:12',
        'sort|排序'    => 'number',
        'file|幻灯片图片'      => 'require',
        'banner_url_type|链接类型'      => 'require|checkType',
        //'__token__'      => 'require|token',
    ];

    //定义验证提示
    protected $message = [
        
    ];

    protected function checkType($value, $rule='', $data)
  	{
  		if($value == 3 && !$data['ext_appid']){
  			return '外链appid不能为空';
  		}
      	return true;	
  	}

}