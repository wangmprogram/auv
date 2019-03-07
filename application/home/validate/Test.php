<?php

namespace app\index\validate;
use think\Validate;
class Test extends Validate{
	protected $rule = [
		'name'=>'require|max:5',
		'age'=>'require|number|gt:20',
		'addr'=>'require|chs'
	];
	protected  $message = [];
}