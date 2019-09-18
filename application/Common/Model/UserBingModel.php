<?php
namespace app\Common\Model;
class UserBingModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'tp_user_bing';

    public function __construct($data = []) {
        parent::__construct($data);
    }
}