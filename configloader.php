<?
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

setlocale(LC_ALL, 'ru_RU.UTF-8');
setlocale(LC_NUMERIC, 'en_EN');
if (!isset($_SESSION)) {
	@session_start ();
}
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

$headers::$rand = "dev-1";
$headers::$rand .= "-".rand(100,10000000);
?>