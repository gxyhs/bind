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
class Extend extends ChannelBaseController
{  
    public function task_excal(){
        $dbCallCaseTask = new CallCaseTaskModel();
        $dbCallCase = new CallCaseModel();
        $task = $dbCallCaseTask->where('id',input('task_id'))->field('id,name')->find();
        if(empty($task)){
            // throw new Exception('empty task id', 1);
            $this->error('empty task id');
        }
        $data = $dbCallCase->where('task_id',input('task_id'))->field('task_id,phone,extend_id,case_message,softphone,call_duration,call_time,status')->select();
        $head = ['task_id','phone','extend_id','case_message','softphone','call_duration','call_time','status'];
        $name = $task['name'];
        $data = $this->object_array($data);
        return leading_out($data,$head,$name);
    }
}