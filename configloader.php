<?
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

setlocale(LC_ALL, 'ru_RU.UTF-8');
/*
 * Инициализация соединения с БД
 * 
 * @var \WeppsCore\Connect\ConnectWepps $db
 */
ConnectWepps::getInstance($projectSettings);

/*
 * Инищиализация Smarty
 * 
 * @var \Smarty $smarty
 */
$smarty = SmartyWepps::getSmarty();

/*
 * Подключение файлов js,css и meta
 * 
 * @var \WeppsCore\Utils\TemplateHeadersWepps $headers
 */

$headers = new TemplateHeadersWepps();

$headers::$rand = "dec6-1";
$headers::$rand = "-".rand(100,10000000);
?>