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
function smarty_modifier_format($string,$nobr='')
{
	
	//$search = array ("'/\n|\r\n/'", " - "," с ");
	//$replace = array ("<br/>","&nbsp;&mdash; "," с&nbsp;");
	$string=preg_replace('/((http|ftp|telnet|gopher):\/\/[^ \n$]+)(|.html)/iu', '<A href="\\1\\3" target="_blank">\\1\\3</A>', $string);
	
	$search = array ("'\n|\r\n'","' - '","' с '","' в '","' и '","' а '","' не '","' для '","' на '");
	$replace = array ("<br/>","&nbsp;&mdash; "," с&nbsp;"," в&nbsp;"," и&nbsp;"," а&nbsp;"," не&nbsp;"," для&nbsp;"," на&nbsp;");
	
	if ($nobr=='nobr') {
		unset($search[0]);
		unset($replace[0]);
	}
	$string = preg_replace($search, $replace, $string);
	return $string;
}

?>
