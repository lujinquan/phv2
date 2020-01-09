<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;
use think\Loader;
use hisi\PclZip;
use think\Db;
include EXTEND_PATH.'phpexcel/PHPExcel.php';
use hisi\Dir;
use Env;

/**
 * 语言包模型
 * @package app\common\model
 */
class SystemExport extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = false;
    /**
     * [export description]
     * @param  array   $tableData      	[导入进去的数据，单纯用作遍历]
     * @param  array   $titleArr      	[导入进去的数据，单纯用作遍历]
     * @param  integer $sheetType     	[创建工作表类型，1代表只创建一张工作表，2代表创建多张工作表]
     * @param  array   $tableInfo 		[表头基本信息]
     * @param  bool    $downloadType 	[下载模式：1、直接下载到浏览器，2、直接下载到浏览器并让用户选择存放的文件夹，3、先下载到服务器]
     * @return [type]             [description]
     */
    public function exportExcel($tableData = array() , $titleArr = array() , $sheetType = 1 , $tableInfo = array() , $downloadType = 1)
	{
		//ob_clean();
		
		if(empty($tableData)){
			return $this->error('暂无数据导出！');
		}
		
		// 默认表基本信息
		$tableInfoInit = [
			'Creator' => 'Lucas',
			'LastModifiedBy' => 'Admin',
			'Title' => 'Title',
			'Subject' => 'Subject',
			'Description' => 'Description',
			'Keywords' => 'Keywords',
			'Category' => 'Category' ,
			'FilePath' => '/upload/excel/' , 
			'FileName' => 'FileName',
			'FileSuffix' => 'xlsx'
		]; 
		foreach($tableInfoInit as $k => &$t){
			if(!isset($tableInfo[$k]) || empty($tableInfo[$k])){
				$tableInfo[$k] = $t;
			}
		}
	
    	$objPHPExcel = new \PHPExcel();
    	$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel); //保存excel—2007格式

        //设置文档基本属性
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator($tableInfo['Creator']);
        $objProps->setLastModifiedBy($tableInfo['LastModifiedBy']);
        $objProps->setTitle($tableInfo['Title']);
        $objProps->setSubject($tableInfo['Subject']);
        $objProps->setDescription($tableInfo['Description']);
        $objProps->setKeywords($tableInfo['Keywords']);
        $objProps->setCategory($tableInfo['Category']);
        /*----------------创建sheet-----------------*/
        

        $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

        /*----------------创建sheet-----------------*/

        $objPHPExcel->setActiveSheetIndex(0);
        //$objActSheet = $objPHPExcel->getActiveSheet();

        //设置当前活动sheet的名称
        //$objPHPExcel->setTitle($tableInfo['Title']);

        //设置第一行（标题行）
        $i = 0;
        foreach($titleArr as $titleKey => $title){ 
        		
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$i].'1' , ' ' . $title . ' ');  
            $i++;
        }

        $j = 2; //从第2行开始写数据
        foreach ($tableData as $rowIndex => $row) {
        	$i = 0;
        	foreach($row as $r){ 	
	            $objPHPExcel->getActiveSheet()->setCellValue($letter[$i]. $j , ' ' . $r . ' ');  
	            $i++;
	        }
	        $j++;
        }

//halt($tableData);
        // ak 是行索引
        //foreach($tableData as $a){ 
            //$objActSheet->getRowDimension($ak+1)->setRowHeight(18);//设置行高度
            // bk 是列
            //$lineIndex = 1; // 行索引

            //$colIndex = 1; // 列索引

// foreach($a as $ab => $b){
// 	dump($ab);
// }
// exit;
            //foreach($a as $b){
            	//halt($b);
                // if($ak === 0){ //如果是第一行
                //     $objActSheet->getColumnDimension($letter[$bk])->setWidth(20); //设置列宽度                  
                //     $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFont()->setBold(true); //设置是否加粗
                //     $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);//设置填充颜色
                //     $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->getStartColor()->setRGB('E6E6E6'); //设置填充颜色

                //     $objActSheet->setCellValue($letter[$bk] . ($ak+1), $values[$bk]);  //写入标题
                // }
                // if($bk == 'A'){ //将第一列的格式改成文本，其他列不变
                //     $objActSheet->setCellValue($letter[$bk] . ($ak+2), ' ' . $b . ' ');
                // }else{
                //     $objActSheet->setCellValue($letter[$bk] . ($ak+2), $b);  
                // }
                
                // $bk++;  
            //}
            //unset($a); //主动销毁变量，否则当数据量过大会报错内存溢出：Allowed memory size ……
        //}

        //生成excel表格，自定义名
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

//$downloadType = 3;

        // 方案一：直接在浏览器上下载
        if($downloadType == 1){

        	header("Pragma: public");
	        header("Expires: 0");
	        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
	        header("Content-Type:application/force-download");
	        header("Content-Type:application/vnd.ms-execl");
	        header("Content-Type:application/octet-stream");
	        header("Content-Type:application/download");
	        header('Content-Disposition:attachment;filename=' . $tableInfo['FileName']);
	        header("Content-Transfer-Encoding:binary");
	        $objWriter->save('php://output');

        // 方案二：直接在浏览器上下载并让用户选择存放的文件夹
        }else if($downloadType == 2){

        // 方案三：先下载到服务器
        }else{

        	if(strtoupper(substr(PHP_OS,0,3))==='WIN'){ //如果是windows服务器，则保存成GBK编码格式
	            $filename = convertGBK($tableInfo['FileName']);
	        }else{ //如果不是，则保存成UTF-8格式
	            $filename = convertUTF8($tableInfo['FileName']);
	        }

	        //$filePath = $tableInfo['FilePath'] . $filename . date('YmdHis', time()) . '.xlsx';
	        $suffix = '_'.date('YmdHis', time()) . '.'.$tableInfo['FileSuffix'];
	        $objWriter->save('.'.$tableInfo['FilePath'].$filename.$suffix);

	        $result = [];
	        $result['code'] = 1;
	        $result['msg'] = '导出成功！';
	        $result['data'] = $tableInfo['FilePath'].$tableInfo['FileName'].$suffix;
	        return $result;  // 返回的文件名需要是以UTF-8编码
        }     
  
	   
	}
}
