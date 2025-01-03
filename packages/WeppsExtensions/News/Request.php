<?php
namespace WeppsExtensions\News;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestNewsWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				ExceptionWepps::error(404);
				exit();
				break;
		}
	}
}
$request = new RequestNewsWepps($_REQUEST);
/** @var \Smarty $smarty */
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>