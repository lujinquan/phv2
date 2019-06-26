<?php

// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------

namespace app\system\admin;
use app\common\controller\Common;
use app\system\model\SystemAffiche as AfficheModel;
use app\common\model\SystemAnnex as AnnexModel;
use app\common\model\Cparam as CparamModel;
use app\system\model\SystemNotice;
use app\system\model\SystemHelp;
use think\Db;

/**
 * 系统API控制器
 */
class Api extends Common 
{
    /**
     * 获取 一条消息提醒数据
     * @param id 消息id
     * @return json
     */
    public function getAfficheRow() 
    {
        if ($this->request->isAjax()) {
            $id = input('param.id/d', '');
            if ($id) {
                $afficheModel = new AfficheModel;
                $row = $afficheModel->get($id); //halt(session('admin_user.uid'));
                if ($row) {
                    //将阅读的人加入到已读行列中
                    if ($row['read_users']) {
                        $arrTemp = explode('|', $row['read_users']);
                        if (!in_array(session('admin_user.uid') , $arrTemp)) {
                            $arrTemp[] = session('admin_user.uid');
                            $readUsers = array_filter($arrTemp);
                            $readUsers = '|' . implode('|', $readUsers) . '|';
                            $afficheModel->where([['id', 'eq', $id]])->update(['read_users' => $readUsers]);
                        }
                    } else {
                        $readUsers = '|' . session('admin_user.uid') . '|';
                        $afficheModel->where([['id', 'eq', $id]])->update(['read_users' => $readUsers]);
                    }
                    $data = [];
                    $row['create_time'] = tranTime($row['create_time']);
                    $data['data'] = $row;
                    $data['msg'] = '';
                    $data['code'] = 0;
                    return json($data); 
                }
            } else {
                return $this->error('未知消息ID');
            }
        }
    }



    /**
     * 获取帮助文档数据
     * @return json
     */
    public function helpdoc() 
    {
        $systemHelp = new SystemHelp;
        $docs = $systemHelp->select();
        $nodes = [];
        $types = CparamModel::getCparams('help_type');
        foreach ($docs as $d) {
            $nodes[$d['type'] - 1]['name'] = $types[$d['type']];
            $nodes[$d['type'] - 1]['spread'] = true;
            $nodes[$d['type'] - 1]['id'] = $d['type'];
            $nodes[$d['type'] - 1]['alias'] = $d['type'];
            $nodes[$d['type'] - 1]['name'] = $types[$d['type']];
            $nodes[$d['type'] - 1]['children'][] = [
                'name' => $d['title'], 
                'id' => $d['id'], 
                'alias' => $d['type'] . $d['id'], 
                'content' => htmlspecialchars_decode($d['content'])
            ];
        }
        $data = [];
        $data['data'] = $nodes;
        // 模板实例如下：
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

    /**
     * 更新公告阅读记录
     * @param id 公告id
     * @return string 提示信息
     */
    public function update_notice_reads() 
    {
        $id = input('param.id/d');
        $systemNotice = new SystemNotice;
        $result = $systemNotice->updateReads($id);
        return $result;
    }

    /**
     * 附件上传
     * @param string $from 来源
     * @param string $group 附件分组,默认sys[系统]，模块格式：m_模块名，插件：p_插件名
     * @param string $water 水印，参数为空默认调用系统配置，no直接关闭水印，image 图片水印，text文字水印
     * @param string $thumb 缩略图，参数为空默认调用系统配置，no直接关闭缩略图，如需生成 500x500 的缩略图，则 500x500多个规格请用";"隔开
     * @param string $thumb_type 缩略图方式
     * @param string $input 文件表单字段名
     * @author Lucas <598936602@qq.com>
     * @return json
     */
    public function upload($from = 'input', $group = 'sys', $water = '', $thumb = '', $thumb_type = '', $input = 'file')
    {
        return json(AnnexModel::upload($from, $group, $water, $thumb, $thumb_type, $input));
    }

}