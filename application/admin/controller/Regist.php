<?php
namespace app\admin\controller;
use app\Common\Controller\UserBaseController;
use think\Db;
/**
 * 登录
 * @author yhs 2019.09.10
 */
class Regist extends UserBaseController
{
    public function index()
    {
        return $this->fetch('regist:index');
    }

    public function regist()
    {
        $data = request()->param();
        return $this->user_regist($data);
    }
}