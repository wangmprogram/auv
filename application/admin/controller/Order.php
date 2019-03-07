<?php
/**
 *订单
 */
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Paginator;
use think\Session;

class Order extends Base
{ 
    /**
     *显示订单列表
     */
    public function orderList()
    {
        $where = []; // 数据查询条件
        $condition = []; // 条件数据
        // 搜索日期范围
        $start_date = input('get.start_date');
        $start_date = !empty($start_date) ? strtotime($start_date." 00:00:00") : 0;
        $condition['start_date'] = !empty($start_date) ? date("Y-m-d", $start_date): '';
        $end_date = input('get.end_date');
        $end_date = !empty($end_date) ?  strtotime($end_date . " 23:59:59") : time();
        $condition['end_date'] = !empty($end_date) ? date("Y-m-d", $end_date): '';
        $where['add_time'] = ['between', [$start_date, $end_date]];
        //支付状态处理
        $pay_status = input('get.pay_status');
        if($pay_status != ''){
            $where['pay_status'] = ['eq',$pay_status];
        }
        $condition['pay_status'] = $pay_status; 
        //支付方式
        $pay_code = input('get.pay_code');
        if(!empty($pay_code)){
            $where['pay_code'] = ['eq',$pay_code];
        }
        $condition['pay_code'] = !empty($pay_code) ? $pay_code : ''; 
        //发货状态
        $shipping_status = input('get.shipping_status');
        if($shipping_status != ''){
            $where['shipping_status'] = ['eq',$shipping_status];
        }
        $condition['shipping_status'] = $shipping_status; 
        //订单状态
        $order_status = input('get.order_status');
        if($order_status != ''){
            $where['order_status'] = ['eq',$order_status];
        }
        $condition['order_status'] = $order_status; 
        // 搜索关键字处理
        if(input('get.keytype')=='consignee'){
            $consignee = input('get.keywords');
            if(!empty($consignee)){
                $where['consignee'] = ['like',"%{$consignee}%"];
            }
        }else if(input('get.keytype')=='order_sn'){
            $order_sn = input('get.keywords');
            if(!empty($order_sn)){
                $where['order_sn'] = ['eq',$order_sn];
            }
        }
        $condition['keytype'] = isset($consignee) ? 'consignee':'order_sn';
        $condition['keywords'] = isset($consignee) ? (!empty($consignee)?$consignee: ''):(!empty($order_sn)?$order_sn: '');
        //查询
        $record = Db::name('order')
                        ->where($where)
                        ->count();
        $orders = Db::name('order')
                        ->where($where)
                        ->order('add_time DESC')
                        ->paginate(10, false, ['query'=> $condition]);
        $this->assign('condition', $condition);
        $this->assign('orders', $orders);
        $this->assign('record', $record);
        return view();
    }
    /**
     *删除订单
     */
    public function orderDel()
    {
        $order_id = input('post.order_id');
        if(Db::name('order')->delete($order_id)){
            return ['status'=>1,'msg'=>''];
        }else{
            return ['status'=>0,'msg'=>''];
        }
    }
    /**
     *订单详情
     */
    public function orderDetail()
    {
        $order_id = input('order_id');
        $order = Db::name('order')->where('order_id',$order_id)->find();
        $user = Db::name('users')->where('user_id',$order['user_id'])->find();
        $goods = Db::name('order_goods')->where('order_id',$order['order_id'])->select();
        $status = [];
        if($order['order_status'] == '0'){
            $status['order_status'] = '未确认';
        }else if($order['order_status'] == '1'){
            $status['order_status'] = '已确认';
        }else if($order['order_status'] == '2'){
            $status['order_status'] = '未发货';
        }else if($order['order_status'] == '3'){
            $status['order_status'] = '已收货';
        }else if($order['order_status'] == '4'){
            $status['order_status'] = '已完成';
        }else if($order['order_status'] == '5'){
            $status['order_status'] = '已作废';
        }

        if($order['pay_status'] == '0'){
            $status['pay_status'] = '未支付';
        }else if($order['pay_status'] == '1'){
            $status['pay_status'] = '已支付';
        }

        if($order['shipping_status'] == '0'){
            $status['shipping_status'] = '未发货';
        }else if($order['shipping_status'] == '1'){
            $status['shipping_status'] = '已发货';
        }else if($order['shipping_status'] == '1'){
            $status['shipping_status'] = '部分发货';
        }
        $province = Db::name('region')->where('id',$order['province'])->find();
        $city = Db::name('region')->where('id',$order['city'])->find();
        $twon = Db::name('region')->where('id',$order['twon'])->find();
        $order['full_address'] = $province['name'].','.$city['name'].','.$twon['name'].','.$order['address'];
        foreach($goods as $key=>$vo){
            $goods[$key]['goods_total'] = $vo['goods_num']*$vo['final_price'];
            
        }
        $this->assign('order', $order);
        $this->assign('user', $user);
        $this->assign('goods', $goods);
        $this->assign('status',$status);
        return view();
    }
    /**
     *订单操作
     */
    public function orderHandle()
    {
        $order_act = input('order_act');
        $order_id = input('order_id');
        if($order_act == 'comfire'){
            Db::name('order')->where('order_id',$order_id)->update(['order_status'=>'1']);
            return ['status'=>1,'msg'=>''];
        }elseif($order_act == 'cal_comfire'){
            Db::name('order')->where('order_id',$order_id)->update(['order_status'=>'0']);
            return ['status'=>1,'msg'=>''];
        }elseif($order_act == 'valid'){
            Db::name('order')->where('order_id',$order_id)->update(['order_status'=>'5']);
            return ['status'=>1,'msg'=>''];
        }elseif($order_act == 'remove'){
            Db::name('order')->where('order_id',$order_id)->delete();
            return ['status'=>2,'msg'=>''];
        }
    }
    /**
     *发货列表
     */
    public function deliverList()
    {
        $where = []; // 数据查询条件
        $condition = []; // 条件数据
         //收货人
        $consignee = input('get.consignee');
        if(!empty($consignee)){
            $where['consignee'] = ['eq',$consignee];
        }
        $condition['consignee'] = !empty($consignee) ? $consignee : '';
        //订单编号
        $order_sn = input('get.order_sn');
        if(!empty($order_sn)){
            $where['order_sn'] = ['eq',$order_sn];
        }
        $condition['order_sn'] = !empty($order_sn) ? $order_sn : '';
        //发货状态
        $shipping_status = input('get.shipping_status');
        if(empty($shipping_status)){
            $where['shipping_status'] = '0';
        }else{
            $where['shipping_status'] = ['eq',$shipping_status];
        }
        $condition['shipping_status'] = !empty($shipping_status) ? $shipping_status : '0';
        //查询
        if($where['shipping_status'] == 0){
            $where['order_status'] = 1;
        }
        $record = Db::name('order')
                        ->where($where)
                        ->count();
        $delivers = Db::name('order')
                        ->where($where)
                        ->order('add_time DESC')
                        ->paginate(10, false, ['query'=> $condition]);
        $this->assign('condition', $condition);
        $this->assign('delivers', $delivers);
        $this->assign('record', $record);
        return view();
    }
    /**
     *发货订单详情
     */
    public function deliverDetail(){
        $order_id = input('order_id');
        $order = Db::name('order')->where('order_id',$order_id)->find();
        $goods = Db::name('order_goods')->where('order_id',$order_id)->select();
        $province = Db::name('region')->where('id',$order['province'])->find();
        $city = Db::name('region')->where('id',$order['city'])->find();
        $twon = Db::name('region')->where('id',$order['twon'])->find();
        $order['full_address'] = $province['name'].','.$city['name'].','.$twon['name'].','.$order['address'];
        $this->assign('order',$order);
        $this->assign('goods',$goods);
        return view();
    }
    /**
     *去发货
     */
    public function deliverSend(){
        $order_id = input('order_id');
        $order = Db::name('order')->where('order_id',$order_id)->find();
        $goods = Db::name('order_goods')->where('order_id',$order_id)->select();
        $shipping_list = Db::name('shipping')->select();
        $this->assign('order',$order);
        $this->assign('goods',$goods);
        $this->assign('shipping_list',$shipping_list);
        if($order['shipping_status'] != '1'){
            if($ids = input('ids')){
                $ids = explode(',',substr($ids,0,-1));
                //print_r($ids);
                if(Db::name('order_goods')->where('order_id',$order_id)->where('rec_id','in',$ids)->update(['is_send'=>'1'])){
                    //修改订单的发货状态
                    if(!Db::name('order_goods')->where('order_id',$order_id)->where('is_send','0')->count()){
                        Db::name('order')->where('order_id',$order_id)->update(['shipping_status'=>'1']);
                        return ['status'=>1,'msg'=>'','url'=>url('admin/Order/deliverList')];
                    }else{
                        Db::name('order')->where('order_id',$order_id)->update(['shipping_status'=>'2']);
                        return ['status'=>2,'msg'=>''];
                    }
                }else{
                    return ['status'=>0,'msg'=>''];
                }
            }
            return view();
        }else{
            return $this->fetch('admin/Order/orderList');
        }
    }
    /**
     *批量发货
     */

}
