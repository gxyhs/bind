<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Request;
use think\File;
use think\loader;
use app\Common\Model\CallCaseModel;
use think\facade\App;
use app\Common\Model\AdminUserModel;
use app\Common\Model\ChannelUserModel;
use think\Db;
class Index extends AdminBaseController
{   
    protected $adminUser;
    public function __construct() {
        parent::__construct();
        $this->CallCase = new CallCaseModel();
        $this->adminUser = new AdminUserModel();
        $this->ChannelUser = new ChannelUserModel();
	}
    public function index(){
        if($_POST || $_GET){
            $info = $this->get_paging_info();
            if(count($info)){
                $length = $info['page_length'];
                $start = $info['page_start'];
                $list = $this->CallCase->field('id,task_id,phone,extend_id,case_message,add_time')->where([['extend_id','like',"%".input('search')."%"]])->limit($start,$length)->select();
                
                $list = $this->object_array($list);
                foreach ($list as $k=>$v){
                    $find =  Db::table('sys_call_case_task')->field('id,name')->where('id',$v['task_id'])->find();
                    $list[$k]['task_id'] = $find['name'];
                    $list[$k]['id'] = '<input type="checkbox" class="ids" id="'.$v['id'].'">';
                    $list[$k][] = $this->bt_onclick('call_del',$v['id'],lang('delete'));
                }
                $count = $this->CallCase->where([['extend_id','like',"%".input('search')."%"]])->count();
                $data =  $this->show_paging_info($info['page_echo'],$count,$list);
                return $data;
            }
        }
        $this->assign('search',input('search'));
        return $this->fetch();
    }
    public function change_password(){
        if(IS_POST){
            $original = md5(trim($_POST['original_password']));
            $uid = session('admin_uid');
            $condition = ['id'=>$uid];
            $oglFind = $this->adminUser->where($condition)->find();
            $back = [];
            if($original != $oglFind->password){
                $back['message'] = "Kata sandi asli salah";
                $back['status'] = 0;
                return json($back);
            }
            if(trim($_POST['new_password']) != trim($_POST['confirm_password'])){
                $back['message'] = "Dua kata sandi tidak konsisten";
                $back['status'] = 0;
                return json($back);
            }
            try{
                $this->adminUser->where($condition)->update(['password'=>md5(trim($_POST['new_password']))]);
                $back['message'] = "success";
                $back['status'] = 1;
            }catch(Exception $e){
                $back['message'] = $e->getMessage();
                $back['status'] = 0;
            }
            return json($back);
        }else{
            return json(['status'=>0]);
        }
    } 

    /**
     *
     * 测试导出
     */
    public function demo_out(){
        $data = Db::table('tp_execl')->select();
        $head = ['id','lab_one','lab_two'];
        $name = 'user';
        return leading_out($data,$head,$name);
        exit;
    }

