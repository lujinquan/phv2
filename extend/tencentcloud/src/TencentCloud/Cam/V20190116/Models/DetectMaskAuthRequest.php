<?php
/*
 * Copyright (c) 2017-2018 THL A29 Limited, a Tencent company. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentCloud\Cam\V20190116\Models;
use TencentCloud\Common\AbstractModel;

/**
 * DetectMaskAuth请求参数结构体
 *
 * @method string getSkey() 获取登录态Skey
 * @method void setSkey(string $Skey) 设置登录态Skey
 * @method string getClientIP() 获取IP
 * @method void setClientIP(string $ClientIP) 设置IP
 * @method string getClientUA() 获取浏览器UA
 * @method void setClientUA(string $ClientUA) 设置浏览器UA
 * @method string getType() 获取人脸类型
 * @method void setType(string $Type) 设置人脸类型
 * @method string getName() 获取用户名称
 * @method void setName(string $Name) 设置用户名称
 * @method string getIdcard() 获取用户Idcard
 * @method void setIdcard(string $Idcard) 设置用户Idcard
 * @method integer getOwnerUin() 获取主账号
 * @method void setOwnerUin(integer $OwnerUin) 设置主账号
 */
class DetectMaskAuthRequest extends AbstractModel
{
    /**
     * @var string 登录态Skey
     */
    public $Skey;

    /**
     * @var string IP
     */
    public $ClientIP;

    /**
     * @var string 浏览器UA
     */
    public $ClientUA;

    /**
     * @var string 人脸类型
     */
    public $Type;

    /**
     * @var string 用户名称
     */
    public $Name;

    /**
     * @var string 用户Idcard
     */
    public $Idcard;

    /**
     * @var integer 主账号
     */
    public $OwnerUin;

    /**
     * @param string $Skey 登录态Skey
     * @param string $ClientIP IP
     * @param string $ClientUA 浏览器UA
     * @param string $Type 人脸类型
     * @param string $Name 用户名称
     * @param string $Idcard 用户Idcard
     * @param integer $OwnerUin 主账号
     */
    function __construct()
    {

    }

    /**
     * For internal only. DO NOT USE IT.
     */
    public function deserialize($param)
    {
        if ($param === null) {
            return;
        }
        if (array_key_exists("Skey",$param) and $param["Skey"] !== null) {
            $this->Skey = $param["Skey"];
        }

        if (array_key_exists("ClientIP",$param) and $param["ClientIP"] !== null) {
            $this->ClientIP = $param["ClientIP"];
        }

        if (array_key_exists("ClientUA",$param) and $param["ClientUA"] !== null) {
            $this->ClientUA = $param["ClientUA"];
        }

        if (array_key_exists("Type",$param) and $param["Type"] !== null) {
            $this->Type = $param["Type"];
        }

        if (array_key_exists("Name",$param) and $param["Name"] !== null) {
            $this->Name = $param["Name"];
        }

        if (array_key_exists("Idcard",$param) and $param["Idcard"] !== null) {
            $this->Idcard = $param["Idcard"];
        }

        if (array_key_exists("OwnerUin",$param) and $param["OwnerUin"] !== null) {
            $this->OwnerUin = $param["OwnerUin"];
        }
    }
}
