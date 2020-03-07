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

namespace app\system\admin;

use hisi\Dir;
use hisi\Database as dbOper;
// use phpexcel\PHPExcel;
include EXTEND_PATH.'phpexcel/PHPExcel.php';
use think\Db;
use Env;

/**
 * 数据库管理控制器
 * @package app\system\admin
 */
class Design extends Admin
{
	public function index()
	{
		if ($this->request->isAjax()) {
			$page = input('param.page/d', 1);
            $limit = input('param.limit/d', 10);
            $getData = $this->request->get();
            $where = 1;
            if(isset($getData['table_name']) && $getData['table_name']){
            	$where .= " AND NAME LIKE '%".$getData['table_name']."%'";
            }
            if(isset($getData['table_remark']) && $getData['table_remark']){
            	$where .= " AND Comment LIKE '%".$getData['table_remark']."%'";
            }
            $data = [];
            $sql = "SHOW TABLE STATUS WHERE ".$where;
            $tables = Db::query($sql);
            foreach ($tables as $k => &$v) {
                if(strpos($v['Name'], '_back') !== false || strpos($v['Name'], '_copy') !== false){
                    unset($tables[$k]);
                }else{
                    $v['id'] = $v['Name'];
                }
                $v['id'] = $v['Name'];
            }
            $data['data'] = array_slice($tables, ($page- 1) * $limit, $limit);
            $data['count'] = count($tables);
            $data['code'] = 0;

            return json($data);
        }
		return $this->fetch();
	}

	public function design()
	{
		$table = input('id');
		if ($this->request->isAjax()) {
			$table = input('id');
			//$tableData = Db::query("DESC ".$table);	
			$tableData = Db::query("SHOW FULL FIELDS FROM ".$table);
            	
			
			$data['data'] = $tableData;
            $data['code'] = 0;
            return json($data);
		}
		$this->assign('table',$table);
		return $this->fetch();
	}

    public function php_to_markdown(){

        //$table = input('id');

        //$sql = "SHOW TABLE STATUS WHERE NAME = '".$table."'";
        $sql = "SHOW TABLE STATUS";
        $tableStatus = Db::query($sql);
        //$tableDesc = Db::query("SELECT * FROM mysql.`innodb_index_stats` a WHERE a.table_name = 'ph_ban'");
        //$tableDesc = Db::query("select * from information_schema.tables where table_name = 'ph_ban'");
// dump($tableDesc);
//halt($tableStatus);
        foreach ($tableStatus as $key => $s) {

            $tableData = Db::query("SHOW FULL FIELDS FROM ".$s['Name']);
            $mark = '';
            // 字段
            $mark .= '### ' . $s['Name'] . ' ' . $s['Comment'] . PHP_EOL;

            $mark .= '|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |' . PHP_EOL;
            $mark .= '|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|' . PHP_EOL;
            foreach($tableData as $k => $t){
                //halt($t);
                $mark .= '| ' . ($k+1) .' | ' . $t['Comment'] . ' | ' . $t['Field'] . ' | '. $t['Type'] . ' | ' . $t['Key'] . ' |  | ' . $t['Null'] . ' |  |'  . PHP_EOL;
            }  
            // 索引 
            $mark .= PHP_EOL;
            $mark .= '### 索引' . PHP_EOL;
            $mark .= PHP_EOL;
            $mark .= '|  编号  |  名  |  字段  |  索引类型  |  索引方法  |' . PHP_EOL;
            $mark .= '|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|' . PHP_EOL;
            $mark .= '|   1 |    |    |    |    |' . PHP_EOL;
            // 引擎
            $mark .= PHP_EOL;
            $mark .= '### 引擎' . PHP_EOL;
            $mark .= PHP_EOL;
            $mark .= '|  引擎  |  排序规则  |  字符集  |  数据目录  |' . PHP_EOL;
            $mark .= '|: ------ :|: ------ :|: ------ :|: ------ :|' . PHP_EOL;
            $mark .= '| '.$s['Engine'].' | '.$s['Collation'].' | '.$s['Collation'].' |'.$s['Collation'].' |' . PHP_EOL;
            @unlink('./md/'.$s['Name'].'.md');
            file_put_contents('./md/'.$s['Name'].'.md', $mark); 
        }
        //file_put_contents($table.'.md', $mark, FILE_APPEND); 

    }
	// 页面直接输出模式：http://web.phv2.com/admin.php/system/design/export.html?id=ph_inst,ph_cparam
	public function export()
	{
		$tables = $this->request->param('id/a');
		if(!$tables){
			return $this->error('暂无数据导出！');
		}
		//$tables = explode(',',$tables[0]);
		//halt($tables = explode(',',$tables[0]));
		$tableData = [];
		foreach($tables as $table){
			$tableData[] = Db::query("SELECT * FROM ".$table);
		}
	
    	$objPHPExcel = new \PHPExcel();
    	$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel); //保存excel—2007格式

