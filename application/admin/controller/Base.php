<?php
/**
 *  后台基础控制器
 */

namespace app\admin\controller;
use think\Request;
use think\Controller;
use think\Db;
use think\Session;

class Base extends Controller
{
    public function _initialize(){
        if(!Session::get('admin_info.admin_id')){
            $this->redirect('Login/Login', 302);
        }
    }
}
