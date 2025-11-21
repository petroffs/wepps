<?php
namespace WeppsCore;

use WeppsExtensions\Addons\Jwt\Jwt;

/**
 * Класс для работы с пользователями.
 */
class Users
{
	private $token = '';
	private $get = [];
	private $errors = [];

	/**
	 * Конструктор класса Users.
	 *
	 * @param array $settings Настройки пользователя.
	 */
	public function __construct(array $settings = [])
	{
		$this->get = $settings;
	}

	/**
	 * Метод для входа пользователя в систему.
	 *
	 * @return bool Возвращает true, если вход выполнен успешно, иначе false.
	 */
	public function signIn(): bool
	{
		$sql = "select * from s_Users where Login=? and ShowAdmin=1 and IsHidden=0";
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
			'id' => $res[0]['Id'],
		], $lifetime);
		Utils::cookies('wepps_token', $token, $lifetime);
		Connect::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?", [date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], password_hash($this->get['password'], PASSWORD_BCRYPT), $res[0]['Id']]);
		return true;
	}

	/**
	 * Метод для получения ошибок.
	 *
	 * @return array Возвращает массив ошибок.
	 */
	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Метод для проверки аутентификации пользователя.
	 *
	 * @return bool Возвращает true, если пользователь аутентифицирован, иначе false.
	 */
	public function getAuth(): bool
	{
		$allheaders = Connect::$projectData['headers'] = Utils::getAllheaders();
		$token = '';
		if (!empty($allheaders['authorization']) && strstr($allheaders['authorization'], 'Bearer ')) {
			$token = str_replace('Bearer ', '', $allheaders['authorization']);
		}
		$token = (empty($token) && !empty(Utils::cookies('wepps_token'))) ? Utils::cookies('wepps_token') : $token;
		if (empty($token)) {
			return false;
		}
		$jwt = new Jwt();
		$data = $jwt->token_decode($token);
		if (@$data['payload']['typ'] != 'auth' || empty($data['payload']['id'])) {
			Utils::cookies('wepps_token','');
			return false;
		}
		$sql = "select * from s_Users where Id=? and IsHidden=0";
		$res = Connect::$instance->fetch($sql, [$data['payload']['id']]);
		Connect::$projectData['user'] = $res[0];
		return true;
	}

	/**
	 * Метод для удаления аутентификации пользователя.
	 *
	 * @return bool Возвращает true, если аутентификация удалена, иначе false.
	 */
	public function removeAuth(): bool
	{
		if (!session_id()) {
			session_start();
			session_unset();
			session_destroy();
		}
		if (empty(Connect::$projectData['user'])) {
			return false;
		}
		Utils::cookies('wepps_token','');
		return true;
	}

	/**
	 * Метод для генерации пароля.
	 *
	 * @return string Возвращает сгенерированный пароль.
	 */
	public function password(): string
	{
		$letters = ['a', 'o', 'u', 'i', 'e', 'y', 'A', 'U', 'I', 'E', 'Y', 'w', 'r', 't', 'k', 'm', 'n', 'b', 'h', 'd', 's', 'W', 'R', 'T', 'K', 'M', 'N', 'B', 'H', 'D', 'S'];
		$symbols = ['.', '$', '-', '!'];
		$arr = [];
		for ($i = 1; $i <= 8; $i++) {
			$arr[] = $letters[rand(0, count($letters) - 1)];
		}
		$arr[] = rand(1, 9);
		$arr[] = rand(1, 9);
		$arr[] = $symbols[rand(0, count($symbols) - 1)];
		shuffle($arr);
		return implode('', $arr);
	}
}