<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;

class Login extends AdminBaseController
{
    public function index()
    {
        return $this->adminTpl();
    }
}
