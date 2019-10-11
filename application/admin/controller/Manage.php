<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\facade\Cookie;
use app\Common\Model\AdminUserModel;
class Manage extends AdminBaseController
{
    public function __construct() {
        parent::__construct();
        $this->adminUser = new AdminUserModel();
    }
    /**
     * 管理员模块
     *  @author yhs 2019.09.20
     */
    public function manage_list(){
        if(input()){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                
                $list = $this->adminUser->field('id,account,add_time')->where([['account','like',"%".input('search')."%"]])->limit($start,$length)->select();
                
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['operating'] = $this->bt_onclick('user_edit',$v['id'],lang('edit')).$this->bt_onclick('user_del',$v['id'],lang('delete'));
                }//print_r($list);die;
                $count = $this->adminUser->where([['account','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        return $this->fetch('index/manage_list');
    }
    public function manage_add(){
        if(empty(input('account'))){
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
            $_POST['password'] = md5($_POST['password']);
        }else{
            if($_POST['password']){
                $_POST['password'] = md5($_POST['password']);
            }else{
                unset($_POST['password']);
            }
        }
        $_POST['add_time'] = date('Y-m-d H:i:s');
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
    public function manage_edit(){
        if(input('id')){
            $find = $this->adminUser->field('id,account')->where(['id'=>input('id')])->find();
            return json($find);
        }
    }
    public function manage_del(){
        $del = $this->adminUser->where(['id'=>input('id')])->delete();
        if($del){
            $this->redirect('Manage/manage_list');
        }else{
            $this->success('success');
        }
        
    }
}