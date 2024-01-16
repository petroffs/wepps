<?php
namespace WeppsAdmin\Updates;

/**
 * @var array $argv
 */

if (empty($argv)) {
	exit();
}

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../autoloader.php';
require_once __DIR__ . '/../../../configloader.php';

$obj = new UpdatesWepps($argv);
unset($obj);
?>