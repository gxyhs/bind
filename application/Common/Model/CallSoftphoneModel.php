<?php
namespace app\Common\Model;
class CallSoftphoneModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'sys_call_case_softphone';

    public function __construct($data = []) {
        parent::__construct($data);
    }
}