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

namespace WeppsAdmin\Admin;

require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Validator;
use WeppsCore\Users;

class RequestAdmin extends Request
{
	#[\Override]
	public function request($action = ""): void
	{
		$this->tpl = '';
		#$translate = Admin::getTranslate();
		switch ($action) {
			case "sign-in":
				$users = new Users($this->get);
				$users->signIn();
				$this->errors = $users->errors();
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count'] == 0) {
					$js = "
						<script>
						location.reload();
						</script>
					";
					echo $js;
				}
				break;
			case "sign-out":
				$users = new Users();
				$users->removeAuth();
				$js = "
						<script>
						location.reload()
						</script>
					";
				echo $js;
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestAdmin($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);