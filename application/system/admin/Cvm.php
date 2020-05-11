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

use app\system\model\Cvm as CvmModel;


/**
 * 应用市场控制器
 * @package app\system\admin
 */
class Cvm extends Admin
{
    
    
    /**
     * 应用列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }


    /**
     * 
     * =====================================
     * @author  Lucas 
     * email:   598936602@qq.com 
     * Website  address:  www.mylucas.com.cn
     * =====================================
     * 创建时间: 2020-05-11 15:57:32
     * @return  返回值  
     * @version 版本  1.0
     */
    public function describe_instances()
    {
        $CvmModel = new CvmModel;
		$data = $CvmModel->describeInstances();
		halt($data);
    }

    
    /**
     * 应用列表
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function test()
    {
    	$data = curl_get('https://cfs.tencentcloudapi.com/?Action=DescribeCfsServiceStatus&Version=2019-07-19');
    	$result = json_decode($data,true);
    	halt($result);
        return $this->fetch();
    }

    /**
     * 查询cfs文件服务状态
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function describe_available_zone_info()
    {
    	$CvmModel = new CvmModel;
		$data = $CvmModel->describeAvailableZoneInfo();
    	$result = json_decode($data,true);
    	halt($result);
        return $this->fetch();
    }

    /**
     * 查询cfs文件系统挂载点
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public function describe_mount_targets()
    {
    	// halt(str_coding('a4c80Q2kbNcf0S9tEWHLb3guFOBRMCcgPkZTPC7zN7vJZ27hVJU7yGKzHy8kcgYvogrT1+TmKs9fIuxf0w','DECODE'));
    	// halt(str_coding('6zjCaiDZeVgfOq2yjESY2OfmfT4QYPAf','ENCODE'));
    	$CvmModel = new CvmModel;
		$data = $CvmModel->describeMountTargets();
    	$result = json_decode($data,true);
    	halt($result);
        return $this->fetch();
    }
    
}
