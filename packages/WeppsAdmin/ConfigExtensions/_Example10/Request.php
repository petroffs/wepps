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

namespace WeppsAdmin\ConfigExtensions\_Example10;

require_once __DIR__ . '/../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;

class Request_Example10 extends Request
{
	#[\Override]
	public function request($action = ""): void
	{
		$this->tpl = '';
		if (@Connect::$projectData['user']['ShowAdmin'] != 1) {
			Exception::error404();
		}
		switch ($action) {
			case "test":
				AdminUtils::modal('Тест ОК1');
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new Request_Example10($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);
