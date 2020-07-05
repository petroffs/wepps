<?php
use WeppsAdmin\Bot\BotWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
require_once dirname(__FILE__) . '/../../../config.php';
require_once dirname(__FILE__) . '/../../../autoloader.php';
require_once dirname(__FILE__) . '/../../../configloader.php';

if (!isset($argv[1])) exit();
$obj = new BotWepps($argv);
unset($obj);
/*
 * Пример вызова из консоли
 * php /var/www/pps.ubu/packages/WeppsAdmin/Bot/Request.php yandexbackup
 * php /mnt/sda3/www/host.ubu/packages/WeppsAdmin/Bot/Request.php yandexbackup
 */
?>