<?php
#$start = microtime(true); 
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Template\TemplateWepps;

require_once 'config.php';
require_once 'autoloader.php';
require_once 'configloader.php';

/** 
 * @var \WeppsCore\Utils\TemplateHeadersWepps $headers
 * */
$navigator = new NavigatorWepps();
$obj = new TemplateWepps($navigator, $headers);
unset($obj);
#$stat = array('db'=>ConnectWepps::$instance->count,'time'=>microtime(true)-$start);
//UtilsWepps::debug($stat,1);
ConnectWepps::$instance->close();
?>