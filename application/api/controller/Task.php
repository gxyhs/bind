<?php
namespace app\api\controller;
use app\admin\controller\Channel;
use think\Exception;
use think\Db;
use app\Common\Model\CallCaseModel;

Class Task
{
    public $channel;
    public $account;
    public function __construct()
    {
        $this->channel = new Channel();
        $secret_key = input('secret_key');
        $secret_token = input('secret_token');
        try{
            $this->account = db('channel_user')->where(['secret_key'=>$secret_key,'secret_token'=>$secret_token])->field('id')->find();
            if(empty($this->account)){
                exit(json_encode(['code'=>101,'info'=>'账号不存在','data'=>null]));
            }
        }catch (Exception $e){
                exit(json_encode(['code'=>101,'info'=>'参数验证失败','data'=>null]));
        }
    }

    /**
     * 新建任务
     * @return false|string
     */
    public function newTask()
    {
        try{
            $name = input('post.name');
            $call_multiple = input('post.call_multiple');
            if(!isset($name) ||!isset($call_multiple)){
                return json_encode(['code'=>101,'info'=>'参数错误']);
            }
            $call_soft = explode(',',input('post.call_soft'));
            $softphone_count = count($call_soft);
            $data = [
                'name' => input('post.name'),
                'channel_id' => $this->account['id'],
                'softphone_count' => $softphone_count,
                'call_multiple' => input('post.call_multiple'),
                'recall_count' => input('post.recall_count'),
                'notify_sms' => input('post.notify_sms'),
                'add_time' => date('Y-m-d H:i:s'),
            ];
            $id = Db::table('sys_call_case_task')->insertGetId($data);
            $this->channel->add_softphone($call_soft,$id);
            return json_encode(['code'=>200,'info'=>'success','data'=>['task_id'=>$id]]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 更新任务
     */
    public function updateTask()
    {
        $task = input('post.');
        if(!isset($task['call_multiple']) || !isset($task['notify_sms']) || !isset($task['recall_count']) || !isset($task['task_id'])){
            return json_encode(['code'=>101,'info'=>'参数缺失','data'=>null]);
        }
        try{
            $update_success = Db::table('sys_call_case_task')->where(['id'=>$task['task_id'],'channel_id'=>$this->account['id']])->update(['call_multiple'=>$task['call_multiple'],'notify_sms'=>$task['notify_sms'],'recall_count'=>$task['recall_count']]);
            if($update_success){
                return json_encode(['code'=>200,'info'=>'修改成功','data'=>null]);
            }else{
                return json_encode(['code'=>101,'info'=>'修改失败 ','data'=>null]);
            }
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 获取任务进度
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCompletion()
    {
        $task_id = input('task_id');
        $completion = Db::table('sys_call_case_task')->where(['id'=>$task_id,'channel_id'=>$this->account['id']])->field('completion')->find();
        if(empty($completion)){
            return json_encode(['code'=>101,'info'=>'当前任务不存在','data'=>null]);
        }else{
            $completion['completion'] = $completion['completion'].'%';
            return json_encode(['code'=>200,'info'=>'success','data'=>$completion]);
        }
    }

    /**
     * 获取案例列表
     * @return false|string
     */
    public function getCaseList()
    {
        try{
            $id = input('id');
            $page = empty(input('page')) ? 1 : input('page');
            $page_size = 50;
            $task_id = [];
            if(!empty($id)) {
                $task_id = ['task_id'=>$id];
            }
            $task_id['task.channel_id'] = $this->account['id'];
            $case = Db::table('sys_call_case case')->where($task_id)->join('sys_call_case_task task','task.id=case.task_id','right')->field('case.phone,case.extend_id,case.case_message,case.status,case.call_duration,case.call_count,case.add_time,task.id,task.name')->limit(($page-1)*$page_size,$page_size)->select();
            $caseCount = Db::table('sys_call_case case')->where($task_id)->join('sys_call_case_task task','task.id=case.task_id','right')->count();
            return json_encode(['code'=>200,'info'=>'success','data'=>$case,'count'=>$caseCount]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }
    /**
     * 根据任务上传案例
     * @return false|string
     */
    public function taskCase()
    {
        try{
            $task_id = input('post.task_id');
            $file = request()->file();
            if(!isset($file['excel'])){
                return $this->error('没有呼叫案列上传');
            }
            $task = Db::table('sys_call_case_task')->where(['id'=>$task_id,'channel_id'=>$this->account['id']])->find();
            if(empty($task)){
                return json_encode(['code'=>101,'info'=>'当前任务不是你创建的','data'=>null]);
            }
            $call_case_count = $this->excel($file['excel'],$task_id,$this->account['id']);
            //更新呼叫数量
            Db::table('sys_call_case_task')->where('id',$task_id)->update(['call_case_count'=>$call_case_count]);
            return json_encode(['code'=>200,'info'=>'success','data'=>null]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }
    //导入excel
    public function excel($file,$id,$channel_uid){
        $res = leading_in($file);

	    $data = [];
        foreach($res as $k=>$v){
            if(!empty($v['phone'])){
               // $this->error('empty phone',url('channel/add_call_case_softphone'));
                $data[$k]['extend_id'] = $v['extend_id'];
                $data[$k]['case_message']= $v['case_message'];
                $data[$k]['channel_id'] = $channel_uid;
                $data[$k]['add_time'] = date('Y-m-d H:i:s');
                $data[$k]['task_id'] = $id;
                $data[$k]['phone'] = $v['phone'];
            }
        }
        $this->CallCase = new CallCaseModel();
        $chunk_result = array_chunk($data, 1000);
        foreach($chunk_result as $value){
            $result = $this->CallCase->insertAll($value);
        }
        return count($data);
    }
    /**
     * 任务暂停/开始
     * @return false|string
     */
    public function taskStatus()
    {
        try{
            $id = input('post.id');
            if(!is_numeric(input('post.status')) || !in_array(input('post.status'),[1,2])){
                return json_encode(['code'=>101,'info'=>'状态传入错误','data'=>null]);
            }
            $status['status'] = input('post.status'); //1开始,2暂停
            $isSuccess = db('call_case_task')->where(['id'=>$id,'channel_id'=>$this->account['id']])->update($status);
            if($isSuccess){
                return json_encode(['code'=>200,'info'=>'success','data'=>null]);
            }else{
                return json_encode(['code'=>101,'info'=>'状态修改失败','data'=>null]);
            }
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * @notes 删除任务
     * @return false|string
     */
    public function delTask()
    {
        $task_id = input('post.task_id');
        if(empty($task_id) || !is_numeric($task_id)){
            return json_encode(['code'=>101,'info'=>'参数错误','data'=>null]);
        }
        $isPerson = Db::table('sys_call_case_task')->where(['id'=>$task_id,'channel_id'=>$this->account['id']])->find();
        if(empty($isPerson)){
            return json_encode(['code'=>101,'info'=>'删除失败','data'=>null]);
        }
        try{
            Db::startTrans();
//            Db::table('sys_call_case')->where(['task_id'=>$task_id])->delete();
            Db::table('sys_call_case_task')->where('id',$task_id)->delete();
            Db::commit();
            return json_encode(['code'=>200,'info'=>'任务删除成功','data'=>null]);
        }catch (Exception $e){
            Db::rollback();
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);;
        }
    }
}