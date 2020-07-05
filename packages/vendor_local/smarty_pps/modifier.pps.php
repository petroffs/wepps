<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_pps($id,$tablename)
{
    if (!isset($_SESSION['user']['ShowAdmin'])) return "";
    $t = $_SESSION['user']['ShowAdmin'];
    if ($_SESSION['user']['ShowAdmin']!=1) return "";
    
    if ($tablename=='navigator') {
    	$str = "
			<div class=\"pps_admin_navigator\">
				<a href=\"/_pps/navigator{$id}\" target=\"_blank\"></a>
			</div>
		";
        return $str;
    }
    $str = "
		<div class=\"pps_admin_list\">
			<a href=\"/_pps/lists/{$tablename}/{$id}/\" target=\"_blank\"></a>
		</div>
	";
    return $str;
}
?>
