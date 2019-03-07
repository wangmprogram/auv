<?php
/**
 *前台-用户个人中心
 */
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;
use think\Paginator;

class User extends Pub
{
    /**
     * 用户-个人中心
     *
     * @return \think\Response
     */
    public function userIndex()
    {
        //用户信息
        $user_id = Session::get('user_info.user_id');
        $info = Db::name('users')->where('user_id',$user_id)->find();
        $level = Db::name('user_level')->select();
        $this->assign('info',$info);
        $this->assign('level',$level);
        //遍历订单商品
        $order = Db::name('order')
                            ->where('user_id',$user_id)
                            ->order('add_time DESC')
                            ->select();
        foreach($order as $key=>$vo){
            $goods = Db::name('order_goods')->where('order_id',$vo['order_id'])->select();
            $order[$key]['goods'] = $goods;
        }
        //dump($order);exit;
        $this->assign('order',$order);
        return $this->fetch();
    }
    /**
     *修改用户资料
     */
    public function userUserinfo(Request $request)
    {
        if($request->isPost()){
            $user_id = Session::get('user_info.user_id');
            $data = input('post.');
            $re = Db::name('users')->where('nickname',$data['nickname'])->where('user_id','neq',$user_id)->find();
            if($re){
                return ['code'=>0,'msg'=>'昵称重复'];
            }
            Db::name('users')->where('user_id',$user_id)->update($data);
                return ['code'=>1,'msg'=>'修改成功','url'=>url('User/userIndex')];

        }else{
            $user_id = Session::get('user_info.user_id');
            $info = Db::name('users')->where('user_id',$user_id)->find();
            $this->assign('info',$info);
            return $this->fetch();
        }
    }
    /**
     *会员头像处理
     */
    public function upHeaderImg()
    {
        $user_id = Session::get('user_info.user_id');
        $pic = Db::name('users')->field('head_pic')->where('user_id',$user_id)->find();
        //组装路劲
        if($pic['head_pic']!=''){
            $pic['head_pic'] = strtr($pic['head_pic'],'"\"','"/"');
            $img = UPLOADS_PATH . "static/uploads/".$pic['head_pic'];
            unlink($img);
        }
        // TP的文件上传代码
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS .'uploads');
            if($info){
                $data['head_pic'] =  $info->getSaveName();
                //var_dump($data['head_pic']);exit;
                Db::name('users')->where('user_id',$user_id)->update($data);
                return ['code'=>1, 'msg'=>'跟换成功', 'imginfo'=>$data['head_pic']];
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    /**
     *退出
     */
    public function logout()
    {
        Session::clear();
        $this->redirect('Login/login', 302);
    }
    /**
     *安全设置
     */
    public function safety(){
        $user_id = Session::get('user_info.user_id');
        $info = Db::name('users')->where('user_id',$user_id)->find();
        $this->assign('info',$info);
        return view();
    }
    /**
     *修改密码
     */
    public function password(Request $request){
        if($request->isPost()){
            $user_id = Session::get('user_info.user_id');
            $data = input('post.');
            $pad = Db::name('users')->field('password')->where('user_id',$user_id)->find();
            $data['password'] = md5($data['old_password']);
            if($pad['password']!= $data['password']){
                return ['code'=>0, 'msg'=>'当前密码与您账号密码不一致'];
            }
            $datapad['password'] = md5($data['new_password']);
            $re = Db::name('users')->where('user_id',$user_id)->update($datapad);
            if($re){
                return  ['code'=>1, 'msg'=>'修改成功,请重新登录','url'=>url('User/logout')];
            }else{
                return  ['code'=>0, 'msg'=>'失败'];
            }
        }else{
            return view();
        }
    }
    /**
     *地址管理
     */
    public function userAddressList()
    {
        $user_id = Session::get('user_info.user_id');
            $infos = Db::name('user_address')->where('user_id',$user_id)->select();
            foreach ($infos as $key=>$vo) {
                $area = Db::name('region')->field('name')->where('id','in',[$vo['province'],$vo['city'],$vo['district'],
                    $vo['twon']])
                    ->select();
                $info = '';
            foreach($area as $v){
                $info.=$v['name'].',';
            }
            $info .= $vo['address'];
            $infos[$key]['full_address'] =$info;
        }
        $this->assign('infos',$infos);
        return $this->fetch();
    }
    /**
     *添加地址页面
     */
    public  function addAddress(){
            $city = Db::name('region')->where('parent_id',0)->select();
            $this->assign('city',$city);
            return view();
    }
    /**
     *添加地址功能
     */
    public function Address(Request $request){
        if($request->isPost()){
            $user_id = Session::get('user_info.user_id');
            $data = input('post.');
            $data['user_id'] = $user_id;
            if(!Db::name('user_address')->where('user_id',$user_id)->count()){
                $data['is_default'] = 1;
            }
            $re = Db::name('user_address')->insert($data);
            if($re){
                return  ['code'=>1, 'msg'=>'添加成功','url'=>url('User/editAddress')];
            }else{
                return  ['code'=>0,'msg'=>'添加失败'];
            }
        }
    }
    /**
     *地区联动-市
     */
    public function city(){
        $parent_id = input('get.parent_id');
        $citys = Db::name('region')->where('parent_id',$parent_id)->select();
        $html = "";
        foreach($citys as $v){
            $html .='<option value="'.$v['id'].'">'.$v['name'].'</option>';
        }
        return ['data'=>$html];
    }
    /**
     *地区联动-县
     */
    public function district(){
        $parent_id = input('get.parent_id');
        $district = Db::name('region')->where('parent_id',$parent_id)->select();
        $html = "";
        foreach($district as $v){
            $html .='<option value="'.$v['id'].'">'.$v['name'].'</option>';
        }
        return ['data'=>$html];
    }
    /**
     *地区联动-市
     */
    public function twon(){
        $parent_id = input('get.parent_id');
        $twon = Db::name('region')->where('parent_id',$parent_id)->select();
        $html = "";
        foreach($twon as $v){
            $html .='<option value="'.$v['id'].'">'.$v['name'].'</option>';
        }
        return ['data'=>$html];
    }
    /**
     *修改地址页面
     */
    public function editAddress(){
        $address_id = input('get.address_id');
        $info = Db::name('user_address')->where('address_id',$address_id)->find();
        $province = Db::name('region')->where('parent_id',0)->select();
        $area = Db::name('region')->field('name,id')->where('id','in',[$info['province'],$info['city'],
            $info['district'], $info['twon']])->select();
        $this->assign('province',$province);
        $this->assign('info',$info);
        $this->assign('area',$area);
        return view();
    }
    /**
     *修改地址功能
     */
    public function addressupd(){
        //dump(input('get.'));exit;
        $data = input('post.');
        $address_id['address_id'] = $data['address_id'];
        unset($data['address_id']);
        Db::name('user_address')->where('address_id',$address_id['address_id'])->update($data);
        return ['code'=>1,'msg'=>'修改成功'];
    }
    /**
     *删除地址
     */
    public function delAddress(){
        $address_id = input('post.address_id');
        $re = Db::name('user_address')->where('address_id',$address_id)->delete();
        if($re){
            return ['code'=>1,'msg'=>'删除成功'];
        }else{
            return ['code'=>0,'msg'=>'删除失败'];
        }
    }
    /**
     *默认地址
     */
    public function isdefault(){
        $address_id = input('post.address_id');
        $user_id = Session::get('user_info.user_id');
        $data['is_default'] = 1;
        $default['is_default'] = 0;
        $re = Db::name('user_address')->where('address_id',$address_id)->update($data);
        if($re){
            Db::name('user_address')->where('user_id',$user_id)->where('address_id','neq',$address_id)->update($default);
            return ['code'=>1,'msg'=>'设置成功','url'=>url('User/userAddressList')];
        }else{
            return ['code'=>0,'msg'=>'设置失败'];
        }
    }
}
