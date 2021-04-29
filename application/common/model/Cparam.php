<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | Motto ：No pains, no gains !
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 系统配置模型
 * @package app\admin\model
 */
class Cparam extends Model
{
    // 设置模型名称
    protected $name = 'cparam';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'mtime';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    //protected $field = ['group','sort','name','value','options','status'];

    /**
     * 获取某类型的(下拉列)
     * @param int $id 选中的ID
     * @param string $name 类型名称
     * @author Lucas <598936602@qq.com>
     * @return string
     */
    public static function getOption($name = '', $id = 0)
    {
        $o = self::where('name', $name)->value('options');
        $rows = parse_attr($o);
        $str = '';
        foreach ($rows as $k => $v) {
            if ($id == $k) {
                $str .= '<option value="' . $k . '" selected>' . $v . '</option>';
            } else {
                $str .= '<option value="' . $k . '">' . $v . '</option>';
            }
        }
        return $str;
    }

    /**
     * 获取系统配置信息
     * @param  string $name 配置名
     * @param  bool $update 是否更新缓存
     * @author Lucas <598936602@qq.com>
     * @return mixed
     */
    public static function getCparams($name = '', $update = false)
    {
        $result = cache('cparams');
        if (!$result || $update == true) {
            $configs = self::column('type,options', 'name');
            //halt($configs);
            $result = [];
            foreach ($configs as $config) {
                switch ($config['type']) {
                    case 'array':
                        // 待完善
                    case 'radio':
                        // 待完善
                    case 'select':
                        $result[$config['name']] = parse_attr($config['options']);

                       
                        $result['help_type'] = Db::name('system_help_type')->order('sort asc,id asc')->column('id,type_name');
                    case 'checkbox':

                        // if ($config['name'] == 'config_group') {
                        //     $v = parse_attr($config['value']);
                        //     if (!empty($config['value'])) {
                        //         $result[$config['group']][$config['name']] = array_merge(config('hs_system.config_group'), $v);
                        //     } else {
                        //         $result[$config['group']][$config['name']] = config('hs_system.config_group');
                        //     }
                        // } else {
                        //     $result[$config['group']][$config['name']] = parse_attr($config['value']);
                        // }
                        break;
                    case 'json':
                        $result[$config['name']] = json_decode($config['options']);
                    default:
                        //$result[$config['group']][$config['name']] = $config['value'];
                        break;
                }
            }
            // cache('cparams', $result);
        }
        return $name != '' ? $result[$name] : $result;
    }

    // public static function paramsParse($data = [])
    // {
    //     $params = self::getCparams();

    //     $keys = ['inst'=>'inst_all','damage'=>'damages','struct'=>'structs','owner'=>'owners','usenature'=>'uses'];

    //     foreach(){
            
    //     }

    //     halt($data);
    // }
    /**
     * 删除配置
     * @param string|array $id 节点ID
     * @author Lucas <598936602@qq.com>
     * @return bool
     */
    public function del($ids = '')
    {
        if (is_array($ids)) {
            $error = '';
            foreach ($ids as $k => $v) {
                $map = [];
                $map['id'] = $v;
                $row = self::where($map)->find();
                if ($row['system'] == 1) {
                    $error .= '[' . $row['title'] . ']为系统配置，禁止删除！<br>';
                    continue;
                }
                self::where($map)->delete();
            }
            if ($error) {
                $this->error = $error;
                return false;
            }
            return true;
        }
        $this->error = '参数传递错误';
        return false;
    }
}
