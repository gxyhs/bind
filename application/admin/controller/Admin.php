<?php
namespace app\admin\controller;
use think\Controller;
use think\Validate;
use think\captcha\Captcha;
use app\Common\Model\AdminUserModel;
use think\facade\Cookie;

/**
 * 管理员登录
 * @author yhs 2019.09.10
 */
class Admin extends Controller
{   
    public function index()
    {   
        return $this->fetch('admin:index');
    }

    public function login()
    {
        return $this->adminLogin(array_filter($_POST));
    }
    public function adminLogin($data){
        $validate = new Validate([
            'account' => 'require',
            'password' => 'require',
        ]);

        if(!$validate->check($data)){
            $this->error($validate->getError());
        }
        $captcha = new Captcha();
        if(empty($data['captcha']) ||  !$captcha->check($data['captcha'])){
            $this->error('验证码有误！');
        }
        $model = new AdminUserModel();
        $condition = ['account'=>$data['account']];
        $res = $model->get_admin_info($condition);
        if(empty($res)){
            $this->error('找不到账号');
        }
        if($res['password'] == md5($data['password'])){
            session('admin_uid',$res['id']);
            session('account',$res['account']);
            session('is_login',1);
            $this->redirect('index/index');
        }else{
            $this->error('密码错误');
        }
    }
    function verify(){
        $captcha = new Captcha();
        return $captcha->entry();
    }
    public function logout() {
        session('is_login',NUll);
        session('admin_uid',NULL);
        $url = url("Admin/index");
        $this->redirect($url);
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
