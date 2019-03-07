<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Picture extends base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function pictureList()
    {
        return $this->fetch();
    }
}
