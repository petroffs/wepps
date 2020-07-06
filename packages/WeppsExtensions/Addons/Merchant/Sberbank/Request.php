<?
use WeppsExtensions\Addons\Merchant\Sberbank\SberbankWepps;
use WeppsCore\Utils\UtilsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

$obj = new SberbankWepps($_REQUEST);
$obj->output();
unset($obj);
?>