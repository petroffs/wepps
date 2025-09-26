<?php
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
	// if (empty($_COOKIE['XDEBUG_SESSION'])) {
	// 	Utils::cookies('XDEBUG_SESSION', 'VSCODE', 3600);
	// }
}