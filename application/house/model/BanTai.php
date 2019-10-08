<?php
namespace app\house\model;

use app\system\model\SystemBase;

class BanTai extends SystemBase
{
	// 设置模型名称
    protected $name = 'ban_tai';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $type = [
        'ctime' => 'timestamp:Y-m-d H:i:s',
        'data_json' => 'json',
    ];

    public function SystemUser()
    {
        return $this->hasOne('app\system\model\SystemUser', 'id', 'cuid')->bind('nick');
    }

    public function checkWhere($data)
    {
        if(!$data){
            $data = request()->param();
        }
        $where = [];
     	// 检索房屋编号
        
        $where[] = ['ban_id','eq',$data['id']];
        
        // 检索业务类型
        if(isset($data['ban_tai_type']) && $data['ban_tai_type']){
            $where[] = ['ban_tai_type','eq',$data['ban_tai_type']];
        }
        // 检索描述内容
        if(isset($data['ban_tai_remark']) && $data['ban_tai_remark']){
            $where[] = ['ban_tai_remark','like','%'.$data['ban_tai_remark'].'%'];
        }
        return $where;
    }

    public static function store($data)
    {

    }
}