<?php

namespace app\Common\Controller;
use think\facade\Cookie;
use think\Lang; 
use app\Common\Model\AdminUserModel;
use app\Common\Model\CommonModel;
use think\Exception;
use think\Validate;
class AdminBaseController extends BaseController {

    public function __construct($checkLogin = True) {
        parent::__construct();
        // if ($checkLogin) {
        //     $this->isLogin();
        // }
        $controller = strtolower(CONTROLLER_NAME);
        $action = strtolower(ACTION_NAME);
        $tempname = '/' . $controller . '/' . $action;
        $this->assign('controller', $controller);
        $this->assign('action', $action);
        $this->assign('tempname', $tempname);
        
        // $lang = lang('title');
        // print_r($lang);die;
        //语言切换
        if(!(Cookie::has('think_var'))){
            $this->lang();
        }
        $lang = empty(Cookie('think_var')) ? 'ZH-CH' : Cookie('think_var');
        $this->assign('think_lang',$lang);
    }

    protected function isLogin() {
        $admin_uid = session('admin_uid');
        if (empty($admin_uid)) {
            $url = url('Login/index');
            $this->redirect($url);
            exit;
        }
    }

    public function jump404() {
        //只有在app_debug=False时才会正常显示404页面，否则会有相应的错误警告提示
        abort(404, '页面异常');
    }
    //空方法
    public function _empty() {
        return $this->jump404();
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
            $this->success('登录成功',url('index/index'));
        }else{
            $this->error('密码错误');
        }
    }

}
