<?php

namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Paginator;


class Member extends base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function memberList(){
       $level = Db::name('user_level')->field('level_id,level_name')->select();
        $where = []; // 数据查询条件
        $condition = []; // 条件数据
        // 搜索关键字处理
        $user_name = input('get.keyword');
        if(!empty($user_name)){
            $where['nickname|phone'] = ['like', "%{$user_name}%"];
        }
        $condition['keyword'] = !empty($user_name) ? $user_name: '';
        // 搜索开始日期
        $start_date = input('get.start_date');
        $start_date = !empty($start_date) ? strtotime($start_date." 00:00:00") : 0;
        $condition['start_date'] = !empty($start_date) ? date("Y-m-d", $start_date): '';
        // 搜索结束日期
        $end_date = input('get.end_date');
        $end_date = !empty($end_date) ?  strtotime($end_date . " 23:59:59") : time();
        $condition['end_date'] = !empty($end_date) ? date("Y-m-d", $end_date): '';
        // 查询
        $where['reg_time'] = ['between', [$start_date, $end_date]];
        $infos = Db::name('users')
            ->where($where)
            ->order('user_id ASC')
            ->paginate(8, false, ['query'=> $condition]);
        $this->assign('condition', $condition);
        $this->assign('infos', $infos);
        $this->assign('level',$level);
        return view();
    }
    //添加会员
    public function memberAdd(Request $request){
        if($request->isPost()){
            // 获取表单上传文件
            $file = request()->file('img');
            if($file){
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS .'uploads');
                if($info){
                    // 获取文件位置
                    $head_pic = $info->getSaveName();
                }else{
                    // 上传失败获取错误信息
                    return ['code'=>0,'msg'=>$file->getError()];
                }
            }
            $data = input('post.');
            $data['reg_time']=time();
            $data['head_pic'] = $head_pic;
            $data['password'] = md5($data['password']);
            $re = Db::name('users')->where('nickname',$data['nickname'])->find();
            if($re){
                return ["code"=>0,'msg'=>'会员昵称已存在'];
            }else{
               $user =  Db::name('users')->insert($data);
                if($user){
                    return ["code"=>1,'msg'=>'添加成功'];
                }else{
                    return ["code"=>0,'msg'=>'添加失败'];
                }
            }
        }
        return $this->fetch();
    }
    //会员状态修改
    public function memberIslock(){
        $user_id = input('post.id');
        $re = Db::name('users')->field('is_lock')->where('user_id',$user_id)->find();
        if($re['is_lock']==0){
            $data = [
                'is_lock'=>1
            ];
            Db::name('users')->where('user_id',$user_id)->update($data);
            return ["code"=>1,'msg'=>'修改成功'];
        }else{
            $data = [
                'is_lock'=>0
            ];
            Db::name('users')->where('user_id',$user_id)->update($data);
            return ["code"=>1,'msg'=>'修改成功'];
        }
    }
    //重置用户密码为123456
    public  function memberPad(){
        $user_id = input('post.id');
        $data = [
            'password'=>md5(123456)
        ];
        $re = Db::name('users')->where('user_id',$user_id)->update($data);
        return ["code"=>1,'msg'=>'重置成功'];
    }
    //编辑会员信息
    public function memberUpd(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $password = Db::name('users')->field('password,total_amount,head_pic')->where('user_id',
                $data['user_id'])->find();
            $level = Db::name('user_level')->field('amount')->where('level_id',$data['level'])->find();
            if($data['head_pic']==''){
                unset($data['head_pic']);
                unset($data['img']);
            }else{
                //组装路劲
                $password['head_pic'] = strtr($password['head_pic'],'"\"','"/"');
                $img = UPLOADS_PATH . "static/uploads/".$password['head_pic'];
                // dump($img);
                // exit;
                unlink($img);
                // 获取表单上传文件
                $file = request()->file('img');
                if($file){
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS .'uploads');
                    if($info){
                        // 获取文件位置
                        $head_pic = $info->getSaveName();
                    }else{
                        // 上传失败获取错误信息
                        return ['code'=>0,'msg'=>$file->getError()];
                    }
                }
                $data['head_pic'] = $head_pic;
            }
            if(!$password['total_amount']<=$level['amount']){
                $data['total_amount'] = $level['amount'];
            }else{
                $data['total_amount'] = $password['total_amount'];
            }
            $user_id['user_id']=$data['user_id'];
            unset($data['user_id']);
            if($data['password']==''){
                $name = Db::name('users')->field('nickname')->where('nickname',$data['nickname'])->where('user_id','neq',$user_id['user_id'])->find();
                if($name){
                    return ["code"=>0,'msg'=>'用户名已存在'];
                }
                $data['password'] = $password['password'];
                Db::name('users')->where('user_id',$user_id['user_id'])->update($data);
                    return ["code"=>1,'msg'=>'修改成功'];

            }else{
                $name = Db::name('users')->field('nickname')->where('nickname',$data['nickname'])->where('user_id','neq',$user_id['user_id'])->find();
                if($name){
                    return ["code"=>0,'msg'=>'用户名已存在'];
                }
                $data['password'] = md5($data['password']);
                Db::name('users')->where('user_id',$user_id['user_id'])->update($data);
                    return ["code"=>1,'msg'=>'修改成功'];
            }
        }else{
            $user_id = input('get.id');
            $user = Db::name('users')->where('user_id',$user_id)->find();
            $infos = Db::name('user_level')->select();
            $this->assign('user',$user);
            $this->assign('infos',$infos);
            return $this->fetch();
        }
    }
    //会员等级列表
    public function memberLevel(){
        $level = Db::name('user_level')->select();
        $this->assign('level',$level);
        return $this->fetch();
    }
    //添加会员
    public function memberlistAdd(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $name = Db::name('user_level')->field('level_name')->where('level_name',$data['level_name'])->find();
            if($name){
                return ["code"=>0,'msg'=>'等级名称已存在'];
            }
            $re = Db::name('user_level')->insert($data);
            if($re){
                return ["code"=>1,'msg'=>'添加成功'];
            }else{
                return ["code"=>0,'msg'=>'添加成功'];
            }
        }else{
            return $this->fetch();
        }
    }
    //修改会员等级
    public function memberlistUpd(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $level_id['level_id'] = $data['level_id'];
            unset($data['level_id']);
            Db::name('user_level')->where('level_id',$level_id['level_id'])->update($data);
            return ["code"=>1,'msg'=>'修改成功'];
        }else{
            $level_id = input('get.level_id');
            $info = Db::name('user_level')->where('level_id',$level_id)->find();
            $this->assign('info',$info);
            return $this->fetch();
        }
    }
    //删除会员等级
    public function memberleveldel(){
        $level_id = input('post.level_id');
        $user = Db::name('users')->where('level',$level_id)->select();
        if($user){
            return ["code"=>0,'msg'=>'删除失败,改会员等级下还有会员，无法删除'];
        }
        $re = Db::name('user_level')->where('level_id',$level_id)->delete();
        if($re){
            return ["code"=>1,'msg'=>'删除成功'];
        }else{
            return ["code"=>0,'msg'=>'删除成功'];
        }
    }
    public function memberDel(){
        return $this->fetch();
    }
    public function memberRecordBrowse(){
        return $this->fetch();
    }
    public function memberRecordDownload(){
        return $this->fetch();
    }
    public function memberRecordShare(){
        return $this->fetch();
    }

}




