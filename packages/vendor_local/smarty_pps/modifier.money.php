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
function smarty_modifier_money($string)
{
	if (!is_numeric($string)) return 0;
	$tmp = "";
	if (strstr($string,"от ")!==false) {
		$tmp = "от ";
		$string = str_replace("от ","",$string);
	}
    return $tmp.number_format($string,0,","," ");
}

?>
