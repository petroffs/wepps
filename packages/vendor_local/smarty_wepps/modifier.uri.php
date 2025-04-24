<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty plugin
 *
 */
function smarty_modifier_uri($string)
{
	$uri = $_SERVER['REQUEST_URI'];
	$pattern = '/\/(\w+\d+).html/i';
	$replacement = '';
	$uri = preg_replace($pattern,$replacement,$uri);
	
	$prop = "/property/";
	$glob = "/global/";
	$ex = explode($prop,$uri);
	$curr = (strstr($string,$prop)) ? $prop : $glob;
	$currValue = str_replace($curr,"",$string);
	//return "//!".$currValue."!$uri";
	if (strstr(rawurldecode($uri),rawurldecode($currValue))) return $uri;
	$ex[0] = $ex[0]."/";
	$tmp = "";
	if ($curr==$prop) {
		$tmp = $ex[0].$prop.$ex[1]."".$currValue;
		if (!strstr($tmp,$prop)) {
			$tmp = $ex[0].$prop.$ex[1]."".$currValue;	
		}
	} else {
		$prop1 = ($ex[1]!="") ? $prop : "";
		$tmp = $ex[0].$currValue.$prop1.$ex[1];
		if (!strstr($tmp,$glob)) {
			$tmp = $ex[0].$glob.$currValue.$prop1.$ex[1];
		}
	}
	
	$pattern = '/\/\?page=(\d+)\//i';
	$replacement = '';
	$tmp = preg_replace($pattern,$replacement,$tmp);
	
	$tmp = str_replace("//","/",$tmp);
	$tmp = str_replace("//","/",$tmp);
	//return $tmp2 = $tmp[strrpos($tmp,$prop)];
	//return $tmp2 = strpos($tmp,$prop);
	
	return $tmp;
}

?>
