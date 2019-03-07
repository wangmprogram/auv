<?php
/**
 *前台-购物车
 */
namespace app\home\controller;

use think\Request;
use think\Db;
use think\captcha\Captcha;
use think\Session;


class Cart extends Pub
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$user_id = Session::get('user_info.user_id');
    	$carts = Db::name('cart c')
                            ->field('c.*,g.original_img')
                            ->join('goods g','g.goods_id=c.goods_id')
                            ->where('user_id',$user_id)
                            ->select();
        $this->assign('carts',$carts);
        return $this->fetch();
    }
    /**
     *更改购物车事件
     */
    public function AsyncUpdateCart(){
        if($cart = input('cart/a')){
            $result['goods_num'] = 0;
            $result['total_fee'] = 0;
            foreach($cart as $vo){
                if($vo['selected']){
                    $result['goods_num'] += $vo['goods_num'];
                    $res = Db::name('cart')->where('id',$vo['id'])->find();
                    $total_fee = $res['total_fee'] = $res['goods_num']*$res['goods_price'];
                    $result['cartList'][] = $res;
                    Db::name('cart')->where('id',$vo['id'])->update(['selected'=>1]);
                }else{
                    Db::name('cart')->where('id',$vo['id'])->update(['selected'=>0]);
                }
                if(!empty($total_fee)){
                    $result['total_fee'] += $total_fee;
                }             
            }
            $data['result'] = $result;
            $data['status'] = 1;
            return $data;
        }
    }
    // 删除购物车商品
    function delete(){
        $id = input('cart_ids/a');
        for($i=0;$i<count($id);$i++){
            Db::name('cart')->delete($id[$i]);
            $flag = 1;
        }       
        if($flag == 1){
            return ['status'=>1,'msg'=>'删除成功'];
        }else{
            return ['status'=>0,'msg'=>'删除失败'];
        }
    }
    /**
     *购物车商品数量更改
     */
    public function changeNum(){
        if($cart = input('cart/a')){
            if($res = Db::name('cart')->where('id',$cart['id'])->find()){
                Db::name('cart')->where('id',$cart['id'])->update(['goods_num'=>$cart['goods_num']]);
                $data['goods_fee'] = $cart['goods_num']*$res['goods_price'];
                $data['status'] = '1';
                return $data;
            }
        }
    }
    /**
     *去结算页面
     */
    public function payment(){
        //用户的收货地址
        $user_id = Session::get('user_info.user_id');
        $infos = Db::name('user_address')->where('user_id',$user_id)->select();
        foreach ($infos as $key=>$vo) {
            $area = Db::name('region')
                                ->field('name')
                                ->where('id','in',[$vo['province'],$vo['city'],$vo['district'],$vo['twon']])
                                ->select();
            $res = Db::name('region')
                                ->where('id',$vo['province'])
                                ->find();
            $infos[$key]['spec'] = $vo['consignee']." ".$res['name'];
            $info = '';
            foreach($area as $v){
                $info.=$v['name'].',';
            }
            $info .= $vo['address'];
            $infos[$key]['full_address'] =$info;
        }
        //购物车选中商品
        $user_id = Session::get('user_info.user_id');
        $goods = Db::name('cart')->where('user_id',$user_id)->where('selected',1)->select();
        $total_price = 0;
        foreach($goods as $k=>$vo){
            $price = $vo['goods_price']*$vo['goods_num'];
            $total_price += $price;
        }
        $this->assign('goods',$goods);
        $this->assign('total_price',$total_price);
        $this->assign('infos',$infos);
        // dump($goods);
        return $this->fetch();
    }
    /**
     *提交订单
     */
    public function submitOrder()
    {   
        $address_id = input('address_id');
        //数据入库，保存到订单数据中
        $user_id = Session::get('user_info.user_id');
        //获取购物车和商品信息
        $carts = Db::name('cart')
                    ->where('user_id', $user_id)
                    ->where('selected', 1)
                    ->select();
        //获取总金额
        $total = '';
        foreach ($carts as $key => $val) {
            $total += $val['goods_num'] * $val['goods_price'];
        }
        //获取收货人和地址
        $address = Db::name('user_address')
                    ->where('address_id', $address_id)
                    ->find();
        //数据保存到订单表中，并生成订单号
        $order_sn = date('Ymd',time()).time();
        $add_time = time();
        $order = [
            'order_sn' => $order_sn,
            'user_id' => $user_id,
            'order_status' => 0,
            'shipping_status' => 0,
            'pay_status' => 0,
            'consignee' => $address['consignee'],
            'province' => $address['province'],
            'city' => $address['city'],
            'district' => $address['district'],
            'address' => $address['address'],
            'mobile' => $address['mobile'],
            'total_amount' =>$total,
            'add_time'=> $add_time,
            'shipping_code' => 'shentong',
            'shipping_name' => '申通物流',
            'goods_price'=> $total,
            'order_amount'=> $total

        ];
        //数据添加到订单表中
        if(Db::name('order')->insert($order)){
            $order_id = Db::name('order')->getLastInsID();
            Session::set('order_id',$order_id);
            foreach($carts as $cart){
                //将商品存入订单商品表中
                $order_goods = [
                    'order_id' =>$order_id,
                    'goods_id' =>$cart['goods_id'],
                    'goods_name' =>$cart['goods_name'],
                    'goods_num' => $cart['goods_num'],
                    'goods_price' => $cart['goods_price'],
                    'spec_key_name' => $cart['spec_key_name'],
                    "img" => $cart['img']
                ];
                //商品入库
                if(Db::name('order_goods')->insert($order_goods)){
                    //数据入库并删除购物车里的数据
                    if(Db::name('cart')->where('id', $cart['id'])->delete()){
                        $status = 1;
                    }else{
                        return ['status'=>0, 'msg'=>'提交订单失败'];
                    }
                }else{
                    return ['status'=>0, 'msg'=>'提交订单失败'];
                }
            }
        }else{
            return ['status'=>0, 'msg'=>'提交订单失败'];
        }
        //判断
        if(isset($status)){
            return ['status'=>1];
        }else{
            return ['status'=>0];
        }
    }
    /**
     *订单跳转页面
     */
    public function goOrder(){
        $order_id = Session::get('order_id');
        if($data = input('post.')){
            $where = [];
            if($data['pay_code'] == 'alipay'){
                $where = [
                    'pay_code'=>$data['pay_code'],
                    'pay_status'=>1
                ];
            }else if($data['pay_code'] == 'cod'){
                $where = [
                    'pay_code'=>$data['pay_code'],
                ];
            }
            if(Db::name('order')->where('order_id',$order_id)->update($where)){
                return ['status'=>1,'msg'=>'付款成功','url'=>url('home/Order/orderList')];
            }else{
                return ['status'=>0,'msg'=>'付款失败'];
            }
        }else{
            $order = Db::name('order')
                                ->where('order_id',$order_id)
                                ->find();
            $this->assign('order',$order);
            return view();
        }
    }
    /**
     * 猜你喜欢
     */
     function ajax_favorite(){
        // $p = input('p');
        // $i = 6;
        $where = ['is_recommend'=> 1 ,'is_on_sale'=>1 ];
        $favourite_goods = Db::name('goods')
                                    ->where($where)
                                    ->orderRaw('rand()')
                                    ->limit(6)
                                    ->select();
        $str = "";
        foreach ($favourite_goods as $value) {
            $str .= '
                <li>
                    <div class="pad">
                        <a href="/Home/Goods/goodsDetials?goods_id='.$value['goods_id'].'">
                            <img src="/static/home'.$value['original_img'].'" style="display: inline;">
                        </a>
                        <div class="shop_name2">
                            <a href="">'.$value['goods_name'].'</a>
                        </div>
                        <div class="price-tag">
                            <span class="now"><em class="li_xfo">￥</em><em>'.$value['shop_price'].'</em></span>
                            <span class="old"><em>￥</em><em>'.$value['market_price'].'</em></span>
                        </div>
                    </div>
                </li>
             ';
            
        }
        return $str;
     }
}