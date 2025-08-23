<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\LogsWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UsersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Addons\Jwt\JwtWepps;
use WeppsExtensions\Addons\Messages\Mail\MailWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestProfileWepps extends RequestWepps {
	public function request($action = "") {
		switch ($action) {
			case "sign-in":
				$this->signIn();
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
			case 'password':
				$this->password();
				$outer = ValidatorWepps::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count']==0) {
					$outer = ValidatorWepps::setFormSuccess("Запрос на смену пароля отправлен",$this->get['form']);
					echo $outer['html'];
				}
				break;
			case 'password-confirm':
				$this->confirmPassword();
				$outer = ValidatorWepps::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count']==0) {
					$outer = ValidatorWepps::setFormSuccess("Пароль установлен",$this->get['form']);
					echo $outer['html'];
				}
				break;
			case 'reg':
				break;
			default :
				ExceptionWepps::error(404);
				break;
		}
	}
	public function signIn(): bool
	{
		$sql = "select * from s_Users where Login=? and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql, [$this->get['login']]);
		$this->errors = [];
		if (empty($res[0]['Id'])) {
			$this->errors['login'] = 'Неверный логин';
		} elseif (strlen($res[0]['Password']) == 32) {
			if (md5($this->get['password']) != $res[0]['Password']) {
				$this->errors['password'] = 'Неверный пароль';
			}
		} elseif (!password_verify($this->get['password'], $res[0]['Password'])) {
			$this->errors['password'] = 'Неверный пароль';
		}
		if (!empty($this->errors)) {
			return false;
		}
		$lifetime = 3600 * 24 * 180;
		$jwt = new JwtWepps();
		$token = $jwt->token_encode([
			'typ' => 'auth',
			'id' => $res[0]['Id']
		], $lifetime);
		UtilsWepps::cookies('wepps_token', $token, $lifetime);
		ConnectWepps::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?", [date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], password_hash($this->get['password'], PASSWORD_BCRYPT), $res[0]['Id']]);
		return true;
	}
	private function password() {
		$sql = "select * from s_Users where Login=? and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql, [$this->get['login']]);
		$this->errors = [];
		if (empty($user = $res[0])) {
			$this->errors['login'] = 'Неверный логин';
		}
		
		/*
		 * Добавить проверку на рекапчу
		 */

		if (!empty($this->errors)) {
			return false;
		}
		UtilsWepps::cookies('wepps_token','');
		$lifetime = 3600 * 24;
		$jwt = new JwtWepps();
		$token = $jwt->token_encode([
			'typ' => 'pass',
			'id' => $user['Id']
		], $lifetime);
		$logs = new LogsWepps();
		$payload = $jwt->token_decode($token);
		$jdata = [
			'token' => $token,
			'nameFirst' => $user['NameFirst'],
			'email' => $user['Email'],
			'exp' => $payload['payload']['exp'],
		];
		$logs->add('password',$jdata,date('Y-m-d H:i:s'),@$_SERVER['REMOTE_ADDR']);
		return true;
	}
	private function confirmPassword() {
		UtilsWepps::cookies('wepps_token','');
		$sql = "select * from s_Users where Login=? and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql, [$this->get['login']]);
		$this->errors = [];
		if (empty($user = $res[0])) {
			$this->errors['login'] = 'Неверный логин';
		}
		
		/*
		 * Добавить проверку на рекапчу
		 */

		if (!empty($this->errors)) {
			return false;
		}
		$lifetime = 3600 * 24;
		$jwt = new JwtWepps();
		$token = $jwt->token_encode([
			'typ' => 'pass',
			'id' => $user['Id']
		], $lifetime);
		// $url = 'https://'.ConnectWepps::$projectDev['host']."/profile/password.html?token={$token}";
		// $text = "<b>Добрый день, {$user['NameFirst']}!</b><br/><br/>Поступил запрос на смену пароля в Личном Кабинете!";
		// $text.= "<br/><br/>Для установки нового пароля перейдите по ссылке:";
		// $text.= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Установить новый пароль</a></center>";
		// $mail = new MailWepps('html');
		// $mail->mail($user['Email'],"Восстановление доступа",$text);
		#ConnectWepps::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?", [date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], password_hash($this->get['password'], PASSWORD_BCRYPT), $res[0]['Id']]);
		return true;
	}
}
$request = new RequestProfileWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);