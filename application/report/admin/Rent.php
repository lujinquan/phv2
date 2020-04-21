<?php
namespace app\report\admin;
use think\Db;
use app\system\admin\Admin;
use app\report\model\Report as ReportModel;
use app\report\model\MonthReport as MonthReportModel;
include EXTEND_PATH.'phpexcel/PHPExcel.php';

class Rent extends Admin
{

    public function index()
    {

    	if ($this->request->isAjax()) {
            
        }
        return $this->fetch();
    }

    /**
     * [months 月租金报表]
     * @return [type] [description]
     */
    public function months()
    {
        // //把所有月租金报表数据同步写入到文件中去
        // $ReportModel = new ReportModel;
        // $tempData = $ReportModel->where([['type','eq','RentReport']])->column('date,data');
        // foreach ($tempData as $k => $v) {
        //     file_put_contents(ROOT_PATH.'file/report/rent/'.$k.'.txt', $v);
        // }
    	if ($this->request->isAjax()) {
            $ReportModel = new ReportModel;
            $where = [['type','eq','RentReport']];
            $getData = $this->request->post();
            $instid = (isset($getData['inst_id']) && $getData['inst_id'])?$getData['inst_id']:INST;
            $ownerid = (isset($getData['owner_id']) && $getData['owner_id'])?$getData['owner_id']:1;
            $query_month = (isset($getData['query_month']) && $getData['query_month'])?str_replace('-','',$getData['query_month']):date('Ym');

            $where[] = [['date','eq',$query_month]];

            $tempData = @file_get_contents(ROOT_PATH.'file/report/rent/'.$query_month.'.txt');
            //halt($res);
            //$tempData = $ReportModel->where($where)->value('data');

            if($tempData){
                $temps = json_decode($tempData,true);
                $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            }else{
                $data['data'] = [];
            }
            $temps = json_decode($tempData,true);
            $data['data'] = isset($temps[$ownerid][$instid])?$temps[$ownerid][$instid]:[];
            $data['msg'] = '';
            $data['code'] = 0;
            //halt(json_encode($data));
            return json_encode($data);
        }
        return $this->fetch();
    }

    /**
     * [months 生成月租金报表]
     * @return [type] [description]
     */
    public function makeMonthReport()
    {
        if ($this->request->isAjax()) {
            //set_time_limit(0);
            $date = date('Ym');
            //$date = 201909;
            //Debug::remark('begin');
            $MonthReportModel = new MonthReportModel;
            $HouseReportdata = $MonthReportModel->makeMonthReport($date);
            //Debug::remark('end');
            $where = [['type','eq','RentReport'],['date','eq',$date]];

            $ReportModel = new ReportModel;
            $res = $ReportModel->where($where)->find();

            if($res){
                $re = $ReportModel->where($where)->update(['data'=>json_encode($HouseReportdata)]);
            }else{
                $re = $ReportModel->create([
                    'data'=>json_encode($HouseReportdata),
                    'type'=>'RentReport',
                    'date'=>$date,
                ]);
            }
            
            $data = [];
            $data['msg'] = $date.'月报，保存成功！';
            $data['code'] = 1;
            return json($data);
        }
    }

    /**
     * [months 月租金分析报表]
     * @return [type] [description]
     */
    public function analyze()
    {
    	if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * [months 欠租明细报表]
     * @return [type] [description]
     */
    public function unpaidRent()
    {
        if ($this->request->isAjax()) {
            $ReportModel = new ReportModel;
            $result = $ReportModel->getUnpaidRent();
            $data = [];
            $data['data'] = $result['data'];
            $data['count'] = count($result['data']);
            $data['code'] = 0;
            $data['msg'] = '';
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * [months 欠租明细报表]
     * @return [type] [description]
     */
    public function export()
    {
        if ($this->request->isAjax()) {

            $ReportModel = new ReportModel; 
            $tableTemp =  $ReportModel->getUnpaidRent();
           
            $table = $tableTemp['data'];

            if(!$table){
                return $this->error('暂无数据导出！');
            }

            $tableData = [];
            //设置字段的排序
            $sort = ['number','address','tenant','inst','owner','use','curMonthUnpaidRent','beforeMonthUnpaidRent','beforeYearUnpaidRent','total','remark'];
            //标题
            $values = ['房屋编号','地址','户名','管段','产别','使用性质','本月欠租','以前月欠租','以前年欠租','合计欠租','备注'];
            $sortFlip = array_flip($sort);
            //将数组重新按一定顺序组装成数值型键值对数组
            $y = 0;//halt($table);
            foreach($table as $s){
                foreach($s as $u => $o){
                    $tableData[$y][$sortFlip[$u]] = $o;
                } 
                ksort($tableData[$y]);
                $y++;
            }
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

            /*----------------创建sheet-----------------*/

            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();

            //设置当前活动sheet的名称
            $objActSheet->setTitle('欠租明细');

            // ak 是行
            foreach($tableData as $ak => $a){ 
                $objActSheet->getRowDimension($ak+1)->setRowHeight(18);//设置行高度
                // bk 是列
                foreach($a as $bk => $b){
    
                    if($ak === 0){ //如果是第一行
                        $objActSheet->getColumnDimension($letter[$bk])->setWidth(20); //设置列宽度                  
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFont()->setBold(true); //设置是否加粗
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);//设置填充颜色
                        $objActSheet->getStyle($letter[$bk] . ($ak+1))->getFill()->getStartColor()->setRGB('E6E6E6'); //设置填充颜色

                        $objActSheet->setCellValue($letter[$bk] . ($ak+1), $values[$bk]);  //写入标题
                    }
                    if($bk == 'A'){ //将第一列的格式改成文本，其他列不变
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), ' ' . $b . ' ');
                    }else{
                        $objActSheet->setCellValue($letter[$bk] . ($ak+2), $b);  
                    }
                    
                    
     
                }
            }
            //生成excel表格，自定义名
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

            /*------------这种是保存到浏览器下载位置（客户端）-------------------*/

            $filename = $tableTemp['op'].'欠租明细_' . date('YmdHis', time()) . '.xlsx';    //定义文件名

            /*
            
            // 方案一：直接在浏览器上下载
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename=' . $filename);
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');

            */
           //echo strtoupper(substr(PHP_OS,0,3))==='WIN'?'windows 服务器':'不是 widnows 服务器';

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
}