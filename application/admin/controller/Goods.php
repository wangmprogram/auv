<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;

class Goods extends Base
{

    /**
     * 商品列表
     */
    public function goodsList(){
        $goodsInfos = Db::name('goods g')
                ->field('g.*,c.name cname')
                ->join('goods_category c',"g.cat_id=c.id")
                ->order('goods_id desc')
                ->paginate(8);
        $count = Db::name('goods')->count();
        $this->assign('goodsInfos', $goodsInfos);
        $this->assign('count', $count);
        return view();
    }
    /**
     * 图片上传
     */
    public function upPicture(){
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS .'uploads'.DS.'goodsPicture');
            if($info){
                $data['head_pic'] =  $info->getSaveName();
                //var_dump($data['head_pic']);exit;
                //Db::name('users')->where('user_id',$user_id)->update($data);
                return ['code'=>1, 'msg'=>'跟换成功', 'imginfo'=>$data['head_pic']];
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    /**
     * 添加商品
     */
    public function goodsAdd(Request $req){
        if($req->isPost()){
            $files = request()->file('file');
            $goods_name = input('post.goods_name');
            $cat_id = input('post.s_id');
            $brand_id = input('post.brand_id');
            $shop_price = input('post.shop_price');
            $market_price = input('post.market_price');
            $cost_price = input('post.cost_price');
            $keywords = input('post.keywords');
            $goods_content = input('post.goods_content');
            $data = [
                'goods_name'=>$goods_name,
                'cat_id'=>$cat_id,
                'brand_id'=>$brand_id,
                'shop_price'=>$shop_price,
                'market_price'=>$market_price,
                'cost_price'=>$cost_price,
                'keywords'=>$keywords,
                'goods_content'=>$goods_content,
            ];
            $goods_id = Db::name('goods')->insertGetId($data);
            if($goods_id){
                $goods_sn = "TP".$goods_id;
                Db::name('goods')
                    ->where('goods_id',$goods_id)
                    ->update(['goods_sn'=>$goods_sn]);
            }else{
                return ['status'=>0,'msg'=>'商品添加失败'];
            }
            $files = request()->file('file');

            foreach($files as $key=>$file){
                // 移动到框架应用根目录/public/uploads/ 目录下
                // /public/upload/goods/2016/01-21/56a08f61212f7.jpg
                // /public/upload/goods/
                $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'home'.DS.'public'.DS."upload".DS."goods");
                if($info){
                    $src = "/public/upload/goods/". $info->getSaveName();
                    Db::name('goods_images')
                                ->insert(['goods_id'=>$goods_id,'image_url'=>$src]);
                    //添加第一张图片到商品主表中
                    if($key==0){
                        Db::name('goods')
                                ->update(['goods_id'=>$goods_id,'original_img'=>$src]);
                    }
                }else{
                    // 上传失败获取错误信息
                    echo $file->getError();
                }    
            }
            return ['status'=>1,'msg'=>'商品添加成功'];
           
        }
        $parentCate = Db::name('goods_category')
                                    ->where('parent_id', 0)
                                    ->order("sort_order asc")
                                    ->select();
        $firstChildCate = Db::name('goods_category')
                                    ->where('parent_id', $parentCate[0]['id'])
                                    ->order("sort_order asc")
                                    ->select();
        $secondChildCate = Db::name('goods_category')
                                    ->where('parent_id', $firstChildCate[0]['id'])
                                    ->order("sort_order asc")
                                    ->select();
        $brands = Db::name('brand')
                        ->where('cat_id',1)
                        ->select();
        $this->assign('parentCate', $parentCate);
        $this->assign('firstChildCate', $firstChildCate);
        $this->assign('secondChildCate', $secondChildCate);
        $this->assign('brands', $brands);
        return view();
    }
    /**
     * 商品删除
     */
    function goodsDel(){
        $goods_id = input('goods_id');
        Db::name('goods')->delete($goods_id);
        Db::name('goods_images')
            ->where('goods_id',$goods_id)
            ->delete();
        Db::name('goods_attr')
            ->where('goods_id',$goods_id)
            ->delete();
        Db::name('spec_goods_price')
            ->where('goods_id',$goods_id)
            ->delete();
        return ['status'=>1];
    }
    /**
     * 添加商品规格
     */
    function goodsSpcAdd(Request $request){
        if($request->isAjax()){
            $id=input("post.id");
            $info=Db::table("__SPEC__ s")
                    ->field("s.id,s.name as sname,t.name,order,search_index")
                    ->join("__GOODS_TYPE__ t","s.type_id=t.id")
                    ->where("type_id",$id)
                    ->select();
            
            foreach($info as $k=>$va){
                $arr=Db::table("__SPEC_ITEM__")
                            ->field("id,item")
                            ->where("spec_id",$va["id"])
                            ->select();
                $arr1=[];
                foreach($arr as $val){
                    $arr1[]=$val;
                };
                $info[$k]["spec_item"]=$arr1;
            }
            return $info;
        }
        //获取商品id
        $id=input("get.id");
        $this->assign('goods_id', $id);
        //查询所有商品模型
        $type=Db::table("__GOODS_TYPE__")->select();
        $this->assign('type', $type);
        //获取当前商品所有数据（取模型id）
        $good=Db::table("__GOODS__")->where("goods_id",$id)->find();
        $goods_type=$good["spec_type"];
        $this->assign('goods_type', $goods_type);
        return $this->fetch();
    }
     //表单提交商品规格插入表
    public function goodsSpcIn(Request $request)
    {
        $post=input("post.");
        $goods_id=$post["goods_id"];
        $goods_type=$post["goods_type"];
        $price=$post["price"];
        $store_count=$post["store_count"];
        unset($post["goods_id"]);
        unset($post["price"]);
        unset($post["store_count"]);
        unset($post["goods_type"]);
        Db::table("__GOODS__")->where("goods_id",$goods_id)->update(["spec_type"=>$goods_type]);
        $key="";
        $key_name="";
        foreach($post as $k=>$v){
            $key .=  $k.'_';
            $catename = Db::name('spec s')
                            ->field('s.name')
                            ->join('spec_item i','s.id=i.spec_id')
                            ->where('i.id',$k)
                            ->find();
            $key_name .= $catename['name'].":".$v." ";
        }
        $co=[
            "goods_id"=>$goods_id,
            'key'=>substr($key,0,-1)
        ];
        $con=[
            "goods_id"=>$goods_id,
            "price"=>$price,
            'store_count' =>$store_count,
            "key_name"=>substr($key_name,0,-1),
            'key'=>substr($key,0,-1)
        ];
        $orign = Db::table("__SPEC_GOODS_PRICE__")->where($co)->find();
        if($orign){
                //修改规格表价格 库存
            Db::table("__SPEC_GOODS_PRICE__")
                ->where($co)
                ->update(["price"=>$price,"store_count"=>$store_count]);
                //修改商品更改总库存
            Db::name('goods')
                ->where('goods_id',$goods_id)
                ->setDec("store_count",$orign['store_count']);
            Db::name('goods')
                ->where('goods_id',$goods_id)
                ->setInc("store_count",$store_count);
                
            return ["code"=>1,"msg"=>"修改成功"];
        }else{
            //添加规格后 更改商品主表的库存
            if(Db::table("__SPEC_GOODS_PRICE__")->insert($con)){
                Db::name('goods')
                    ->where('goods_id',$goods_id)
                    ->setInc("store_count",$store_count);
                return ["code"=>1,"msg"=>"添加成功"];
            }else{
                return ["code"=>0,"msg"=>"添加失败"];
            }
        }
    }
    /**
     * 添加商品属性界面
     */
    function goodsAtAdd(Request $request){
        if($request->isAjax()){
            $id=input("post.id");
            $attribute=Db::table("__GOODS_ATTRIBUTE__")->where("type_id",$id)->select();
            $da="";
            foreach($attribute as $att){
                if($att["attr_input_type"]==1){
                    $f=$att["attr_values"];
                    $f=explode("\n",$f);
                    $da.='<div class="row cl"><label class="form-label col-xs-4 col-sm-2"><span class="c-red">*</span>'.$att["attr_name"].'：</label><div class="formControls col-xs-8 col-sm-9"> <span class="select-box"><select class="select" name="'.$att["attr_id"].'" size="1">';
                    foreach($f as $c){
                    $da.='<option value="'.$c.'">'.$c.'</option>';  
                    }
                    $da.='</select></span></div></div>';
                }else{
                    $da.='<div class="row cl"><label class="form-label col-xs-4 col-sm-2">'.$att["attr_name"].'：</label><div class="formControls col-xs-8 col-sm-9"><input type="text" name="'.$att["attr_id"].'" id="'.$att["attr_id"].'" placeholder="" value="" class="input-text" style="width:90%"></div></div>';
                }
            }
            return $da;
        }
        $id=input("get.id");
        $good=Db::name("goods")->where("goods_id",$id)->find();
        $goods_type=$good["goods_type"];
        //商品模型id
        $this->assign('goods_type', $goods_type);
        $type=Db::name("goods_type")->select();
        //所有商品模型
        $this->assign('type', $type);
        $this->assign('goods_id', $id);
        return $this->fetch();
    }
    //表单提交商品属性插入表
    public function goodsAtIn(Request $request)
    {
        $post=input("post.");
        $goods_id=$post["goods_id"];
        $goods_type=$post["goods_type"];
        unset($post["goods_id"]);
        unset($post["goods_type"]);
        Db::table("__GOODS__")->where("goods_id",$goods_id)->update(["goods_type"=>$goods_type]);
        $flag = 1;
        foreach($post as $k=>$v){
            $con = [];
            $con = [
                "goods_id"=>$goods_id,
                "attr_id"=>$k,
                "attr_value"=>$v
            ];
            $co = [];
            $co=[
                "goods_id"=>$goods_id,
                "attr_id"=>$k
            ];
            if(Db::table("__GOODS_ATTR__")->where($co)->count()>0){
                $flag =0;
                Db::table("__GOODS_ATTR__")->where($co)->update(["attr_value"=>$v]);
            }else{
                Db::table("__GOODS_ATTR__")->insert($con);
            }
        }
        if($flag){
            return ["code"=>1,"msg"=>"添加成功"];
        }else{
            return ["code"=>1,"msg"=>"修改成功"];
        }
        
    }
    
