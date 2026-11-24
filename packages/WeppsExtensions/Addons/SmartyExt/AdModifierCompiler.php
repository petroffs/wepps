<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Addons\SmartyExt;

use Smarty\Compile\Modifier\Base;
use WeppsCore\Connect;

class AdModifierCompiler extends Base
{
	#[\Override]
	public function compile($params, \Smarty\Compiler\Template $compiler): string
    {
		$id = $params[0];
		$tablename = $params[1];
		$panel = $params[2];
		$user = @Connect::$projectData['user']['ShowAdmin'];
        if ($user != 1) {
            return '(\'\')';
        }
        switch ($tablename) {
            case "'navigator'":
				return '(\'<div class="navigator w_admin_navigator"><a href="/_wepps/navigator\'.' . $id . '.\'" target="_blank"></a></div>\')';
            case "'panels'":
                return '(\'
                    <div class="w_admin_list w_admin_panels\">
                        <a href="/_wepps/lists/s_Panels/\'.' . $panel . '.\'/" target="_blank" title="Редактировать панель"></a>
                        <a href="/_wepps/lists/s_Panels/add/?NavigatorId=\'.' . $id . '.\'" target="_blank" title="Добавить панель"></a>
                        <a href="/_wepps/lists/s_Blocks/add/?PanelId=\'.' . $panel . '.\'" target="_blank" title="Добавить блок"></a>
                    </div>\')';
            default:
				#return '('.$tablename.')';
				return '(\'<div class="default w_admin_list"><a href="/_wepps/lists/\' . ' . $tablename . '. \'/\' .' . $id . ' . \'/" target="_blank"></a></div>\')';
        }
	}
}
