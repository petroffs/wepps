<?php
require_once '../../../configloader.php';

use WeppsCore\Connect;
use WeppsCore\Logs;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsCore\Users;
use WeppsCore\Utils;
use WeppsCore\Validator;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\Messages\Mail\Mail;
use WeppsExtensions\Addons\RemoteServices\RecaptchaV2;

class RequestProfile extends Request {
	public function request($action = "") {
		switch ($action) {
			case "sign-in":
				$this->signIn();
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
						location.reload();
						</script>
					";
				echo $js;
				break;
			case 'password':
				$this->password();
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count']==0) {
					$outer = Validator::setFormSuccess("Запрос на смену пароля отправлен",$this->get['form']);
					echo $outer['html'];
				}
				break;
			case 'password-confirm':
				$this->confirmPassword();
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count']==0) {
					$outer = Validator::setFormSuccess("Пароль установлен",$this->get['form']);
					echo $outer['html'];
				}
				break;
			case 'reg':
				break;
			default :
				Exception::error(404);
				break;
		}
	}
	public function signIn(): bool
	{
		$sql = "select * from s_Users where Login=? and DisplayOff=0";
		$res = Connect::$instance->fetch($sql, [$this->get['login']]);
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
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'auth',
			'id' => $res[0]['Id']
		], $lifetime);
		Utils::cookies('wepps_token', $token, $lifetime);
		Connect::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?", [date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], password_hash($this->get['password'], PASSWORD_BCRYPT), $res[0]['Id']]);
		return true;
	}
	private function password() {
		$sql = "select * from s_Users where Login=? and DisplayOff=0";
		$res = Connect::$instance->fetch($sql, [$this->get['login']]);
		$this->errors = [];
		if (empty($user = $res[0])) {
			$this->errors['login'] = 'Неверный логин';
		}
		$recaptcha = new RecaptchaV2(new TemplateHeaders());
		$response = $recaptcha->check($this->get['g-recaptcha-response']);
		if ($response['response']['success'] !== true) {
		    $this->errors['g-recaptcha-response'] = 'Ошибка проверки reCAPTCHA, попробуйте еще раз';
		}
		if (!empty($this->errors)) {
			echo $recaptcha->reset();
			return false;
		}
		Utils::cookies('wepps_token','');
		$lifetime = 3600 * 24;
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'pass',
			'id' => $user['Id']
		], $lifetime);
		$logs = new Logs();
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
		Utils::cookies('wepps_token','');
		$sql = "select * from s_Users where Login=? and DisplayOff=0";
		$res = Connect::$instance->fetch($sql, [$this->get['login']]);
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
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'pass',
			'id' => $user['Id']
		], $lifetime);
		// $url = 'https://'.Connect::$projectDev['host']."/profile/password.html?token={$token}";
		// $text = "<b>Добрый день, {$user['NameFirst']}!</b><br/><br/>Поступил запрос на смену пароля в Личном Кабинете!";
		// $text.= "<br/><br/>Для установки нового пароля перейдите по ссылке:";
		// $text.= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Установить новый пароль</a></center>";
		// $mail = new Mail('html');
		// $mail->mail($user['Email'],"Восстановление доступа",$text);
		#Connect::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?", [date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], password_hash($this->get['password'], PASSWORD_BCRYPT), $res[0]['Id']]);
		return true;
	}
}
$request = new RequestProfile($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);