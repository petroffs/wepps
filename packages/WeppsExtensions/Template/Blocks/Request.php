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

namespace WeppsExtensions\Template\Blocks;

require_once __DIR__ . '/../../../../configloader.php';

use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Request;

class RequestBlocks extends Request
{
	#[\Override]
	public function request($action = ""): void
    {
		switch ($action) {
			case 'sortable':
				if (@Connect::$projectData['user']['ShowAdmin'] == 1 && !empty($this->get['items'])) {
					$ex = explode(",", $this->get['items']);
					$co = 50;
					$str = "";
					foreach ($ex as $value) {
						$str .= "update s_Blocks set Priority='{$co}' where Id='{$value}';\n";
						$co += 5;
					}
					Connect::$db->exec($str);
				} else {
					exit();
				}
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestBlocks($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);
