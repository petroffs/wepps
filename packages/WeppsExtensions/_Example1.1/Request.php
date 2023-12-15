<?php
namespace WeppsExtensions\Example;
use WeppsCore\Utils\RequestWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestExampleWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestExample.tpl";
				break;
		}
	}
}
$request = new RequestExampleWepps ($_REQUEST);
/** @var \Smarty $smarty */
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>