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

namespace app\house\admin;

use think\Db;
use think\Debug;
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
    /**
     * 处理异动数据【预计耗时s】
     * @return [type] [description]
     */
    public function check_data()
    {
        set_time_limit(0);
        debug('begin');
        switch (input('type')) { 
            case 1:
                $msg = $this->check_ban_data();
                break;
            case 2:
                $msg = $this->check_house_data();
                break;
            case 3:
                $msg = $this->check_room_data();
                break;
            case 4:
                $msg = $this->check_tenant_data();
                break;
            case 5:
                $msg = $this->check_rent_data();
                break;
            case 6:
                $msg = $this->check_change_data();
                break;
            case 7:
                $msg = $this->check_admin_data();
                break;
            case 8:
                $msg = $this->check_log_data();
                break;
            case 9:
                $msg = $this->check_config_data();
                break;
            case 10:
                $msg = $this->check_order_data();
                break;
            case 11:
                $msg = $this->check_report_data();
                break;
            case 12:
                $msg = $this->check_msg_data();
                break;
            default:
                return $this->error('暂未开发！');
                break;
        }
        debug('end');
        $time = floor(debug('begin','end')).'s';
        return $this->success($msg.'，耗时：'.$time);
    }

    // public function check_ban_data()
    // {
    	
    // }
    
    public function check_house_data()
    {
        $owners = ['1'=>'市','2'=>'区','3'=>'代','5'=>'自','6'=>'','7'=>''];
        $houses = Db::name('house')->alias('a')->where([['house_szno','eq','']])->join('ban d','a.ban_id = d.ban_id','left')->field('house_id,ban_inst_pid,ban_owner_id')->order('house_id asc')->select();
        foreach ($houses as $v) {
           $str = '租直昌'.$owners[$v['ban_owner_id']].'0'.$v['ban_inst_pid'].'-';
           Db::name('house')->where([['house_id','eq',$v['house_id']]])->update(['house_szno'=>$str]); 
           
        }
        return $msg = '检测完毕，无误！';
        
    } 
    

    







}