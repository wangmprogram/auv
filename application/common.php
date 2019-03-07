<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Request;
Request::hook('foo','func');
Request::instance()->bind("uname","admin");
Request::instance()->addr="nanjing";
function foo(){
	return "应用函数库";
}
function func(Request $request,$a){
	return $a; 
}


function gl($str){
	return htmlspecialchars(trim($str));
}
function setCategoryData($array,$parent_id=0){
	static $cats = [];
	foreach($array as $value){
		if($value['parent_id'] == $parent_id){
			$cats[] = $value;
			setCategoryData($array,$value['cat_id']);
		}
	}
	return $cats;
}