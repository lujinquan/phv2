<?php

namespace app\deal\admin;

use app\system\admin\Admin;

/**
 * 减免
 */
class Changecut extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            
        }
        $group = input('group','x');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '租金减免',
                'url' => '?group=x',
            ],
            [
                'title' => '租金减免年审',
                'url' => '?group=y',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        //$this->assign('ban_number',input('param.ban_number',''));
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch('index_'.$group);
    }

    public function apply()
    {
    	if ($this->request->isAjax()) {
            
        }
        $group = input('group','x');
        return $this->fetch('apply_'.$group);
    }

    public function record()
    {
    	if ($this->request->isAjax()) {
            
        }
        $group = input('group','x');
        $tabData = [];
        $tabData['menu'] = [
            [
                'title' => '租金减免',
                'url' => '?group=x',
            ],
            [
                'title' => '租金减免年审',
                'url' => '?group=y',
            ],
        ];
        $tabData['current'] = url('?group='.$group);
        //$this->assign('ban_number',input('param.ban_number',''));
        $this->assign('group',$group);
        $this->assign('hisiTabData', $tabData);
        $this->assign('hisiTabType', 3);
        return $this->fetch('record_'.$group);
    }
}