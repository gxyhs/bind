<?php
namespace app\Common\Model;
class SoftphoneModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'sys_softphone';

    public function __construct($data = []) {
        parent::__construct($data);
    }
}