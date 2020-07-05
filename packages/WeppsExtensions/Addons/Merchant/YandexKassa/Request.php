<?
use PPSExtensions\Addons\Merchant\YandexKassa\YandexKassaPPS;
use PPS\Utils\UtilsPPS;

require_once __DIR__ . '/../../../../../config.php';
require_once __DIR__ . '/../../../../../autoloader.php';
require_once __DIR__ . '/../../../../../configloader.php';

$obj = new YandexKassaPPS($_REQUEST);
echo $obj->getOutput();
unset($obj);
?>