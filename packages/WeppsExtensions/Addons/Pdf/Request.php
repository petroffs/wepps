<?php
namespace WeppsExtensions\Addons\Pdf;

require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../configloader.php';

$obj = new PdfWepps($_REQUEST);
$obj->output(false);
unset($obj);
?>