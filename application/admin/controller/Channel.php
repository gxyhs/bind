<?php
/**
 * 客户管理
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\ChannelBaseController;
use app\Common\Model\ChannelUserModel;
use app\Common\Model\SoftphoneModel;
use think\console\Input;
use think\Request;
use think\File;
use think\loader;
use app\Common\Model\CallCaseModel;
use app\Common\Model\CallSoftphoneModel;
use app\Common\Model\StatisticalModel;
use think\facade\App;
use think\facade\Env;
use think\Db;

class Channel extends ChannelBaseController
{   
    public function __construct() {
        parent::__construct();
        $this->channelUser = new ChannelUserModel();
        $this->softphone = new SoftphoneModel();
        $this->CallCase = new CallCaseModel();
        $this->CallSoftphone = new CallSoftphoneModel();
        $this->Statistical = new StatisticalModel();
        $this->callTimeUrl = "http://149.129.237.184:8088/download/";
        $this->callUrl = "https://cs-recording.oss-ap-southeast-5.aliyuncs.com/";
        $this->status = [
            lang('offline'),
            lang('online'),
            lang('in_the_call'),
            lang('talking'),
        ];
        $this->call_status = [
            0=>lang('no_call'),
            2=>lang('call_completion'),
        ];
        $this->inline = [
            lang('off_line'),
            lang('on_line')
        ];
	}
    public function index(){
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $list = $this->CallCase->alias('a')
                ->join('sys_call_case_task b','a.task_id = b.id','LEFT')
                ->field('a.id,b.name as task_id,a.phone,a.softphone,a.extend_id,a.recording_file,a.case_message,a.status,a.call_duration,a.call_count,a.add_time,a.call_time')
                ->where('a.channel_id',session('channel_uid'))
                ->where([['a.phone','like',"%".input('search')."%"]])
                ->limit($start,$length)
                ->order('a.add_time desc')
                ->select();
                $list = $this->object_array($list);
                foreach ($list as $k=>$v){
                    $list[$k]['status'] = $this->call_status[$v['status']];
                    $list[$k]['id'] = '<input type="checkbox" class="ids" id="'.$v['id'].'" task_id="'.$v['task_id'].'">';
                    $call_param = $v['id'].','.$v['task_id'];
                    $list[$k][] = $this->bt_onclick('call_del',$call_param,lang('delete'));
                    if($v['recording_file']){
                        $startTime = strtotime(date("Y-m-d"),time());
                        $endTime = $startTime+24*3600;
                        $recording_file = strtotime($v['call_time']);
                        if($startTime < $recording_file){
                            $list[$k]['recording_file'] = '<audio controls="controls"><source src="'.$this->callTimeUrl.$v['recording_file'].'" type="audio/mp3"></audio>';
                        }else{
                            //$list[$k]['recording_file'] = $this->callUrl.$v['recording_file'];
                            $list[$k]['recording_file'] = '<audio controls="controls"><source src="'.$this->callUrl.$v['recording_file'].'" type="audio/mp3"></audio>';
                            //"https://cs-recording.oss-ap-southeast-5.aliyuncs.com/20200116/01af22f5-51a5-45ae-a5c2-6a6ddd5ef378.wav";
                        }
                    }
                    unset($list[$k]['call_time']);
                }
                $count = $this->CallCase->where('channel_id',session('channel_uid'))->where([['extend_id','like',"%".input('search')."%"]])->count();
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
            $oglFind = $this->channelUser->where($condition)->find();
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
                $this->channelUser->where($condition)->update(['password'=>md5(trim($_POST['new_password']))]);
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
     * 导入
     */
    public function demo_in(){
        if($_FILES){
            $file = request()->file('excel');
            $res = leading_in($file);
            foreach($res as $k=>$v){
                $res[$k]['add_time'] = date('Y-m-d H:i:s');
                $res[$k]['channel_id'] = session('channel_uid');
            }
            if(is_array($res)){
               $result = $this->CallCase->insertAll($res);
               if($result){
                   $this->success('导入成功',url('Channel/index'));
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
            db('call_case_task')->where(['id'=>input('task_id')])->setDec('call_case_count');
            $this->redirect('Channel/index');
        }else{
            $this->error('success');
        }
    }
    public function batch_del(){
        if(input('ids')){
            $ids = explode(',',input('ids'));
            $del = $this->CallCase->where(['id'=>$ids])->delete();
            if($del){
                //拆分任务id
                $task_ids = explode(',',input('task_ids'));
                //得出获取任务id出现的次数
                $task_ids_new = array_count_values($task_ids);
                //获取出现的任务id
                $keys = array_keys($task_ids_new);
                //拼接成字符串
                $keys_id = implode(',',$keys);
                //拼接sql
                $update_case_count_sql = 'UPDATE sys_call_case_task SET call_case_count=(CASE id';
                foreach ($task_ids_new as $k=>$v){
                    $update_case_count_sql .= ' WHEN '.$k.' THEN call_case_count-'.$v;
                }
                $update_case_count_sql .= ' END) WHERE id IN('.$keys_id.')';
                Db::query($update_case_count_sql);
                $this->redirect('Channel/index');
            }else{
                $this->error('error');
            }
        }else{
            $this->error('empty ids');
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
                
                $condition['channel_id'] = session('channel_uid');
                $list = $this->softphone->field('id,account,password,status,enable,add_time')->where($condition)->where([['account','like',"%".input('search')."%"]])->order('account asc')->limit($start,$length)->select();
                // print_r($this->softphone->getLastSql());die;
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['id'] = '<input type="checkbox" class="ids" id="'.$v['id'].'">';
                    $list[$k]['status'] = $this->inline[$v['status']];
                    $list[$k]['operating'] = '<a href="javascript:void(0)" onclick="edit_pass('.$v['id'].')" class="btn btn-info btn-xs edit-pass" id="tel_'.$v['id'].'" data-toggle="modal" data-target="#edit_phone_pass">修改密码</a>';
                    $list[$k]['enable'] = $v['enable'] == 1 ? lang('yes') : lang('no');
                }
                $count =  $this->softphone->where($condition)->where([['account','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }

        $this->assign('search',input('search'));
        $this->assign('status_key',input('status'));
        $this->assign('status',$this->status);
        $userList = $this->channelUser->field('id,account')->select();
        $this->assign('userList', $this->object_array($userList));
        //话机在线数量

        $where['channel_id'] = session('channel_uid');
        $where['status'] = 1;
        $telCount = $this->softphone->where($where)->count();
        $this->assign('telCount',$telCount);
        return $this->fetch();
    }
    public function tel_add(){
        if(empty(input('account')) || input('channel_id') == -1 || empty(input('password'))){
            $back['message'] = "Memiliki opsi yang tidak terisi";
            $back['status'] = 0;
            return json($back);
        }
        if(empty(input('id'))){
            $_POST['add_time'] = date('Y-m-d H:i:s');
        }
        try{
            if(empty(input('id'))){
                $this->softphone->insert($_POST);
            }else{
                $this->softphone->where(['id'=>input('id')])->update($_POST);
            }
            $back['message'] = "success";
            $back['status'] = 1;
        }catch(Exception $e){
            $back['message'] = $e->getMessage();
            $back['status'] = 0;
        }
        return json($back);
    }
    public function tel_password_edit()
    {
        $request = Request();
        if(!$request->isPost()){
            return json(['code'=>101,'info'=>'请求错误']);
        }
        $tel_id = input('post.tel_id');
        $tel_password = input('post.tel_password');
        if(!is_numeric($tel_id) || !is_numeric($tel_password)){
            return json(['code'=>101,'info'=>'参数错误']);
        }
        try{
            $this->softphone->startTrans();
            $this->softphone->where(['id'=>$tel_id])->update(['password'=>$tel_password]);
            $this->softphone->commit();
            return json(['code'=>200,'info'=>'success']);
        }catch (\Exception $e){
            $this->softphone->rollback();
            return json(['code'=>101,'info'=>$e->getMessage()]);
        }
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
            $this->redirect('Channel/telephone');
        }else{
            $this->error('error');
        }
        
    }
    /**
     * 批量添加到呼叫任务
     */
    public function batch_call_task(){
        $ids = explode(',',input('ids'));
        foreach($ids as $k=>$v){
            $find = $this->CallSoftphone->where(['task_id'=>$v])->find();
            $soft = $this->softphone->where(['id'=>$v])->find();
            if(empty($find)){
                $data = ['task_id'=>$v,'account'=>$soft['account'],'add_time'=>date('Y-m-d H:i:s')];
                $this->CallSoftphone->insert($data);
            }
        }
        $this->redirect('Channel/call_case_softphone');
    }
    /**add_call_case_softphone_ajax
     * 呼叫任务列表
     */
    public function call_case_softphone(){
        if(input()){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $condition = array();
                $condition['channel_id'] = session('channel_uid');
                $list = Db::table('sys_call_case_task')->field('id,name,softphone_count,recall_count,called_count,call_case_count,call_multiple,completion,status,add_time')->where($condition)->where([['name','like',"%".input('search')."%"]])->limit($start,$length)->order('add_time desc')->select();
                
                $list = $this->object_array($list);
                foreach($list as $k=>$v){
                    $list[$k]['status'] = $this->status[$v['status']];
                    $list[$k]['completion'] = $v['completion']==0 ? 0 :($v['completion']).'%';
                    if($v['status'] == 1){
                        $str = $this->bt_onclick('start',$v['id'],lang('stop'));
                    }elseif($v['status'] == 0){
                        $str = $this->bt_onclick('start',$v['id'],lang('start'));
                    }elseif($v['status'] == 3){
                        $str = $this->bt_onclick('start',$v['id'],lang('start_again'));
                    }elseif($v['status'] == 2){
                        $str = $this->bt_onclick('start',$v['id'],lang('task_continue'));
                    }else{
                        $str = '';
                    }
                    $url = url('Channel/edit_call_case_softphone',['id'=>$v['id']]);
                    if($v['status'] == 3){
                        $str = $this->bt_onclick('start',$v['id'],lang('start_again'));
                    }else{
                        $str = $str ? $str.$this->operating($url,lang('edit')) : '';
                    }
                    
                    $list[$k][] = $str.$this->bt_onclick('task_del',$v['id'],lang('delete'));
                }
                $count = Db::table('sys_call_case_task')->where($condition)->where([['name','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        $this->assign('status_key',input('status'));
        $this->assign('status',$this->status);
        $userList = $this->channelUser->field('id,account')->select();
        $this->assign('userList', $this->object_array($userList));
        return $this->fetch();
    }
    public function add_call_case_softphone(){

        $where = [
            'channel_id' => session('channel_uid'),
        ];
        //任务表
        $case_task = Db::table('sys_call_case_task')->where($where)->select();
        $ids = [];
        foreach($case_task as $v){
            $ids[] = $v['id'];
        }
        $call_soft = $this->CallSoftphone->where(['task_id'=>$ids])->select();
        $accounts = [];
        foreach($call_soft as $v){
            $accounts[] = $v['account'];
        }
        //->whereNotIn('account',$accounts)
        $soft = $this->softphone->field('id,account')->where($where)->order('account asc')->select();
        $this->assign('soft',$soft);
        return $this->fetch();
    }
    public function edit_call_case_softphone(){
        $table = Db::table('sys_call_case_task');
        $id = input('id');
        if(empty($id)){
            $this->error('error');
        }
        $this->assign('id',$id);
        $find = $table->where('id',$id)->find();
        $this->assign('find',$find);
        $where = [
            'channel_id' => session('channel_uid'),
        ];
        $CallSoftphone = $this->CallSoftphone->where(['task_id'=>$find['id']])->order('account asc')->select();
        $accounts = [];
        foreach($CallSoftphone as $k=>$v){
            $accounts[] = $v['account'];
        }
        $this->assign('checked',$accounts);
        $this->assign('accounts',implode(',',$accounts));
        $soft = $this->softphone->field('id,account')->where($where)->order('account asc')->select();
        $this->assign('soft',$soft);
        return $this->fetch();
    }
    //开始任务
    public function task_start(){
        $id = input('id');
        $find = Db::table('sys_call_case_task')->where('id',$id)->find();
        if($find['status'] == 1){//暂停
            $status = ['status'=>2];
        }elseif($find['status'] == 0){//点击开始
            $status = ['status'=>1];
        }else{
            $status = ['status'=>1];
            $data['status'] = 0;
            $data['call_count'] = 0;
            $where = [
                'task_id' => $id,
                'call_duration' => 0,
                'softphone' => null
            ];
            $this->CallCase->where($where)->update($data);
        }
        Db::table('sys_call_case_task')->where('id',$id)->update($status);
        $this->redirect('Channel/call_case_softphone');
    }
    //删除任务
    public function task_del(){
        $id = input('id');
        Db::table('sys_call_case_task')->where('id',$id)->delete();
        $this->redirect('Channel/call_case_softphone');
    }
    public function add_call_case_softphone_ajax(){
        //ob_start(); // 开启大小无限制的缓冲区
        // ob_clean();// ob_flush() // 清空/冲出 当前缓冲区
        // ob_get_contents(); // 获取 当前缓冲区的内容
        // ob_end_clean();
        set_time_limit(0);
        Db::startTrans();
        try{
            // if(empty(input('name'))){
            //     $this->error('empty name',url('channel/add_call_case_softphone'));
            // }
            $call_soft = explode(',',input('call_soft'));
            $softphone_count = count($call_soft);
            if(mb_strlen(input('notify_sms'),'utf-8') > 250){
                $this->error("短信内容不能超过250个字符 ！");
            }
            if(input('id')){//修改 
                $id = input('id');
                $data_edit = [
                    'softphone_count' => $softphone_count,
                    'call_multiple' => input('call_multiple'),
                    'recall_count' => input('recall_count'),
                    'notify_sms' => input('notify_sms'),
                ];
                Db::table('sys_call_case_task')->where('id',$id)->update($data_edit);
                $this->CallSoftphone->where('task_id',$id)->delete();
                $this->add_softphone($call_soft,$id);
            }else{
                $file = request()->file();
                if(!isset($file['excel'])){
                    return $this->error('没有呼叫案列上传');
                }
                $data = [
                    'name' => input('name'),
                    'channel_id' => session('channel_uid'),
                    'softphone_count' => $softphone_count,
                    'call_multiple' => input('call_multiple'),
                    'recall_count' => input('recall_count'),
                    'notify_sms' => input('notify_sms'),
                    'add_time' => date('Y-m-d H:i:s'),
                ];
                $id = Db::table('sys_call_case_task')->insertGetId($data);
                if($id){
                    $call_case_count = $this->excel($file['excel'],$id);
                    //更新呼叫数量
                    Db::table('sys_call_case_task')->where('id',$id)->update(['call_case_count'=>$call_case_count]);
                    $this->add_softphone($call_soft,$id);
                }
            }
            Db::commit();
            $this->redirect('Channel/call_case_softphone');
        }catch(Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
    }
    //导入excel
    public function excel($file,$id){
        $res = leading_in($file);
        $data = [];
        $res_list = array_chunk($res, 10000);
        foreach($res_list as $value){
            foreach($value as $k=>$v){
                if(!empty($v['phone'])){
                // $this->error('empty phone',url('channel/add_call_case_softphone'));
                    $data[$k]['extend_id'] = $v['extend_id'];
                    $data[$k]['case_message']= $v['case_message'];
                    $data[$k]['channel_id'] = session('channel_uid');
                    $data[$k]['add_time'] = date('Y-m-d H:i:s');
                    $data[$k]['task_id'] = $id;
                    $data[$k]['phone'] = $v['phone'];
                }
            }
            $result = $this->CallCase->insertAll($data);
            unset($value);
            unset($data);
        }
        return count($res);
    }
    //添加呼叫表
    public function add_softphone($data,$id){
        foreach($data as $k=>$v){
            $res[$k]['add_time'] = date('Y-m-d H:i:s');
            $res[$k]['task_id'] = $id;
            $res[$k]['account'] = $v;
        }
        $result = $this->CallSoftphone->insertAll($res);
        return $result;
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
    public function taskStatistics()
    {
        if(Request()->isPost()){
            $info = $this->get_paging_info();
            $length = $info['page_length'];
            $start = $info['page_start'];
            $softphone = $this->Statistical->statisticalList($_GET,$start,$length);
            $count = $this->Statistical->statisticalCount($_GET);
            $data =  $this->show_paging_info($info['page_echo'],$count,$softphone);
            return $data;
        }
        $this->assign('search',input('search'));
        $this->assign('status_key',input('status'));
        $this->assign('status',$this->status);
        return $this->fetch();
    }
    public function getTaskId()
    {
        return db('call_case_task')->order('id desc')->column('id');
    }
    public function outCall()
    {
        if(empty(input('get.id')) || !is_numeric(input('get.id'))){
            return 'Request error';
        }
        $where = ['id'=>input('get.id'),'channel_id'=>session('channel_uid')];
        $where2 = ['task_id'=>input('get.id')];
        $id = Db::table('sys_call_case_task')->where($where)->field('name')->find();
        /*if(empty($id)){
            return $this->error('task id not exist');
        }*/
        if(input('get.call') != '' && is_numeric(input('get.call')) && in_array(input('get.call'),[0,1,2])){
            $where2['status'] = input('get.call');
        }
        $list = $this->CallCase->field('task_id,phone,softphone,call_time,extend_id,recording_file,case_message,status,call_duration,call_count,add_time,call_time')->where($where2)->order('add_time desc')->select();
        foreach($list as $k=>$v){
            $list[$k]['name']=$id['name'];
            if($v['status'] == 0){
                $list[$k]['status'] = '未呼叫';
            }elseif($v['status'] == 2){
                $list[$k]['status'] = '已呼叫';
            }elseif($v['status'] == 1){
                $list[$k]['status'] = '呼叫中';
            }
            if($v['recording_file']){
                $startTime = strtotime(date("Y-m-d"),time());
                $endTime = $startTime+24*3600;
                $recording_file = strtotime($v['call_time']);
                if($startTime < $recording_file){
                    $list[$k]['recording_file'] = $this->callTimeUrl.$v['recording_file'];
                }else{
                    $list[$k]['recording_file'] = $this->callUrl.$v['recording_file'];
                }
            }
            unset($v['call_time']);
        }
        $list = $this->object_array($list);
        $head = ['任务id','电话','话机接听','接听时间','扩展id','录音','描述','呼叫状态','通话时长','呼叫数量','addtime','任务名称'];
        leading_out($list,$head,date('YmdHis',time()).'_task_case');
    }
}
