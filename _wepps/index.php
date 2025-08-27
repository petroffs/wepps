<?php
use WeppsAdmin\Admin\Admin;

require_once '../configloader.php';
$projectSettings['Services']['memcached']['active'] = false;

if (!session_id()) {
    session_start();
}

$obj = new Admin($_GET['ppsUrl'],$headers);
unset($obj);