<?
namespace WeppsExtensions\Addons;

use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\FilesWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Mail\MailWepps;
use WeppsAdmin\Bot\BotTestWepps;
require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestAddonsWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'test':
				$obj = new BotTestWepps();
				$obj->mail();
				break;
			case 'hook2':
				exit();
				$json = '{
							  "object_kind": "push",
							  "event_name": "push",
							  "before": "66648abcf68c6c401b9cdb7451df5e424e1b8493",
							  "after": "9e60745c3e24c24e40d02ddfd871691b1c0310fd",
							  "ref": "refs/heads/master",
							  "checkout_sha": "9e60745c3e24c24e40d02ddfd871691b1c0310fd",
							  "message": null,
							  "user_id": 1713972,
							  "user_name": "Aleksey Petrov",
							  "user_username": "petroffs",
							  "user_email": "mail@petroffs.com",
							  "user_avatar": "https://assets.gitlab-static.net/uploads/-/system/user/avatar/1713972/avatar.png",
							  "project_id": 4453617,
							  "project": {
							    "id": 4453617,
							    "name": "pps",
							    "description": "",
							    "web_url": "https://gitlab.com/lubluweb/pps",
							    "avatar_url": null,
							    "git_ssh_url": "git@gitlab.com:lubluweb/pps.git",
							    "git_http_url": "https://gitlab.com/lubluweb/pps.git",
							    "namespace": "lubluweb",
							    "visibility_level": 0,
							    "path_with_namespace": "lubluweb/pps",
							    "default_branch": "master",
							    "ci_config_path": null,
							    "homepage": "https://gitlab.com/lubluweb/pps",
							    "url": "git@gitlab.com:lubluweb/pps.git",
							    "ssh_url": "git@gitlab.com:lubluweb/pps.git",
							    "http_url": "https://gitlab.com/lubluweb/pps.git"
							  },
							  "commits": [
							    {
							      "id": "9e60745c3e24c24e40d02ddfd871691b1c0310fd",
							      "message": "test mail",
							      "timestamp": "2018-02-04T04:19:46+03:00",
							      "url": "https://gitlab.com/lubluweb/pps/commit/9e60745c3e24c24e40d02ddfd871691b1c0310fd",
							      "author": {
							        "name": "Aleksey Petrov",
							        "email": "mail@petroffs.com"
							      },
							      "added": [
							
							      ],
							      "modified": [
							        "packages/WeppsExtensions/Addons/Request.php"
							      ],
							      "removed": [
							
							      ]
							    }
							  ],
							  "total_commits_count": 1,
							  "repository": {
							    "name": "pps",
							    "url": "git@gitlab.com:lubluweb/pps.git",
							    "description": "",
							    "homepage": "https://gitlab.com/lubluweb/pps",
							    "git_http_url": "https://gitlab.com/lubluweb/pps.git",
							    "git_ssh_url": "git@gitlab.com:lubluweb/pps.git",
							    "visibility_level": 0
							  }
							}';
				$body = json_decode($json, true);
				UtilsWepps::debug("git message - ".$body['commits'][0]['message'],1);
				//$cmd = "git --work-tree=/var/www/pps.ubu --git-dir=/var/www/pps.ubu/.git fetch origin master";
				$cmd = "ssh -vT git@gitlab.com";
				system($cmd.' 2>&1');
				
				break;
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