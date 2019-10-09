<?php
/**
 * 定时任务
 * @author yhs 2019.10.09
 */
namespace app\Admin\Controller;
use app\Common\Controller\BaseController;
use app\Common\Model\ChannelUserModel;
use app\Common\Model\CallCaseModel;
use think\Db;

class Crontab extends BaseController
{   
    public function completion(){
        $table = Db::table('sys_call_case_task');
        $where = ['status'=>[1,2,3]];
        $list = $table->where($where)->where('completion','<',1)->where('call_case_count','>',0)->select();
        foreach($list as $k => $v){
            $crontab = [
                'status' => 2,
                'task_id' => $v['id'],
            ];
            $CallCase = new CallCaseModel();
            $count = $CallCase->where($crontab)->count();
            $completion = $count/$v['call_case_count'];
            if($completion != $v['completion']){
                try{
                    $table->where('id',$v['id'])->update(['completion'=>$completion]);
                }catch(Exception $e){
                     return $e->getMessage();
                }
            }
        }
        return 'success';
    }
    
}