<?php
/**
 *后台-注册显示
 */
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;

class Reg extends Pub
{
    public function reg(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $data['reg_time'] = time();
            $data['level'] = 1;
            $data['is_lock']=1;
            $data['password'] = md5($data['password']);
            $name = Db::name('users')->field('nickname')->where('nickname',$data['nickname'])->find();
            $phone = Db::name('users')->field('phone')->where('phone',$data['phone'])->find();
            $email = Db::name('users')->field('email')->where('phone',$data['email'])->find();
            if($name){
                return ['code'=>0, 'msg'=>'用户名已存在'];
            }
            if($phone){
                return ['code'=>0, 'msg'=>'电话号码已被注册'];
            }
            if($email){
                return ['code'=>0, 'msg'=>'邮箱号已被注册'];
            }
            $user = Db::name('users')->insert($data);
            if($user){
                return ['code'=>1,'msg'=>'注册成功','url'=>url('Login/login')];
            }else{
                return ['code'=>0,'msg'=>'注册失败'];
            }
        }else{
            return $this->fetch();
        }
    }
}
