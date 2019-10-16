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
            $where = [
                ['status','neq',0],
                ['call_case_count','neq',0],
                ['completion','neq',100]
            ];
        $call_case_task_data = $call_case_task->field('id,call_case_count')->select()->toArray();
	    if(empty($call_case_task_data)){
            $output->writeln('No Data');
            return false;
	    }
            $CallCase = new CallCaseModel();
            //处理数据
            foreach($call_case_task_data as $k=>$v){
                $crontab = [
                    'status' => 2,
                    'task_id' => $v['id'],
                ];
                $count = $CallCase->where($crontab)->count();
                $proportion = round($count / $v['call_case_count'], 2)*100;
                $sql .= ' WHEN '.$v['id'].' THEN '.$proportion;
            }
            $sql .= ' END)';
            $call_case_task_id = array_column($call_case_task_data,'id');
            $call_case_task_ids = implode(',',$call_case_task_id);
            $sql .= ' WHERE id IN('.trim($call_case_task_ids,',').')';
            Db::query($sql);
            $output->writeln('successfully');
        }catch (\Exception $e){
	    trace($e->getMessage(),'error');
            $output->writeln($e->getMessage());
        }
    }
    public function logPrint($postObj) {
        $fp = fopen('/data/api2.txt', 'a+');
        fwrite($fp, var_export($postObj, true));
        fclose($fp);
    }
}
