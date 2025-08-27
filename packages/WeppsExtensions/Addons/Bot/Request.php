<?php
require_once __DIR__ . '/../../../../configloader.php';

use WeppsExtensions\Addons\Bot\Bot;

if (!isset($argv[1])) exit();
$obj = new Bot
($argv);
unset($obj);