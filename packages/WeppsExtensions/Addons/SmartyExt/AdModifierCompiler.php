<?php
namespace WeppsExtensions\Addons\SmartyExt;

use Smarty\Compile\Modifier\Base;
use WeppsCore\Connect;

class AdModifierCompiler extends Base {
	public function compile($params, \Smarty\Compiler\Template $compiler) : string {
		$id = $params[0];
		$tablename = $params[1];
		$panel = $params[2];
		$user = @Connect::$projectData['user']['ShowAdmin'];
        if ($user!=1) {
            return '(\'\')';
        }
        switch ($tablename) {
            case "'navigator'":
				return (string) '(\'<div class="navigator pps_admin_navigator"><a href="/_wepps/navigator\'.'.$id.'.\'" target="_blank"></a></div>\')';
            case "'panels'":
                return (string) '(\'
                    <div class="pps_admin_list pps_admin_panels\">
                        <a href="/_wepps/lists/s_Panels/\'.'.$panel.'.\'/" target="_blank" title="Редактировать панель"></a>
                        <a href="/_wepps/lists/s_Panels/add/?NavigatorId=\'.'.$id.'.\'" target="_blank" title="Добавить панель"></a>
                        <a href="/_wepps/lists/s_Blocks/add/?PanelId=\'.'.$panel.'.\'" target="_blank" title="Добавить блок"></a>
                    </div>\')';
            default:
				#return '('.$tablename.')';
				return (string) '(\'<div class="default pps_admin_list"><a href="/_wepps/lists/\' . ' . $tablename . '. \'/\' .' . $id . ' . \'/" target="_blank"></a></div>\')';
        }
	}
}
