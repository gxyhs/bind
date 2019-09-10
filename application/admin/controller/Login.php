<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;

class Login extends AdminBaseController
{
    public function index()
    {   
        return $this->fetch('login:index');
    }

    public function login()
    {
        return $this->adminLogin(array_filter($_POST));
    }
}
