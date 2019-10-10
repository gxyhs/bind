<?php
namespace app\Common\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\Common\Model\CallCaseTaskModel;
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
            $call_case_task_data = $call_case_task->where('call_case_count','neq',0)->field('id,call_case_count')->with('getCallCaseTask')->select();
            //处理数据
            foreach($call_case_task_data as $k=>$v){
                $tmp = 0;
                foreach($v['get_call_case_task'] as $kk=>$vv){
                    //已完成的呼叫案例数
                    if($vv['status'] == 2){
                        ++$tmp;
                    }
                }
                $proportion = number_format($tmp / $v['call_case_count'], 2, '.', '');
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