<?php

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UsersWepps;

setlocale(LC_ALL, 'ru_RU.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');

ConnectWepps::getInstance($projectSettings);
$smarty = SmartyWepps::getSmarty();
$users = new UsersWepps();
$users->getAuth();
$smarty->assign('user',@ConnectWepps::$projectData['user']);
$headers = new TemplateHeadersWepps();
$headers::$rand = "dev-1";
if (ConnectWepps::$projectDev['debug']==1) {
	$headers::$rand .= "-".rand(100,10000000);
}