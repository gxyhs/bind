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
    
    protected function insertStatistics($data)
    {
        try{
            Db::table('sys_task_statistical')->insertAll($data);
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
    }
    protected function saveAll($datas,$model){
        // $model || $model=$this->name;
        $sql   = ''; //Sql
        $lists = []; //记录集$lists
        $pk    =  'id';//获取主键
        
        foreach ($datas as $data) {
            foreach ($data as $key=>$value) {
                if($pk===$key){
                    $ids[]=$value['id'];
                }else{
                    $lists[$key].= sprintf("WHEN %u THEN '%s' ",$data[$pk],$value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql.= sprintf("`%s` = CASE `%s` %s END,",$key,$pk,$value);
        }
        $sql = sprintf('UPDATE __%s__ SET %s WHERE %s IN ( %s )',strtoupper($model),rtrim($sql,','),$pk,implode(',',$ids));
        print_r($sql);die;
        return M()->execute($sql);
    }
    public function logPrint($postObj) {
        $fp = fopen('/data/api7.txt', 'a+');
        fwrite($fp, var_export($postObj, true));
        fclose($fp);
    }
}