    /**
     * 编辑商品
     */
    public function goodsEdit(){
        return view();
    }

    /**
     * 显示商品分类列表
     */
    public function categoryList()
    {
        //从数据库取得商品所有商品分类信息
        $infos = Db::name('goods_category')
                        ->order("parent_id asc")
                        ->order("sort_order asc")
                        ->select();
        // dump($infos);
        // $arr = $this->recursiveCategory($infos);
        // dump($arr);
        $count = Db::name('goods_category')->count();
        $this->assign('infos',$this->recursiveCategory($infos));
        $this->assign('count',$count);
        return view();
    }
    /**
     * 数组处理
     * @param  [type]  $arr       [description]
     * @param  integer $parent_id [description]
     * @return [type]             [description]
     */
    public function recursiveCategory($arr,$parent_id=0){
        static $array = [];
        foreach ($arr as $value) {
            if($value['parent_id'] == $parent_id){
                $array[] = $value;
                $this->recursiveCategory($arr,$value['id']);
            }
        }
        return $array;
    }
    /**
     * 所有是否状态管理
     */
    public function startstop(){
        //去值
        $val = input('post.val');
        $type = input('post.type');
        //判断是否为商品列表id(特殊)
        $goods_id = input('goods_id');
        if(isset($goods_id)){
            $data['goods_id'] = input('post.goods_id');
        }else{
            $data['id'] = input('post.id');
        }
        //取数据库表名
        $table = input('table');
        //字段名和值
        $data[$type] = $val;
        //修改数据库
        $res = Db::name($table)->update($data);
        if($res){
            return 'true';
        }else{
            return 'false';
        }
    }
    /**
     * 新增商品分类
     */
    public function categoryAdd(Request $req){
        if($req->isPost()){
            $id = input('post.id');
            $sort_order = intval(input('post.sort_order'));
            $sort_order = $sort_order?$sort_order:50;
            $name = input('post.name');
            //判断该分类下分类名称是否重复
            $res = Db::name('goods_category')
                        ->where('name',$name)
                        ->where('parent_id',$id)
                        ->count();
            if($res){
                return ['status'=>0,'msg'=>'当前分类下分类名称重复'];
            }
            $arr = Db::name('goods_category')
                            ->field('level,parent_id_path')
                            ->where('id',$id)
                            ->find();
            $level = $arr['level']+1;
            $parent_id_path = $arr['parent_id_path'];
            $data = [
                'parent_id' => $id,
                'name' => $name,
                'sort_order' => $sort_order,
                'level' => $level
            ];
            if($curid = Db::name('goods_category')->insertGetId($data)){
                $parent_id_path = $parent_id_path.'_'.$curid;
                Db::name('goods_category')
                    ->where('id',$curid)
                    ->update(['parent_id_path'=>$parent_id_path]);
                return ['status'=>1,'msg'=>'分类添加成功'];
            }else{
                return ['status'=>0,'msg'=>'分类添加失败'];
            }
        }
        //从数据库取得商品所有商品分类信息
        $infos = Db::name('goods_category')
                        ->where('level','neq','3')
                        ->order("parent_id asc")
                        ->order("sort_order asc")
                        ->select();
        $this->assign('infos',$this->recursiveCategory($infos));
        // dump($this->recursiveCategory($infos));
        return view();
    }
    /**
     * 商品分类删除
     */
    public function cateDel(){
        $id = input('post.id');
        if(Db::name('goods')->where('cat_id',$id)->count()){
            return ['status'=>0,'msg'=>'该分类下还有商品删除失败'];
        }
        $res1 = Db::name('goods_category')
                    ->where('parent_id',$id)
                    ->count();
        //判断该分类下是否有子分类
        if($res1){
            return ['status'=>0,'msg'=>'该分类下还有子分类删除失败'];
        }
        //判断该分类下还有商品 未做

        $res = Db::name('goods_category')->delete($id);
        if($res){
            return ['status'=>1,'msg'=>'分类删除成功'];
        }else{
            return ['status'=>0,'msg'=>'分类删除失败'];
        }


    }
    /**
     * 商品分类编辑
     */
    public function categoryEdit(Request $req){
        if($req->isPost()){
            $id = input('id');
            $parent_id = input('post.parent_id');
            $data = input('post.');
            if($id == $parent_id){
                return ['status'=>0,'msg'=>'自己不能是自己的父类'];
            }
            $arr = Db::name('goods_category')
                        ->field('level,parent_id_path')
                        ->where('id',$parent_id)
                        ->find();
            $data['level'] = $arr['level']+1;
            $data['parent_id_path'] = $arr['parent_id_path']."_".$id;
            Db::name('goods_category')->update($data);
            return ['status'=>1,'msg'=>'商品分类编辑成功'];
        }
        $id = input('id');
        $data = Db::name('goods_category')->find($id);
        $this->assign('data',$data);
        //从数据库取得商品所有商品分类信息
        $infos = Db::name('goods_category')
                        ->where('level','neq','3')
                        ->order("parent_id asc")
                        ->order("sort_order asc")
                        ->select();
        $this->assign('infos',$this->recursiveCategory($infos));
        return view();
    }
    
