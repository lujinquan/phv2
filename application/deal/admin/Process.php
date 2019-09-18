<?php
namespace app\deal\admin;
use app\system\admin\Admin;

/**
 * 审核
 */
class Process extends Admin
{

    public function index()
    {
    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    /**
     * @title 审核(此处的审核有别与补充资料)
     * @author Mr.Lu
     * @description
     */
    public function process(){
   
        if($this->request->isPost()) {
            if(DATA_DEBUG){
                return jsons('3000' ,'数据调试中，暂时无法进行相关业务');
            }

            $data = $this->request->post();

            model('ph/LeaseAudit')->check_process($data['ChangeOrderID']);

            if(!isset($data['reson'])) $data['reson']='';
       
            $result = model('ph/LeaseAudit')->create_child_order($data['ChangeOrderID'], $data['reson']);

            if($result === true){

                return jsons('2000' ,'审核完成');
            }else{

                return jsons('4000' ,'审核异常');
            }
            
        }

    }


}