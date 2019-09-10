<?php
namespace app\Common\Model;
use think\Db;
class AdminUserModel extends CommonModel {

    // 设置当前模型对应的完整数据表名称
    protected $table = 'tp_admin_user';

    public function __construct($data = []) {
        parent::__construct($data);
    }

    public function get_admin_info($data)
    {
        $res = Db::name('admin_user')->where('user_name',$data)->limit('1')->select();
        return $res;
    }

}

