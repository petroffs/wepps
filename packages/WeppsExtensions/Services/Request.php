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

namespace WeppsExtensions\Services;

require_once '../../../configloader.php';

use WeppsCore\Request;

class RequestServices extends Request {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestServices.tpl";
				break;
		}
	}
}
$request = new RequestServices($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);