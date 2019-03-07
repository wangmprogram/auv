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
use app\home\controller\Pub;

class Goods extends Pub
{

        /**
     * 显示商品列表
     *
     * @return \think\Response
     */
    public function goodsList()
    {
        //处理传过来的分类ID
        $cat_id = input('cat_id');
        $cat = Db::name('goods_category')
            ->find($cat_id);
        if($cat['level'] == 1){
            $cats = Db::name('goods_category')
                ->where('parent_id', $cat_id)
                ->select();
            $ids = [];
            $names = [];
            foreach($cats as $vo){
                $cat_3s = Db::name('goods_category')
                    ->where('parent_id', $vo['id'])
                    ->select();
                foreach($cat_3s as $v){
                    $ids[] = $v['id'];
                    $names[] = $v['name'];
                }
            }
        }elseif($cat['level'] == 2){
            $cats = Db::name('goods_category')
                ->where('parent_id', $cat_id)
                ->select();
            $ids = [];
            $names = [];
            foreach($cats as $vo){
                $ids[] = $vo['id'];
                $names[] = $vo['name'];
            }
        }else{
            $ids = $cat_id;
            $cats = Db::name('goods_category')
                ->find($ids);
            $names = [$cats['name']];
        }
        // 品牌
        $brands = Db::name('brand')
            ->where('cat_id', 'in', $ids)
            ->select();
        // 产品
        $goods = Db::name('goods')
            ->where('cat_id', 'in', $ids)
            ->where('is_on_sale',1)
            ->select();
        // 图片
       // foreach($goods as $key=>$good){
       //     $
       // }
        // 推荐热卖
        $hots = Db::name('goods')
            ->where('is_hot', '1')
            ->where('is_on_sale',1)
            ->limit(5)
            ->select();
        // 图片
        $img = [];
        foreach($goods as $key=>$good){
            $img[] = Db::name('goods_images')
                ->where('goods_id','in',$good['goods_id'])
                ->select();
        }
        $image=[];
        foreach($img as $k1=>$v1){
            foreach($v1 as $k2=>$v2){
                $image[] = $v2;
            }
        }
        $this->assign('image', $image);
        $this->assign('hots', $hots);
        $this->assign('goods', $goods);
        // 分类名称
        $this->assign('names', $names);
        $this->assign('brands', $brands);
        return view();
    }

