<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Utils;

class RequestLegacy extends Request
{
	public function request($action = "")
	{
		switch ($action) {
			case 'agree':
				$lifetime = 60*60*24*180;
				Utils::cookies('wepps_cookies_default',$this->get['default']??'false',$lifetime);
				Utils::cookies('wepps_cookies_analytics',$this->get['analytics']??'false',$lifetime);
				if ($this->get['default']??'false' === 'true') {
					echo "<script>$('.legal-modal').remove()</script>";
				}
				break;
			case 'settings':
				$this->tpl = 'RequestSettings.tpl';
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new RequestLegacy($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);