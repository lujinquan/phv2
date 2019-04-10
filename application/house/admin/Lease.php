<?php
namespace app\house\admin;
use app\system\admin\Admin;

class Lease extends Admin
{

    public function index()
    {
    	return $this->fetch();
    }
}