<?
use WeppsExtensions\Addons\Merchant\YandexKassa\YandexKassaWepps;
use WeppsCore\Utils\UtilsWepps;

require_once __DIR__ . '/../../../../../config.php';
require_once __DIR__ . '/../../../../../autoloader.php';
require_once __DIR__ . '/../../../../../configloader.php';

$obj = new YandexKassaWepps($_REQUEST);
echo $obj->getOutput();
unset($obj);
?>