    /**
     * 商品类型 用于设置商品的属性
     */
    public function goodsType(){
        $infos = Db::name('goods_type')
                    ->order('id asc')
                    ->select();
        $this->assign('infos',$infos);
        return view();
    }
    /**
     * 添加商品模型
     */
    public function goodsTypeAdd(Request $req){
        if($req->isPost()){
            $name = input('post.name');
            if(Db::name('goods_type')->insert(['name'=>$name])){
                return ['status'=>1,'msg'=>'商品模型添加成功'];
            }else{
                return ['status'=>0,'msg'=>'商品模型添加失败'];
            }
        }
        return view();
    }
    /**
     * 编辑商品模型
     */
    public function goodsTypeEdit(Request $req){
        if($req->isPost()){
            $data = input('post.');
            Db::name('goods_type')->update($data);
            return ['status'=>1, 'msg'=>'商品模型编辑成功'];
        }
        $id = input('id');
        $info = Db::name('goods_type')->find($id);
        $this->assign('info',$info);
        return view();
    }
    /**
     * 商品模型删除
     */
    public function goodsTypeDel(){
        $id = input('id');
        //根据id区商品属性表查找是否有此模型的属性  未做
        if(Db::name('goods_attribute')->where('type_id',$id)->count()){
            return ['status'=>0, 'msg'=>'该模型下有商品属性'];
        }
        if(Db::name('spec')->where('type_id',$id)->count()){
            return ['status'=>0, 'msg'=>'该模型下有商品规格'];
        }
        if(Db::name('goods')->where('goods_type',$id)->count()){
            return ['status'=>0, 'msg'=>'该模型下有商品'];
        }
        //删除模型
        if(Db::name('goods_type')->delete($id)){
            return ['status'=>1, 'msg'=>'模型删除成功'];
        }else{
            return ['status'=>0, 'msg'=>'模型删除失败'];
        }
         
    }
    /**
     * 商品属性
     */
    public function goodsAttr(){
        $infos = Db::name('goods_attribute')
                        ->alias('a')
                        ->join('goods_type t', 'a.type_id=t.id')
                        ->order('a.attr_id')
                        ->paginate(15);
        $count = Db::name('goods_attribute')->count();
        $this->assign('infos', $infos);
        $this->assign('count', $count);
        return view();
    }
    /**
     * 添加商品属性
     */
    public function goodsAttrAdd(Request $req){
        if($req->isPost()){
            $name = input('post.attr_name');
            $type_id = input('post.type_id');
            $res = Db::name('goods_attribute')
                        ->where('attr_name', $name)
                        ->where('type_id', $type_id)
                        ->count();
            if($res){
                return ['status'=>0, 'msg'=>'该模型下属性名称已存在'];
            }
            $data = input('post.');
            if(Db::name('goods_attribute')->insertGetId($data)){
                return ['status'=>1, 'msg'=>'商品属性添加成功'];
            }else{
                return ['status'=>0, 'msg'=>'商品属性添加失败'];
            }
        }
        $infos  =Db::name('goods_type')->select();
        $this->assign('infos', $infos);
        return view();
    }
    /**
     * 商品属性编辑
     */
    public function goodsAttrEdit(Request $req){
        if($req->isPost()){
            $attr_id = input('post.attr_id');
            $name = input('post.attr_name');
            $type_id = input('post.type_id');
            $res = Db::name('goods_attribute')
                        ->where('attr_id','neq',$attr_id)
                        ->where('attr_name', $name)
                        ->where('type_id', $type_id)
                        ->count();
            if($res){
                return ['status'=>0, 'msg'=>'该模型下属性名称已存在'];
            }
            $data = input('post.');
            Db::name('goods_attribute')->update($data);
            return ['status'=>1, 'msg'=>'属性修改成功'];
            
        }
        $attr_id = input('attr_id');
        $data = Db::name('goods_attribute')->find($attr_id);
        $infos  =Db::name('goods_type')->select();
        $this->assign('data', $data);
        $this->assign('infos', $infos);
        return view();
    }
    /**
     * 商品属性删除
     */
    public function goodsAttrDel(){
        $attr_id = input("attr_id");
        if(Db::name('goods_attr')->where('attr_id',$attr_id)->count()){
            return ['status'=>0, 'msg'=>'属性下还有商品'];
        }
        if(Db::name('goods_attribute')->delete($attr_id)){
            return ['status'=>1, 'msg'=>'属性删除成功'];
        }else{
            return ['status'=>0, 'msg'=>'属性删除失败'];
        }
        //商品完成后再做
    }
    /**
     * 商品品牌
     */
    public function brandList(){
        $infos = Db::name('brand b')
                    ->field('b.*,c.name cate_name')
                    ->join('goods_category c', 'c.id=b.parent_cat_id','left')
                    ->paginate(15);
        $count = Db::name('brand')->count();
        $this->assign('infos', $infos);
        $this->assign('count', $count);
        // dump($infos);
        return view();
    }
    /**
     * 添加商品品牌
     */
    public function brandAdd(Request $request){
        if($request->isPost()){
            $data = input('post.');
            unset($data['file']);
            if(Db::name('brand')->insert($data)){
                return ['status'=>1, 'msg'=>"商品品牌添加成功"];
            }else{
                return ['status'=>0, 'msg'=>'商品品牌添加失败'];
            }

        }
        //从数据库取得商品所有商品分类信息
        $parentCate = Db::name('goods_category')
                                    ->where('parent_id', 0)
                                    ->order("sort_order asc")
                                    ->select();
        $firstChildCate = Db::name('goods_category')
                                    ->where('parent_id', $parentCate[0]['id'])
                                    ->order("sort_order asc")
                                    ->select();
        $this->assign('parentCate', $parentCate);
        $this->assign('firstChildCate', $firstChildCate);
        return view();
    }
    /**
     * 上传logo
     */
    public function upLogo(){
        //取得上传文件信息默认name file
        $file = request()->file('file');
        // 移动到框架应用根目录/public/static/uploads/brandLogo 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS .'uploads'.DS.'brandLogo');
            if($info){
                $imginfo =  $info->getSaveName();
                //var_dump($data['head_pic']);exit;
                // Db::name('users')->where('user_id',$user_id)->update($data);
                return ['code'=>1, 'msg'=>'上传成功', 'imginfo'=>$imginfo];
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    /**
     * [getCategory 获取子分类]
     * @return [type] [description]
     */
    public function getCategory()
    {
        $cate_id = input("post.cate_id");
        $data = Db::name('goods_category')
                        ->where('parent_id', $cate_id)
                        ->select();
        return $data;
    }
     public function doCat()
    {
        $infos = Db::name('goods_category')
            ->where('parent_id', input('post.id'))
            ->select();
        return $infos;
    }
      /**
     * 获取该分类下的品牌
     */
    public function getBrand()
    {
        $id = input("post.id");
        $type = input('post.type');
        $data = Db::name('brand')
                        ->where("cat_id", $id)
                        ->select();
        return $data;
    }
     /**
    *商品品牌删除
     */
    public function brandDel()
    {
        $id = input('post.id');
        if(Db::name('goods')->where('brand_id',$id)->count()){
            return ['status'=>0,'msg'=>'该品牌下还有商品'];
        }
        if(Db::name('brand')->delete($id)){
            return ['status'=>1,'msg'=>'删除成功！'];
        }else{
            return ['status'=>0,'msg'=>'删除失败！'];
        }
    }
    /**
    *商品品牌编辑
     */
    public function brandEdit(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            if(Db::name('brand')
                ->where('name','eq',$data['name'])

                ->where('id','neq',$data['id'])
                ->count()){
                return ['status'=>0,'msg'=>'品牌名已存在！'];
            }
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('image');
            // 移动到框架应用根目录/public/upload/ 目录下
            if($file) {
                $info = $file->move(ROOT_PATH . 'public' . DS .'public'. DS.'upload');
                if($info){
                    // 成功上传后 获取上传信息
                    $data['logo'] = DS .'public'.DS. 'upload'.DS . $info->getSaveName();
                }else{
                    // 上传失败获取错误信息
                    return ['status'=>0,'msg'=>'logo上传失败！'];
                }
            }
            unset($data['image']);
            if(Db::name('brand')->update($data)){
                return ['status'=>1,'msg'=>'修改成功！'];
            }else{
                return ['status'=>0,'msg'=>'修改失败！'];
            }
        }
        $id = input('id');
        $data = Db::name('brand')
            ->where('id', $id)
            ->find();
        $this->assign('data',$data);
        return view();
    }
    
  
    /**
     * 商品规格
     */
    public function goodsSpec(){
        // $infos = Db::name('spec s')
        //             ->field('s.*,t.name type_name,item')
        //             ->join('goods_type t','s.type_id=t.id')
        //             ->join('spec_item i','i.spec_id=s.id')
        //             ->select();
        // dump($infos);
        // foreach($infos as $k=>$v){
        //     echo $v['item'] ;
        // }
        // dump($infos);
        // exit;
        $infos = Db::name('spec s')
                    ->field('s.*,t.name type_name')
                    ->join('goods_type t','s.type_id=t.id')
                    ->select();
        $count = Db::name('spec')->count();
        // $array = $infos->toArray();
        foreach ($infos as $key=>$value) {
             $str = $this->getSpecItem($value['id']);
             $infos[$key]["spec_item"] = $str;
        }
        // dump($infos);
        // exit;
        $this->assign('infos',$infos);
        $this->assign('count',$count);
        return view();
    }
    /**
     * 获取每个规格项下的所有规格值
     */
    public function getSpecItem($id){
        $arr = Db::name('spec_item')
                    ->field('item')
                    ->where('spec_id',$id)
                    ->order('id asc')
                    ->select();
        foreach ($arr as $value) {
            $arr1[] = $value['item'];
        }
        $str = implode(',', $arr1);
        return $str;
    }
    /**
     * 添加新的商品规格
     */
    public function goodsSpecAdd(Request $request){
        if($request->isPost()){
            $name = input('post.name');
            $type_id = input('post.type_id');
            $order = intval(input('post.order'));
            $order = $order?$order:50;
            $items = explode(",",input('post.items'));
            $data = [
                'name'=>$name,
                'type_id'=>$type_id,
                'order'=>$order
            ];
            $res = Db::name('spec')
                        ->where('name',$name)
                        ->where('type_id',$type_id)
                        ->count();
            if($res){
                return ['status'=>0,'msg'=>'该模型下规格名称已存在'];
            }
            $id = Db::name('spec')
                        ->insertGetId($data);
            if($id){
                foreach($items as $val){
                    Db::name('spec_item')
                        ->insert(['spec_id'=>$id,"item"=>$val]);
                }
                return ['status'=>1,'msg'=>'商品规格添加成功'];
            }else{
                return ['status'=>0,'msg'=>'商品规格添加失败'];
            }
        }
        $infos = Db::name('goods_type')->select();
        $this->assign('infos', $infos);
        return view();
    }
    /**
     * 编辑商品规格
     */
    public function GoodsSpecEdit(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $id = input('post.id');
            $items = explode(",",input('post.items'));
            unset($data['items']);
            Db::name('spec')->update($data);
            Db::name('spec_item')
                ->where('spec_id',$id)
                ->delete();
            foreach($items as $val){
                    Db::name('spec_item')
                        ->insert(['spec_id'=>$id,"item"=>$val]);
                }
            return ['status'=>1,'msg'=>'商品规格编辑成功'];
        }
        $id = input('id');
        if(Db::name('')){

        }
        $info = Db::name('spec')
                        ->find($id);
        $cates = Db::name('goods_type')
                        ->select();
        $arr = Db::name("spec_item")
                        ->field('item')
                        ->where('spec_id',$id)
                        ->select();
        foreach ($arr as $value) {
            $arr1[] = $value['item'];
        }
        $items = implode(',', $arr1);
        $this->assign('info',$info);
        $this->assign('cates',$cates);
        $this->assign('items',$items);
        return view();
    }
    /**
     * 删除商品规格
     */
    public function goodsSpecDel(){
        $id = input('post.id');
        if(empty($id)){
            return ['status'=>0,'msg'=>'规格项ID非法'];
        }
        $res = Db::name('spec_item')
                ->where('spec_id',$id)
                ->count();
        $res1 = Db::name('spec s')
                ->field('s.id')
                ->join('spec_item i','s.id=i.spec_id')
                ->count();
        if($res1){
            return ['status'=>0,'msg'=>'此规格下有商品请清空后删除'];
        }
        if($res){
            return ['status'=>0,'msg'=>'此规格下有规格项请清空后删除'];
        }
        if(Db::name('spec')->delete($id)){
            return ['status'=>1,'msg'=>'规格项删除成功'];
        }else{
            return ['status'=>0,'msg'=>'规格项删除失败'];
        }
    }
    // 商品评论
     public function comList(){
        $where = []; // 数据查询条件
        $condition = []; // 条件数据
        // 搜索关键字处理
        $user_name = input('get.keyword');
//        dump($user_name);
        if(!empty($user_name)){
            $where['c.goods_id'] = ['like',"%$user_name%"];
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
        $where['add_time'] = ['between', [$start_date, $end_date]];
        $data = Db::name('comment')
                ->alias('c')
                ->field('c.*,g.goods_name')
                ->join('order_goods g','c.rec_id=g.rec_id')
                ->where($where)
            ->paginate(8, false, ['query'=> $condition]);
//                ->select();
//        dump($data);
        $this->assign('condition', $condition);
        $this->assign('data', $data);
        return view();
    }
   
}
