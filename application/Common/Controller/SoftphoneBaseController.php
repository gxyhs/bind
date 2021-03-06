<?php

namespace app\Common\Controller;
use think\facade\Cookie;
use think\Lang;
use app\Common\Model\CommonModel;
use think\Exception;

class SoftphoneBaseController extends BaseController {

    public function __construct($checkLogin = True) {
        parent::__construct();
        $this->isLogin();
        $controller = strtolower(CONTROLLER_NAME);
        $action = strtolower(ACTION_NAME);
        $tempname = '/' . $controller . '/' . $action;
        $this->assign('controller', $controller);
        $this->assign('action', $action);
        $this->assign('tempname', $tempname);
        
        // $lang = lang('title');
        //语言切换
        // if(!(Cookie::has('think_var'))){
        //     $this->lang();
        // }
        $lang = empty(Cookie('think_var')) ? 'ZH-CH' : Cookie('think_var');
        $this->assign('think_lang',$lang);
    }

    protected function isLogin() {
        $admin_uid = session('soft_uid');
        if (empty($admin_uid)) {
            $url = url('Soft/index');
            $this->redirect($url);
            exit;
        }
    }

    public function jump404() {
        //只有在app_debug=False时才会正常显示404页面，否则会有相应的错误警告提示
        abort(404, '页面异常');
    }
    //空方法
    public function _empty() {
        return $this->jump404();
    }
    public function get_paging_info(){
        if(empty($_POST['aoData'])) return [];
        $res = $_POST['aoData'];
        $iDisplayStart = 0; // 起始索引
        $iDisplayLength = 0;//分页长度
        $jsonarray= json_decode($res);
        foreach($jsonarray as $value){
            if($value->name=="sEcho"){
                $sEcho=$value->value;
            }
            if($value->name=="iDisplayStart"){
                $iDisplayStart=$value->value;
            }
            if($value->name=="iDisplayLength"){
                $iDisplayLength=$value->value;
            }
        }
        return [
            'page_echo' =>$sEcho,
            'page_start'=>$iDisplayStart,
            'page_length'=>$iDisplayLength,
        ];
    }
    public function show_paging_info($echo,$count,$ary){
        $return_ary = [];
        foreach($ary as $val){
            $temp = array_values($val);
            array_push($return_ary,$temp);
        }
        $json_data = array ('sEcho'=>$echo,'iTotalRecords'=>$count,'iTotalDisplayRecords'=>$count,'aaData'=>$return_ary);
        return $json_data;
    }
    /**
     * array 二维数组
     * return array
     */
    public function object_array($array) { 
        $array = json_decode(json_encode($array),true);
        if(is_object($array)) {  
            $array = (array)$array;  
         } if(is_array($array)) {  
             foreach($array as $key=>$value) {  
                 $array[$key] = $this->object_array($value);  
            }  
         }  
         return $array;  
    }
    public function operating($url,$msg){
        return '<a class="btn btn-info btn-xs" href="'.$url.'">'.$msg.'</a>';
    }
    public function bt_onclick($click,$v,$msg){
        return '<a class="btn btn-info btn-xs" onclick="'.$click.'('.$v.')">'.$msg.'</a>';
    }
}
