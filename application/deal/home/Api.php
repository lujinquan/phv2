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

namespace app\deal\home;

use think\Db;
use app\common\controller\Common;
use app\house\model\Ban as BanModel;
use app\house\model\Room as RoomModel;
use app\house\model\House as HouseModel;
use app\deal\model\ChangeCut as ChangeCutModel;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    public function codeCert()
    {
        $route = $this->request->route();
//halt($route);
        $upload = '/upload/qrcode/'.$route['name'].'.png';

        $filename = $_SERVER['DOCUMENT_ROOT'].$upload;

        $fields = "a.id,a.szno,a.data_json,a.change_order_number,a.tenant_id,a.tenant_name,a.change_status,b.house_id,b.house_number,b.house_floor_id,a.is_back,b.house_use_id,d.ban_address,d.ban_struct_id,d.ban_damage_id,d.ban_owner_id,d.ban_floors,d.ban_inst_id";

        $find = Db::name('change_lease')->alias('a')->join('house b','a.house_id = b.house_id','left')->join('ban d','b.ban_id = d.ban_id','left')->field($fields)->where([['qrcode','eq',$upload]])->whereOr([['qrcode','like','%'.$route['name'].'%']])->find();

        if($find){
            $houseRow = Db::name('house')->where([['house_id','eq',$find['house_id']]])->find();
            $detail = json_decode($find['data_json'],true);
            $date = $detail['applyYear'].'年'.$detail['applyMonth'].'月'.$detail['applyDay'].'日';
            if(is_file($filename) && ($houseRow['house_status'] == 1) && ($houseRow['tenant_id'] == $find['tenant_id'])){
                $info = <<<EOF

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE=edge,
    chrome=1" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="Description" content=""/>
    <meta name="keywords" content=""/>
    <title></title>
    <script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>
    <!-- <script src="/public/static/gf/js/jquery.min.js"></script> -->

    <style>
        body{width:100%;margin:0;padding:0;background:url('/static/system/image/750_success.jpg') no-repeat;background-size:cover;}
        header{margin-top:80px;margin-bottom:20px;font-size:22px;text-align:center;}
        table{width:74%;margin:0 13%;font-size:14px;}
        table td{height:20px;font-size:14px;}
        tr>td+td{text-align:right;}
        table tr{height:30px;}
    </style>
</head>
<body>
    <header>防伪鉴定证书</header>
    <table cellspacing="0" cellpadding="0" >
        <tr>
            <td width="31%">租直NO</td>
            <td>{$find['szno']}</td>
        </tr>
        <tr>
            <td>房屋编号</td>
            <td>{$find['house_number']}</td>
        </tr>
        <tr>
            <td>楼栋地址</td>
            <td>{$find['ban_address']}</td>
        </tr>
        <tr>
            <td>结构类别</td>
            <td>{$detail['applyStruct']}</td>
        </tr>
        <tr>
            <td>房屋层</td>
            <td>{$find['ban_floors']}</td>
        </tr>
        <tr>
            <td>居住层</td>
            <td>{$find['house_floor_id']}</td>
        </tr>
        <tr>
            <td>承租人姓名</td>
            <td>{$find['tenant_name']}</td>
        </tr>
        <tr>
            <td>承租人身份证</td>
            <td>{$detail['applyRentNumber']}</td>
        </tr>
        <tr>
            <td>租约签订日期</td>
            <td>{$date}</td>
        </tr>
        <tr style="height:100px;">
            <td colspan="2" style="position:relative;text-align:right;">
                武汉市住房保障和房屋管理局
                <img style="width:90px;position:absolute;top:20px;right:20%;" src="/static/system/image/zhang08.png" />
            </td>
        </tr>
    </table>
</body>
</html>

EOF;
    
    echo $info;
            }else{
$info = <<<EOF
            
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE=edge,
    chrome=1" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="Description" content=""/>
    <meta name="keywords" content=""/>
    <title></title>
    <script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>
    <!-- <script src="/public/static/gf/js/jquery.min.js"></script> -->

    <style>
        body{width:100%;margin:0;padding:0;background:url('/static/system/image/750_fail.jpg') no-repeat;background-size:cover;}
        header{margin-top:80px;margin-bottom:20px;font-size:22px;text-align:center;}
        table{width:74%;margin:0 13%;font-size:14px;}
        table td{height:20px;font-size:14px;}
        tr>td+td{text-align:right;}
        table tr{height:30px;}
    </style>
</head>
<body>
    <header>防伪鉴定证书</header>
    <table cellspacing="0" cellpadding="0" >
        <tr>
            <td style="height:230px;font-size:16px;text-align:center;">此租约鉴定无效</td>
        </tr>
        <tr style="height:100px;">
            <td colspan="2" style="position:relative;text-align:right;">
                武汉市住房保障和房屋管理局
                <img style="width:90px;position:absolute;top:20px;right:20%;" src="/static/system/image/zhang08.png" />
            </td>
        </tr>
    </table>
</body>
</html>

EOF;

    echo $info;            
            }
            
            
        }else{
$info = <<<EOF
            
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE=edge,
    chrome=1" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="Description" content=""/>
    <meta name="keywords" content=""/>
    <title></title>
    <script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>
    <!-- <script src="/public/static/gf/js/jquery.min.js"></script> -->

    <style>
        body{width:100%;margin:0;padding:0;background:url('/static/system/image/750_fail.jpg') no-repeat;background-size:cover;}
        header{margin-top:80px;margin-bottom:20px;font-size:22px;text-align:center;}
        table{width:74%;margin:0 13%;font-size:14px;}
        table td{height:20px;font-size:14px;}
        tr>td+td{text-align:right;}
        table tr{height:30px;}
    </style>
</head>
<body>
    <header>防伪鉴定证书</header>
    <table cellspacing="0" cellpadding="0" >
        <tr>
            <td style="height:230px;font-size:16px;text-align:center;">此租约鉴定无效</td>
        </tr>
        <tr style="height:100px;">
            <td colspan="2" style="position:relative;text-align:right;">
                武汉市住房保障和房屋管理局
                <img style="width:90px;position:absolute;top:20px;right:20%;" src="/static/system/image/zhang08.png" />
            </td>
        </tr>
    </table>
</body>
</html>

EOF;

    echo $info;
        }
        
    }
}