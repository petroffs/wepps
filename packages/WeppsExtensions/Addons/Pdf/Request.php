<?php
namespace WeppsExtensions\Pdf;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';
$obj = new PdfWepps($_REQUEST);
$obj->output(true);
unset($obj);
?>