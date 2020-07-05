<?
namespace PPSExtensions\Pdf;
//use PPSExtensions\Pdf\PdfPPS;
require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';
$obj = new PdfPPS($_REQUEST);
$obj->output(true);
unset($obj);
?>