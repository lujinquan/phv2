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
     * @param  $FileSystemId String 必填 需要查询的文件系统ID
     * @return mixed
     */
    public function describe_mount_targets()
    {
        $FileSystemId = 'cfs-1s3s9yyh';
    	$CvmModel = new CvmModel;
		$data = $CvmModel->describeMountTargets($FileSystemId);
    	$result = json_decode($data,true);
    	halt($result);
        return $this->fetch();
    }

    /**
     * 修改cfs文件系统挂载点【腾讯云平台暂不支持，已废弃】
     * @author Lucas <598936602@qq.com>
     * @param  $FsLimit Integer 必填 文件系统容量限制大小，输入范围0-1073741824, 单位为GB；其中输入值为0时，表示不限制文件系统容量
     * @param  $FileSystemId String 必填 文件系统ID
     * @return mixed
     */
    public function update_cfs_file_system_size_limit_request()
    {
        $FsLimit = 100; //Gb
        $FileSystemId = 'cfs-1s3s9yyh'; //文件系统ID
        $CvmModel = new CvmModel;
        $data = $CvmModel->updateCfsFileSystemSizeLimitRequest($FsLimit , $FileSystemId);
        $result = json_decode($data,true);
        halt($result);
        return $this->fetch();
    }
    
}