    /**
     * 测试导入
     */
    public function demo_in(){
        if($_FILES){
            $file = request()->file('excel');
            $res = leading_in($file);
            foreach($res as $k=>$v){
                $res[$k]['add_time'] = date('Y-m-d H:i:s');
                $res[$k]['channel_id'] = session('admin_uid');
            }
            if(is_array($res)){
               $result = $this->CallCase->insertAll($res);
               if($result){
                    $this->redirect('Index/index');
               }else{
                   $this->error('导入失败');
               }
            }
        }
        return $this->fetch();
    }
    public function call_del(){
        $del = $this->CallCase->where(['id'=>input('id')])->delete();
        if($del){
            $this->redirect('Index/index');
        }else{
            $this->error('error');
        }
    }
    public function batch_del(){
        if(input('ids')){
            $ids = explode(',',input('ids'));
            $del = $this->CallCase->where(['id'=>$ids])->delete();
            if($del){
                $this->redirect('Index/index');
            }else{
                $this->error('error');
            }
        }else{
            $this->error('empty ids');
        }
    }
    public function add(){
        if(IS_POST){
            $data = input('');
            $rule = [
                'lab_one' => 'require',
                'lab_two' => 'require',
            ];
            $msg = [
                'lab_one.require' => '不能为空',
                'lab_two.require' => '不能为空',
            ];
            $validate = new Validate($rule,$msg);
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = DB::table('tp_execl')->insert($data);
            if($res){
                $this->success('添加成功',url('Index/index'));
            }else{
                $this->error('添加失败');
            }
        }else{
            exit;
        }
    }
    public function edit(){
        if(IS_POST){
            $data = input('');
            $rule = [
                'operator_id'      => 'require',
                'lab_one' => 'require',
                'lab_two' => 'require',
            ];
            $msg = [
                'operator_id.require'      => '不能为空',
                'lab_one.require' => '不能为空',
                'lab_two.require' => '不能为空',
            ];
            $validate = new Validate($rule,$msg);
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $condition['id'] = $data['operator_id'];
            unset($data['operator_id']);
            $res = DB::table('tp_execl')->where($condition)->update($data);
            if($res){
                $this->success('修改成功',url('Index/index'));
            }else{
                $this->error('修改失败');
            }
        }else{
            exit;
        }
    }

    public function delete(){
        if(IS_GET){
            $condition['id'] = input('id') ?? $this->error('删除出错');
            $res = Db::table('tp_execl')->where($condition)->delete();
            if($res){
                $this->success('删除成功',url('Index/index'));
            }else{
                $this->error('删除失败');
            }
        }
    }
    public function downTask()
    {
        $id = input('get.id');
        if(empty($id)){
            return 'No user selected';
        }
        $beginTime = input('get.beginTime');
        $endTime = input('get.endTime');
        if(empty($beginTime) && empty($endTime)){
            $list = $this->CallCase->alias('a')->join('sys_call_case_task b','a.task_id=b.id')->where(['a.channel_id'=>$id])->field('b.name,a.phone,a.softphone,a.call_count')->select()->toArray();
        }elseif(!empty($beginTime) && !empty($endTime)){
            $beginTime = date('Y-m-d H:i:s',strtotime($beginTime));
            $endTime = date('Y-m-d H:i:s',strtotime($endTime));
            $list = $this->CallCase->alias('a')->join('sys_call_case_task b','a.task_id=b.id')->where(['a.channel_id'=>$id])->where('a.add_time','between time',[$beginTime,$endTime])->field('b.name,a.phone,a.softphone,a.call_count')->select()->toArray();
        }else{
            return '导出失败';
        }
        // $chunk_result = array_chunk($list, 1000);
        // foreach($chunk_result as $val){
        //     foreach ($val as $k=>$v) {
        //         $find =  Db::table('sys_call_case_task')->field('id,name')->where('id',$v['task_id'])->find();
        //         $val[$k]['task_id'] = $find['name'];
        //         unset($val[$k]['id']);
        //     }
        //     unset($val);
        // }
        
        $list = $this->object_array($list);
        //echo count($list);die;
        $title = ['任务昵称','主叫号码','被叫号码','呼叫时长'];
        $this->exportToExcel(date('YmdHis',time()),$title,$list);
    }
    /**
     * @creator yhs
     * @data 2020/02/12
     * @desc 数据导出到excel(csv文件)
     * @param $filename 导出的csv文件名称 如date("Y年m月j日").'-PB机构列表.csv'
     * @param array $tileArray 所有列名称
     * @param array $dataArray 所有列数据
     */
    public static function exportToExcel($filename, $tileArray=[], $dataArray=[]){
        ini_set('memory_limit','10240M');
        ini_set('max_execution_time',0);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=".$filename);
        $fp=fopen('php://output','w');
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
//        $fp=fopen('D://hello.csv','w');
        fputcsv($fp,$tileArray);
        $index = 0;
        foreach ($dataArray as $item) {
            if($index==10000){
                $index=0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp,$item);
        }
 
        ob_flush();
        flush();
        ob_end_clean();
    }
}
