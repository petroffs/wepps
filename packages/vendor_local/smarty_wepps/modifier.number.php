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
function smarty_modifier_number($string)
{
    //return number_format($string,0,",","");
    return sprintf("%04d", $string);
}

?>
