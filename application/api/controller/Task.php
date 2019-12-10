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
    public function newTask()
    {
        try{
        $call_soft = explode(',',input('call_soft'));
        $softphone_count = count($call_soft);
        $data = [
            'name' => input('name'),
            'channel_id' => input('channel_id'),
            'softphone_count' => $softphone_count,
            'call_multiple' => input('call_multiple'),
            'recall_count' => input('recall_count'),
            'notify_sms' => input('notify_sms'),
            'add_time' => date('Y-m-d H:i:s'),
        ];
            $id = Db::table('sys_call_case_task')->insertGetId($data);
            $this->channel->add_softphone($call_soft,$id);
            return json_encode(['code'=>200,'info'=>'success','data'=>null]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }
    public function task_case()
    {
        try{
        $task_id = input('task_id');
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
    public function task_status()
    {
        try{
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
                $this->channel->CallCase->where($where)->update($data);
            }
            Db::table('sys_call_case_task')->where('id',$id)->update($status);
            return json_encode(['code'=>200,'info'=>'success','data'=>null]);
        }catch (Exception $e){
            return json_encode(['code'=>101,'info'=>$e->getMessage(),'data'=>null]);
        }
    }
}