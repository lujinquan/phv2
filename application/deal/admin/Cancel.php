<?php
namespace app\deal\admin;
use app\system\admin\Admin;

/**
 * 注销
 */
class Cancel extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    public function apply()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    public function record()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }
}