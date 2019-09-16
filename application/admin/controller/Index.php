<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;
use think\Request;
use think\File;
use think\loader;
use think\facade\Cookie;
use app\Common\Model\AdminUserModel;use think\facade\App;class Index extends AdminBaseController
{   
    protected $adminUser;
    public function __construct() {
        parent::__construct();
        $this->adminUser = new AdminUserModel();
	}
    public function index()
    {
        return $this->fetch();
    }
    public function lang(){
        $lang = input('?get.lang') ? input('get.lang') : 'ZH-CN';
        switch ($lang) {
            case 'ZH-CN':
                cookie('think_var', 'ZH-CN');
                break;
            case 'EN':
                cookie('think_var', 'EN');
                break;
            case 'INDO':
                cookie('think_var', 'INDO');
                break;
            default:
                cookie('think_var', 'ZH-CN');
        }
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
    }	public function pull_out(){
        $where = new Where();
        $result = $this->model->where($where)->order('id desc')->select();
        // ������excel
        foreach ($result as $k=>&$v) {
            $v['add_time'] = date('Y-m-d H:i',$v['add_time']);
            switch ($v['status']) {
                case 0:
                    $v['status'] = 'δ����';
                    break;
                case 1:
                    $v['status'] = '��ͬ������';
                    break;
                case 2:
                    $v['status'] = '�Ѿܾ�����';
                    break;
            }
        }
        $xlsCell  = array(
            array('id','ID'),
            array('name','����'),
            array('telphone','�ֻ���'),
            array('add_time','��ȡʱ��'),
            array('status','״̬')
        );
        $xlsName = date('Y-m-d').'�����������';
        Func::exportExcel($xlsName, $xlsCell, $result);
    }

    public static function exportExcel($expTitle, $expCellName, $expTableData, $topData) {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//�ļ�����
        $fileName = $xlsTitle;//or $xlsTitle �ļ����ƿɸ����Լ�����趨
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $topNum  = count($topData);

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//�ϲ���Ԫ��
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);

        for ($i = 0; $i < count($topData); $i++) {
            for ($j = 0; $j < count($topData[$i]); $j++) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$j] . ($i + 2), $topData[$i][$j]);
            }
        }

        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . ($topNum + 2), $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                if ($expCellName[$j][0] == 'account_type') {
                    if ($expTableData[$i][$expCellName[$j][0]] == 0) {
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '����');
                    } else {
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '����');
                    }
                } else {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), $expTableData[$i][$expCellName[$j][0]]);
                }
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment�´��ڴ�ӡinline�����ڴ�ӡ
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function pull_in(){
        $rootPath = App::getRootPath();
        require $rootPath.'extend/PHPExcel/PHPExcel.php';
        // dump($file);die;
        // �ƶ������Ӧ�ø�Ŀ¼/public/uploads/ Ŀ¼��
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'excel');
        $file = $rootPath.'/upload/123.xlsx';
        if ($file) {
            //��ȡ�ļ�����Ŀ¼��
//            $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'excel/' . $info->getSaveName();
            $filename = '123.xlsx';
            //��ȡ�ļ�����׺
            $suf = substr($filename, strrpos($filename, '.'));
            if ($suf == '.xlsx') {
                $objReader = new \PHPExcel_Reader_Excel2007();
            } else if($suf == '.xls'){
                $objReader = new \PHPExcel_Reader_Excel5();
            }else{
                return '�ļ����ʹ���';
            }
            $objPHPExcel = $objReader->load($file, $encode = 'utf-8'); //��ȡexcel�ļ�
            $sheet = $objPHPExcel->getSheet(0); //���ǰ�ı�
            $highestRow = $sheet->getHighestRow(); // ȡ��������
            $highestColumn = $sheet->getHighestColumn(); // ȡ��������
            $a = 0;
            //��������������ѭ����������
            for ($i = 2; $i <= $highestRow; $i++) {
                //*Ϊʲô$i=2? (��ΪExcel����һ��Ӧ�������������䣬�༶���ӵڶ��п�ʼ����������Ҫ�����ݡ�)
                $data[$a]['id'] = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue(); //����
                $data[$a]['user_name'] = $objPHPExcel->getActiveSheet()->getCell("B" . $i)->getValue(); //����
                $data[$a]['password'] = $objPHPExcel->getActiveSheet()->getCell("C" . $i)->getValue(); //�༶
                // ��������ݸ����Լ���������ж��ٸ��ֶ����о���
                $a++;
            }

            //�����ݿ��������
            // $res = Db::name('student')->insertAll($data);
            // if($res){
            //         $this->success('�����ɹ���');
            // }else{
            //         $this->error('����ʧ�ܣ�');
            //    }
            dump($data);die;
        } else {
            // �ϴ�ʧ�ܻ�ȡ������Ϣ
            $this->error($file->getError());
        }
    }}}
