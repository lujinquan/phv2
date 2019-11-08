<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [

    // 应用调试模式
    'app_debug'              => false,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => ['plugins' => Env::get('root_path'). 'plugins/'],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'htmlspecialchars_decode,htmlspecialchars,trim',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 是否强制使用路由
    'url_route_must'         => false,
    // 使用注解路由
    'route_annotation'       => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    // 'url_controller_layer'   => 'controller',
    'url_controller_layer'   => defined('ENTRANCE') ? 'admin' : 'home',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => Env::get('app_path') . 'system/view/block/dispatch_jump.tpl',
    'dispatch_error_tmpl'    => Env::get('app_path') . 'system/view/block/dispatch_jump.tpl',

    // 异常页面的模板文件
    'exception_tmpl'         => Env::get('app_path') . 'system/view/block/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    //机构查询
    'inst_ids' => [
        '1' => [4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33],
        '2' => [4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,34],
        '3' => [19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,35],
        '4' => [4],
        '5' => [5],
        '6' => [6],
        '7' => [7],
        '8' => [8],
        '9' => [9],
        '10' => [10],
        '11' => [11],
        '12' => [12],
        '13' => [13],
        '14' => [14],
        '15' => [15],
        '16' => [16],
        '17' => [17],
        '18' => [18],
        '19' => [19],
        '20' => [20],
        '21' => [21],
        '22' => [22],
        '23' => [23],
        '24' => [24],
        '25' => [25],
        '26' => [26],
        '27' => [27],
        '28' => [28],
        '29' => [29],
        '30' => [30],
        '31' => [31],
        '32' => [32],
        '33' => [33],
        '34' => [34],
        '35' => [35],
    ],
    //机构页面展示
    'inst_check_names' => [
        '1' => ['1'=>'武昌区公司','2'=>'紫阳所','3'=>'粮道所','4'=>'紫阳所01管段','5'=>'紫阳所02管段','6'=>'紫阳所03管段','7'=>'紫阳所04管段','8'=>'紫阳所05管段','9'=>'紫阳所06管段','10'=>'紫阳所07管段','11'=>'紫阳所08管段','12'=>'紫阳所09管段','13'=>'紫阳所10管段','14'=>'紫阳所11管段','15'=>'紫阳所12管段','16'=>'紫阳所13管段','17'=>'紫阳所14管段','18'=>'紫阳所15管段','34'=>'紫阳所16管段','19'=>'粮道所01管段','20'=>'粮道所02管段','21'=>'粮道所03管段','22'=>'粮道所04管段','23'=>'粮道所05管段','24'=>'粮道所06管段','25'=>'粮道所07管段','26'=>'粮道所08管段','27'=>'粮道所09管段','28'=>'粮道所10管段','29'=>'粮道所11管段','30'=>'粮道所12管段','31'=>'粮道所13管段','32'=>'粮道所14管段','33'=>'粮道所15管段','35'=>'粮道所16管段'],
        '2' => ['2'=>'紫阳所','4'=>'紫阳所01管段','5'=>'紫阳所02管段','6'=>'紫阳所03管段','7'=>'紫阳所04管段','8'=>'紫阳所05管段','9'=>'紫阳所06管段','10'=>'紫阳所07管段','11'=>'紫阳所08管段','12'=>'紫阳所09管段','13'=>'紫阳所10管段','14'=>'紫阳所11管段','15'=>'紫阳所12管段','16'=>'紫阳所13管段','17'=>'紫阳所14管段','18'=>'紫阳所15管段','34'=>'紫阳所16管段'],
        '3' => ['3'=>'粮道所','19'=>'粮道所01管段','20'=>'粮道所02管段','21'=>'粮道所03管段','22'=>'粮道所04管段','23'=>'粮道所05管段','24'=>'粮道所06管段','25'=>'粮道所07管段','26'=>'粮道所08管段','27'=>'粮道所09管段','28'=>'粮道所10管段','29'=>'粮道所11管段','30'=>'粮道所12管段','31'=>'粮道所13管段','32'=>'粮道所14管段','33'=>'粮道所15管段','35'=>'粮道所16管段'],
        '4' => ['4'=>'紫阳所01管段'],
        '5' => ['5'=>'紫阳所02管段'],
        '6' => ['6'=>'紫阳所03管段'],
        '7' => ['7'=>'紫阳所04管段'],
        '8' => ['8'=>'紫阳所05管段'],
        '9' => ['9'=>'紫阳所06管段'],
        '10' => ['10'=>'紫阳所07管段'],
        '11' => ['11'=>'紫阳所08管段'],
        '12' => ['12'=>'紫阳所09管段'],
        '13' => ['13'=>'紫阳所10管段'],
        '14' => ['14'=>'紫阳所11管段'],
        '15' => ['15'=>'紫阳所12管段'],
        '16' => ['16'=>'紫阳所13管段'],
        '17' => ['17'=>'紫阳所14管段'],
        '18' => ['18'=>'紫阳所15管段'],
        '19' => ['19'=>'粮道所01管段'],
        '20' => ['20'=>'粮道所02管段'],
        '21' => ['21'=>'粮道所03管段'],
        '22' => ['22'=>'粮道所04管段'],
        '23' => ['23'=>'粮道所05管段'],
        '24' => ['24'=>'粮道所06管段'],
        '25' => ['25'=>'粮道所07管段'],
        '26' => ['26'=>'粮道所08管段'],
        '27' => ['27'=>'粮道所09管段'],
        '28' => ['28'=>'粮道所10管段'],
        '29' => ['29'=>'粮道所11管段'],
        '30' => ['30'=>'粮道所12管段'],
        '31' => ['31'=>'粮道所13管段'],
        '32' => ['32'=>'粮道所14管段'],
        '33' => ['33'=>'粮道所15管段'],
        '34' => ['34'=>'紫阳所16管段'],
        '35' => ['35'=>'粮道所16管段'],
    ],
    //信息新增页面展示【只有管段】
    'inst_data_names' => [
        '1' => ['4'=>'紫阳所01管段','5'=>'紫阳所02管段','6'=>'紫阳所03管段','7'=>'紫阳所04管段','8'=>'紫阳所05管段','9'=>'紫阳所06管段','10'=>'紫阳所07管段','11'=>'紫阳所08管段','12'=>'紫阳所09管段','13'=>'紫阳所10管段','14'=>'紫阳所11管段','15'=>'紫阳所12管段','16'=>'紫阳所13管段','17'=>'紫阳所14管段','18'=>'紫阳所15管段','34'=>'紫阳所16管段','19'=>'粮道所01管段','20'=>'粮道所02管段','21'=>'粮道所03管段','22'=>'粮道所04管段','23'=>'粮道所05管段','24'=>'粮道所06管段','25'=>'粮道所07管段','26'=>'粮道所08管段','27'=>'粮道所09管段','28'=>'粮道所10管段','29'=>'粮道所11管段','30'=>'粮道所12管段','31'=>'粮道所13管段','32'=>'粮道所14管段','33'=>'粮道所15管段','35'=>'粮道所16管段'],
        '2' => ['2'=>'紫阳所','4'=>'紫阳所01管段','5'=>'紫阳所02管段','6'=>'紫阳所03管段','7'=>'紫阳所04管段','8'=>'紫阳所05管段','9'=>'紫阳所06管段','10'=>'紫阳所07管段','11'=>'紫阳所08管段','12'=>'紫阳所09管段','13'=>'紫阳所10管段','14'=>'紫阳所11管段','15'=>'紫阳所12管段','16'=>'紫阳所13管段','17'=>'紫阳所14管段','18'=>'紫阳所15管段','34'=>'紫阳所16管段'],
        '3' => ['3'=>'粮道所','19'=>'粮道所01管段','20'=>'粮道所02管段','21'=>'粮道所03管段','22'=>'粮道所04管段','23'=>'粮道所05管段','24'=>'粮道所06管段','25'=>'粮道所07管段','26'=>'粮道所08管段','27'=>'粮道所09管段','28'=>'粮道所10管段','29'=>'粮道所11管段','30'=>'粮道所12管段','31'=>'粮道所13管段','32'=>'粮道所14管段','33'=>'粮道所15管段','35'=>'粮道所16管段'],
        '4' => ['4'=>'紫阳所01管段'],
        '5' => ['5'=>'紫阳所02管段'],
        '6' => ['6'=>'紫阳所03管段'],
        '7' => ['7'=>'紫阳所04管段'],
        '8' => ['8'=>'紫阳所05管段'],
        '9' => ['9'=>'紫阳所06管段'],
        '10' => ['10'=>'紫阳所07管段'],
        '11' => ['11'=>'紫阳所08管段'],
        '12' => ['12'=>'紫阳所09管段'],
        '13' => ['13'=>'紫阳所10管段'],
        '14' => ['14'=>'紫阳所11管段'],
        '15' => ['15'=>'紫阳所12管段'],
        '16' => ['16'=>'紫阳所13管段'],
        '17' => ['17'=>'紫阳所14管段'],
        '18' => ['18'=>'紫阳所15管段'],
        '19' => ['19'=>'粮道所01管段'],
        '20' => ['20'=>'粮道所02管段'],
        '21' => ['21'=>'粮道所03管段'],
        '22' => ['22'=>'粮道所04管段'],
        '23' => ['23'=>'粮道所05管段'],
        '24' => ['24'=>'粮道所06管段'],
        '25' => ['25'=>'粮道所07管段'],
        '26' => ['26'=>'粮道所08管段'],
        '27' => ['27'=>'粮道所09管段'],
        '28' => ['28'=>'粮道所10管段'],
        '29' => ['29'=>'粮道所11管段'],
        '30' => ['30'=>'粮道所12管段'],
        '31' => ['31'=>'粮道所13管段'],
        '32' => ['32'=>'粮道所14管段'],
        '33' => ['33'=>'粮道所15管段'],
        '34' => ['34'=>'紫阳所16管段'],
        '35' => ['35'=>'粮道所16管段'],
    ],

    //租约申请里面的序列化字段
    'apply_columns' => ['applyAddress','applyStruct','applyHouseFloor','applyLiveFloor','applyRentName','applyRentNumber','applyRentTel','applyRentName1','applyRentNumber1','applyRepresent','applyYear','applyMonth','applyDay','applyRoom1_data1','applyRoom1_data2','applyRoom1_data3','applyRoom1_data4','applyRoom1_data5','applyRoom1_data6','applyRoom2_data1','applyRoom2_data2','applyRoom2_data3','applyRoom2_data4','applyRoom2_data5','applyRoom2_data6','applyRoom3_data1','applyRoom3_data2','applyRoom3_data3','applyRoom3_data4','applyRoom3_data5','applyRoom3_data6','applyRoom4_data1','applyRoom4_data2','applyRoom4_data3','applyRoom4_data4','applyRoom4_data5','applyRoom4_data6','applyRoom5_data1','applyRoom5_data2','applyRoom5_data3','applyRoom5_data4','applyRoom5_data5','applyRoom5_data6','applyRoom5_data7','applyRoom6_data1','applyRoom6_data2','applyRoom6_data3','applyRoom6_data4','applyRoom6_data5','applyRoom6_data6','applyRoom6_data7','applyRoom7_data1','applyRoom7_data2','applyRoom7_data3','applyRoom7_data4','applyRoom7_data5','applyRoom7_data8','applyRoom7_data9','applyRoom8_data1','applyRoom8_data2','applyRoom8_data3','applyRoom8_data4','applyRoom8_data5','applyRoom8_data6','applyRoom8_data7','applyRoom8_data8','applyRoom8_data9','applyRoom9_data1','applyRoom9_data2','applyRoom9_data3','applyRoom9_data4','applyRoom9_data5','applyRoom10_data1','applyRoom10_data2','applyRoom10_data3','applyRoom10_data4','applyRoom10_data5','applyRoom11_data1','applyRoom11_data2','applyRoom11_data3','applyRoom11_data4','applyRoom11_data5','applyRoom12_data1','applyRoom12_data2','applyRoom12_data3','applyRoom12_data4','applyRoom12_data5','applyRoom12_data6','applyRoom12_data7','applyRoom12_data8','applyRoom12_data9','applyRoom13_data1','applyRoom13_data2','applyRoom13_data3','applyRoom13_data4','applyRoom13_data5','applyRoom13_data6','applyRoom13_data7','applyRoom13_data8','applyRoom14_data1','applyRoom14_data2','applyRoom14_data3','applyRoom14_data4','applyRoom14_data5','applyRoom14_data6','applyRoom14_data7','applyRoom14_data8','applyRoom15_data1','applyRoom15_data2','applyRoom15_data3','applyRoom15_data4','applyRoom15_data5','applyRoom15_data6','applyRoom15_data7','applyRoom15_data8','applyRoom16_data1','applyRoom16_data2','applyRoom16_data3','applyRoom16_data4','applyRoom16_data5','applyRoom16_data6','applyRoom16_data7','applyRoom16_data8','applyRoom17_data1','applyRoom17_data2','applyRoom17_data3','applyRoom17_data4','applyRoom17_data5','applyRoom18_data1','applyRoom18_data2','applyRoom18_data3','applyRoom18_data4','applyRoom18_data5','applyRoom19_data1','applyRoom19_data2','applyRoom19_data3','applyRoom19_data4','applyRoom19_data5','applyRoom20_data1','applyRoom20_data2','applyRoom20_data3','applyRoom20_data4','applyRoom20_data5','applyRoom21_data1','applyRoom21_data2','applyRoom21_data3','applyDev1_data1','applyDev1_data2','applyDev1_data3','applyDev1_data4','applyDev1_data5','applyDev1_data6','applyDev2_data1','applyDev2_data2','applyDev2_data3','applyDev2_data4','applyDev2_data5','applyDev2_data6','applyDev3_data1','applyDev3_data2','applyDev3_data3','applyDev3_data4','applyDev3_data5','applyDev3_data6','applyDev4_data1','applyDev4_data2','applyDev4_data3','applyDev4_data4','applyDev4_data5','applyDev4_data6','applyDev5_data1','applyDev5_data2','applyDev5_data3','applyDev5_data4','applyDev5_data5','applyDev5_data6','applyType'],

];
