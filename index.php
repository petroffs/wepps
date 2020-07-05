<?
$start = microtime(true); 

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Template\TemplateWepps;
use WeppsCore\Utils\UtilsWepps;

require_once 'config.php';
require_once 'autoloader.php';
require_once 'configloader.php';

if (!session_start()) session_start();
$navigator = new NavigatorWepps();
$obj = new TemplateWepps($navigator, $headers);
unset($obj);
$stat = array('db'=>ConnectWepps::$instance->count,'time'=>microtime(true)-$start);
//UtilsWepps::debug($stat,1);
ConnectWepps::$instance->close();
?>