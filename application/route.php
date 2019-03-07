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

// return [
//     '__pattern__' => [
//         'name' => '\w+',
//     ],
//     '[hello]'     => [
//         ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//         ':name' => ['index/hello', ['method' => 'post']],
//     ],

// ];
use think\Route;
Route::pattern([
	"id"=>'[0-9]+',
	'name'=>'[a-z]+'
	]);
// Route::rule('test','index/index/test');
// Route::rule('test/[:id]','index/index/test');
Route::rule('test/:id$','index/index/test','get|post');
//Route::rule('routevar','index/index/routevar','*',['method'=>'get','ext'=>'html']);
Route::rule('routevar/[:id]/[:name]','index/index/routevar','*',[],[]);                                                         