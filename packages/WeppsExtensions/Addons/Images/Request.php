<?php
namespace WeppsExtensions\Addons\Images;

require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../configloader.php';

$obj = new ImagesWepps($_GET);
$obj->stamp('center','bottom',0.1,'News|Gallery');
$obj->output();
$obj->save();
unset($obj);