    public function goodsDetials(){
        //商品id需要更改
        $goods_id = input('goods_id');
        //商品信息
        $data = Db::name('goods')
                ->where('goods_id',$goods_id)
                ->find();
        //家族族谱
        $pip = Db::name('goods_category')
                                ->field('parent_id_path')
                                ->where('id',$data['cat_id'])
                                ->find();
        $parent_id_path = explode("_",$pip['parent_id_path']);
        $parr = [];
        foreach($parent_id_path as $pv){
            $parr[] = $pv;
        }
        $parentArr = Db::name('goods_category')
                            ->field('id,name')
                            ->where('id','in',$parr)
                            ->select();
        //商品品牌 parentArr
        $brandName = Db::name('brand')
                        ->where('id',$data['brand_id'])
                        ->find();
        //商品图片
        $imgs = Db::name('goods_images')
                    ->where('goods_id',$goods_id)
                    ->select();
        //商品类型
        $spec_type = $data['spec_type'];
       // 推荐热卖
        $hots = Db::name('goods')
            ->where('is_hot', '1')
            ->limit(5)
            ->select();
        //该商品规格信息
        $specInfo = Db::name('spec_goods_price')
                            ->where('goods_id',$goods_id)
                            ->select();
        $as=[];
        $spec_price=[];
        foreach($specInfo as $key=>$sp){
            $as[]=explode("_",$sp["key"]);
            $spec_price[]=explode("_",$sp["key"]);
            $spec_price[$key]['price'] = $sp['price'];
            $spec_price[$key]['store_count'] = $sp['store_count'];
            
        }
        $b=[];
        foreach($as as $key=>$a){
            foreach($a as $k=>$va){
                $b[$k][]=$va;
            }
        }
        //定义数组存储该商品所有规格信息
        $c=[];
        foreach($b as $k1=>$v1){
            $key = Db::name('spec_item i')
                    ->field('s.name,s.id')
                    ->join('spec s','s.id=i.spec_id')
                    ->where('i.id',$v1[0])
                    ->find();
            foreach($v1 as $k2=>$v2){
                $item = Db::name('spec_item')
                            ->where('id',$v2)
                            ->find();
                $c[$key['name']][$v2] = $item['item'];
            }
        }
        $hot_goods = Db::name('goods')
                ->where('cat_id',$data['cat_id'])
                ->select();
        $infos = Db::name('comment')->where('goods_id',$goods_id)->select();
        foreach($infos as $key=>$vo){
            $header_pic = Db::name('users')->field('head_pic')->where('user_id',$vo['user_id'])->find();
            $infos[$key]['head_pic'] = $header_pic['head_pic'];
        }
        $cate=Db::table("__GOODS_ATTR__ a")
        ->join("__GOODS_ATTRIBUTE__ e","a.attr_id=e.attr_id")
        ->where("goods_id",$goods_id)
        ->select();
        //商品评论开始
         $infosa = Db::name('comment')->where('goods_id',$goods_id)->select();
        if(empty($infosa)){
            $n = 0;
        }else{
            $n = 1;
        }
        foreach($infosa as $key=>$vo){
            $header_pic = Db::name('users')->field('head_pic')->where('user_id',$vo['user_id'])->find();
            $infosa[$key]['head_pic'] = $header_pic['head_pic'];
           if(!empty($vo['img'])){
               $pic = [];
               $pic = explode(',',$vo['img']);
               $infosa[$key]['img'] = $pic;
           }else{
               $infosa[$key]['img'] = '';
           }
        }
        $this->assign('infosa',$infosa);
        $this->assign('n',$n);
        //结束
        $this->assign("cate",$cate);
        $this->assign("spec_price",json_encode($spec_price,1));
        $this->assign('infos',$infos);
        $this->assign('hot_goods',$hot_goods);
        $this->assign('data',$data);
        $this->assign('parentArr',$parentArr);
        $this->assign('imgs',$imgs);
        $this->assign('brandName',$brandName['name']);
        $this->assign('hots',$hots);
        $this->assign('c',$c);
        return view();
    }
    /**
     * 搜索商品
     *
     * @return \think\Response
     */
    public function search()
    {
        $where = []; // 数据查询条件
        // 搜索关键字处理
        $name = input('keyword');
        if(Db::name('search_word')->where('keywords',$name)->count()){
            Db::name('search_word')->where('keywords',$name)->setInc('search_num');
        }else{
            $keyword = ['keywords'=>$name];
            Db::name('search_word')->insert($keyword);
        }
        if(!empty($name)){
            $where['goods_name'] = ['like',"%{$name}%"];
        }
        // 查询
        $datas = Db::name('goods')
            ->where($where)
            ->select();
        //查询结果为空
        // if(empty($datas)){
        //     return $this->redirect('home/Index/index');
        // }
        // 推荐热卖
        $hots = Db::name('goods')
            ->where('is_hot', '1')
            ->limit(5)
            ->select();
        // 图片
        if(!empty($datas)){
            $img = [];
            foreach($datas as $key=>$good){
                $img[] = Db::name('goods_images')
                    ->where('goods_id','in',$good['goods_id'])
                    ->select();
            }
            //
            foreach($img as $k1=>$v1){
                foreach($v1 as $k2=>$v2){
                    $image[] = $v2;
                }
            }
            $this->assign('image',$image);
        }
        
        $this->assign('datas',$datas);
        $this->assign('hots',$hots);
        return view();
    }
    /**
     *加入购物车
     */
    public function cartIn()
    {
        $user_info = Session::get('user_info');
        $user_id = $user_info['user_id'];
        // $user_id = 1;
        // 测试关掉
        if(!$user_id){
            return ["code"=>2,"msg"=>"请先登录"];
        }
        $post=input("post.");
        $goods_id=$post["goods_id"];
        $img = Db::name('goods')
                    ->where('goods_id',$goods_id)
                    ->find();
        $img = $img['original_img'];
        $key=substr(input("post.key"),0,-1);
        $key_name=substr(input("post.key_name"),0,-1);
        $good=Db::table("__GOODS__")->where("goods_id",$goods_id)->find();
        if(empty($key)){
            $item1=Db::table("__SPEC_GOODS_PRICE__")->where("goods_id",$goods_id)->find();
            if(empty($item1)){
                $key="";
                $key_name="";
                $price=intval($good["shop_price"]);

            }else{
                $key=$item1["key"];
                $key_name=$item1["key_name"];
                $price=intval($item1["price"]);
            }
            
        }else{
            
            $price=Db::table("__SPEC_GOODS_PRICE__")->where("key",$key)->find();
            $price=intval($price["price"]);
        }
        $num=$post["num"];
        $num=intval($num);
        $con=[
            "user_id"=>$user_id,
            "goods_id"=>$goods_id,
            "goods_sn"=>$good["goods_sn"],
            "goods_name"=>$good["goods_name"],
            "goods_price"=>$price,
            "goods_num"=>$num,
            'spec_key'=>$key,
            "spec_key_name"=>$key_name,
            "add_time"=>time(),
            "img"=>$img
        ];
        if(Db::table("__CART__")->where("user_id",$user_id)->where("goods_id",$goods_id)->where("spec_key",$key)->count()>0){
            $num1=Db::table("__CART__")->field("goods_num")->where("user_id",$user_id)->where("goods_id",$goods_id)->where("spec_key",$key)->find();
            $num1=$num1["goods_num"];
            $good_num=$num1+$num;
            if(Db::table("__CART__")->where("user_id",$user_id)->where("goods_id",$goods_id)->where("spec_key",$key)->update(["goods_num"=>$good_num])){
                return ["code"=>1,"msg"=>"添加成功"];
            }else{
                return ["code"=>0,"msg"=>"添加失败"];
            }
        }else{
            if(Db::table("__CART__")->insert($con)){
                return ["code"=>1,"msg"=>"添加成功"];
            }else{
                return ["code"=>0,"msg"=>"添加失败"];
            }
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
                                    ->limit(7)
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