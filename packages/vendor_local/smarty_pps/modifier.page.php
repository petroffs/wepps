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
function smarty_modifier_page($string,$page=1)
{
	$uri = $string;
	$pattern = '/page=(\d+)/i';
	$replacement = '';
	$page = ($page==1) ? "" : "page=$page";
	$znak = (strstr($uri,"?")) ? "&" : "?";
	$uri = preg_replace($pattern,$replacement,$uri).$znak.$page;
	$uri = str_replace("&&","&",$uri);
	$uri = str_replace("?&","?",$uri);
	if (substr($uri,-1)=="&") $uri = substr($uri,0,-1);
	if (substr($uri,-1)=="?") $uri = substr($uri,0,-1);
	return $uri;
}

?>
