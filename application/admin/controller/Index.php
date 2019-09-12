<?php
namespace app\admin\controller;
use app\Common\Controller\AdminBaseController;
use think\Db;
use think\Request;
use think\File;
use think\loader;
use think\facade\Cookie;
use think\facade\App;
class Index extends AdminBaseController
{   
    public function __construct() {
		parent::__construct();
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

    public function pull_out(){
        $where = new Where();
        $result = $this->model->where($where)->order('id desc')->select();
        // 导出到excel
        foreach ($result as $k=>&$v) {
            $v['add_time'] = date('Y-m-d H:i',$v['add_time']);
            switch ($v['status']) {
                case 0:
                    $v['status'] = '未处理';
                    break;
                case 1:
                    $v['status'] = '已同意申请';
                    break;
                case 2:
                    $v['status'] = '已拒绝申请';
                    break;
            }
        }
        $xlsCell  = array(
            array('id','ID'),
            array('name','姓名'),
            array('telphone','手机号'),
            array('add_time','领取时间'),
            array('status','状态')
        );
        $xlsName = date('Y-m-d').'申请加入名单';
        Func::exportExcel($xlsName, $xlsCell, $result);
    }

    public static function exportExcel($expTitle, $expCellName, $expTableData, $topData) {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $topNum  = count($topData);

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
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
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '餐饮');
                    } else {
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '果蔬');
                    }
                } else {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), $expTableData[$i][$expCellName[$j][0]]);
                }
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function pull_in(){
        $rootPath = App::getRootPath();
        require $rootPath.'extend/PHPExcel/PHPExcel.php';
        // dump($file);die;
        // 移动到框架应用根目录/public/uploads/ 目录下
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'excel');
        $file = $rootPath.'/upload/123.xlsx';
        if ($file) {
            //获取文件所在目录名
//            $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'excel/' . $info->getSaveName();
            $filename = '123.xlsx';
            //获取文件名后缀
            $suf = substr($filename, strrpos($filename, '.'));
            if ($suf == '.xlsx') {
                $objReader = new \PHPExcel_Reader_Excel2007();
            } else if($suf == '.xls'){
                $objReader = new \PHPExcel_Reader_Excel5();
            }else{
                return '文件类型错误';
            }
            $objPHPExcel = $objReader->load($file, $encode = 'utf-8'); //获取excel文件
            $sheet = $objPHPExcel->getSheet(0); //激活当前的表
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
            $a = 0;
            //将表格里面的数据循环到数组中
            for ($i = 2; $i <= $highestRow; $i++) {
                //*为什么$i=2? (因为Excel表格第一行应该是姓名，年龄，班级，从第二行开始，才是我们要的数据。)
                $data[$a]['id'] = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue(); //姓名
                $data[$a]['user_name'] = $objPHPExcel->getActiveSheet()->getCell("B" . $i)->getValue(); //年龄
                $data[$a]['password'] = $objPHPExcel->getActiveSheet()->getCell("C" . $i)->getValue(); //班级
                // 这里的数据根据自己表格里面有多少个字段自行决定
                $a++;
            }

            //往数据库添加数据
            // $res = Db::name('student')->insertAll($data);
            // if($res){
            //         $this->success('操作成功！');
            // }else{
            //         $this->error('操作失败！');
            //    }
            dump($data);die;
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }
}
