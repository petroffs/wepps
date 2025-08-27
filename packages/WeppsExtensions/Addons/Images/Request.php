<?php
require_once __DIR__ . '/../../../../configloader.php';

use WeppsExtensions\Addons\Images\Images;

$obj = new Images($_GET);
$obj->stamp('center','bottom',0.1,'News|Gallery');
$obj->output();
$obj->save();
unset($obj);