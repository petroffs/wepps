<?
use WeppsExtensions\Images\ImagesWepps;
require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';
$obj = new ImagesWepps($_GET);
$obj->output();
//$obj->save();
unset($obj);
?>