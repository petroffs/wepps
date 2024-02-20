<?php
namespace WeppsExtensions\Addons\Bot;

require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../configloader.php';

/** @var array $argv */
if (!isset($argv[1])) exit();
$obj = new BotWepps($argv);
unset($obj);
?>