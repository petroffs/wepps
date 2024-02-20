<?php
namespace WeppsExtensions\Addons\Rest;

/**
 * @var array $argv
 * */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../autoloader.php';
require_once __DIR__ . '/../../../configloader.php';

$obj = new RestWepps(['cli'=>@$argv]);
unset($obj);
?>