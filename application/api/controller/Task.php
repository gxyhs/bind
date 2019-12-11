<?php
namespace app\api\controller;
use app\admin\controller\Channel;
use think\Exception;
use think\Db;

Class Task
{
    public $channel;
    public function __construct()
    {
        $this->channel = new Channel();
    }

    /**
     * 新建任务
     * @return false|string
     */
    public function newTask()
    {
        try{
        $call_soft = explode(',',input('post.call_soft'));
        $softphone_count = count($call_soft);
        $data = [
            'name' => input('post.name'),
            'channel_id' => input('post.channel_id'),
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
            $call_case_count = $this->channel->excel($file['excel'],$task_id);
            //更新呼叫数量
            Db::table('sys_call_case_task')->where('id',$task_id)->update(['call_case_count'=>$call_case_count]);
            return json_encode(['code'=>200,'info'=>'success','data'=>null]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
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
            Db::table('sys_call_case_task')->where('id',$id)->update($status);
            return json_encode(['code'=>200,'info'=>'success','data'=>null]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }
}