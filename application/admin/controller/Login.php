<?php
namespace app\admin\controller;
use think\Controller;
use think\Validate;
use think\captcha\Captcha;
use app\Common\Model\ChannelUserModel;

/**
 * 渠道用户登录
 * @author yhs 2019.09.20
 */
class Login extends Controller
{   
    public function user()
    {   
        return $this->fetch('login:user');
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
        if( empty($data['captcha']) || !$captcha->check($data['captcha'])){
            $this->error('验证码有误！');
        }
        $model = new ChannelUserModel();
        $condition = ['account'=>$data['account']];
        $res = $model->where($condition)->find();
        if(empty($res)){
            $this->error('找不到账号');
        }
        if($res['password'] == md5($data['password'])){
            session('channel_uid',$res['id']);
            session('account',$res['account']);
            session('is_login',2);
            $this->redirect('Channel/index');
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
        session('channel_uid',NUll);
        $url = url("Login/user");
        $this->redirect($url);
    }
}
