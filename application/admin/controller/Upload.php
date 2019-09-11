<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;

class Upload extends AdminBaseController
{   

    public function list()
    {
        return $this->fetch();
    }
}
