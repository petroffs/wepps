<?php
require_once __DIR__ . '/../../../configloader.php';

/**
 * @var array $argv
 */
if (empty($argv)) {
	exit();
}
$obj = new Updates($argv);
unset($obj);