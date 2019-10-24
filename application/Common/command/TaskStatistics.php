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
    // protected function execute(Input $input, Output $output)
    // {
    //     try{
    //         Db::startTrans();
    //         $softphone = Db::table('sys_call_case_softphone')->alias('softphone')->join('sys_call_case_task task','task.id=softphone.task_id','right')->where('task.completion','neq',100)->field('softphone.id,softphone.account as softphone,softphone.task_id,task.channel_id')->select();
    //         //插入数据计时
    //         $tmp_number_insert = 1;
    //         //修改数据计时
    //         $tmp_number_update = 1;
    //         //存储插入数据
    //         $tmp_data = [];
    //         //任务统计id
    //         $ids = '';
    //         //电话数量
    //         $call_count = '';
    //         //通话时长
    //         $duration = '';
    //         //平均通话时长
    //         $average_duration = '';
    //         foreach ($softphone as $k=>$v){
    //             $where = ['task_id'=>$v['task_id'],'softphone'=>$v['softphone'],'channel_id'=>$v['channel_id']];
    //             $tmp_call= Db::table('sys_call_case')->where($where)->field('count(id) as call_count,sum(call_duration) as duration,avg(call_duration) as average_duration')->select();
    //             if($tmp_call[0]['duration'] == null){
    //                 $tmp_call[0]['duration'] = 0;
    //             }
    //             if($tmp_call[0]['average_duration'] == null){
    //                 $tmp_call[0]['average_duration'] = 0;
    //             }
    //             //监测数据是否存在
    //             $is_exists_statistical = Db::table('sys_task_statistical')->where($where)->field('id')->find();
    //             //不存在插入,存在修改
    //             if(empty($is_exists_statistical)){
    //                 $tmp_data[$k]['softphone'] = $v['softphone'];
    //                 $tmp_data[$k]['task_id'] = $v['task_id'];
    //                 $tmp_data[$k]['channel_id'] = $v['channel_id'];
    //                 $tmp_data[$k]['add_time'] = date('Y-m-d H:i:s',time());
    //                 $tmp_data[$k] = array_merge($tmp_data[$k],$tmp_call[0]);
    //                 if($tmp_number_insert === 1000){
    //                     $output->writeln($tmp_number_insert);
    //                     $this->insertStatistics($tmp_data);
    //                     $tmp_number_insert = 1;
    //                     $tmp_data = [];
    //                 }else{
    //                     ++$tmp_number_insert;
    //                 }
    //             }else{
    //                 $ids .= $is_exists_statistical['id'].',';
    //                 $call_count .= ' WHEN '.$is_exists_statistical['id'].' THEN '.$tmp_call[0]['call_count'];
    //                 $duration .= ' WHEN '.$is_exists_statistical['id'].' THEN '.$tmp_call[0]['duration'];
    //                 $average_duration .= ' WHEN '.$is_exists_statistical['id'].' THEN '.$tmp_call[0]['average_duration'];
    //                 if($tmp_number_update === 100){
    //                     $this->updateStatistics($call_count,$duration,$average_duration,$ids);
    //                     $call_count = '';
    //                     $duration = '';
    //                     $average_duration = '';
    //                     $ids = '';
    //                     $tmp_number_update = 1;
    //                 }else{
    //                     ++$tmp_number_update;
    //                 }
    //             }
    //         }
    //         if(!empty($ids)){
    //             $this->updateStatistics($call_count,$duration,$average_duration,$ids);
    //         }
    //         if(!empty($tmp_data)){
    //             $this->insertStatistics($tmp_data);
    //         }
    //         Db::commit();
    //         $output->writeln('successfully');
    //     }catch (\Exception $e){
    //         trace($e->getMessage(),'error');
    //         Db::rollback();
    //         $output->writeln($e->getMessage());
    //     }
    // }
    
    protected function insertStatistics($data)
    {
        try{
            Db::table('sys_task_statistical')->insertAll($data);
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
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
            $ids = [];
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
            //print_r($update);die;
            Db::query($update);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}