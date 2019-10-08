<?php
namespace app\admin\controller;
use app\Common\Controller\SoftphoneBaseController;
use app\Common\Model\SoftphoneModel;
class Softphone extends SoftphoneBaseController
{
    public function __construct() {
        parent::__construct();
        $this->Softphone = new SoftphoneModel();
    }
    /**
     * 话机模块
     *  @author yhs 2019.09.20
     */
    public function index(){
        
        return $this->fetch();
    }
    public function change_password(){
        if(IS_POST){
            $original = md5(trim($_POST['original_password']));
            $uid = session('channel_uid');
            $condition = ['id'=>$uid];
            $oglFind = $this->Softphone->where($condition)->find();
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
                $this->Softphone->where($condition)->update(['password'=>$_POST['new_password']]);
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
}