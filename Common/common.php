<?php

function arrayToObject($e){
	if( gettype($e)!='array' ) return;
	foreach($e as $k=>$v){
		if( gettype($v)=='array' || getType($v)=='object' )
			$e[$k]=(object)arrayToObject($v);
	}
	return (object)$e;
}

function objectToArray($e){
	$e=(array)$e;
	foreach($e as $k=>$v){
		if( gettype($v)=='resource' ) return;
		if( gettype($v)=='object' || gettype($v)=='array' )
			$e[$k]=(array)objectToArray($v);
	}
	return $e;
}

//Y-m-d H:i:s 格式专用mktime()
function dateToTime($date) {
	$date = explode(' ', $date);
	$date = array_merge(explode('-', $date['0']), explode(':', $date['1']));
	list($Y, $m, $d, $H, $i, $s) = $date;
	$date = mktime($H, $i, $s, $m, $d, $Y);
	unset($Y, $m, $d, $H, $i, $s);
	return $date;
}

//每页条数修正
function pageSizeFix($page_size, $default = 20, $max = 200) {
	$page_size = empty($page_size) ? $default : $page_size;
	$page_size = $page_size > $max ? $max : $page_size;
	$page_size = $page_size < 1 ? $default : $page_size;
	return $page_size;
}

//页码修正
function pageNoFix($page_no) {
	$page_no = empty($page_no) ? 1 : $page_no;
	$page_no = $page_no < 1 ? 1 : $page_no;
	return $page_no;
}

/**
 *Utf-8、gb2312都支持的汉字截取函数
 *cut_str(字符串, 截取长度, 开始长度, 编码);
 *编码默认为 utf-8
 *开始长度默认为 0
*/

function cut_zhstr($string, $sublen, $start = 0, $code = true)
{
    if($code)//utf-8
    {
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);

        if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen))."...";
        return join('', array_slice($t_string[0], $start, $sublen));
    }
    else
    {
        $start = $start*2;
        $sublen = $sublen*2;
        $strlen = strlen($string);
        $tmpstr = '';

        for($i=0; $i< $strlen; $i++)
        {
            if($i>=$start && $i< ($start+$sublen))
            {
                if(ord(substr($string, $i, 1))>129)
                {
                    $tmpstr.= substr($string, $i, 2);
                }
                else
                {
                    $tmpstr.= substr($string, $i, 1);
                }
            }
            if(ord(substr($string, $i, 1))>129) $i++;
        }
        if(strlen($tmpstr)< $strlen ) $tmpstr.= "...";
        return $tmpstr;
    }
}
function debug($val, $dump = false, $exit = true) {
	if($dump) {
		$func = $this->isAjax() ? 'var_dump' : 'dump';
	} else {
		$func = (is_array($val) || is_object($val)) ? 'print_r' : 'printf';
	}
	header("Content-type: text/html; charset=utf-8"); 
	echo '<pre>debug调试输出:<hr />';
	$func($val);
	echo '</pre>';
	if($exit) exit();
}

function strUp2Underline_format($name){
  $temp_array = array();
  for($i=0;$i<strlen($name);$i++){
	$ascii_code = ord($name[$i]);
	if($ascii_code >= 65 && $ascii_code <= 90){
	  if($i == 0){
		 $temp_array[] = chr($ascii_code + 32);
	  }else{
		$temp_array[] = '_'.chr($ascii_code + 32);
	  }
	}else{
	  $temp_array[] = $name[$i];
	}
  }
  return implode('',$temp_array);
}

function mkdirs($dir) {
	if (! is_dir ( $dir )) {
		if (! mkdirs ( dirname ( $dir ) )) {
			return false;
		}
		if (! mkdir ( $dir, 0777, true )) {
			return false;
		}
	}
	return true;
}



/**
 * 简单对称加密算法之加密
 * @param String $string 需要加密的字串
 * @param String $skey 加密EKY
 * @author Anyon Zou <zoujingli@qq.com>
 * @date 2013-08-13 19:30
 * @update 2014-10-10 10:10
 * @return String
 */
function encode($string = '', $skey = 'cxphp') {
	$strArr = str_split(base64_encode($string));
	$strCount = count($strArr);
	foreach (str_split($skey) as $key => $value)
		$key < $strCount && $strArr[$key].=$value;
	return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
}
/**
 * 简单对称加密算法之解密
 * @param String $string 需要解密的字串
 * @param String $skey 解密KEY
 * @author Anyon Zou <zoujingli@qq.com>
 * @date 2013-08-13 19:30
 * @update 2014-10-10 10:10
 * @return String
 */
function decode($string = '', $skey = 'cxphp') {
	$strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
	$strCount = count($strArr);
	foreach (str_split($skey) as $key => $value)
		$key <= $strCount && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
	return base64_decode(join('', $strArr));
}
