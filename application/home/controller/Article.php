<?php
/**
 *  前台-文章管理
 */
namespace app\home\controller;
use think\Request;
use think\Db;
use think\Controller;

class Article extends Pub
{
	public function detail()
	{
		$article_id = input('article_id');
		$article = Db::name('article')->where('article_id',$article_id)->find();
		$this->assign('art',$article);
		return view();
	}

}