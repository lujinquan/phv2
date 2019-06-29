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

// 为方便系统升级，二次开发中用到的公共函数请写在此文件，禁止修改common.php文件
// ===== 系统升级时此文件永远不会被覆盖 =====
if (!function_exists('tranTime')) { 

	function tranTime($time) { 

		$rtime = date("m-d H:i",$time); 
		$htime = date("H:i",$time); 

		$time = time() - $time; 

		if ($time < 60) { 
			$str = '刚刚'; 
		} elseif ($time < 60 * 60) { 
			$min = floor($time/60); 
			$str = $min.'分钟前'; 
		} elseif ($time < 60 * 60 * 24) { 
			$h = floor($time/(60*60)); 
			$str = $h.'小时前 '.$htime; 
		} elseif ($time < 60 * 60 * 24 * 3) { 
			$d = floor($time/(60*60*24)); 
			if ($d==1) {
				$str = '昨天 '.$rtime; 
			} else { 
				$str = '前天 '.$rtime; 
			} 
	    } else { 
			$str = $rtime; 
		} 
		
		return $str; 

	}

} 

if (!function_exists('bubble_sort')) {
	
	function bubble_sort(&$sort,&$a,$type='asc'){//默认为正序排列
		$len = count($a);
		if($type=='desc'){
			//从大到小，倒序排列
			for($i=1;$i<$len;$i++)
			{
				for($j=$len-1;$j>=$i;$j--)
				{
					
					if($a[$j]>$a[$j-1])
					{
						$x=$a[$j];
						$a[$j]=$a[$j-1];
						$a[$j-1]=$x;
						
						$y = $sort[$j];
						$sort[$j] = $sort[$j-1];
						$sort[$j-1] = $y;
					}
				}
			}
		}else{
			//从小到大，正序排列
			for($i=1;$i<$len;$i++)
			{
				for($j=$len-1;$j>=$i;$j--)
				{
					if($a[$j]<$a[$j-1])
					{
						$x=$a[$j];
						$a[$j]=$a[$j-1];
						$a[$j-1]=$x;
						
						$y = $sort[$j];
						$sort[$j] = $sort[$j-1];
						$sort[$j-1] = $y;
					}
				}
			}
		}
		return $sort;
	}
}