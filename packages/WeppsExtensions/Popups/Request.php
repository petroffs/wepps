<?php
namespace WeppsExtensions\Popups;

use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsExtensions\Addons\Bot\BotTestWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

/**
 * @var Smarty $smarty
 */

class RequestPopupsWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'test':
				$obj = new BotTestWepps();
				$obj->mail();
				break;
			default:
				ExceptionWepps::error(404);
				break;
		}
	}
}

$request = new RequestPopupsWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);