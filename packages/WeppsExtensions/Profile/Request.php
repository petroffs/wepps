<?php
require_once '../../../configloader.php';

use WeppsCore\Connect;
use WeppsCore\Tasks;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsCore\Users;
use WeppsCore\Utils;
use WeppsCore\Validator;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\RemoteServices\RecaptchaV2;
use WeppsExtensions\Cart\CartUtils;

/**
 * Класс для обработки запросов профиля пользователя.
 */
class RequestProfile extends Request
{
	/**
	 * Обрабатывает запросы профиля пользователя.
	 *
	 * @param string $action Действие, которое необходимо выполнить.
	 */
	public function request($action = "")
	{
		switch ($action) {
			case "sign-in":
				$this->signIn();
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
						//location.reload();
						location.href='/profile/';
						</script>
					";
				echo $js;
				break;
			case 'password':
				$this->password();
				$this->outer('Запрос на смену пароля отправлен');
				break;
			case 'password-confirm':
				$this->confirmPassword();
				$this->outer('Пароль установлен');
				break;
			case 'reg':
				$this->reg();
				$this->outer('Для завершения регистрации, загляните в почту');
				break;
			case 'reg-confirm':
				$this->confirmReg();
				$this->outer("Регистрация завершена<br/><br/><a href=\"/profile/\" class=\"pps_button\">Войти в личный кабинет</a>");
				break;
			case 'addOrdersMessage':
				$this->addOrdersMessage();
				break;
			case 'change-name':
				$this->changeName();
				$this->outer("Данные обновлены");
				break;
			case 'change-email':
				$this->changeEmail();
				break;
			case 'change-phone':
				$this->changePhone();
				break;
			case 'change-password':
				$this->changePassword();
				$this->outer("Пароль обновлен");
				break;
			default:
				Exception::error(404);
				break;
		}
	}
	/**
	 * Выполняет вход пользователя.
	 *
	 * @return bool Возвращает true, если вход выполнен успешно, иначе false.
	 */
	private function signIn(): bool
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
		if (!empty(array_filter($this->errors))) {
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
	/**
	 * Отправляет запрос на смену пароля.
	 *
	 * @return bool Возвращает true, если запрос отправлен успешно, иначе false.
	 */
	private function password(): bool
	{
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
		if (!empty(array_filter($this->errors))) {
			echo $recaptcha->reset();
			return false;
		}
		Utils::cookies('wepps_token', '');
		$lifetime = 3600 * 24;
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'pass',
			'id' => $user['Id']
		], $lifetime);
		$tasks = new Tasks();
		$payload = $jwt->token_decode($token);
		$jdata = [
			'token' => $token,
			'nameFirst' => $user['NameFirst'],
			'email' => $user['Email'],
			'exp' => $payload['payload']['exp'],
		];
		$tasks->add('password', $jdata, date('Y-m-d H:i:s'), @$_SERVER['REMOTE_ADDR']);
		return true;
	}
	private function checkPassword(): bool
	{
		$this->get['password'] = trim($this->get['password']);
		$this->get['password2'] = trim($this->get['password2']);

		if (strlen($this->get['password']) < 6) {
			$this->errors['password'] = 'Пароль должен быть не менее 6 символов';
		} elseif (!preg_match('/[A-Z]/', $this->get['password'])) {
			$this->errors['password'] = 'Пароль должен содержать хотя бы одну заглавную букву';
		} elseif (!preg_match('/[a-z]/', $this->get['password'])) {
			$this->errors['password'] = 'Пароль должен содержать хотя бы одну строчную букву';
		} elseif (!preg_match('/[^A-Za-z0-9]/', $this->get['password'])) {
			$this->errors['password'] = 'Пароль должен содержать хотя бы один спецсимвол';
		} elseif ($this->get['password'] != $this->get['password2']) {
			$this->errors['password'] = 'Пароли не совпадают';
		}
		return true;
	}
	/**
	 * Подтверждает смену пароля.
	 *
	 * @return bool Возвращает true, если смена пароля подтверждена успешно, иначе false.
	 */
	private function confirmPassword()
	{
		Utils::cookies('wepps_token', '');

		$this->errors = [];
		if (empty($this->get['token'])) {
			$this->errors['password'] = 'Неверный токен';
		} else {
			$jwt = new Jwt();
			$token = $jwt->token_decode($this->get['token']);
			if ($token['status'] !== 200) {
				$this->errors['password'] = $token['message'];
			} elseif ($token['payload']['typ'] != 'pass') {
				$this->errors['password'] = 'Неверный токен';
			} else {
				self::checkPassword();
			}
		}
		if (!empty(array_filter($this->errors))) {
			return false;
		}
		$password = password_hash($this->get['password'], PASSWORD_BCRYPT);
		Connect::$instance->query("update s_Users set Password=? where Id=?", [$password, $token['payload']['id']]);
		$user = Connect::$instance->fetch("select * from s_Users where Id=?", [$token['payload']['id']])[0];
		$tasks = new Tasks();
		$jdata = [
			'id' => $token['payload']['id'],
			'nameFirst' => $user['NameFirst'],
			'email' => $user['Email'],
		];
		$tasks->add('password-confirm', $jdata, date('Y-m-d H:i:s'), @$_SERVER['REMOTE_ADDR']);
		// $user = new Users([
		// 		'login' => $user['Login'],
		// 		'password' => $this->get['password']
		// ]);
		// $user->signIn();
		return true;
	}
	/**
	 * Регистрирует пользователя.
	 *
	 * @return bool Возвращает true, если регистрация прошла успешно, иначе false.
	 */
	private function reg(): bool
	{
		$this->get['login'] = strtolower(trim($this->get['login'] ?? ''));
		$this->get['phone'] = Utils::phone($this->get['phone'] ?? '')['num'] ?? '';
		$this->errors = [];
		$this->errors['login'] = Validator::isEmail($this->get['login'], 'Нверный формат');
		$this->errors['phone'] = Validator::isNotEmpty($this->get['phone'], 'Нверный формат');
		$this->errors['nameSurname'] = Validator::isNotEmpty($this->get['nameSurname'], 'Пустое поле');
		$this->errors['nameFirst'] = Validator::isNotEmpty($this->get['nameFirst'], 'Пустое поле');
		if (empty($this->error['login']) && !empty(Connect::$instance->fetch('SELECT * from s_Users where Login=?', [$this->get['login']])[0])) {
			$this->errors['login'] = 'Пользователь уже существует';
			return false;
		}
		if (!empty($this->error['login'])) {
			return false;
		}
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'reg',
			'login' => $this->get['login']
		]);
		$tasks = new Tasks();
		$jdata = [
			'login' => $this->get['login'],
			'email' => $this->get['login'],
			'phone' => $this->get['phone'],
			'nameFirst' => $this->get['nameFirst'],
			'nameSurname' => $this->get['nameSurname'],
			'namePatronymic' => $this->get['namePatronymic'],
			'token' => $token
		];
		$tasks->add('reg-confirm', $jdata, date('Y-m-d H:i:s'), @$_SERVER['REMOTE_ADDR']);
		return true;
	}
	/**
	 * Подтверждение регистрации пользователя
	 *
	 * @return bool Возвращает true, если регистрация подтверждена успешно, иначе false
	 */
	private function confirmReg(): bool
	{
		Utils::cookies('wepps_token', '');
		$this->get['password'] = trim($this->get['password']);
		$this->get['password2'] = trim($this->get['password2']);
		$this->errors = [];
		if (empty($this->get['token'])) {
			$this->errors['password'] = 'Неверный токен';
		} else {
			$jwt = new Jwt();
			$token = $jwt->token_decode($this->get['token']);
			if ($token['status'] !== 200) {
				$this->errors['password'] = $token['message'];
			} else if ($token['payload']['typ'] != 'reg') {
				$this->errors['password'] = 'Неверный токен';
			} else {
				if ($this->get['password'] != $this->get['password2']) {
					$this->errors['password'] = 'Пароли не совпадают';
				}
				if (strlen($this->get['password']) < 6) {
					$this->errors['password'] = 'Пароль должен быть не менее 6 символов';
				}
			}
		}
		if (!empty(array_filter($this->errors))) {
			return false;
		}
		/*
		 * Регистрация пользователя
		 */
		if (!empty($json = Connect::$instance->fetch('SELECT * from s_Tasks where Id=?', [$token['payload']['tsk']])[0]['BRequest'])) {
			$jdata = json_decode($json, true);
			if (!empty(Connect::$instance->fetch('SELECT * from s_Users where Login=?', [$jdata['login']])[0])) {
				$this->errors['password'] = 'Пользователь уже существует';
				return false;
			}
			$password = password_hash($this->get['password'], PASSWORD_BCRYPT);
			$row = [
				'Login' => $jdata['login'],
				'Password' => $password,
				'Name' => trim("{$jdata['nameSurname']} {$jdata['nameFirst']} {$jdata['namePatronymic']}"),
				'Email' => $jdata['email'],
				'UserPermissions' => 3,
				'Phone' => $jdata['phone'],
				'CreateDate' => date('Y-m-d H:i:s'),
				'NameFirst' => $jdata['nameFirst'],
				'NameSurname' => $jdata['nameSurname'],
				'NamePatronymic' => $jdata['namePatronymic'],
			];
			$prepare = Connect::$instance->prepare($row);
			Connect::$instance->query('INSERT ignore into s_Users  ' . $prepare['insert'], $prepare['row']);
			$tasks = new Tasks();
			$jdata = [
				'nameFirst' => $row['NameFirst'],
				'email' => $row['Email'],
			];
			$tasks->add('reg-complete', $jdata, $row['CreateDate'], @$_SERVER['REMOTE_ADDR']);
		}
		return true;
	}
	/**
	 * Добавляет сообщение к заказу.
	 *
	 * @return bool Возвращает true, если сообщение добавлено успешно, иначе false.
	 */
	private function addOrdersMessage(): bool
	{
		if (empty($this->get['id']) || empty($this->get['message'])) {
			return false;
		}
		if (empty(Connect::$instance->fetch('select Id from Orders where Id=? and UserId=?', [$this->get['id'], Connect::$projectData['user']['Id']]))) {
			return false;
		}
		$arr = Connect::$instance->prepare([
			'Name' => 'Msg',
			'OrderId' => $this->get['id'],
			'UserId' => Connect::$projectData['user']['Id'],
			'EType' => 'msg',
			'EDate' => date('Y-m-d H:i:s'),
			'EText' => trim(strip_tags($this->get['message']))
		]);
		$sql = "insert into OrdersEvents {$arr['insert']}";
		Connect::$instance->query($sql, $arr['row']);
		$cartUtils = new CartUtils();
		$order = $cartUtils->getOrder($this->get['id'], Connect::$projectData['user']['Id']);
		$this->assign('order', $order);
		$this->tpl = 'ProfileOrdersItem.tpl';
		return true;
	}
	/**
	 * Изменяет имя пользователя.
	 *
	 * @return bool Возвращает true, если имя изменено успешно, иначе false.
	 */
	private function changeName(): bool
	{
		$this->errors = [];
		$this->errors['nameSurname'] = Validator::isNotEmpty($this->get['nameSurname'], 'Пустое поле');
		$this->errors['nameFirst'] = Validator::isNotEmpty($this->get['nameFirst'], 'Пустое поле');
		if (!empty(array_filter($this->errors))) {
			return false;
		}
		$row = [
			'NameFirst' => $this->get['nameFirst'],
			'NameSurname' => $this->get['nameSurname'],
			'NamePatronymic' => $this->get['namePatronymic'],
		];
		$row['Name'] = preg_replace('/\s+/', ' ', trim("{$row['NameSurname']} {$row['NameSurname']} {$row['NamePatronymic']}"));
		$prepare = Connect::$instance->prepare($row);
		$prepare['row']['Id'] = Connect::$projectData['user']['Id'];
		Connect::$instance->query("UPDATE s_Users set {$prepare['update']} where Id=:Id", $prepare['row']);
		return true;
	}
	/**
	 * Изменяет email пользователя.
	 *
	 * @return bool Возвращает true, если email изменен успешно, иначе false.
	 */
	private function changeEmail(): bool
	{

		return true;
	}
	/**
	 * Изменяет телефон пользователя.
	 *
	 * @return bool Возвращает true, если телефон изменен успешно, иначе false.
	 */
	private function changePhone(): bool
	{

		return true;
	}
	/**
	 * Изменяет пароль пользователя.
	 *
	 * @return bool Возвращает true, если пароль изменен успешно, иначе false.
	 */
	private function changePassword(): bool
	{
		self::checkPassword();
		if (!empty(array_filter($this->errors))) {
			return false;
		}
		Connect::$instance->query("update s_Users set Password=? where Id=?", [password_hash($this->get['password'], PASSWORD_BCRYPT), Connect::$projectData['user']['Id']]);
		return true;
	}
}
$request = new RequestProfile($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);