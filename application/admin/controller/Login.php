<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;
/**
 * 登录
 * @author yhs 2019.09.10
 */
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
