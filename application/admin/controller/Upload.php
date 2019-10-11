<?php
/**
 * 上传呼叫案列
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use app\Common\Model\CallCaseModel;
class Upload extends AdminBaseController
{   
    public function __construct() {
        parent::__construct();
        $this->CallCase = new CallCaseModel();
	}
    public function list()
    {   
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $list = $this->CallCase ->field('id,case_message,create_time')->where([['case_message','like',"%".input('search')."%"]])->limit($start,$length)->select();
                $list = $this->object_array($list);
                
                $count = $this->CallCase->where([['case_message','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        return $this->fetch();
    }
    public function call_add(){
        if(empty(input('phone')) || empty(input('desc'))){
            $back['message'] = "Memiliki opsi yang tidak terisi";
            $back['status'] = 0;
            return json($back);
        }
        $data = [
            'phone' => input('phone'),
            'desc' => input('desc'),
            'create_time' => time(),
        ];
        try{
            $this->CallCase->insert($data);
            $back['message'] = "success";
            $back['status'] = 1;
        }catch(Exception $e){
            $back['message'] = $e->getMessage();
            $back['status'] = 0;
        }
        return json($back);
    }
    public function call_del(){
        $del = $this->CallCase->where(['id'=>input('id')])->delete();
        if($del){
            $this->redirect('Upload/list');
        }else{
            $this->success('success');
        }
    }
}
