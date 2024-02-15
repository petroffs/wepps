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
function smarty_modifier_isset($variable) {
	return (isset($variable)) ? 1 : 0;
}

?>
