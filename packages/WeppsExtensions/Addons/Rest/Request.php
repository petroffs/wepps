<?php
require_once __DIR__ . '/../../../../configloader.php';

use WeppsExtensions\Addons\Rest\Rest;

$obj = new Rest(['cli' => ($argv ?? [])]);
unset($obj);