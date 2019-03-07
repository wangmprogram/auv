<?php
/**
 *后台-会员忘记密码
 */
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;

class Forget extends Pub
{
    public function forgetPwd(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $re = Db::name('users')->where('phone',$data['username'])->whereOr('email',$data['username'])->find();
            if($re){
                $pad = [
                    'password'=>md5(123456)
                ];
                $xg = Db::name('users')->where('user_id',$re['user_id'])->update($pad);
                if($xg){
                    return ['code'=>1,'msg'=>'密码已重置为123456，请尽快去个人中心安全配置修改密码','url'=>url('Login/login')];
                }else{
                    return ['code'=>0,'msg'=>'重置失败'];
                }
            }else{
                return ['code'=>0,'msg'=>'账户或邮箱不存在'];
            }
        }else{
            return view();
        }
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
        return $captcha->entry('forget_login');
    }
}