<?php
namespace app\report\admin;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;

class House extends Admin
{

	/**
	 * 房屋统计报表
	 * @return [type] [description]
	 */
    public function archives()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    /**
	 * 房屋统计报表
	 * @return [type] [description]
	 */
    public function propertys()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }
}