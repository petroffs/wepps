<?php
require_once __DIR__ . '/../../../../../configloader.php';

$obj = new Pdf($_REQUEST);
$obj->output(false);
unset($obj);