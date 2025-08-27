<?php
require_once __DIR__ . '/../../../../configloader.php';

$obj = new Rest(['cli'=>@$argv]);
unset($obj);
?>