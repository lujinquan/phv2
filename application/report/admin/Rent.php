<?php
namespace app\report\admin;
use app\system\admin\Admin;

class Rent extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    /**
     * [months 月租金报表]
     * @return [type] [description]
     */
    public function months()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    /**
     * [months 月租金分析报表]
     * @return [type] [description]
     */
    public function analyze()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }
}