<?php
/**
 *后台-登录显示
 */
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;

class Login extends Pub
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */

    public function login(){
        return $this->fetch();
    }
    public function dologin(Request $request)
    {
        $phone = input('post.phone');
        $password = input('post.password');
        $verify = input('post.verify');
        $password = md5($password);
         //校验验证码
        $captcha = new Captcha();
        if(!$captcha->check($verify, 'user_login')){
            return ['code'=>0, 'msg'=>'验证码错误'];
        }
        if (empty($phone) || empty($password)) {
            return ['code' => 0, 'msg' => '请填写电话和密码'];
        }
        $users = Db::name('users')->where('phone',$phone)->whereOr('email',$phone)->find();
        //var_dump($users);exit;
        if($users['email']!=$phone){
            if($users['phone']!=$phone){
                return ['code'=>0, 'msg'=>'电话号码或邮箱错误'];
            }
        }
        if($users['password']!=$password){
            return ['code'=>0, 'msg'=>'密码错误'];
        }
        if($users['is_lock']==0){
            return ['code'=>0, 'msg'=>'账号已被锁定，请联系管理员'];
        }
        Session::set('user_info', $users);
        $data['last_login'] = time();
        $data['last_ip'] = $request->ip();
        Db::name('users')->where('user_id',$users['user_id'])->update($data);
        return ['code'=>1, 'url'=>url('User/userIndex')];
    }
    public function verify()
    {
        $config =    [
            'fontSize'      => 18,
            'length'        => 4,
            'useNoise'      => false,
            'imageH'        => 35,
            'useCurve'      =>false
        ];
        $captcha = new Captcha($config);
        return $captcha->entry('user_login');
    }
}
