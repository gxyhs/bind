<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Request;
use think\File;
use think\loader;
use app\Common\Model\CallCaseModel;
use think\facade\App;
use app\Common\Model\AdminUserModel;
use app\Common\Model\ChannelUserModel;
use think\Db;
class Index extends AdminBaseController
{   
    protected $adminUser;
    public function __construct() {
        parent::__construct();
        $this->CallCase = new CallCaseModel();
        $this->adminUser = new AdminUserModel();
        $this->ChannelUser = new ChannelUserModel();
	}
    public function index(){
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $list = $this->CallCase->field('id,task_id,phone,extend_id,case_message,add_time')->where([['extend_id','like',"%".input('search')."%"]])->limit($start,$length)->select();
                
                $list = $this->object_array($list);
                foreach ($list as $k=>$v){
                    $find =  Db::table('sys_call_case_task')->field('id,name')->where('id',$v['task_id'])->find();
                    $list[$k]['task_id'] = $find['name'];
                    $list[$k]['id'] = '<input type="checkbox" class="ids" id="'.$v['id'].'">';
                    $list[$k][] = $this->bt_onclick('call_del',$v['id'],lang('delete'));
                }
                $count = $this->CallCase->where([['extend_id','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        return $this->fetch();
    }
    
    public function change_password(){
        if(IS_POST){
            $original = md5(trim($_POST['original_password']));
            $uid = session('channel_uid');
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
            foreach($res as $k=>$v){
                $res[$k]['add_time'] = date('Y-m-d H:i:s');
                $res[$k]['channel_id'] = session('admin_uid');
            }
            if(is_array($res)){
               $result = $this->CallCase->insertAll($res);
               if($result){
                    $this->redirect('Index/index');
               }else{
                   $this->error('导入失败');
               }
            }
        }
        return $this->fetch();
    }
    public function call_del(){
        $del = $this->CallCase->where(['id'=>input('id')])->delete();
        if($del){
            $this->redirect('Index/index');
        }else{
            $this->error('error');
        }
    }
    public function batch_del(){
        if(input('ids')){
            $ids = explode(',',input('ids'));
            $del = $this->CallCase->where(['id'=>$ids])->delete();
            if($del){
                $this->redirect('Index/index');
            }else{
                $this->error('error');
            }
        }else{
            $this->error('empty ids');
        }
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
