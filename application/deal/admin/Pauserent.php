<?php
namespace app\deal\admin;
use app\system\admin\Admin;

/**
 * 暂停计租
 */
class Pauserent extends Admin
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