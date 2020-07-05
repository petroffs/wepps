<?
namespace PPSExtensions\Profile;

use PPS\Utils\RequestPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Core\DataPPS;
use PPSExtensions\Mail\MailPPS;
use PPS\Connect\ConnectPPS;
use PPS\Validator\ValidatorPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

if (! session_start ())
	session_start ();
class RequestProfilePPS extends RequestPPS {
	public function request($action = "") {
		$users = new DataPPS("s_Users");
		$dateCurr = date("Y-m-d H:i:s");
		switch ($action) {
			case 'setSettingsEmail':
				if (!isset($_SESSION['user']['Id'])) ExceptionPPS::error404();
				$errors = array();
				$errors['email'] = ValidatorPPS::isEmail($this->get['email'], "Неверное значение");
				if ($errors['email']!='')  ExceptionPPS::error404();
				$_SESSION['userAddons']['EmailCode'] = rand(10000,99909);
				$mess = "";
				$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
				$mess .= "---------------\n";
				$mess .= "День добрый!\nМы получили запрос на обновление настроек аккаунта на сайте http://".$_SERVER['HTTP_HOST']."\n\n";
				$mess .= "Код: {$_SESSION['userAddons']['EmailCode']}\n\n";
				$mess.= "С уважением, ".ConnectPPS::$projectInfo['name']."\n";
				$mess = nl2br($mess);
				$obj = new MailPPS('html');
				
				/**
				 * Временно установлен mail@petroffs.com
				 * Потому что mail.ru не принимает почту с тестового сервера.
				 * Потому что mail.ru не принимает почту с тестового сервера.
				 * Потому что mail.ru не принимает почту с тестового сервера.
				 * Потому что mail.ru не принимает почту с тестового сервера.
				 */
				
				//$obj->mail($this->get['email'],"Обновление аккаунта",$mess);
				$obj->mail('mail@petroffs.com',"Обновление аккаунта",$mess);
				$this->tpl = 'ProfileSettingsEmail.tpl';
				break;
				break;
			default :
				ExceptionPPS::error404();
				break;
		}
	}
}

$request = new RequestProfilePPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);

?>