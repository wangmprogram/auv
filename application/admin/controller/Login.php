<?php
/**
 * 后台-后台登陆控制器
 */
namespace app\Admin\controller;
use think\Controller;
use think\Request;
use think\captcha\Captcha;
use think\Session;
use think\Db;

class Login extends Controller
{
    /**
     * 登陆
     */
    public function login()
    {
        if(Session::get('admin_info.admin_id')){
            $this->redirect('Admin/Index/index',302);
        }
        return view();
    }
    /**
     * 处理登陆
     */
    public function dologin(Request $request)
    {
        $user_name = input('post.user_name');
        $password = input('post.password');
        $verify = input('post.verify');
        // 校验验证码
        $captcha = new Captcha();
        if(!$captcha->check($verify, 'admin_login')){
            return ['status'=>0, 'msg'=>'验证码'];
        }
        if (empty($user_name) || empty($user_name)) {
            return ['status' => 0, 'msg' => '请填写账号密码'];
        }
        // 查询
        $auth = Db::name('admin')
                        ->alias('a')
                        ->field('a.*,r.role_name')
                        ->join('__ADMIN_ROLE__ r','a.role_id = r.role_id')
                        ->where('user_name', $user_name)
                        ->find();
        if(!$auth){
            
            return ['status' => 0, 'msg' => '账号不存在!'];
        }
        if($auth['is_lock'] == 0){
            return ['status' => 0, 'msg' => '账号已锁定，请联系超管!'];
        }
        if($auth['password'] != md5($password)){
            return ['status' => 0, 'msg' => '密码不正确!'];
        }
        // 保存登陆状态
        Session::set('admin_info', $auth);
        // 更新登陆状态
        $data['last_login'] = time();
        $data['last_ip'] = $request->ip();
        $data['login_count'] = Db::raw('login_count+1');
        Db::name('admin')
                    ->where('admin_id', $auth['admin_id'])
                    ->update($data);
        // 
        return ['status'=>1, 'url'=> url('Admin/Index/index')];
    }
    /**
     * 退出
     */
    public function logout()
    {
        Session::clear();
        $this->redirect('Login/login', 302);
    }
    /**
     * 验证码
     */
    public function verify()
    {
        $config =    [
            'fontSize'      => 18,    
            'length'        => 4,   
            'useNoise'      => false, 
            'imageH'        => 35
        ];
        $captcha = new Captcha($config);
        return $captcha->entry('admin_login');
    }
}
