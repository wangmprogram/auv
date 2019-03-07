<?php
/**
 *  后台-权限管理
 */
namespace app\admin\controller;
use think\Request;
use think\Db;

class Access extends Base
{
    /**
     * 显示资源列表
     */
    public function accessList()
    {
        $mods = Db::name('system_module')->select();
        $this->assign('mods',$mods);
        return view();
    }
    public function accessAdd()
    {
        $list = Db::name('system_module')
                            ->where('module', 'menu')
                            ->select();
        $this->assign('list', $list);
        return view();
    }
    public function insert()
    {
        $data = input('post.');
        if($data['parent_id'] == 0){
            $data['module'] = 'menu';
            $data['level'] = 1;
        }else{
            $data['module'] = 'module';
            $data['level'] = 2;
        }
        Db::name('system_module')->insertGetId($data);
        $this->error('回去');
    }
}
