<?php
namespace WeppsExtensions\Addons;

use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\FilesWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestAddonsWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'hook':
				exit();
				$token = $_SERVER['HTTP_X_GITLAB_TOKEN'];
				if ($token!='X-pps-601-master') {
					ExceptionWepps::error404();
				}
				$dir = ConnectWepps::$projectDev['root'];
				$git = "{$dir}/.git";
				$json = file_get_contents('php://input');
				$body = json_decode($json, true);
				$branch = str_replace("X-pps-601-","",$token);
				if ("refs/heads/{$branch}" == $body["ref"]) {
					$cmd = "git --work-tree={$dir} --git-dir={$git} fetch origin {$branch}";
					exec($cmd);
					//system($cmd.' 2>&1', $cmd_error);
					$cmd = "git --work-tree={$dir} --git-dir={$git} reset --hard origin/{$branch}";
					exec($cmd);
					//system($cmd.' 2>&1', $cmd_error);
					//UtilsWepps::debugf('gited.');
					//echo "gited.";
					//echo "gited.";
					//$mail = new MailWepps();
					//$mail->mail("mail@petroffs.com", "git - ".$body['project']['name'], "git message - ".$body['commits'][0]['message']);
				}
				break;
			case 'files':
				if (!isset($this->get['fileUrl'])) {
					ExceptionWepps::error404();
				}
				FilesWepps::output($this->get['fileUrl']);
				break;
			case 'upload':
				if (!isset($this->get['filesfield'])) {
					ExceptionWepps::error404();
				}
				if (!isset($this->get['myform'])) {
					ExceptionWepps::error404();
				}
				if (!isset($_FILES)) {
					ExceptionWepps::error404();
				}
				$data = FilesWepps::upload($_FILES,$this->get['filesfield'],$this->get['myform']);
				echo $data['js'];
				ConnectWepps::$instance->close();
				break;
			default:
				$this->tpl = "RequestCustom1.tpl";
				break;
		}
	}
}

$request = new RequestAddonsWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>