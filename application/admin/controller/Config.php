<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;

class Config extends Controller
{
    /**
     * 显示配置列表
     *
     * @return \think\Response
     */
    public function configList(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            //更新
            foreach($data as $key=>$value){
                Db::name('sysconfig')->where('varname',$key)->update(['value'=>$value]);
            }
            return ['status'=>1,'msg'=>'保存成功'];
        }else{ 
            $config = Db::name('sysconfig')->select();
            $this->assign('config',$config);
            return $this->fetch();
        }
    }
    public function configAdd(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            //添加进数据库
            if(Db::name('sysconfig')->where('info',$data['info'])->count()){ 
                return ['status'=>0,'msg'=>'此配置项已存在，请更换'];
            }
            if(Db::name('sysconfig')->where('varname',$data['varname'])->count()){ 
                return ['status'=>0,'msg'=>'此变量名称已存在，请更换'];
            }
            //入库
            if(Db::name('sysconfig')->insertGetId($data)){
                return ['status'=>1,'msg'=>'添加成功'];
            }else{
                return ['status'=>0,'msg'=>'添加失败'];
            }
        }else{
            return $this->fetch();
        }
    }
}
