<?php
namespace app\deal\admin;
use app\system\admin\Admin;

class Index extends Admin
{
    protected $hisiModel = '';//模型名称[通用添加、修改专用]
    protected $hisiTable = '';//表名称[通用添加、修改专用]
    protected $hisiAddScene = '';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名

    public function index()
    {
        return $this->fetch();
    }
}