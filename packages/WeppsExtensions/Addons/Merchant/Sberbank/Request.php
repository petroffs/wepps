<?

use PPSExtensions\Addons\Merchant\Sberbank\SberbankPPS;
use PPS\Utils\UtilsPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

$obj = new SberbankPPS($_REQUEST);
$obj->output();
unset($obj);
?>