<?php
namespace app\Common\Model;
use think\Db;
class ChannelUserModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'sys_channel_user';

    public function __construct($data = []) {
        parent::__construct($data);
    }
}

