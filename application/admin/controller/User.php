<?php
/**
 * 客户管理
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use app\Common\Model\ChannelUserModel;
use app\Common\Model\SoftphoneModel;

class User extends AdminBaseController
{   
    public function __construct() {
        parent::__construct();
        $this->channelUser = new ChannelUserModel();
        $this->softphone = new SoftphoneModel();
        $this->status = [
            lang('offline'),
            lang('online'),
            lang('in_the_call'),
            lang('talking'),
        ];
	}
    public function customer_list()
    {   
        if(input()){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $list = $this->channelUser->field('id,caller_prefix,account,password_txt,secret_key,secret_token,add_time')->where([['account','like',"%".input('search')."%"]])->order('id desc')->limit($start,$length)->select();
               
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['operating'] = $this->bt_onclick('user_edit',$v['id'],lang('edit')).$this->bt_onclick('user_del',$v['id'],lang('delete'));
                }//print_r($list);die;
                $count = $this->channelUser->where([['account','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        return $this->fetch();
    }
    public function user_add(){
        if(empty(input('account')) || empty(input('caller_prefix'))){
            $back['message'] = "Memiliki opsi yang tidak terisi";
            $back['status'] = 0;
            return json($back);
        }
        $_POST['caller_prefix'] = intval($_POST['caller_prefix']); 
        $_POST['password_txt'] = $_POST['password']; 
        if(empty(input('id'))){
            if(empty(input('password'))){
                $back['message'] = "Memiliki opsi yang tidak terisi";
                $back['status'] = 0;
                return json($back);
            }
            $_POST['password'] = md5($_POST['password']);
            $_POST['secret_key'] = $this->guid().$this->guid();
            $_POST['secret_token'] = $this->guid().$this->guid();
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
                $this->channelUser->insert($_POST);
            }else{
                $this->channelUser->where(['id'=>input('id')])->update($_POST);
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
            $find = $this->channelUser->field('id,account,caller_prefix,password_txt')->where(['id'=>input('id')])->find();
            return json($find);
        }
    }
    public function user_del(){
        $del = $this->channelUser->where(['id'=>input('id')])->delete();
        if($del){
            $this->redirect('User/customer_list');
        }else{
            $this->success('success');
        }
        
    }
    /**
     * 话机管理模块
     *  @author yhs 2019.09.18
     */
    public function telephone()
    {   
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $condition = array();
                if(input('channel_id') != -1){
                    $condition = ['channel_id'=>input('channel_id')];
                }
                $list = $this->softphone->field('id,account,password,channel_id,status,enable,add_time')->where($condition)->where([['account','like',"%".input('search')."%"]])->order('account asc')->limit($start,$length)->select();
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['status'] = $this->status[$v['status']];
                    $list[$k]['enable'] = $v['enable'] == 1 ? lang('yes') : lang('no');
                    $account = $this->channelUser->where(['id'=>$v['channel_id']])->value('account');
                    $list[$k]['channel_id'] = $account;
                    $url = url('User/tel_details',['id'=>$v['id']]);
                    $list[$k]['operating'] = $this->bt_onclick('user_edit',$v['id'],lang('edit')).$this->bt_onclick('tel_del',$v['id'],lang('delete'));
                }
                $count = $this->softphone->where($condition)->where([['account','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        $this->assign('channel_id',input('channel_id'));
        $userList = $this->channelUser->field('id,account')->select();
        $this->assign('userList', $this->object_array($userList));
        return $this->fetch();
    }
    public function tel_add(){
        if(input('channel_id') == -1 || empty(input('password'))){
            $back['message'] = "Memiliki opsi yang tidak terisi";
            $back['status'] = 0;
            return json($back);
        }
        $data = input();
        try{
            if(empty(input('id'))){
                //查询是否有重复账号
                $account = $this->softphone->where(['account'=>input('account')])->find();
                if(!empty($account)){
                    $back['message'] = "账号已存在";
                    $back['status'] = 0;
                    return json($back);
                }
                $add_data = [];
                if(input('is_bulk') == 1){
                    $max_account = $this->softphone->where('channel_id',input('channel_id'))->max('account');
                    $caller_prefix = $this->channelUser->where('id',input('channel_id'))->find();
                    if(empty($max_account)){
                        $account = $caller_prefix['caller_prefix'].'0000';
                    }else{
                        $account = $max_account;
                    }
                    for($i=0;$i<input('munber');$i++){
                        $account = intval($account)+1;
                        $add_data[$i] = [
                            'channel_id' => $data['channel_id'],
                            'account' => $account,
                            'password' => $data['password'],
                            'enable' => $data['enable'],
                            'add_time' => date('Y-m-d H:i:s'),
                        ];
                    }
                    $this->softphone->insertAll($add_data);
                }else{
                    $add_data = [
                        'channel_id' => $data['channel_id'],
                        'account' => $data['account'],
                        'password' => $data['password'],
                        'enable' => $data['enable'],
                        'add_time' => date('Y-m-d H:i:s'),
                    ];
                    $this->softphone->insert($add_data);
                }
            }else{
                unset($data['munber']);
                unset($data['is_bulk']);
                $this->softphone->where(['id'=>input('id')])->update($data);
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
            $find = $this->softphone->where(['id'=>input('id')])->find();
            return json($find);
        }
    }
    public function tel_del(){
        $del = $this->softphone->where(['id'=>input('id')])->delete();
        if($del){
            $this->redirect('User/telephone');
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
    /**
     * uuid
     */
    public function guid(){
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8);
        $uuid .= substr($str,8,4);
        $uuid .= substr($str,12,4);
        $uuid .= substr($str,16,4);
        $uuid .= substr($str,20,12);
        return  $uuid;
    
    }
}
