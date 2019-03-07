<?php
/**
 *文章
 */
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Paginator;
use think\Session;
use app\Admin\controller\Base;
class Article extends base
{
	/**
	 *文章分类
	 */
    public function articleCatList()
    {
        $cats = setCategoryData(Db::name('article_cat')
                        ->select());
        $this->assign('cats', $cats);
        return view();
    }
    public function articleCatAdd(Request $request)
    {
    	if($request->isPost()){
            $data = input('post.');
            $data['sort_order'] = isset($data['sort_order'])? $data['sort_order']:50;
            //添加进数据库
            if(Db::name('article_cat')->where('cat_name',$data['cat_name'])->count()){ 
                return ['status'=>0,'msg'=>'此分类已存在，请更换'];
            }
            //入库
            if(Db::name('article_cat')->insertGetId($data)){
                return ['status'=>1,'msg'=>'添加成功'];
            }else{
                return ['status'=>0,'msg'=>'添加失败'];
            }
        }else{
        	$cats = setCategoryData(Db::name('article_cat')->select());
        	$this->assign('cats', $cats);
            return $this->fetch();
        }
    }
    public function articleCatEdit(Request $request)
    {
    	if($request->isPost()){

            $data = input('post.');
            //检测用户名是否存在
            $rs = Db::name('article_cat')
                        ->where('cat_name','=',$data['cat_name'])
                        ->where('cat_id','<>',$data['cat_id'])
                        ->count();
            if($rs){
                return ['status'=>0,'msg'=>'此分类名已存在，请更换'];
            }
            //更新
            $data['sort_order'] = isset($data['sort_order'])? $data['sort_order']:50;
            if(Db::name('article_cat')->update($data)){
                return ['status'=>1,'msg'=>'编辑成功'];
            }else{
                return ['status'=>1,'msg'=>'编辑成功'];
            }
        }else{
            $cat_id = input('cat_id');
            $info = Db::name('article_cat')
                        ->where('cat_id',$cat_id)
                        ->find();
            // 上级分类
            $cats = setCategoryData(Db::name('article_cat')->select());
            $this->assign('info', $info);
            $this->assign('cats', $cats);
            $this->assign('cat_id',$cat_id);
            return $this->fetch();
        }
    }
    public function articleCatDel()
    {
    	$cat_id = input('post.cat_id');
        if(Db::name('article_cat')->where('parent_id',$cat_id)->count()){
            return ['status'=>0,'msg'=>'该分类存在子类，请先清空该子类'];
        }
        if(Db::name('article')->where('cat_id',$cat_id)->count()){
            return ['status'=>0,'msg'=>'该分类存在文章，请先清空该文章'];
        }
        if(Db::name('article_cat')->delete($cat_id)){
            return ['status'=>1,'msg'=>'删除成功'];
        }else{
            return ['status'=>0,'msg'=>'删除失败'];
        }
    }
    /**
	 *文章
	 */
    public function articleList()
    {
        $cats = setCategoryData(Db::name('article_cat')->select());
        $articles = Db::name('article')
                                ->order('article_id desc')
                                ->select();
        $this->assign('cats', $cats);
        $this->assign('articles',$articles);
        return view();
    }
    public function articleAdd(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            $data['add_time'] = strtotime($data['add_time']);
            //入库
            if(Db::name('article')->insertGetId($data)){
                return ['status'=>1,'msg'=>'添加成功'];
            }else{
                return ['status'=>0,'msg'=>'添加失败'];
            }
        }else{
            $cats = setCategoryData(Db::name('article_cat')->select());
            $this->assign('cats', $cats);
            return $this->fetch();
        }
    }
    public function articleEdit(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            $article_id = $data['article_id'];
            //入库
            if(Db::name('article')->where('article_id',$article_id)->update($data)){
                return ['status'=>1,'msg'=>'修改成功'];
            }else{
                return ['status'=>0,'msg'=>'修改失败'];
            }
        }else{
            $article_id = input('article_id');
            $article = Db::name('article')
                                    ->where('article_id',$article_id)
                                    ->find();
            $cats = setCategoryData(Db::name('article_cat')->select());
            $this->assign('cats', $cats);
            $this->assign('article', $article);
            return $this->fetch();
        }
    }
    public function articleDel()
    {
        $article_id = input('post.article_id');
        if(Db::name('article')->delete($article_id)){
            return ['status'=>1,'msg'=>'删除成功'];
        }else{
            return ['status'=>0,'msg'=>'删除失败'];
        }
    }
}