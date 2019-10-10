<?php
namespace app\Common\Model;
use think\Db;
class CallCaseModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'sys_call_case';
    protected $pk = 'id';
    public function __construct($data = []) {
        parent::__construct($data);
    }
}

