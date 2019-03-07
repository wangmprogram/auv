<?php
/**
 *后台-首页显示
 */
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;

class Index extends Pub
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        // echo 123;
        //商品数据
        $goods = Db::name('goods')
                    ->where('is_on_sale',1)
                    ->select();
        $this->assign('goods',$goods);
        return $this->fetch('Index/index');
    }

}
