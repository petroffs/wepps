<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_pps($id,$tablename,$panel=0)
{
    if (!isset($_SESSION['user']['ShowAdmin'])) return "";
    if ($_SESSION['user']['ShowAdmin']!=1) return "";
    
    switch ($tablename) {
    	case 'navigator':
    		$str = "
				<div class=\"pps_admin_navigator\">
					<a href=\"/_pps/navigator{$id}\" target=\"_blank\"></a>
				</div>
			";
    		break;
    	case 'panels':
    		$str = "
				<div class=\"pps_admin_list pps_admin_panels\">
					<a href=\"/_pps/lists/s_Panels/{$panel}/\" target=\"_blank\" title=\"Редактировать панель\"></a>
					<a href=\"/_pps/lists/s_Panels/add/?DirectoryId=$id\" target=\"_blank\" title=\"Добавить панель\"></a>
					<a href=\"/_pps/lists/s_Blocks/add/?PanelId=$panel\" target=\"_blank\" title=\"Добавить блок\"></a>
				</div>
			";
    		break;
    	case 'blocks':
    		$str = "
				<div class=\"pps_admin_list pps_admin_blocks\">
					<a href=\"/_pps/lists/s_Blocks/add/?PanelId=$id\" target=\"_blank\">B</a>
				</div>
			";
    		break;
    	default:
    		$str = "
				<div class=\"pps_admin_list\">
					<a href=\"/_pps/lists/{$tablename}/{$id}/\" target=\"_blank\"></a>
				</div>
			";
    		break;
    }
    return $str;
}
?>
