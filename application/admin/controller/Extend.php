<?php
/**
 * 导出exExcel
 * @author yhs 2019.09.17
 */
namespace app\admin\controller;
use app\Common\Controller\ChannelBaseController;
use app\Common\Model\CallCaseTaskModel;
use app\Common\Model\CallCaseModel;
use Exception;
use think\Db;
class Extend extends ChannelBaseController
{  
    public function task_excal(){
        $dbCallCaseTask = new CallCaseTaskModel();
        $dbCallCase = new CallCaseModel();
        $task = $dbCallCaseTask->where(['id'=>input('task_id'),'channel_id'=>session('channel_uid')])->field('id,name')->find();
        if(empty($task)){
            // throw new Exception('empty task id', 1);
            $this->error('task id not exist');
        }
        $data = $dbCallCase->where('task_id',input('task_id'))->field('task_id,phone,extend_id,case_message,softphone,call_duration,call_time,status')->select();
        $head = ['task_id','phone','extend_id','case_message','softphone','call_duration','call_time','status'];
        $name = $task['name'];
        $data = $this->object_array($data);
        return leading_out($data,$head,$name);
    }
    public function downTask()
    {
        $task_id = input('get.task_id');
        if(!is_numeric($task_id)){
            $this->error('task id not exist');
        }
        $data = db('task_statistical')->where(['task_id'=>$task_id])->field('task_id,softphone,call_count,duration,average_duration,channel_id')->select();
        if(empty($data)){
            $this->error('empty task_statistical');
        }
        $title = ['任务Id','话机','电话数量','通话时长','平均通话时长','所属渠道用户id'];
        return leading_out($data,$title,'task_id_'.$task_id.'_'.date('YmdHis',time()));
    }
}