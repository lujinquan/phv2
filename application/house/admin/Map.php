<?php
namespace app\house\admin;
use app\system\admin\Admin;
use app\house\model\Ban as BanModel;
use app\common\model\Cparam as ParamModel;

class Map extends Admin
{
	public function index()
	{
		return $this->fetch();
	}
}