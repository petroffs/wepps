<?php

use WeppsAdmin\Admin\AdminWepps;

require_once '../config.php';
$projectSettings['Services']['memcached']['active'] = false;
require_once '../autoloader.php';
require_once '../configloader.php';

if (!session_id()) {
    session_start();
}

$obj = new AdminWepps($_GET['ppsUrl'],$headers);
unset($obj);