        //设置文档基本属性
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("Lucas");
        $objProps->setLastModifiedBy("Lucas");
        $objProps->setTitle("Office XLS");
        $objProps->setSubject("Office XLS");
        $objProps->setDescription("Test document, generated by PHPExcel");
        $objProps->setKeywords("system data");
        $objProps->setCategory("data report");
        /*----------------创建sheet-----------------*/
        

        $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];
        //设置子标题栏
	    foreach($tableData as $ak => $a){
	    	//每个table创建一个sheet
			if($ak>0){
				$objPHPExcel->createSheet();
			}
	    	$objPHPExcel->setActiveSheetIndex($ak);
	        $objActSheet = $objPHPExcel->getActiveSheet();
	        $objActSheet->setTitle($tables[$ak]); //设置当前活动sheet的名称
	    	foreach($a as $bk => $b){
	    		//halt($bk);
	    		//halt($b);
	    		$i = 0;
	    		foreach($b as $ck => $c){

	    			if($bk == 1){ //如果是第一行，写入标题
						$objActSheet->setCellValue($letter[$i] . $bk, $ck);
	    			}
	    			$objActSheet->setCellValue($letter[$i] . ($bk+1), $c);  
	    			//halt($c);
	    			$i++;
	    		}
	    	}
            unset($a); //主动销毁变量，否则当数据量过大会报错内存溢出：Allowed memory size ……
	    }

        //生成excel表格，自定义名
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        /*------------这种是保存到浏览器下载位置（客户端）-------------------*/

        $filename = '数据导出_' . date('YmdHis', time()) . '.xlsx';    //定义文件名

        // 方案一：直接在浏览器上下载
        // header("Pragma: public");
        // header("Expires: 0");
        // header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        // header("Content-Type:application/force-download");
        // header("Content-Type:application/vnd.ms-execl");
        // header("Content-Type:application/octet-stream");
        // header("Content-Type:application/download");
        // header('Content-Disposition:attachment;filename=' . $filename);
        // header("Content-Transfer-Encoding:binary");
        // $objWriter->save('php://output');
        // 
        // 方案二：先保存在服务器，然后返回文件路径【注意windows默认使用GBK编码，linux默认使用UTF-8编码】
        if(strtoupper(substr(PHP_OS,0,3))==='WIN'){ //如果是windows服务器，则保存成GBK编码格式
            $filePath = './upload/excel/'.convertGBK($filename);
       }else{ //如果不是，则保存成UTF-8格式
            $filePath = './upload/excel/'.convertUTF8($filename);
       }

        $objWriter->save($filePath);

        $returnJson = [];
        $returnJson['code'] = 1;
        $returnJson['msg'] = '导出成功！';
        $returnJson['data'] = '/upload/excel/'.$filename;
        return json($returnJson); // 返回的文件名需要是以UTF-8编码
	   
	}

    // 页面直接输出模式：http://web.phv2.com/admin.php/system/design/export.html?id=ph_inst,ph_cparam
    public function exportStruct()
    {

        $tables = $this->request->param('id/a');
        if(!$tables){
            return $this->error('暂无数据导出！');
        }

        $tableData = [];
        foreach($tables as $key => $table){
            $tableData[$key]['detail'] = Db::query("SHOW FULL FIELDS FROM ".$table);
            // $sql = ;
            // halt($sql);
            $tableData[$key]['info'] = Db::query("select table_name,table_comment from information_schema.tables where table_schema = '".config()['database']['database'] ."' and table_name = '".$table."'");
            //$tableData[$key]['name'] = $table;
        }
        //halt(config()['database']['database']);
    //halt($tableData);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel); //保存excel—2007格式

        //设置文档基本属性
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("Lucas");
        $objProps->setLastModifiedBy("Lucas");
        $objProps->setTitle("Office XLS");
        $objProps->setSubject("Office XLS");
        $objProps->setDescription("Test document, generated by PHPExcel");
        $objProps->setKeywords("system data");
        $objProps->setCategory("data report");
        /*----------------创建sheet-----------------*/
        

        $letter = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];
        $titleArr = ['Field'=>'字段名','Type'=>'字段类型','Collation'=>'','Null'=>'是否为空','Key'=>'键','Default'=>'默认值','Extra'=>'','Privileges'=>'','Comment'=>'备注'];
        //halt($tableData);
        //设置子标题栏
        $j = 0;
        $objPHPExcel->createSheet();
        //$objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->setTitle('表结构'); //设置当前活动sheet的名称
        foreach($tableData as $ak => $a){
     
            foreach($a['detail'] as $bk => $b){
                $i = 0;
                foreach($b as $ck => $c){
                    if($titleArr[$ck]){
                        if($bk == 1){ //如果是第一行，写入标题
                            $objActSheet->mergeCells($letter[0] . $j . ':' . $letter[5] . $j);
                            $objActSheet->getStyle($letter[0].$j)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                            $objActSheet->getStyle($letter[0].$j)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                            $objActSheet->getStyle($letter[0].$j)->getFill()->getStartColor()->setARGB('FF808080');
                            $objActSheet->setCellValue($letter[$i] . $j, $a['info'][0]['table_name'].'：'.$a['info'][0]['table_comment']);
                            $objActSheet->setCellValue($letter[$i] . ($j+1), $titleArr[$ck]);

                        } 
                        $objActSheet->setCellValue($letter[$i] . ($j+2), $c); 
                        $objActSheet->getColumnDimension($letter[$i])->setWidth(18); 
                        $i++;
                    }
                }
                $j++;
            }
            $j += 3;
            $objActSheet->mergeCells($letter[0] . ($j-1) . ':' . $letter[5] . $j);
            //$objActSheet->mergeCells($letter[0] . $j . ':' . $letter[5] . $j);
            unset($a); //主动销毁变量，否则当数据量过大会报错内存溢出：Allowed memory size ……
        }

        //生成excel表格，自定义名
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        /*------------这种是保存到浏览器下载位置（客户端）-------------------*/

        $filename = '表结构导出_' . date('YmdHis', time()) . '.xlsx';    //定义文件名

        // 方案一：直接在浏览器上下载
        // header("Pragma: public");
        // header("Expires: 0");
        // header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        // header("Content-Type:application/force-download");
        // header("Content-Type:application/vnd.ms-execl");
        // header("Content-Type:application/octet-stream");
        // header("Content-Type:application/download");
        // header('Content-Disposition:attachment;filename=' . $filename);
        // header("Content-Transfer-Encoding:binary");
        // $objWriter->save('php://output');
        // 
        // 方案二：先保存在服务器，然后返回文件路径【注意windows默认使用GBK编码，linux默认使用UTF-8编码】
        if(strtoupper(substr(PHP_OS,0,3))==='WIN'){ //如果是windows服务器，则保存成GBK编码格式
            $filePath = './upload/excel/'.convertGBK($filename);
       }else{ //如果不是，则保存成UTF-8格式
            $filePath = './upload/excel/'.convertUTF8($filename);
       }

        $objWriter->save($filePath);

        $returnJson = [];
        $returnJson['code'] = 1;
        $returnJson['msg'] = '导出成功！';
        $returnJson['data'] = '/upload/excel/'.$filename;
        return json($returnJson); // 返回的文件名需要是以UTF-8编码
       
    }
}