<?php
namespace WeppsAdmin\Admin;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Utils\UsersWepps;
use WeppsCore\Utils\UtilsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestAdminWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		#$translate = AdminWepps::getTranslate();
		switch ($action) {
			case "sign-in":
				$users = new UsersWepps($this->get);
				$users->signIn();
				$this->errors = $users->errors();
				$outer = ValidatorWepps::setFormErrorsIndicate($this->errors, $this->get['form']);
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
				$users = new UsersWepps();
				$users->removeAuth();
				$js = "
						<script>
						location.reload();
						</script>
					";
				echo $js;
				break;
			case "logoff":
				if (isset(ConnectWepps::$projectData['user']['Id'])) {
					UtilsWepps::debug('remove auth');
					$js = "
						<script>
						location.reload()
						</script>
					";
					echo $js;
				}
				break;
			case "hook":
				if (empty(ConnectWepps::$projectServices['wepps']['git']) || ConnectWepps::$projectServices['wepps']['git'] != $_SERVER['HTTP_X_GITLAB_TOKEN']) {
					http_response_code(401);
					echo "FAIL";
					exit();
				}
				$dir = ConnectWepps::$projectDev['root'];
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
				$token = ConnectWepps::$projectServices['wepps']['git'];
				if (empty($token) || $token!=$_SERVER['HTTP_CLIENTTOKEN']) {
					http_response_code(401);
					echo "FAIL";
					exit();
				}
				$dir = ConnectWepps::$projectDev['root'];
				$git = "{$dir}/.git";
				$branch = 'master';
				echo "git start\n";
				$cmd = "git --work-tree={$dir} --git-dir={$git} fetch origin {$branch} && git --work-tree={$dir} --git-dir={$git} reset --hard origin/{$branch}";
				exec($cmd);
				echo "\n";
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestAdminWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);