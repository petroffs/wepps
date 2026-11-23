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

namespace WeppsExtensions\_Example11;

require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;

class Request_Example11 extends Request
{
	#[\Override]
	/**
	 * @return void
	 */
	public function request($action = "")
	{
		switch ($action) {
			case 'test':
				$this->assign('test', 'test1');
				$this->tpl = "RequestExample.tpl";
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new Request_Example11($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);