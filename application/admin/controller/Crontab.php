<?php
namespace app\admin\controller;
use think\Controller;
use app\Common\Model\CallCaseTaskModel;
use app\Common\Model\CallCaseModel;
use think\Db;

/**
 * 定时任务
 * @author yhs 2019.10.15
 */
class Crontab extends Controller{
    public function CallTaskProgress(){
        try{
            $call_case_task = new CallCaseTaskModel();
            $sql = 'UPDATE sys_call_case_task SET completion=(CASE id';
            $where = [
                ['status','neq',0],
                ['call_case_count','neq',0],
                ['completion','neq',100]
            ];
            $call_case_task_data = $call_case_task->field('id,call_case_count')->where($where)->select()->toArray();
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
            if(!empty($call_case_task_ids)){
                Db::query($sql);
            }
            return 'successfully';
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }
    public function logPrint($postObj) {
        $fp = fopen('/data/api1.txt', 'a+');
        fwrite($fp, var_export($postObj, true));
        fclose($fp);
    }
}