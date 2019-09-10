<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;

class Login extends AdminBaseController
{
    public function index()
    {   
        $find = Db::name('login')->where('id',1)->find();print_r($find);die;
        return $this->adminTpl();
    }
}
