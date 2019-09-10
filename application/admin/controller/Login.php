<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;

class Login extends AdminBaseController
{
    public function index()
<<<<<<< HEAD
    {   
        $find = Db::name('login')->where('id',1)->find();print_r($find);die;
=======
    {
>>>>>>> cf5537026b1ad47e79c9af2dc13e0cbb3c6d8b4c
        return $this->adminTpl();
    }
}
