<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Addons\Messages\Mail\MailWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

if (! session_start ())
	session_start ();
class RequestProfileWepps extends RequestWepps {
	public function request($action = "") {
		#$users = new DataWepps("s_Users");
		#$dateCurr = date("Y-m-d H:i:s");
		switch ($action) {
			case 'setSettingsEmail':
				if (!isset($_SESSION['user']['Id'])) ExceptionWepps::error404();
				$this->errors = [];
				$this->errors['email'] = ValidatorWepps::isEmail($this->get['email'], "Неверное значение");
				if ($this->errors['email']!='')  ExceptionWepps::error404();
				$_SESSION['userAddons']['EmailCode'] = rand(10000,99909);
				$mess = "";
				$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
				$mess .= "---------------\n";
				$mess .= "День добрый!\nМы получили запрос на обновление настроек аккаунта на сайте http://".$_SERVER['HTTP_HOST']."\n\n";
				$mess .= "Код: {$_SESSION['userAddons']['EmailCode']}\n\n";
				$mess.= "С уважением, ".ConnectWepps::$projectInfo['name']."\n";
				$mess = nl2br($mess);
				$obj = new MailWepps('html');
				
				//$obj->mail($this->get['email'],"Обновление аккаунта",$mess);
				$obj->mail(ConnectWepps::$projectDev['email'],"Обновление аккаунта",$mess);
				$this->tpl = 'ProfileSettingsEmail.tpl';
				break;
			default :
				ExceptionWepps::error404();
				break;
		}
	}
}

$request = new RequestProfileWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);

?>