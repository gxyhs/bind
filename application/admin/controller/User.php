<?php
/**
 * 客户管理
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use app\Common\Model\AdminUserModel;
use app\Common\Model\UserBingModel;
class User extends AdminBaseController
{   
    public function __construct() {
        parent::__construct();
        $this->adminUser = new AdminUserModel();
        $this->userBing = new UserBingModel();
	}
    public function customer_list()
    {   
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                if(!empty(input('search'))){
                    $list = $this->adminUser->field('id,user_name,email,create_time')->where(['is_admin'=>1])->where([['user_name','like',"%".input('search')."%"]])->whereOr([['email','like',"%".input('search')."%"]])->limit($start,$length)->select();
                }else{
                    $list = $this->adminUser->field('id,user_name,email,create_time')->where(['is_admin'=>1])->limit($start,$length)->select();
                }
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                    $list[$k]['operating'] = $this->bt_onclick('user_edit',$v['id'],lang('edit')).$this->bt_onclick('user_del',$v['id'],lang('delete'));
                }//print_r($list);die;
                $count = $this->adminUser->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        
        $this->assign('search',input('search'));
        return $this->fetch();
    }
    public function user_add(){
        if(empty(input('user_name')) || empty(input('email'))){
            $back['message'] = "Memiliki opsi yang tidak terisi";
            $back['status'] = 0;
            return json($back);
        }
        if(empty(input('id'))){
            if(empty(input('password'))){
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
    public function user_del(){
        $del = $this->adminUser->where(['id'=>input('id')])->delete();
        if($del){
            $this->success('success');
        }else{
            $this->success('success');
        }
        
    }
    /**
     * 话机管理模块
     */
    public function telephone()
    {   
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $condition = array();
                if(input('user_id') != -1){
                    $condition = ['user_id'=>input('user_id')];
                }
                $list = $this->userBing->field('id,bing_name,password,user_id')->where($condition)->where([['bing_name','like',"%".input('search')."%"]])->limit($start,$length)->select();
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $username = $this->adminUser->where(['id'=>$v['user_id']])->value('user_name');
                    $list[$k]['user_id'] = $username;
                    $url = url('User/tel_details',['id'=>$v['id']]);
                    $list[$k]['operating'] = $this->operating($url,lang('details')).$this->bt_onclick('user_edit',$v['id'],lang('edit')).$this->bt_onclick('tel_del',$v['id'],lang('delete'));
                }
                $count = $this->userBing->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        $this->assign('user_id',input('user_id'));
        $userList = $this->adminUser->field('id,user_name')->where(['is_admin'=>1])->select();
        $this->assign('userList', $this->object_array($userList));
        return $this->fetch();
    }
    public function tel_add(){
        if(empty(input('bing_name')) || input('user_id') == -1 || empty(input('password'))){
            $back['message'] = "Memiliki opsi yang tidak terisi";
            $back['status'] = 0;
            return json($back);
        }
        if(empty(input('id'))){
            $_POST['create_time'] = time();
        }else{
            $_POST['update_time'] = time();
        }
        try{
            if(empty(input('id'))){
                $this->userBing->insert($_POST);
            }else{
                $this->userBing->where(['id'=>input('id')])->update($_POST);
            }
            $back['message'] = "success";
            $back['status'] = 1;
        }catch(Exception $e){
            $back['message'] = $e->getMessage();
            $back['status'] = 0;
        }
        return json($back);
    }
    public function tel_edit(){
        if(input('id')){
            $find = $this->userBing->where(['id'=>input('id')])->find();
            return json($find);
        }
    }
    public function tel_del(){
        $del = $this->userBing->where(['id'=>input('id')])->delete();
        if($del){
            $this->success('success');
        }else{
            $this->success('success');
        }
        
    }
    /**
     * 话机详情
     */
    public function tel_details(){

        return $this->fetch();
    }
}
