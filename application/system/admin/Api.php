<?php

namespace app\system\admin;

use app\common\controller\Common;
use app\system\model\SystemAffiche as AfficheModel;
use think\Db;

/**
 * 后台公共控制器
 * @package app\system\admin
 */
class Api extends Common
{
	public function getAfficheRow()
	{
		if ($this->request->isAjax()) {
            $id = input('param.id/d', '');
            if($id){
            	$afficheModel = new AfficheModel;
            	$row = $afficheModel->get($id);//halt(session('admin_user.uid'));
            	if($row){
            		//将阅读的人加入到已读行列中
            		//$readUsers = $afficheModel->appendReadId();
            		if($row['read_users']){
            			$arrTemp = explode('|',$row['read_users']);
            			if(!in_array(session('admin_user.uid'),$arrTemp)){
            				$readUsers = array_filter(array_push($arrTemp,session('admin_user.uid')));
            				$readUsers = '|'.implode('|',$readUsers).'|';
            				$afficheModel->where([['id','eq',$id]])->update(['read_users'=>$readUsers]);
            			}
            			
            		}else{
            			$readUsers = '|'.session('admin_user.uid').'|';
            			$afficheModel->where([['id','eq',$id]])->update(['read_users'=>$readUsers]);
            		}
            		
            		
            		$data = [];
            		$data['data'] = $row;
            		$data['msg'] = '';
            		$data['code'] = 0;
            		return json($data);
            		//return $this->success('获取成功！',$row);
            	}
            }else{
            	return $this->error('未知消息ID');
            }
        }
	}
}