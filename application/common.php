<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
$rootPath = App::getRootPath();
require $rootPath.'extend/PHPExcel/PHPExcel.php';
// 应用公共文件
/**
 * @param $data 导出的数据
 * @param $head 第一行的标题
 * @param $name 文件名
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 * @throws PHPExcel_Writer_Exception
 */
 function leading_out($data,$head,$name){

    $num = 1;
    $letter_begin = 65;
    date_default_timezone_set("Asia/Shanghai");
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->getProperties()->setCreator("admin")  // 创建者
    ->setLastModifiedBy("admin")                    // 最后修改者
    ->setTitle($name)                               // 标题
    ->setSubject($name)                             // 主题
    ->setDescription($name)                         // 描述
    ->setKeywords("excel")                          // 关键字
    ->setCategory("result file");                   // 种类

    $A = $letter_begin;
    $format = [];
    for($i = 0;$i<count($head);$i++){
        $f=intval($i/26)+64;
        $s=($i%26)+$A;
        if(is_array($head[$i])){
            $element = $head[$i][0];
            $format[$i] = $head[$i][1];
        }else{
            $element = $head[$i];
        }
        if($f>=$A){
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue(chr($f).chr($s).$num,$element);
        }else{
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue(chr($s).$num,$element);
        }

    }
    foreach($data as $k => $v){
        $A = $letter_begin;
        $num += 1;
        $count = 0;
        foreach($v as $val){
            $f=intval($count/26)+64;
            $s=($count%26)+$A;
            if(isset($format[$count])){
                switch($format[$count]){
                    case 'string':
                        if($f>=$A){
                            $objPHPExcel->setActiveSheetIndex(0)
                                ->setCellValueExplicit(chr($f).chr($s).$num,$val, PHPExcel_Cell_DataType::TYPE_STRING);
                        }else{
                            $objPHPExcel->setActiveSheetIndex(0)
                                ->setCellValueExplicit(chr($s).$num,$val, PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                        break;
                    case 'numeric':
                        if($f>=$A){
                            $objPHPExcel->setActiveSheetIndex(0)
                                ->setCellValueExplicit(chr($f).chr($s).$num,$val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        }else{
                            $objPHPExcel->setActiveSheetIndex(0)
                                ->setCellValueExplicit(chr($s).$num,$val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        }
                        break;
                    case 'date':
                        if($f>=$A){
                            if(empty($val)){
                                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr($f).chr($s).$num,$val);
                            }else{
                                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr($f).chr($s).$num, PHPExcel_Shared_Date::stringToExcel($val));
                                $objPHPExcel->getActiveSheet()->getStyle(chr($f).chr($s).$num)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                            }
                        }else{
                            if(empty($val)){
                                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr($s).$num,$val);
                            }else{
                                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr($s).$num,PHPExcel_Shared_Date::stringToExcel($val));
                                $objPHPExcel->getActiveSheet()->getStyle(chr($s).$num)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                            }
                        }
                        break;
                }

            }else{
                if($f>=$A){
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValueExplicit(chr($f).chr($s).$num,$val, \PHPExcel_Cell_DataType::TYPE_STRING);
                }else{
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValueExplicit(chr($s).$num,$val, \PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }
            $count++;
        }
    }

    $objPHPExcel->getActiveSheet()->setTitle($name);
    $objPHPExcel->setActiveSheetIndex(0);
    ob_end_clean();
    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$name.'.xlsx"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

/**
 * 导入execl文件
 * @return string
 * @throws \PHPExcel_Exception
 * @throws \PHPExcel_Reader_Exception
 */
 function leading_in($file){

    // 移动到框架应用根目录/public/uploads/ 目录下
     $info = $file->validate(['size'=>1048576,'ext'=>'xls,xlsx'])->move( './upload');
    if ($info) {
        $fileName = $info->getSaveName();
        //获取文件路径
        $filePath = App::getRootPath().'public'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.$fileName;
        //获取文件后缀
        $suf = $info->getExtension();
        if ($suf == 'xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        } else if($suf == 'xls'){
            $objReader = new \PHPExcel_Reader_Excel5();
        }else{
            return '文件类型错误';
        }
        $objPHPExcel = $objReader->load($filePath, $encode = 'utf-8'); //获取excel文件
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
        return $data;
    } else {
        // 上传失败获取错误信息
        $this->error($file->getError());
    }
}