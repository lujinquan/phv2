<?php

namespace app\system\admin;

use app\common\controller\Common;
use app\system\model\SystemAffiche as AfficheModel;
use app\system\model\SystemHelp;
use app\system\model\SystemNotice;
use app\common\model\Cparam as CparamModel;
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
                                    //dump($arrTemp);halt(session('admin_user.uid'));
                                    $arrTemp[] = session('admin_user.uid');
            				$readUsers = array_filter($arrTemp);
                                    //halt($readUsers);
            				$readUsers = '|'.implode('|',$readUsers).'|';
            				$afficheModel->where([['id','eq',$id]])->update(['read_users'=>$readUsers]);
            			}
            		}else{
            			$readUsers = '|'.session('admin_user.uid').'|';
            			$afficheModel->where([['id','eq',$id]])->update(['read_users'=>$readUsers]);
            		}
            		
            		
            		$data = [];
                        $row['create_time'] = tranTime($row['create_time']);
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

      public function helpdoc()
      {
          $systemHelp = new SystemHelp;
          $docs = $systemHelp->select();

          $nodes = [];
          $types = CparamModel::getCparams('help_type');
          //halt($types);
          foreach($docs as $d){
            $nodes[$d['type']-1]['name'] = $types[$d['type']];
            $nodes[$d['type']-1]['spread'] = true;
            $nodes[$d['type']-1]['id'] = $d['type'];
            $nodes[$d['type']-1]['alias'] = $d['type'];
            $nodes[$d['type']-1]['name'] = $types[$d['type']];
            $nodes[$d['type']-1]['children'][] = [
                'name' => $d['title'],
                'id' => $d['id'],
                'alias' => $d['type'].$d['id'],
                'content' => htmlspecialchars_decode($d['content'])
                //'content' => $d['content']
            ];
          }
          //halt($nodes);
            $data = [];
            $data['data'] = $nodes;
            // $data['data']  =  [
            //                         [
            //                               'name'=> '常见问题',
            //                               'spread'=>true,
            //                               'id'=> 1,
            //                               'alias'=> 'changjianwentyi',
            //                               'children'=> [
            //                               [
            //                                 'name'=> '问题1（设置跳转）',
            //                                 'id'=> 11,
            //                                 'alias'=> 'wenti1',
            //                                 'content'=> 'content1'
            //                               ], 
            //                               [
            //                                 'name'=> '问题2',
            //                                 'id'=> 12,
            //                                 'alias'=> 'wenti2',
            //                                 'content'=> 'content2'
            //                               ]
            //                             ],

            //                         ],
            //                         [
            //                               'name'=> '产品使用',
            //                               'spread'=>true,
            //                               'id'=> 2,
            //                               'alias'=> 'changjianwentyi',
            //                               'children'=> [
            //                               [
            //                                 'name'=> '产品使用1',
            //                                 'id'=> 13,
            //                                 'alias'=> 'wenti1',
            //                                 'content'=> 'content3'
            //                               ], 
            //                               [
            //                                 'name'=> '产品使用2',
            //                                 'id'=> 14,
            //                                 'alias'=> 'wenti2',
            //                                 'content'=> 'content4'
            //                               ]
            //                             ],

            //                         ],

            //                   ];

            $data['msg'] = '';
            $data['code'] = 0;
            return json($data);
      }

      public function update_notice_reads()
      {
            $id = input('param.id/d');
            $systemNotice = new SystemNotice;
            $result = $systemNotice->updateReads($id);
            return $result;
      }
}