<?php
namespace WeppsExtensions\News;
use WeppsCore\Utils\RequestWepps;

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
				$this->tpl = "RequestNews.tpl";
				break;
		}
	}
}
$request = new RequestNewsWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>