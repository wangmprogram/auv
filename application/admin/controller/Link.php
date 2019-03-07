<?php

namespace app\admin\controller;
use think\Db;
use think\Request;

class Link extends Base
{
    /**
     * 显示友情链接列表
     *
     * @return \think\Response
     */
    public function linkList()
    {
        $list = Db::name('friend_link')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
    public function linkAdd(Request $request)
    {
        $act = input('param.act','add');
        $this->assign('act',$act);
        $link_id = input('param.link_id');
        $link_info = [];
        if($link_id){
            $link_info = Db::name('friend_link')->where('link_id',$link_id)->find();
            $this->assign('info',$link_info);
        }
        $this->assign('info',$link_info);
        return $this->fetch();
    }
    public function linkHandle(){
        $data = input('post.');
        if($data['act'] == 'del'){
            $r = Db::name('friend_link')->where('link_id',$data['link_id'])->delete();
            if($r) exit(json_encode(1));
        }
        //print_r($data);exit;
        $result = $this->validate($data,'FriendLink.q'.$data['act'], [], true);
        //var_dump($result);exit;
        if(true !== $result){
            // 验证失败 输出错误信息
            $validate_error = '';
            foreach ($result as $key =>$value){
                $validate_error .=$value.',';
            }
            $this->error($validate_error);
        }
        //var_dump($result);exit;
        if($data['act'] == 'add'){
            unset($data['link_id']);
            unset($data['act']);
            $r = Db::name('friend_link')->insert($data);
        }elseif($data['act'] == 'edit'){
            unset($data['act']);
            $r = Db::name('friend_link')->where('link_id',$data['link_id'])->update($data);
        }
        if($r){
            $this->success("操作成功",url('Admin/Link/linkList'));
        }else{
            $this->error("操作失败");
        }
    }
}
