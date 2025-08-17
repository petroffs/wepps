<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UsersWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Addons\Messages\Mail\MailWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestProfileWepps extends RequestWepps {
	public function request($action = "") {
		switch ($action) {
			case "sign-in":
				
				break;
			case "sign-out":
				$users = new UsersWepps();
				$users->removeAuth();
				$js = "
						<script>
						location.reload();
						</script>
					";
				echo $js;
				break;
			default :
				ExceptionWepps::error(404);
				break;
		}
	}
}
$request = new RequestProfileWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);