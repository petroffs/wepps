<?php
require_once __DIR__ . '/../../../configloader.php';

use WeppsAdmin\Updates\Updates;

if (empty($argv)) {
	exit();
}
$obj = new Updates($argv);
unset($obj);