<?php
namespace app\admin\controller;
use think\Controller;
use think\Validate;
use app\Common\Model\AdminUserModel;

/**
 * 登录
 * @author yhs 2019.09.10
 */
class Login extends Controller
{   
    public function index()
    {   
        return $this->fetch('login:index');
    }

    public function login()
    {
        return $this->adminLogin(array_filter($_POST));
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
        $condition = ['user_name'=>$data['user_name']];
        $res = $model->get_admin_info($condition);
        if(empty($res)){
            $this->error('找不到账号');
        }
        if($res['password'] == md5($data['password'])){
            $model->where($condition)->setInc('munber');
            session('admin_uid',$res['id']);
            session('user_name',$res['user_name']);
            $this->success('登录成功',url('index/index'));
        }else{
            $this->error('密码错误');
        }
    }
    public function logout() {
        session(NULL);
        $url = url("login/index");
        $this->redirect($url);
    }
}
