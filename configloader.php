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

require_once 'config.php';
require_once 'packages/vendor/autoload.php';

use WeppsCore\Connect;
use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Users;
use WeppsCore\Utils;

setlocale(LC_ALL, 'ru_RU.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');

Connect::getInstance($projectSettings);
$smarty = Smarty::getSmarty();
$users = new Users();
$users->getAuth();
$smarty->assign('user', @Connect::$projectData['user']);
$headers = new TemplateHeaders();
$headers::$rand = "dev-1";
if (Connect::$projectDev['debug'] == 1) {
	$headers::$rand .= "-" . rand(100, 10000000);
	// if (empty(Utils::cookies('XDEBUG_SESSION'))) {
	// 	Utils::cookies('XDEBUG_SESSION', 'VSCODE', 3600);
	// }
}