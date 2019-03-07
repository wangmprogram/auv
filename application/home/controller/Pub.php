<?php
/**
 *  前台-公共头部尾部
 */
namespace app\home\controller;
use think\Request;
use think\Db;
use think\Controller;
use think\Session;

class Pub extends Controller
{
	public function _initialize()
	{
		$article_cat = Db::name('article_cat')->where('show_in_nav','1')->select();
		$article = Db::name('article')->where('is_open','1')->select();
		$link = Db::name('friend_link')->select();
		$this->assign('article_cat',$article_cat);
		$this->assign('article',$article);
		$this->assign('link',$link);
        //头部/一级菜单
        $category1 = Db::name('goods_category')
            ->where('level',1)
            ->limit(8)
            ->select();
        //二级菜单
        $category2 = Db::name('goods_category')
            ->where('level',2)
            ->select();
        //三级菜单
        $category3 = Db::name('goods_category')
            ->where('level',3)
            ->select();
        //搜索关键字
        $keywords = Db::name('search_word')
                            ->order('search_num desc')
                            ->limit(5)
                            ->select();
        $this->assign('category1',$category1);
        $this->assign('category2',$category2);
        $this->assign('category3',$category3);
        $this->assign('keywords',$keywords);
        //遍历购物车
        if($user_id=Session::get('user_info.user_id')){
            $num = Db::name('cart')->where('user_id',$user_id)->count();
            $this->assign('num',$num);
        }
        //遍历网站配置
        $config = Db::name('sysconfig')->select();
        $sysconfig = []; 
        foreach($config as $key=>$vo){
            // $sysconfig[$vo['varname']] => $vo['value']; 
            $this->assign($vo['varname'],$vo['value']);
        }

    	return view();
	}
	

}