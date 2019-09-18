<?php
/**
 * 客户管理
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use app\Common\Model\AdminUserModel;
class User extends AdminBaseController
{   
    public function __construct() {
        parent::__construct();
        $this->adminUser = new AdminUserModel();
	}
    public function customer_list()
    {   
        if($_POST || $_GET){
            $table = new AdminUserModel();
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                if(!empty(input('search'))){
                    $list = $table->field('id,user_name,email,create_time')->whereOr([['user_name','like',"%".input('search')."%"]])->whereOr([['email','like',"%".input('search')."%"]])->limit($start,$length)->select();
                }else{
                    $list = $table->field('id,user_name,email,create_time')->limit($start,$length)->select();
                }
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                    $url = url('User/user_del',['id'=>$v['id']]);
                    $list[$k]['operating'] = $this->bt_onclick('user_edit',$v['id'],lang('edit')).$this->bt_onclick('user_del',$v['id'],lang('delete'));
                }//print_r($list);die;
                $count = $table->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        
        $this->assign('search',input('search'));
        return $this->fetch();
    }
    public function user_add(){
        if(empty(input('id'))){
            if(empty(input('user_name')) || empty(input('email')) || empty(input('password'))){
                $back['message'] = "Memiliki opsi yang tidak terisi";
                $back['status'] = 0;
                return json($back);
            }
            $_POST['create_time'] = time();
            $_POST['password'] = md5($_POST['password']);
        }else{
            if($_POST['password']){
                $_POST['password'] = md5($_POST['password']);
            }else{
                unset($_POST['password']);
            }
            $_POST['update_time'] = time();
        }
        $_POST['is_admin'] = 1;
        try{
            if(empty(input('id'))){
                $this->adminUser->insert($_POST);
            }else{
                $this->adminUser->where(['id'=>input('id')])->update($_POST);
            }
            $back['message'] = "success";
            $back['status'] = 1;
        }catch(Exception $e){
            $back['message'] = $e->getMessage();
            $back['status'] = 0;
        }
        return json($back);
    }
    public function user_edit(){
        if(input('id')){
            $find = $this->adminUser->field('id,user_name,email')->where(['id'=>input('id')])->find();
            return json($find);
        }
    }
    public function telephone()
    {   
        if($_POST || $_GET){
            $table = new AdminUserModel();
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                if(!empty(input('search'))){
                    $list = $table->field('id,user_name,email,create_time')->whereOr([['user_name','like',"%".input('search')."%"]])->whereOr([['email','like',"%".input('search')."%"]])->limit($start,$length)->select();
                }else{
                    $list = $table->field('id,user_name,email,create_time')->limit($start,$length)->select();
                }
                $list = $this->object_array($list);
                $this->assign('list',$list);
                $count = $table->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        return $this->fetch();
    }
}
