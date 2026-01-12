<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Validator;
use WeppsCore\Users;

class RequestAdmin extends Request
{
	public function request($action = "")
	{
		$this->tpl = '';
		#$translate = Admin::getTranslate();
		switch ($action) {
			case "sign-in":
				$users = new Users($this->get);
				$users->signIn();
				$this->errors = $users->errors();
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count'] == 0) {
					$js = "
						<script>
						location.reload();
						</script>
					";
					echo $js;
				}
				break;
			case "sign-out":
				$users = new Users();
				$users->removeAuth();
				$js = "
						<script>
						location.reload()
						</script>
					";
				echo $js;
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestAdmin($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);