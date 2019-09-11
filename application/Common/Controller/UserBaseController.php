<?php
namespace app\Common\Controller;
use app\Common\Model\AdminUserModel;
use think\Validate;
class UserBaseController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
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
        $validate = new Validate($rule,$msg,);
        if(!$validate->check($data)){
            var_dump($this->error($validate->getError()));die;
        }
        if($data['password'] != $data['re_password']){
            $this->error('两次密码输如不一致');
        }
        unset($data['re_password']);
        unset($data['policy']);
        unset($data['__token__']);
        $data['sex'] = $data['option-yes'];
        unset($data['option-yes']);
        $model = new AdminUserModel();
        $res = $model->add_user($data);
        if($res){
            $this->success('注册成功');
        }else{
            $this->error('注册失败');
        }
    }
}