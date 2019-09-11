<?php

namespace app\Common\Controller;
use app\Common\Model\AdminUserModel;
use think\Validate;
use think\Controller;

class BaseController extends Controller {

    public function __construct() {
        parent::__construct();
        include_once dirname(dirname(__FILE__)) . "/const.php";
        include_once dirname(dirname(__FILE__)) . "/define.php";
    }

    public function adminLogin($data){
        $validate = new Validate([
            'user_name' => 'require',
            'password' => 'require',
        ]);

        if(!$validate->check($data)){
            var_dump($this->error($validate->getError()));
        }

        $model = new AdminUserModel();
        $res = $model->get_admin_info($data['user_name']);
        if(empty($res)){
            $this->error('找不到账号');
        }
        if($res[0]['password'] == $data['password']){
            session('admin_uid',$res[0]['admin_id']);
            session('user_name',$res[0]['user_name']);
            $this->success('登录成功',url('index/index'));
        }else{
            $this->error('密码错误');
        }
    }

}
