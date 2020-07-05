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
function smarty_modifier_phone($string,$path)
{
	switch ($path) {
		case "1":
			$str = substr($string,1,3);
		break;
		case "2":
			$str = substr($string,-7);
			break;
	}
	return $str;
}

?>
