<?php
namespace app\Common\Model;
use think\Db;
Class CallCaseTaskModel extends CommonModel
{
    protected $table = 'sys_call_case_task';
    protected $pk = 'id';
    public function getCallCaseTask()
    {
        return $this->hasMany('CallCaseModel','task_id','id')->field('task_id,status');
    }
    public function caseList(){
        $list = Db::table($this->table)
            ->alias('a')
            ->join('sys_call_case b','a.id=b.task_id')
            ->field(['a.id,a.call_case_count,count(b.id) as count'])
            ->group('a.name')
            ->where([['a.status','neq',0],['a.call_case_count','neq',0],['a.completion','neq',100],['b.status','eq',2]])
            ->select();
        return $list;
    }
}