<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Comments extends base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function feedbackList()
    {
        return $this->fetch();
    }
}
