<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;

class Index extends AdminBaseController
{   
    public function __construct() {
		parent::__construct();
	}
    public function index()
    {
        return $this->adminTpl();
    }
}
