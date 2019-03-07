<?php
/**
 *后台-首页控制器
 */
namespace app\admin\controller;
use think\Request;
use think\Session;
use think\Db;

class Index extends Base
{
    /**
     * 首页
     */
    public function index()
    {   
        //获取当前按用户的角色
        $user_id = Session::get('admin_info.admin_id');
        $res = Db::name('admin')->find();
        $role_id = $res['role_id'];
        $role = Db::name('admin_role')->where('role_id',$role_id)->find();
        $role_name = $role['role_name'];
        $this->assign('role_name',$role_name);
        //根据角色获取权限
        $role_id = Session::get('admin_info.role_id');
        $access = Db::name('access')
                            ->field('right_id')
                            ->where('role_id',$role_id)
                            ->select();
        //将获取的权限编号的二维数组转换成一维数组
        $rightIds = [];
        foreach($access as $value){
            $rightIds[] =$value['right_id'];
        }
        $menus = [];
        if($role_id==1){
            //超级管理员获取菜单
            $top_menu = Db::name('system_module')
                                ->where('parent_id',0)
                                ->select();
        }else{
            //普通管理员获取菜单
            $top_menu = Db::name('system_module')
                                ->where('mod_id','in',$rightIds)
                                ->where('parent_id',0)
                                ->select();
        }
        $ids = [];
        foreach($top_menu as $value){
            $menus[$value['mod_id']] = $value;
            $ids[] = $value['mod_id']; 
        }
        //获取子菜单
        $child_menu = Db::name('system_module')
                                    ->where('parent_id','in',$ids)
                                    ->select();
        foreach($child_menu as $value){
            $menus[$value['parent_id']]['childNode'][] = $value;
        }
        $this->assign('menus',$menus);
        return $this->fetch();
    }
    /**
     *欢迎页面
     */
    public function welcome()
    {
        return $this->fetch();
    }
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal()
    {  
            $table = input('table'); // 表名
            $id_name = input('id_name'); // 表主键id名
            $id_value = input('id_value'); // 表主键id值
            $field  = input('field'); // 修改哪个字段
            $value  = input('value'); // 修改字段值
            Db::name($table)->where($id_name,$id_value)->update([$field=>$value]); // 根据条件保存修改的数据
    }
}
