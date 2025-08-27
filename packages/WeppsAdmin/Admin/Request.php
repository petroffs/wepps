<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Validator;
use WeppsCore\Users;
use WeppsCore\Utils;

class RequestAdmin extends Request {
	public function request($action="") {
		$this->tpl = '';
		#$translate = Admin::getTranslate();
		switch ($action) {
			case "sign-in":
				$users = new Users($this->get);
				$users->signIn();
				$this->errors = $users->errors();
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count']==0) {
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
						location.href='/profile/';
						</script>
					";
				echo $js;
				break;
			case "logoff":
				if (isset(Connect::$projectData['user']['Id'])) {
					Utils::debug('remove auth');
					$js = "
						<script>
						location.reload()
						</script>
					";
					echo $js;
				}
				break;
			case "hook":
				if (empty(Connect::$projectServices['wepps']['git']) || Connect::$projectServices['wepps']['git'] != $_SERVER['HTTP_X_GITLAB_TOKEN']) {
					http_response_code(401);
					echo "FAIL";
					exit();
				}
				$dir = Connect::$projectDev['root'];
				$git = "{$dir}/.git";
				$json = file_get_contents('php://input');
				$body = json_decode($json, true);
				$message = trim($body['commits'][0]['message']);
				$branch = "master";
				if (strstr($message, "dev:")) {
					$branch = "dev";
				}
				if (preg_match('/prod:|dev:|chore:|fix:|feat:|refact:|test:/i', $message)) {
					$cmd = "git --work-tree={$dir} --git-dir={$git} fetch origin {$branch} && git --work-tree={$dir} --git-dir={$git} reset --hard origin/{$branch}";
					#$cmd = "cd {$dir} && git pull origin {$branch}";
					exec($cmd);
				}
				echo "OK";
				exit();
			case "git":
				$json = file_get_contents('php://input');
				$token = Connect::$projectServices['wepps']['git'];
				if (empty($token) || $token!=$_SERVER['HTTP_CLIENTTOKEN']) {
					http_response_code(401);
					echo "FAIL";
					exit();
				}
				$dir = Connect::$projectDev['root'];
				$git = "{$dir}/.git";
				$branch = 'master';
				echo "git start\n";
				$cmd = "git --work-tree={$dir} --git-dir={$git} fetch origin {$branch} && git --work-tree={$dir} --git-dir={$git} reset --hard origin/{$branch}";
				exec($cmd);
				echo "\n";
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestAdmin ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);