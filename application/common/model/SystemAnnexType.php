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
namespace app\common\model;

use think\Model;
/**
 * 附件类型模型
 * @package app\common\model
 */
class SystemAnnexType extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = false;

    public function tenant()
    {
        return $this->hasOne('tenant', 'tenant_id', 'tenant_id')->bind('tenant_name,tenant_tel,tenant_card');
    }
}