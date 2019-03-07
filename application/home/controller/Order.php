<?php
/**
 *后台-商品显示
 */
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;
use think\Paginator;

class Order extends Pub
{
    /**
     *评论列表
     */
    public function commentList(){
        $user_id = Session::get('user_info.user_id');
        $re = Db::name('order')->where('user_id',$user_id)->select();
        if(!$re){
            $n = 0;
            $this->assign('n',$n);
            return view();
        }else{
            $n = 1;
            $this->assign('n',$n);
        $is_send = input('get.is_send');
        $where = [];
        if($is_send==3){
            $where = [
                'is_send'=>3
            ];
        }elseif($is_send==2){
            $where = [
                'is_send'=>2
            ];
        }elseif($is_send==1){
            $where = [];
        }
        $user_id = Session::get('user_info.user_id');
        $infos = Db::name('order')->where('user_id',$user_id)->select();
        $goodsorder = [];
        foreach($infos as $v){
           $order = Db::name('order_goods')->where('order_id',$v['order_id'])->where($where)->select();
            $goodsorder[] = $order;
        }
        //dump($goodsorder);exit;
        $this->assign('infos',$infos);
        $this->assign('goodsorder',$goodsorder);
        return view();
        }
    }
    public function comment(){
        $rec_id = input('rec_id');
        $goodsorder = Db::name('order_goods')->where('rec_id',$rec_id)->find();
        $order = Db::name('order')->where('order_id',$goodsorder['order_id'])->find();
        $this->assign('order',$order);
        $this->assign('goodsorder',$goodsorder);
        return view();
    }
    public function upload(){
        $file = request()->file('uploadFile');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads'.DS.'imgPicture');
        if($info){
            return ['status'=>1,'msg'=>'ok', 'filename'=> $info->getSaveName()];
        }else{
            return ['status'=>1,'msg'=>$file->getError(), 'filename'=> ''];
        }
    }

    public function commentadd()
    {
        $data = input('post.');
        $data['img'] = substr($data['img'],0,-1);
        unset($data['add_comment']);
        $data['user_id'] = Session::get('user_info.user_id');
        $data['username'] = Session::get('user_info.nickname');
        $data['add_time'] = time();
        $re = Db::name('comment')->insert($data);
        $is_comment = [
            'is_comment'=>1,
            'is_send'=>3
        ];
        Db::name('order_goods')->where('rec_id',$data['rec_id'])->update($is_comment);
        $res = Db::name('order_goods')->where('rec_id',$data['rec_id'])->find();
        $order_id = $res['order_id'];
        if($re){
            if(!(Db::name('order_goods')->where('order_id',$order_id)->where('is_send','neq','3')->select())){
                Db::name('order')->where('order_id',$order_id)->update(['order_status'=>4]);
            }
            return ['code'=>1,'msg'=>'评论成功','url'=>url('User/userIndex')];
        }else{
            return ['code'=>0,'msg'=>'评论失败'];
        }
    }
    /**
     *确认收货
     */
    public function commit(){
        $rec_id = input('rec_id');
        $order_id = input('order_id');
        if(Db::name('order_goods')->where('rec_id',$rec_id)->update(['is_send'=>2])){
            //如果所有商品都确认收货了即订单的状态要改为已收货状态,并且付款状态为已付款
            if(!Db::name('order_goods')->where('order_id',$order_id)->where('is_send','eq',0)->count()){
                Db::name('order')->where('order_id',$order_id)->update(['order_status'=>2,'pay_status'=>1]);
            }
            return ['status'=>1];
        }else{
            return ['status'=>0];
        }
    }
    /**
     *取消订单
     */
    public function cancelOrder(){
        $order_id = input('order_id');
        if(Db::name('order')->where('order_id',$order_id)->update(['order_status'=>3])){
            return ['status'=>1];
        }else{
            return ['status'=>0];
        }
    }
    /**
     *支付订单
     */
    public function dopay(){
        $order_id = input('order_id');
        if(Db::name('order')->where('order_id',$order_id)->update(['pay_status'=>1,'pay_code'=>'alipay','pay_name'=>'支付宝付款'])){
            return ['status'=>1];
        }else{
            return ['status'=>0];
        }
    }
    /**
     *订单列表
     */
    public function orderList(){
        $user_id = Session::get('user_info.user_id');
        $where = [];
        if($flag = input('flag')){
            if($flag == 1){
                $where = [];
            }else if($flag == 2){
                $where['pay_status'] = ['eq',0];
            }else if($flag == 3){
                $where['order_status'] = ['in',[0,1,2]];
            }else if($flag == 4){
                $where['order_status'] = ['eq',4];
            }else if($flag == 5){
                $where['order_status'] = ['eq',3];
            }
        }
        $order = Db::name('order')
                            ->where('user_id',$user_id)
                            ->where($where)
                            ->order('add_time DESC')
                            ->select();
        foreach($order as $key=>$vo){
            $goods = Db::name('order_goods')->where('order_id',$vo['order_id'])->select();
            $order[$key]['goods'] = $goods;
        }
        //dump($order);exit;
        $this->assign('order',$order);
        return view();
    }
}