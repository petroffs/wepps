<?php
namespace WeppsAdmin\Bot;

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../autoloader.php';
require_once __DIR__ . '/../../../configloader.php';

/** @var array $argv */
if (!isset($argv[1])) exit();
$obj = new BotWepps($argv);
unset($obj);
/*
 * Пример вызова из консоли
 * php /var/www/pps.ubu/packages/WeppsAdmin/Bot/Request.php yandexbackup
 * php /mnt/sda3/www/host.ubu/packages/WeppsAdmin/Bot/Request.php yandexbackup
 */
?>