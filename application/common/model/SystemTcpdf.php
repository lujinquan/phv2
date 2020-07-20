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
use think\Db;
use app\common\model\Cparam as ParamModel;
include EXTEND_PATH.'tcpdf/tcpdf.php';
use hisi\Dir;
use Env;

/**
 * pdf模型
 * @package app\common\model
 */
class SystemTcpdf extends Model
{
	private $html = '<table width="100%" border="1 solid #f00" cellspacing="1" cellpadding="5">
              <tr><td width="140" align="center">房屋坐落</td><td width="80" align="center">栋号</td><td width="80" align="center">建成年份</td><td width="80" align="center">层数</td><td width="80" align="center">完损等级</td><td width="80" align="center">机构类别</td><td width="80" align="center">建筑面积</td><td width="80" align="center">规定租金</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr><tr><td width="140" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td><td width="80" align="center">测试</td></tr></table>';
	/**
	 * 实例一： 设置页面100px * 100px，并导出。
	 * =====================================
	 * @author  Lucas 
	 * email:   598936602@qq.com 
	 * Website  address:  www.mylucas.com.cn
	 * =====================================
	 * 创建时间: 2020-07-14 16:09:37
	 * @return  返回值  
	 * @version 版本  1.0
	 */
	public function example_000($html = '',$format = 'PDF_PAGE_FORMAT')
	{
        //halt($html);
		if(!$html){
			$html = $this->html;
		}

        // [100,100]是自定义的页面大小
        $pdf = new \Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
        // 设置打印模式  
        $pdf->SetCreator(PDF_CREATOR);
        // 设置作者 
        $pdf->SetAuthor('Lucas');
        // 设置标题
        $pdf->SetTitle('缴费单');
        // 设置主题
        $pdf->SetSubject('TCPDF Tutorial');
        // 设置关键词
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        // 是否显示页眉  
        $pdf->setPrintHeader(false);
        // 设置页眉显示的内容  
        $pdf->SetHeaderData('logo.png', 60, '', 'lucas', array(0,64,255), array(0,64,128));
        // 设置页眉字体  
        $pdf->setHeaderFont(Array('dejavusans', '', '12'));
        // 页眉距离顶部的距离  
        $pdf->SetHeaderMargin('0');
        // 是否显示页脚  
        $pdf->setPrintFooter(false);
        // 设置页脚显示的内容  
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        // 设置页脚的字体  
        $pdf->setFooterFont(Array('dejavusans', '', '10'));
        // 设置页脚距离底部的距离  
        $pdf->SetFooterMargin('0');
        // 设置默认等宽字体  
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // 设置行高  
        $pdf->setCellHeightRatio(1);
        // 设置左、上、右的间距  
        $pdf->SetMargins('0', '0', '0');
        // 设置是否自动分页  距离底部多少距离时分页  
        $pdf->SetAutoPageBreak(false, '15');
        // 设置字体  
        $pdf->SetFont('stsongstdlight', '', 14, '', true);
        // 设置图像比例因子  
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->setFontSubsetting(true);

        if(is_array($html)){
            foreach ($html as $h) {
                $pdf->AddPage();
                $pdf->writeHTMLCell(0, 0, '', '', $h, 0, 1, 0, true, '', true);
            }
        }else{
            $pdf->AddPage();
            $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        }
        
        
        
        $pdf->Output('example_001.pdf', 'I');
	}

	public function example_001()
	{

	}
}