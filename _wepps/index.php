<?php

use WeppsAdmin\Admin\AdminWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

/**
 * @var TemplateHeadersWepps $headers
 */

require_once '../config.php';
require_once '../autoloader.php';
require_once '../configloader.php';

if (! session_id()) session_start();
$obj = new AdminWepps($_GET['ppsUrl'],$headers);
unset($obj);
?>