<?php
/**
 * 上传呼叫案列
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use app\Common\Model\AdminUserModel;
class Upload extends AdminBaseController
{   
    public function list()
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
