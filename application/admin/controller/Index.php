<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;
use think\facade\Cookie;
class Index extends AdminBaseController
{   
    public function __construct() {
		parent::__construct();
	}
    public function index()
    {
        return $this->fetch();
    }
    public function lang(){
        $lang = input('?get.lang') ? input('get.lang') : 'ZH-CN';
        switch ($lang) {
            case 'ZH-CN':
                cookie('think_var', 'ZH-CN');
                break;
            case 'EN':
                cookie('think_var', 'EN');
                break;
            case 'INDO':
                cookie('think_var', 'INDO');
                break;
            default:
                cookie('think_var', 'ZH-CN');
        }
    }
}
