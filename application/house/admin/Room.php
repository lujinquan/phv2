<?php
namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\common\model\Cparam as ParamModel;

class Room extends Admin
{

    public function index()
    {   

    }

    public function add()
    {   
    	return $this->fetch('add');
    }
}