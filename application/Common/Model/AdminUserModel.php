<?php
namespace app\Common\Model;
use think\Db;
class AdminUserModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'sys_admin_user';

    public function __construct($data = []) {
        parent::__construct($data);
    }

    public function get_admin_info($condition)
    {//print_r();die;
        $res = Db::table($this->table)->where($condition)->find();
        return $res;
    }

    public function add_user($data)
    {
        $res = Db::table($this->table)->insert($data);
        return $res;
    }
}

