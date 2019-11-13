<?php
namespace app\Common\Model;
use think\Db;
Class StatisticalModel extends CommonModel
{
    protected $table = 'sys_task_statistical';
    public function statisticalList($where,$start,$length){
        return Db::table($this->table)
        ->alias('a')
        ->join('sys_call_case_task b','a.task_id=b.id')
        ->field("a.id,b.name as task_name,a.task_id,a.softphone,a.call_count,a.duration,a.average_duration")
        ->order('a.task_id desc,a.softphone asc')
        ->group('a.id')
        ->where('a.channel_id',session('channel_uid'))
        ->where([['a.softphone','like',"%".$where['search']."%"]])
        ->limit($start,$length)
        ->select();
    }
    public function statisticalCount($where){
        return Db::table($this->table)
        ->where('channel_id',session('channel_uid'))
        ->where([['softphone','like',"%".$where['search']."%"]])
        ->count();
    }
}