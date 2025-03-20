<?php
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UsersWepps;

setlocale(LC_ALL, 'ru_RU.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');

/* if (!isset($_SESSION)) {
	@session_start();
} */

/*
 * Инициализация соединения с БД
 * 
 * @var \WeppsCore\Connect\ConnectWepps $db
 */
/** @var array $projectSettings */
ConnectWepps::getInstance($projectSettings);

/*
 * Инищиализация Smarty
 * 
 * @var \Smarty $smarty
 */
/** @var \Smarty $smarty */
$smarty = SmartyWepps::getSmarty();

$users = new UsersWepps();
$users->getAuth();
$smarty->assign('user',@ConnectWepps::$projectData['user']);

/*
 * Подключение файлов js,css и meta
 * 
 * @var \WeppsCore\Utils\TemplateHeadersWepps $headers
 */
$headers = new TemplateHeadersWepps();
$headers::$rand = "dev-1";
if (ConnectWepps::$projectDev['debug']==1) {
	$headers::$rand .= "-".rand(100,10000000);
}
?>