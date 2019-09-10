<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
class Login extends AdminBaseController
{
    public function index()
    {
        return $this->adminTpl();
    }
}
