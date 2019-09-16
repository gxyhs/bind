<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use app\Common\Model\AdminUserModel;
class Upload extends AdminBaseController
{   
    public function list()
    {   
        if($_POST){
            $table = new AdminUserModel();
            $info = $this->get_paging_info();
            $length = $info['page_length'];
            $start = $info['page_start'];
            $list = $table->field('id,user_name,create_time')->limit($start,$length)->select();
            $list = $this->object_array($list);
            $count = $table->count();
            $data =  $this->show_paging_info($info['page_echo'],$count,$list);
            return $data;
        }
        
        return $this->fetch();
    }
   
}
