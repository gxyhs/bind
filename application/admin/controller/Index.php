<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;
use think\Request;
use think\File;
use think\loader;
use think\facade\Cookie;
use app\Common\Model\AdminUserModel;
use think\facade\App;
class Index extends AdminBaseController
{   
    protected $adminUser;
    public function __construct() {
        parent::__construct();
        $this->adminUser = new AdminUserModel();
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
    public function change_password(){
        if(IS_POST){
            $original = md5(trim($_POST['original_password']));
            $uid = session('admin_uid');
            $condition = ['id'=>$uid];
            $oglFind = $this->adminUser->where($condition)->find();
            $back = [];
            if($original != $oglFind->password){
                $back['message'] = "Kata sandi asli salah";
                $back['status'] = 0;
                return json($back);
            }
            if(trim($_POST['new_password']) != trim($_POST['confirm_password'])){
                $back['message'] = "Dua kata sandi tidak konsisten";
                $back['status'] = 0;
                return json($back);
            }
            try{
                $this->adminUser->where($condition)->update(['password'=>md5(trim($_POST['new_password']))]);
                $back['message'] = "success";
                $back['status'] = 1;
            }catch(Exception $e){
                $back['message'] = $e->getMessage();
                $back['status'] = 0;
            }
            return json($back);
        }else{
            return json(['status'=>0]);
        }
    }

    /**
     * 
     */
    public function demo_out(){
        $data = Db::table('tp_admin_user')->field('id,sex,email,user_name')->select();
        $head = ['id','sex','emal','user_naem'];
        $name = 'user';
        return leading_out($data,$head,$name);
        exit;
    }

    /**
     * ���Ե���
     */
    public function demo_in(){
        if($_FILES){
            $file = request()->file('excel');
            $res = leading_in($file);
            if(is_array($res)){
                $this->success('success',url('Index/index'));
            }
        }else{
            return $this->fetch();
        }
    }
}
