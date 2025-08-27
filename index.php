<?php
require_once 'configloader.php';

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsExtensions\Template\Template;

$navigator = new Navigator();
new Template($navigator, $headers);
Connect::$instance->close();