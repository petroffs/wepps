<?php
namespace WeppsExtensions\Services;

use WeppsCore\Utils\RequestWepps;

/**
 * @var \Smarty $smarty
 */

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestServicesWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestServices.tpl";
				break;
		}
	}
}
$request = new RequestServicesWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);