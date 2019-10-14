<?php
namespace app\admin\controller;
use think\Controller;
use think\Validate;
use app\Common\Model\AdminUserModel;
/**
 * 登录
 * @author yhs 2019.09.10
 */
class Regist extends Controller
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
    public function user_regist($data)
    {
        $rule = [
            'email' => 'require|email',
            'user_name' => 'require',
            'password' => 'require',
            'option-yes' => 'require',
            're_password' => 'require',
            'policy' => 'require',
            '__token__' => 'token',
        ];

        $msg = [
            'email.require' => '邮箱不能为空',
            'email.email' => '请输入正确的邮箱',
            'password.require' => '密码不能为空',
            'option-yes.require' => '性别不能为空',
            're_password' => '再次输入密码不能为空',
            'policy' => '请同意协议',
        ];
        $validate = new Validate($rule,$msg);
        if(!$validate->check($data)){
            $this->error($validate->getError());
        }
        $model = new AdminUserModel();
        if($model->where(['user_name'=>$data['user_name']])->find()){
            $this->error('Nama pengguna sudah ada');
        }
        if($data['password'] != $data['re_password']){
            $this->error('两次密码输入不一致');
        }
        $data['password'] = md5($data['password']);
        unset($data['re_password']);
        unset($data['policy']);
        unset($data['__token__']);
        $data['sex'] = $data['option-yes'];
        $data['create_time'] = time();
        unset($data['option-yes']);
        $res = $model->add_user($data);
        if($res){
            $this->success('注册成功',url('Login/index'));
        }else{
            $this->error('注册失败');
        }
    }
}