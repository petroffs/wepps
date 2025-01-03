<?php
namespace WeppsExtensions\Example;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestExample11Wepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'test':
				$this->assign('test', 'test1');
				$this->tpl = "RequestExample.tpl";
				break;
			default:
				ExceptionWepps::error(404);
				break;
		}
	}
}
$request = new RequestExample11Wepps ($_REQUEST);
/** @var \Smarty $smarty */
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);