<?php
namespace WeppsAdmin\ConfigExtensions\Example;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

/**
 * @var \Smarty $smarty
 */

class RequestExampleWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (@ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
			ExceptionWepps::error404();
		}
		switch ($action) {
			case "test":
				UtilsWepps::debug('test1',1);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestExampleWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);