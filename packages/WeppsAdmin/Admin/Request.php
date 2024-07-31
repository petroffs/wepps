<?php
namespace WeppsAdmin\Admin;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestAdminWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		$translate = AdminWepps::getTranslate();
		//if (!isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin']!=1) ExceptionWepps::error404();
		switch ($action) {
			case "auth":
				$sql = "select * from s_Users where Login = '{$this->get['email']}' 
						and Password = '" . md5($this->get['passw']) . "' and UserBlock = '0' 
						and ShowAdmin = '1'";
				$currUser = ConnectWepps::$instance->fetch($sql);
				$js = "";
				if (! isset($currUser[0]['Id'])) {
					$ppsmess = $translate['mess_denied'];
				} else {
					$ppsmess = $translate['mess_welcome'];
					$authKey = rand(10101, 999999999);
					ConnectWepps::$instance->query("update s_Users set AuthKey=" . $authKey . " where Id=" . $currUser[0]['Id']);
					setcookie('authKey', $authKey, time() + 3600 * 24 * 360, '/');
					setcookie('authEmail', $currUser[0]['Login'], time() + 3600 * 24 * 360, '/');
					$_SESSION['user'] = $currUser[0];
					ConnectWepps::$instance->query("update s_Users set AuthDate = '" . date("Y-m-d H:i:s") . "', 
						MyIP = '" . $_SERVER['REMOTE_ADDR'] . "' 
						where Id = " . $_SESSION['user']['Id']);
					$js = "
						<script>
						location.reload()
						</script>
					";
				}
				echo $js;
				break;
			case "logoff":
				if (isset($_SESSION['user']['Id'])) {
					ConnectWepps::$instance->query("update s_Users set AuthKey='' 
					where Id ='{$_SESSION['user']['Id']}'");
					$_SESSION['user'] = array();
					unset($_SESSION);
					setcookie('authKey', '');
					setcookie('authEmail', '');
					$js = "
						<script>
						location.reload()
						</script>
					";
					echo $js;
				}
				break;
			case "hook":
				if (ConnectWepps::$projectServices['git']['token'] != $_SERVER['HTTP_X_GITLAB_TOKEN']) {
					http_response_code(200);
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
				break;
			case "git":
				$json = file_get_contents('php://input');
				$token = ConnectWepps::$projectServices['git']['token'];
				if ($token!=$_SERVER['HTTP_CLIENTTOKEN']) {
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
/** @var \Smarty $smarty */
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>