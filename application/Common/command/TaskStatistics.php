<?php
namespace app\Common\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

Class TaskStatistics extends Command
{
    protected function configure()
    {
        $this->setName('TaskStatistics')->setDescription('任务统计');
    }
    protected function execute(Input $input, Output $output)
    {
        try{
            Db::startTrans();
            $softphone = Db::table('sys_call_case_softphone')->alias('softphone')->join('sys_call_case_task task','task.id=softphone.task_id','right')->field('softphone.id,softphone.account as softphone,softphone.task_id')->select();
            $tmp_number = 1;
            $tmp_data = [];
            foreach ($softphone as $k=>$v){
                $tmp_call= Db::table('sys_call_case')->where(['task_id'=>$v['task_id'],'softphone'=>$v['softphone']])->field('count(id) as call_count,sum(call_duration) as duration,avg(call_duration) as average_duration')->select();
                if($tmp_call[0]['duration'] == null){
                    $tmp_call[0]['duration'] = 0;
                }
                if($tmp_call[0]['average_duration'] == null){
                    $tmp_call[0]['average_duration'] = 0;
                }
                $tmp_data[$k]['softphone'] = $v['softphone'];
                $tmp_data[$k]['task_id'] = $v['task_id'];
                $tmp_data[$k]['add_time'] = date('Y-m-d H:i:s',time());
                $tmp_data[$k] = array_merge($tmp_data[$k],$tmp_call[0]);
                if($tmp_number === 10){
                    $output->writeln($tmp_number);
                    $this->insertStatistics($tmp_data);
                    $tmp_number = 1;
                    $tmp_data = [];
                }else{
                    ++$tmp_number;
                }
            }
            if(!empty($tmp_data)){
                $this->insertStatistics($tmp_data);
            }
            Db::commit();
            $output->writeln('successfully');
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
    protected function insertStatistics($data)
    {
        try{
            Db::table('sys_task_statistical')->insertAll($data);
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}