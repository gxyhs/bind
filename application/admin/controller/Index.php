<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;

class Index extends AdminBaseController
{
    public function index()
    {
        return $this->adminTpl();
    }
}
