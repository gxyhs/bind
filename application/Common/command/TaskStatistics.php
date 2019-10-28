<?php
namespace app\Common\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;
use app\Common\Model\StatisticalModel;

Class TaskStatistics extends Command
{
    protected function configure()
    {
        $this->setName('TaskStatistics')->setDescription('任务统计');
    }
    protected function execute(Input $input, Output $output)
    {
        try{
            $table = new StatisticalModel();
            Db::startTrans();
            $softphone = Db::table('sys_call_case_softphone')
            ->alias('a')
            ->join('sys_call_case b','a.task_id=b.task_id and a.account=b.softphone')
            ->join('sys_call_case_task c','a.task_id=c.id')
            ->where([['c.completion','neq',100]])
            ->field('c.channel_id,a.id,b.id as cid,a.account as softphone,b.task_id,count(b.id) as call_count,sum(b.call_duration) as duration,avg(b.call_duration) as average_duration')
            ->group('a.id')
            ->select();
            $add_data = [];
            $update_data = [];
            foreach ($softphone as $k=>$v){
                $statistica = $table->where(['softphone'=>$v['softphone'],'task_id'=>$v['task_id']])->field('id')->find();
                if(empty($statistica)){
                    $add_data[$k]['task_id'] = $v['task_id'];
                    $add_data[$k]['softphone'] = $v['softphone'];
                    $add_data[$k]['channel_id'] = $v['channel_id'];
                    $add_data[$k]['call_count'] = $v['call_count'];
                    $add_data[$k]['duration'] = $v['duration'];
                    $add_data[$k]['average_duration'] = $v['average_duration'];
                    $add_data[$k]['add_time'] = date('Y-m-d H:i:s');
                }else{
                    $update_data[$k]['id'] = $statistica['id'];
                    $update_data[$k]['channel_id'] = $v['channel_id'];
                    $update_data[$k]['call_count'] = $v['call_count'];
                    $update_data[$k]['duration'] = $v['duration'];
                    $update_data[$k]['average_duration'] = $v['average_duration'];
                }
            }
            if(!empty($add_data)){
                $chunk_result = array_chunk($add_data, 1000);
                foreach($chunk_result as $value){
                    $table->insertAll($value);
                }
            }
            if(!empty($update_data)){
                $update_result = array_chunk($update_data, 1000);
                foreach($update_result as $value){
                    $call_count = '';
                    $duration = '';
                    $average_duration = '';
                    foreach($value as $val){
                        $call_count .= ' WHEN '.$val['id'].' THEN '.$val['call_count'];
                        $duration .= ' WHEN '.$val['id'].' THEN '.$val['duration'];
                        $average_duration .= ' WHEN '.$val['id'].' THEN '.$val['average_duration'];
                    }
                    $ids = array_column($value,'id');
                    $this->updateStatistics($call_count,$duration,$average_duration,$ids);
                }
            }
            Db::commit();
            $output->writeln('successfully');
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
    protected function updateStatistics($call_count,$duration,$average_duration,$statistical_id)
    {
        try{
            $statistical_id = implode(',',$statistical_id);
            $update = 'UPDATE sys_task_statistical SET call_count=(CASE id '.$call_count.' END),duration=(CASE id '.$duration.' END),average_duration=(CASE id '.$average_duration.' END) WHERE id IN('.$statistical_id.')';
            Db::query($update);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}