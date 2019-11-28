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

//帮助文档
//Route::rule('help/[:id]','system/index/help?flag=:id'); 
//Route::rule('help/[:id]','/help/index.html'); 
Route::rule('help/[:id]','/help/index.html'); 
Route::rule('erweima/[:name]','deal/Api/codeCert'); 