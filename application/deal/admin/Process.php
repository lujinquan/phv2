<?php
namespace app\deal\admin;
use app\system\admin\Admin;

class Process extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }
}