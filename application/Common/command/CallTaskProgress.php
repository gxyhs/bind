<?php
namespace app\Common\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\Common\Model\CallCaseTaskModel;
use app\Common\Model\CallCaseModel;
use think\Db;
Class CallTaskProgress extends Command
{
    protected function configure()
    {
        $this->setName('CallTaskProgress')->setDescription('计算呼叫案例进度');
    }
    protected  function execute(Input $input, Output $output)
    {
        try{
            $call_case_task = new CallCaseTaskModel();
            $sql = 'UPDATE sys_call_case_task SET completion=(CASE id';
            //$call_case_task_data = $call_case_task->where('call_case_count','neq',0)->field('id,call_case_count')->with('getCallCaseTask')->select();
            $where = ['status'=>[1,2,3]];
            $call_case_task_data = $call_case_task->field('id,call_case_count')->where($where)->where('completion','<',100)->where('call_case_count','>',0)->select();
            
            //处理数据
            foreach($call_case_task_data as $k=>$v){
                $CallCase = new CallCaseModel();
                $crontab = [
                    'status' => 2,
                    'task_id' => $v['id'],
                ];
                $count = $CallCase->where($crontab)->count();
                $proportion = number_format($count / $v['call_case_count'], 2, '.', '')*100;
                $sql .= ' WHEN '.$v['id'].' THEN '.$proportion;
            }
            $sql .= ' END)';
            Db::query($sql);
            $output->writeln('successfully');
        }catch (\Exception $e){
            $output->writeln($e->getMessage());
        }
    }
}