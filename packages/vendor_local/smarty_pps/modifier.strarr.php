<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty replace modifier plugin
 *
 * Type:     modifier<br>
 * Name:     explode<br>
 * Purpose:  string to array
 * @author   Aleksey Petrov <mail at petroffs dot com>
  * @param string
 * @param string
 * @return array
 */
function smarty_modifier_strarr($string)
{
	$tmp = explode(":::",$string);
	if ($values!=null) {
		$new = array();
		foreach ($tmp as $value) {
			$new[$value] = trim($value);
		}
		return $new;
	}
    return $tmp;
}

/* vim: set expandtab: */

?>
