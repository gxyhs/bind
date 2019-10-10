<?php
namespace app\Common\Model;
Class CallCaseTaskModel extends CommonModel
{
    protected $table = 'sys_call_case_task';
    protected $pk = 'id';
    public function getCallCaseTask()
    {
        return $this->hasMany('CallCaseModel','task_id','id')->field('task_id,status');
    }
}