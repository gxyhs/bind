<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;
use think\File;
use think\loader;
use think\facade\Cookie;
use think\Validate;
use app\Common\Model\AdminUserModel;

class Index extends AdminBaseController
{   
    protected $adminUser;
    public function __construct() {
        parent::__construct();
        $this->adminUser = new AdminUserModel();
	}
    public function index()
    {
        if($_POST || $_GET){
            $table = DB::table('tp_execl');
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                if(!empty(input('search'))){
                    $list = $table->whereOr([['user_name','like',"%".input('search')."%"]])->whereOr([['email','like',"%".input('search')."%"]])->limit($start,$length)->select();
                }else{
                    $list = $table->limit($start,$length)->select();
                }
                $list = $this->object_array($list);
                foreach ($list as $k=>$v){
                    $whatever = '';
                    foreach ($v as $kk=>$vv){
                        $whatever .= $kk.'='.$vv.'&';
                    }
                    $whatever = trim($whatever,'&');
                    $button = '';
                    $button .= "<button type='button' class='btn btn-default btn-md' data-toggle='modal' data-whatever='".$whatever."' data-target='#editRoleModal'>修改</button>";
                    $button .= "<a class='btn btn-info btn-delete' data-id=".$v['id']." href=".url('Index/delete',['id'=>$v['id']]).">删除</a>";
                    $list[$k][] = $button;
                }
//                $this->assign('list',$list);
                $count = $table->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
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
     * 测试导出
     */
    public function demo_out(){
        $data = Db::table('tp_execl')->select();
        $head = ['id','lab_one','lab_two'];
        $name = 'user';
        return leading_out($data,$head,$name);
        exit;
    }

    /**
     * 测试导入
     */
    public function demo_in(){
        if($_FILES){
            $file = request()->file('excel');
            $res = leading_in($file);
            if(is_array($res)){
               $result = DB::table('tp_execl')->insertAll($res);
               if($result){
                   $this->success('导入成功',url('Index/index'));
               }else{
                   $this->error('导入失败');
               }
            }
        }
        return $this->fetch();

    }
    public function add(){
        if(IS_POST){
            $data = input('');
            $rule = [
                'lab_one' => 'require',
                'lab_two' => 'require',
            ];
            $msg = [
                'lab_one.require' => '不能为空',
                'lab_two.require' => '不能为空',
            ];
            $validate = new Validate($rule,$msg);
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = DB::table('tp_execl')->insert($data);
            if($res){
                $this->success('添加成功',url('Index/index'));
            }else{
                $this->error('添加失败');
            }
        }else{
            exit;
        }
    }
    public function edit(){
        if(IS_POST){
            $data = input('');
            $rule = [
                'operator_id'      => 'require',
                'lab_one' => 'require',
                'lab_two' => 'require',
            ];
            $msg = [
                'operator_id.require'      => '不能为空',
                'lab_one.require' => '不能为空',
                'lab_two.require' => '不能为空',
            ];
            $validate = new Validate($rule,$msg);
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $condition['id'] = $data['operator_id'];
            unset($data['operator_id']);
            $res = DB::table('tp_execl')->where($condition)->update($data);
            if($res){
                $this->success('修改成功',url('Index/index'));
            }else{
                $this->error('修改失败');
            }
        }else{
            exit;
        }
    }

    public function delete(){
        if(IS_GET){
            $condition['id'] = input('id') ?? $this->error('删除出错');
            $res = Db::table('tp_execl')->where($condition)->delete();
            if($res){
                $this->success('删除成功',url('Index/index'));
            }else{
                $this->error('删除失败');
            }
        }
    }

}
