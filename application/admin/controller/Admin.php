<?php
/**
 *管理员
 */
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Paginator;
use think\Session;
use app\admin\controller\Base;

class Admin extends Base
{ 
    /**
     *显示管理员列表
     */
    public function adminList()
    {
        $where = []; // 数据查询条件
        $condition = []; // 条件数据
        // 搜索关键字处理
        $user_name = input('get.keyword');
        if(!empty($user_name)){
            $where['user_name'] = ['like', "%{$user_name}%"];
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
        $admins = Db::name('admin')
                        ->alias('a')
                        ->field('a.*,r.role_name')
                        ->join('__ADMIN_ROLE__ r', 'a.role_id=r.role_id')
                        ->where($where)
                        ->order('admin_id ASC')
                        ->paginate(10, false, ['query'=> $condition]);
        $this->assign('condition', $condition);
        $this->assign('admins', $admins);
        return view();
    }
    /**
     *添加管理员
     */
    public function adminAdd(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            //dump($data['role_id']);exit;
            $data['password'] = md5($data['password']);
            $data['add_time'] = time();
            $data['last_login'] = time();
            $data['is_lock'] = 1;
            $data['last_ip']=$_SERVER['REMOTE_ADDR'];
                //添加进数据库
            if(Db::name('admin')->where('user_name',$data['user_name'])->count()){ 
                return ['status'=>0,'msg'=>'此用户名已被注册，请更换'];
            }
            //入库
            if(Db::name('admin')->insertGetId($data)){
                return ['status'=>1,'msg'=>'添加成功'];
            }else{
                return ['status'=>0,'msg'=>'添加失败'];
            }
        }else{
            $role = Db::name('admin_role')->select();
            $this->assign('role',$role);
            return $this->fetch();
        }
    }
    /**
     *编辑管理员
     */
    public function adminEdit(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            if(!empty($data['password'])){
                $data['password'] = md5($data['password']);
            }else{
                unset($data['password']);
            }
            //检测用户名是否存在
            $rs = Db::name('admin')
                        ->where('user_name','=',$data['user_name'])
                        ->where('admin_id','<>',$data['admin_id'])
                        ->count();
            if($rs){
                return ['status'=>0,'msg'=>'此用户名已被注册，请更换'];
            }
            //更新
            if(Db::name('admin')->update($data)){
                return ['status'=>1,'msg'=>'编辑成功'];
            }else{
                return ['status'=>1,'msg'=>'编辑成功'];
            }
        }else{
            $admin_id = input('admin_id');
            $info = Db::name('admin')
                        ->where('admin_id',$admin_id)
                        ->find();
            // 角色
            $roles = Db::name('admin_role')
                                ->order('role_id ASC')
                                ->select();  
            $this->assign('info', $info);
            $this->assign('roles', $roles);
            return $this->fetch();
        }
    }
    /**
     *删除管理员
     */
    public function adminDel()
    {
        $admin_id = input('post.admin_id');
        if(Db::name('admin')->delete($admin_id)){
            return ['status'=>1,'msg'=>''];
        }else{
            return ['status'=>0,'msg'=>''];
        }
    }
    /**
     *批量删除管理员
     */
    public function adminsDel()
    {
        $ids = input('post.ids');
        $ids = explode(',',substr($ids,0,-1));
        if(Db::name('admin')->delete($ids)){
            return ['status'=>1,'msg'=>''];
        }else{
            return ['status'=>0,'msg'=>''];
        }
    }
    /**
     *停用管理员
     */
    public function adminLock()
    {
        $admin_id = input('post.admin_id');
        $type = input('post.type');
        if($type){
            $data = ['is_lock'=>0];
        }else{
            $data = ['is_lock'=>1];
        }
        if(Db::name('admin')->where('admin_id',$admin_id)->update($data)){
            return 'true';
        }else{
            return 'false';
        }
    }
    /**
     * 权限管理
     */
    public function roleList()
    {
        $roles = Db::name('admin_role')
                            ->order('role_id DESC')
                            ->select();
        $this->assign('roles', $roles);
        return view();
    }
    public function roleAdd(Request $request)
    {
        if($request->isPost()){
            $data['role_name'] = input('post.role_name');
            $data['role_desc'] = input('post.role_desc');
            if(Db::name('admin_role')->where('role_name', $data['role_name'])->count()){
                return ['status'=>0, 'msg'=> '角色名称已存在!'];
            }
            // 校验是否选择权限
            $array = input('post.');
            if(empty($array['access'])){
                return ['status'=>0, 'msg'=> '权限不能为空!'];
            }
            // 角色入库
            if($role_id = Db::name('admin_role')->insertGetId($data)){
                // 权限入库
                $access = $array['access'];
                $accessData = [];
                foreach($access as $key=>$val){
                    $accessData[$key]['role_id'] = $role_id;
                    $accessData[$key]['right_id'] = $val;
                }
                Db::name('access')->insertAll($accessData);
                return ['status'=>1, 'msg'=> '操作成功!'];
            }else{
                return ['status'=>0, 'msg'=> '操作失败!'];
            }
        }
        $this->assign('menus', $this->getMenu());
        return view();
    }
    public function roleDel()
    {
        $role_id = input('post.role_id');
        if(Db::name('admin')->where('role_id', $role_id)->count()){
            return ['status'=>0, 'msg'=> '请先清空所属该角色的管理员!'];
        }
        if(Db::name('admin_role')->delete($role_id)){
            // 删除权限
            Db::name('access')->where('role_id', $role_id)->delete();

            return ['status'=>1, 'msg'=> '操作成功!'];
        }else{
            return ['status'=>0, 'msg'=> '操作失败!'];
        }
    }
    public function roleEdit(Request $request)
    {
        if($request->isPost()){
            $role_id = input('post.role_id');
            $data['role_name'] = input('post.role_name');
            $data['role_desc'] = input('post.role_desc');
            $data['last_modify_time'] = time();

            $map['role_name'] = $data['role_name'];
            $map['role_id'] = ['<>', $role_id];
            if(Db::name('admin_role')->where($map)->count()){
                return ['status'=>0, 'msg'=> '角色名称已存在!'];
            }
            // 校验是否选择权限
            $array = input('post.');
            if(empty($array['access'])){
                return ['status'=>0, 'msg'=> '权限不能为空!'];
            }
            if(Db::name('admin_role')->where('role_id', $role_id)->update($data)){
                // 删除角色权限
                Db::name('access')
                            ->where('role_id', $role_id)
                            ->delete();
                // 权限入库
                $access = $array['access'];
                $accessData = [];
                foreach($access as $key=>$val){
                    $accessData[$key]['role_id'] = $role_id;
                    $accessData[$key]['right_id'] = $val;
                }
                Db::name('access')->insertAll($accessData);

                return ['status'=>1, 'msg'=> '操作成功!'];
            }else{
                return ['status'=>0, 'msg'=> '操作失败!'];
            }
        }
        // 获取角色信息
        $role_id = input('role_id');
        $roleInfo = Db::name('admin_role')
                                ->find($role_id);
        // 获取角色的权限信息
        $roleAccess = Db::name('access')
                                ->field('right_id')
                                ->where('role_id', $role_id)
                                ->select();
        $roleAccessData = [];
        foreach ($roleAccess as $value) {
            $roleAccessData[] = $value['right_id'];
        }

        $this->assign('menus', $this->getMenu());
        $this->assign('roleInfo', $roleInfo);
        $this->assign('roleAccessData', $roleAccessData);
        return view();
    }
    /**
     * [getMenu 获取菜单]
     * @return [type] [description]
     */
    public function getMenu()
    {
        // 获取菜单信息
        $menus = [];
        // (1) 获取顶级菜单进行处理
        $parents = Db::name('system_module')
                            ->where('parent_id', 0)
                            ->select();
        $ids = [];
        foreach($parents as $parent){
            $menus[$parent['mod_id']] = $parent;
            $ids[] = $parent['mod_id'];
        }
        // (2) 通过 $ids 获取所有的顶级菜单的子菜单
        $childs = Db::name('system_module')
                            ->where('parent_id', 'in', $ids)
                            ->select();
        // (3) 处理子菜单
        foreach($childs as $child){
            $menus[$child['parent_id']]['childNodes'][] = $child;
        }
        return $menus;
    }
    /**
     * [profile 个人信息]
     * @return [type] [description]
     */
    public function profile(Request $request){
        $admin_id = Session::get('admin_info.admin_id');
        if($request->isPost()){
            $data['user_name'] = input('post.user_name');
            $data['email'] = input('post.email');
            $password = input('post.password');
            if(!empty($password)){
                $old_password = input('post.old_password');
                $admin = Db::name('admin')
                                ->where('admin_id', $admin_id)
                                ->find();
                if(!$admin || $admin['password'] != md5($old_password)){
                    return ['status'=>0, 'msg'=> '旧密码不正确!'];
                }
                $data['password'] = md5($password);
            }
            // 更新个人信息
            Db::name('admin')->where('admin_id', $admin_id)->update($data);
            return ['status'=>1, 'msg'=> '操作成功!'];
        }
        $profile = Db::name('admin')
                        ->find($admin_id);
        $this->assign('profile', $profile);
        return view();
    }
    /**
     * [su_user 切换用户]
     * @return [type] [description]
     */
    public function su_user()
    {
        return view();
    }
}
