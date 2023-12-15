<?php
namespace WeppsExtensions\PopupsPage;

use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsAdmin\Bot\BotTestWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

/**
 * @var Smarty $smarty
 */

class RequestPopupsPageWepps extends RequestWepps {
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

$request = new RequestPopupsPageWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>