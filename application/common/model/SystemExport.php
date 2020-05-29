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
use app\common\model\Cparam as ParamModel;
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

		set_time_limit(0);

		if(empty($tableData)){
			return $this->error('暂无数据导出！');
		}
		// 默认表基本信息
		$tableInfoInit = [
			'Creator' => 'Lucas', 				// 创建人
			'LastModifiedBy' => 'Lucas', 		// 最后修改人
			'Title' => '导出数据',				// 标题
			'Subject' => 'Subject',				// 题目
			'Description' => 'Description',		// 描述
			'Keywords' => 'Keywords',			// 关键字
			'Category' => 'Category' ,			// 种类
			'FilePath' => '/upload/excel/' ,	// 文件存储位置
			'FileName' => 'FileName',			// 文件名
			'FileSuffix' => 'csv'				// 文件类型后缀,可选 xlsx 、 csv 、xls
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
        

        $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ','CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ'];

        /*----------------创建sheet-----------------*/
        //如果只有一个工作组
        if($sheetType == 1){ 

        	//主体数据的数据过滤
	        $tableData = $this->dataFormat($tableData);
			//halt($tableData);
			//dump(memory_get_usage() / 1024 / 1024); //46M
			
        	$objPHPExcel->setActiveSheetIndex(0);
	        //设置当前活动sheet的名称
	        $objPHPExcel->getActiveSheet()->setTitle($tableInfo['Title']);
	  
	        //设置第一行（标题行）
	        $i = 0;
	        $keyIndexArr = [];
	        $objPHPExcel->getActiveSheet()->freezePane('A2'); //冻结A2左侧及上侧数据窗口
	        
	        foreach($titleArr as $titleIndex => $titleRow){ 
	        	$keyIndexArr[$titleRow['field']] = $titleIndex; //将键名与索引对应
	            $objPHPExcel->getActiveSheet()->setCellValue($letter[$i].'1' , $titleRow['title'] ); 
	            
	            $objPHPExcel->getActiveSheet()->getStyle($letter[$i].'1')->getFont()->setBold(true);
	            //$objPHPExcel->getActiveSheet()->getColumnDimension($letter[$i])->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension($letter[$i])->setWidth($titleRow['width']); 
	            $objPHPExcel->getActiveSheet()->getStyle($letter[$i].'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
	            $objPHPExcel->getActiveSheet()->getStyle($letter[$i].'1')->getFill()->getStartColor()->setARGB('80EEEEEE');
	            $i++;
	        }

	        //dump(memory_get_usage() / 1024 / 1024); //47M

			//从第2行开始向Excel中写数据
	        $j = 2; 
	        foreach ($tableData as $rowIndex => $row) {
	        	//$i = 0;
	        	foreach($row as $rIndex => $r){ // $rIndex是关联数组的key	
	        		//halt($titleArr[$keyIndexArr[$rIndex]]['type']);
	        		if($titleArr[$keyIndexArr[$rIndex]]['type'] == 'string'){
	        			$objPHPExcel->getActiveSheet()->setCellValueExplicit($letter[$keyIndexArr[$rIndex]]. $j ,$r,\PHPExcel_Cell_DataType::TYPE_STRING);
	        		}else{
	        			$objPHPExcel->getActiveSheet()->setCellValue($letter[$keyIndexArr[$rIndex]]. $j , $r);
	        		}
	        		//$objPHPExcel->getActiveSheet()->setCellValueExplicit($letter[$keyIndexArr[$rIndex]]. $j ,$r,\PHPExcel_Cell_DataType::TYPE_STRING);
		            //$objPHPExcel->getActiveSheet()->setCellValue($letter[$keyIndexArr[$rIndex]]. $j , $r . "\t" );  // $r . "\t"
		            //$i++;
		            unset($r);
		        }
		        $j++;
		        unset($row); //主动销毁变量，否则当数据量过大会报错内存溢出：Allowed memory size ……
	        }
	        //dump(memory_get_usage() / 1024 / 1024); // 118M
	    //如果有多个工作组
        }else{

        }

        //halt(memory_get_usage() / 1024 / 1024);

        //生成excel表格，自定义名
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//halt($tableInfo['FileName'] . '.'.$tableInfo['FileSuffix']);
        // 方案一：直接在浏览器上下载
        if($downloadType == 1){

        	header("Pragma: public");
	        header("Expires: 0");
	        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
	        header("Content-Type:application/force-download");
	        header("Content-Type:application/vnd.ms-execl");
	        header("Content-Type:application/octet-stream");
	        header("Content-Type:application/download");
	        header('Content-Disposition:attachment;filename=' . $tableInfo['FileName'] . '.'.$tableInfo['FileSuffix']);
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
	        $suffix = '_'.date('Y-m-d', time()) . '.'.$tableInfo['FileSuffix'];
	        $objWriter->save('.'.$tableInfo['FilePath'].$filename.$suffix);

	        $result = [];
	        $result['code'] = 1;
	        $result['msg'] = '导出成功！';
	        $result['data'] = $tableInfo['FilePath'].$tableInfo['FileName'].$suffix;
	        return $result;  // 返回的文件名需要是以UTF-8编码
        }     
  
	   
	}

	public function dataFormat($data){
		$params = ParamModel::getCparams();
		foreach($data as &$d){
			if(isset($d['ban_owner_id'])){
				$d['ban_owner_id'] = $params['owners'][$d['ban_owner_id']];
			}
			if(isset($d['ban_damage_id'])){
				$d['ban_damage_id'] = $params['damages'][$d['ban_damage_id']];
			}
			if(isset($d['ban_struct_id'])){
				$d['ban_struct_id'] = $params['structs'][$d['ban_struct_id']];
			}
			if(isset($d['ban_inst_id'])){
				$d['ban_inst_id'] = $params['insts'][$d['ban_inst_id']];
			}
			if(isset($d['tenant_inst_id'])){
				$d['tenant_inst_id'] = $params['insts'][$d['tenant_inst_id']];
			}
			if(isset($d['ban_status'])){
				$d['ban_status'] = $params['status'][$d['ban_status']];
			}
			if(isset($d['house_status'])){
				$d['house_status'] = $params['status'][$d['house_status']];
			}
			if(isset($d['tenant_status'])){
				$d['tenant_status'] = $params['status'][$d['tenant_status']];
			}
			if(isset($d['change_status'])){
				$d['change_status'] = $params['op_order_status'][$d['change_status']];
			}
			if(isset($d['ban_use_id'])){
				$d['ban_use_id'] = $params['uses'][$d['ban_use_id']];
			}
			if(isset($d['house_use_id'])){
				$d['house_use_id'] = $params['uses'][$d['house_use_id']];
			}
			if(isset($d['house_is_pause'])){
				$d['house_is_pause'] = $params['is_status'][$d['house_is_pause']];
			}
			if(isset($d['is_invoice'])){
				$d['is_invoice'] = $params['is_invoice'][$d['is_invoice']];
			}
			if(isset($d['pay_way'])){
				$d['pay_way'] = $params['pay_way'][$d['pay_way']];
			}
			
		}
		//halt($params);
		return $data;
	}
}
