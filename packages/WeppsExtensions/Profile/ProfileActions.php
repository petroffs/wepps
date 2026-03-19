<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect;
use WeppsCore\Memcached;
use WeppsCore\Tasks;
use WeppsCore\Utils;
use WeppsCore\Validator;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\Messages\Mail\Mail;
use WeppsExtensions\Cart\CartUtils;

/**
 * Бизнес-логика операций с профилем пользователя.
 * Используется как в веб (через RequestProfile), так и в REST API (через RestV1).
 */
class ProfileActions
{
	public array $errors = [];

	/**
	 * @param bool $useCookies true — веб-режим (работа с cookie), false — REST-режим
	 */
	public function __construct(protected bool $useCookies = false) {}

	/**
	 * Возвращает итоговый ответ: наличие ошибок, сообщение об успехе и список ошибок.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function result(string $message = ''): array
	{
		$hasErrors = !empty(array_filter($this->errors));
		return [
			'status'  => $hasErrors ? 400 : 200, // Bad Request / OK
			'message' => $hasErrors ? 'Bad Request' : $message,
			'data'    => ['errors' => $this->errors],
		];
	}

	/**
	 * Изменяет ФИО и адрес пользователя.
	 * Автоматически собирает поле Name из ФИО.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function changeName(int $userId, string $nameSurname, string $nameFirst, string $namePatronymic = ''): array
	{
		$this->errors = [];
		$this->errors['nameSurname'] = Validator::isNotEmpty($nameSurname, 'Пустое поле');
		$this->errors['nameFirst']   = Validator::isNotEmpty($nameFirst, 'Пустое поле');
		if (!empty(array_filter($this->errors))) {
			return $this->result();
		}
		$row = [
			'NameFirst'      => $nameFirst,
			'NameSurname'    => $nameSurname,
			'NamePatronymic' => $namePatronymic,
			'Name'           => preg_replace('/\s+/', ' ', trim("$nameSurname $nameFirst $namePatronymic")),
		];
		$prepare = Connect::$instance->prepare($row);
		$prepare['row']['Id'] = $userId;
		Connect::$instance->query("UPDATE s_Users SET {$prepare['update']} WHERE Id=:Id", $prepare['row']);
		return $this->result('Данные обновлены');
	}

	/**
	 * Смена e-mail (2 шага).
	 * Шаг 1 — code пустой: валидация + отправка кода на новый адрес.
	 * Шаг 2 — code задан: проверка кода + обновление Email и Login.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function changeEmail(int $userId, string $email, string $code = ''): array
	{
		$email = trim(strtolower($email));
		$this->errors = [];
		$this->errors['login'] = Validator::isEmail($email, 'Неверный формат');

		if (empty(array_filter($this->errors))) {
			if (!empty(Connect::$instance->fetch('SELECT Id FROM s_Users WHERE Email=?', [$email]))) {
				$this->errors['login'] = 'Пользователь с таким email уже существует';
			}
		}

		$memcache = new Memcached('yes');
		$cacheKey = 'email_change_' . md5($userId . '_' . $email);

		if ($code !== '') {
			if ($memcache->get($cacheKey) !== $code) {
				$this->errors['code'] = 'Неверный код';
				return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]];
			}
			Connect::$instance->query('UPDATE s_Users SET Email=?,Login=? WHERE Id=?', [$email, $email, $userId]);
			$memcache->delete($cacheKey);
			return ['status' => 200, 'message' => 'Ваш E-mail обновлен', 'data' => []];
		}

		if (!empty(array_filter($this->errors))) {
			// Проверяем конфликт (email уже существует) или валидация
			if (strpos($this->errors['login'], 'уже существует') !== false) {
				return ['status' => 409, 'message' => $this->errors['login'], 'data' => ['errors' => $this->errors]];
			}
			return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]];
		}

		$newCode = (string) rand(10001, 99999);
		$memcache->set($cacheKey, $newCode, 600);
		(new Mail('html'))->mail($email, 'Подтверждение почты', 'Код подтверждения смены E-mail: <b>' . $newCode . '</b>');
		return ['status' => 202, 'message' => 'Код отправлен', 'data' => []];
	}

	/**
	 * Смена телефона (2 шага).
	 * Шаг 1 — code пустой: валидация + отправка кода на текущий e-mail.
	 * Шаг 2 — code задан: проверка кода + обновление Phone.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function changePhone(int $userId, string $currentEmail, string $phone, string $code = ''): array
	{
		$this->errors = [];
		$phone = Utils::phone($phone)['num'] ?? '';

		if (empty($phone) || strlen($phone) !== 11 || substr($phone, 0, 1) !== '7') {
			$this->errors['phone'] = 'Неверный формат';
		}

		if (empty(array_filter($this->errors))) {
			if (!empty(Connect::$instance->fetch('SELECT Id FROM s_Users WHERE Phone=?', [$phone]))) {
				$this->errors['phone'] = 'Пользователь с таким телефоном уже существует';
			}
		}

		$memcache = new Memcached('yes');
		$cacheKey = 'phone_change_' . md5($userId . '_' . $phone);

		if ($code !== '') {
			if ($memcache->get($cacheKey) !== $code) {
				$this->errors['code'] = 'Неверный код';
				return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]];
			}
			Connect::$instance->query('UPDATE s_Users SET Phone=? WHERE Id=?', [$phone, $userId]);
			$memcache->delete($cacheKey);
			return ['status' => 200, 'message' => 'Ваш телефон обновлен', 'data' => []];
		}

		if (!empty(array_filter($this->errors))) {
			// Проверяем конфликт (телефон уже существует) или валидация
			if (strpos($this->errors['phone'], 'уже существует') !== false) {
				return ['status' => 409, 'message' => $this->errors['phone'], 'data' => ['errors' => $this->errors]];
			}
			return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]];
		}

		$newCode = (string) rand(10001, 99999);
		$memcache->set($cacheKey, $newCode, 600);
		(new Mail('html'))->mail($currentEmail, 'Подтверждение телефона', 'Код подтверждения смены номера телефона: <b>' . $newCode . '</b>');
		return ['status' => 202, 'message' => 'Код отправлен', 'data' => []];
	}

	/**
	 * Проверяет надёжность пароля и совпадение с подтверждением.
	 * Опционально проверяет старый пароль пользователя.
	 * Ошибки записываются в $this->errors['password'].
	 *
	 * @param string $passwordNew - новый пароль
	 * @param string $passwordNew2 - подтверждение нового пароля
	 * @param int|null $userId - если указан, проверяет старый пароль из БД
	 * @param string $passwordOld - тек старый пароль для проверки
	 */
	public function checkPassword(string $passwordNew, string $passwordNew2, ?int $userId = null, string $passwordOld = ''): bool
	{
		$passwordNew  = trim($passwordNew);
		$passwordNew2 = trim($passwordNew2);

		// Если требуется проверка старого пароля
		if ($userId !== null && !empty($passwordOld)) {
			$res = Connect::$instance->fetch('SELECT Password FROM s_Users WHERE Id=?', [$userId]);
			if (empty($res[0])) {
				$this->errors['password'] = 'Пользователь не найден';
				return false;
			}

			$currentHash = $res[0]['Password'];
			if (strlen($currentHash) == 32) {
				// Старый формат MD5
				if (md5($passwordOld) != $currentHash) {
					$this->errors['password'] = 'Неверный текущий пароль';
					return false;
				}
			} else {
				// BCRYPT формат
				if (!password_verify($passwordOld, $currentHash)) {
					$this->errors['password'] = 'Неверный текущий пароль';
					return false;
				}
			}
		}

		// Проверка надёжности нового пароля
		if (strlen($passwordNew) < 6) {
			$this->errors['password'] = 'Пароль должен быть не менее 6 символов';
		} elseif (!preg_match('/[A-Z]/', $passwordNew)) {
			$this->errors['password'] = 'Пароль должен содержать хотя бы одну заглавную букву';
		} elseif (!preg_match('/[a-z]/', $passwordNew)) {
			$this->errors['password'] = 'Пароль должен содержать хотя бы одну строчную букву';
		} elseif (!preg_match('/[^A-Za-z0-9]/', $passwordNew)) {
			$this->errors['password'] = 'Пароль должен содержать хотя бы один спецсимвол';
		} elseif ($passwordNew !== $passwordNew2) {
			$this->errors['password'] = 'Пароли не совпадают';
		}

		return empty($this->errors['password'] ?? '');
	}

	/**
	 * Изменяет пароль пользователя (2-шаговый процесс с подтверждением по e-mail).
	 * Шаг 1 (без code): валидирует новый пароль, отправляет код на e-mail пользователя.
	 * Шаг 2 (с code): проверяет код, обновляет пароль.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function changePassword(int $userId, string $password, string $password2, string $code = ''): array
	{
		$this->errors = [];
		
		// Получаем email пользователя
		$user = Connect::$instance->fetch('SELECT Email FROM s_Users WHERE Id=?', [$userId]);
		if (empty($user[0])) {
			$this->errors['password'] = 'Пользователь не найден';
			return $this->result();
		}
		$userEmail = $user[0]['Email'];

		// Шаг 2: Проверяем код подтверждения
		if (!empty($code)) {
			$memcache = new Memcached('yes');
			$cacheKey = 'password_code_' . $userId;
			$savedCode = $memcache->get($cacheKey);

			if ($savedCode !== $code) {
				$this->errors['code'] = 'Неверный код';
				return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]];
			}

			// Валидируем пароль ещё раз перед обновлением
			if (!$this->checkPassword($password, $password2)) {
				return $this->result();
			}

			Connect::$instance->query('UPDATE s_Users SET Password=? WHERE Id=?', [password_hash(trim($password), PASSWORD_BCRYPT), $userId]);
			$memcache->delete($cacheKey);
			return ['status' => 200, 'message' => 'Пароль обновлен', 'data' => []];
		}

		// Шаг 1: Валидируем новый пароль
		if (!$this->checkPassword($password, $password2)) {
			return $this->result();
		}

		// Отправляем код подтверждения
		$newCode = (string) rand(10001, 99999);
		$memcache = new Memcached('yes');
		$cacheKey = 'password_code_' . $userId;
		$memcache->set($cacheKey, $newCode, 600);
		(new Mail('html'))->mail($userEmail, 'Подтверждение смены пароля', 'Код подтверждения смены пароля: <b>' . $newCode . '</b>');
		
		return ['status' => 202, 'message' => 'Код отправлен', 'data' => []];
	}

	/**
	 * Удаление профиля (2 шага).
	 * Шаг 1 — code пустой: проверка слова + отправка кода на e-mail.
	 * Шаг 2 — code задан: проверка кода + скрытие аккаунта.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function remove(int $userId, string $userLogin, string $userEmail, string $word, string $code = ''): array
	{
		$word = trim(mb_strtolower($word));
		$this->errors = [];
		$this->errors['word'] = ($word !== 'удалить') ? 'Неверное слово' : '';

		$memcache = new Memcached('yes');
		$cacheKey = 'remove_' . md5($userLogin);

		if ($code !== '') {
			if ($memcache->get($cacheKey) !== $code) {
				$this->errors['code'] = 'Неверный код';
				return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]]; // Bad Request
			}
			Connect::$instance->query('UPDATE s_Users SET IsHidden=1, AuthDate=now() WHERE Id=?', [$userId]);
			$memcache->delete($cacheKey);
			return ['status' => 200, 'message' => 'Ваш профиль удален', 'data' => []]; // OK
		}

		if (!empty(array_filter($this->errors))) {
			return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]]; // Bad Request
		}

		$newCode = (string) rand(10001, 99999);
		$memcache->set($cacheKey, $newCode, 600);
		(new Mail('html'))->mail($userEmail, 'Подтверждение удаления аккаунта', 'Код подтверждения удаления аккаунта: <b>' . $newCode . '</b>');
		return ['status' => 202, 'message' => 'Код отправлен', 'data' => []]; // Accepted — код отправлен
	}

	/**
	 * Инициация регистрации: валидация данных + создание задачи подтверждения.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function register(string $login, string $phone, string $nameSurname, string $nameFirst, string $namePatronymic = ''): array
	{
		$this->errors = [];
		$this->errors['login']       = Validator::isEmail($login, 'Неверный формат');
		$this->errors['phone']       = Validator::isNotEmpty($phone, 'Неверный формат');
		$this->errors['nameSurname'] = Validator::isNotEmpty($nameSurname, 'Пустое поле');
		$this->errors['nameFirst']   = Validator::isNotEmpty($nameFirst, 'Пустое поле');

		if (!empty(array_filter($this->errors))) {
			return $this->result();
		}

		if (!empty(Connect::$instance->fetch('SELECT Id FROM s_Users WHERE Login=?', [$login])[0])) {
			$this->errors['login'] = 'Пользователь уже существует';
			return $this->result();
		}

		$jwt   = new Jwt();
		$token = $jwt->token_encode(['typ' => 'reg', 'login' => $login]);
		(new Tasks())->add('reg-confirm', [
			'login'          => $login,
			'email'          => $login,
			'phone'          => $phone,
			'nameFirst'      => $nameFirst,
			'nameSurname'    => $nameSurname,
			'namePatronymic' => $namePatronymic,
			'token'          => $token,
		], date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '');
		return $this->result('Для завершения регистрации, загляните в почту');
	}

	/**
	 * Подтверждение регистрации по токену: проверка пароля + создание аккаунта.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function confirmReg(string $token, string $password, string $password2): array
	{
		if ($this->useCookies) {
			Utils::cookies('wepps_token', '');
		}
		$this->errors = [];

		if (empty($token)) {
			$this->errors['password'] = 'Неверный токен';
			return $this->result();
		}

		$jwt     = new Jwt();
		$decoded = $jwt->token_decode($token);

		if ($decoded['status'] !== 200) {
			$this->errors['password'] = $decoded['message'];
			return $this->result();
		}
		if ($decoded['payload']['typ'] !== 'reg') {
			$this->errors['password'] = 'Неверный токен';
			return $this->result();
		}

		if ($password !== $password2) {
			$this->errors['password'] = 'Пароли не совпадают';
		} elseif (strlen($password) < 6) {
			$this->errors['password'] = 'Пароль должен быть не менее 6 символов';
		}

		if (!empty(array_filter($this->errors))) {
			return $this->result();
		}

		$json = Connect::$instance->fetch('SELECT * FROM s_Tasks WHERE Id=?', [$decoded['payload']['tsk']])[0]['BRequest'] ?? '';
		if (empty($json)) {
			$this->errors['password'] = 'Задача не найдена';
			return $this->result();
		}

		$jdata = json_decode($json, true);
		if (!empty(Connect::$instance->fetch('SELECT Id FROM s_Users WHERE Login=?', [$jdata['login']])[0])) {
			$this->errors['password'] = 'Пользователь уже существует';
			return $this->result();
		}

		$row = [
			'Login'          => $jdata['login'],
			'Password'       => password_hash($password, PASSWORD_BCRYPT),
			'Name'           => trim("{$jdata['nameSurname']} {$jdata['nameFirst']} {$jdata['namePatronymic']}"),
			'Email'          => $jdata['email'],
			'UserPermissions'=> 3,
			'Phone'          => $jdata['phone'],
			'CreateDate'     => date('Y-m-d H:i:s'),
			'NameFirst'      => $jdata['nameFirst'],
			'NameSurname'    => $jdata['nameSurname'],
			'NamePatronymic' => $jdata['namePatronymic'],
		];
		$prepare = Connect::$instance->prepare($row);
		Connect::$instance->query('INSERT IGNORE INTO s_Users ' . $prepare['insert'], $prepare['row']);

		(new Tasks())->add('reg-complete', [
			'nameFirst' => $row['NameFirst'],
			'email'     => $row['Email'],
		], $row['CreateDate'], $_SERVER['REMOTE_ADDR'] ?? '');

		$result = $this->result('Регистрация завершена<br/><br/><a href="/profile/" class="w_button">Войти в личный кабинет</a>');
		$result['data']['login'] = $row['Login'];
		return $result;
	}

	/**
	 * Подтверждение смены пароля по токену (восстановление через почту).
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function confirmPassword(string $token, string $password, string $password2): array
	{
		if ($this->useCookies) {
			Utils::cookies('wepps_token', '');
		}
		$this->errors = [];

		if (empty($token)) {
			$this->errors['password'] = 'Неверный токен';
			return $this->result();
		}

		$jwt     = new Jwt();
		$decoded = $jwt->token_decode($token);

		if ($decoded['status'] !== 200) {
			$this->errors['password'] = $decoded['message'];
			return $this->result();
		}
		if ($decoded['payload']['typ'] !== 'pass') {
			$this->errors['password'] = 'Неверный токен';
			return $this->result();
		}

		if (!$this->checkPassword($password, $password2)) {
			return $this->result();
		}

		Connect::$instance->query(
			'UPDATE s_Users SET Password=? WHERE Id=?',
			[password_hash(trim($password), PASSWORD_BCRYPT), $decoded['payload']['id']]
		);

		$user = Connect::$instance->fetch('SELECT * FROM s_Users WHERE Id=?', [$decoded['payload']['id']])[0];
		(new Tasks())->add('password-confirm', [
			'id'        => $decoded['payload']['id'],
			'nameFirst' => $user['NameFirst'],
			'email'     => $user['Email'],
		], date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '');
		return $this->result('Пароль установлен');
	}

	/**
	 * Вход пользователя по логину и паролю.
	 * Если $useCookies=true — устанавливает cookie wepps_token.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function signIn(string $login, string $password): array
	{
		$this->errors = [];
		$sql = 'SELECT * FROM s_Users WHERE Login=? AND IsHidden=0';
		$res = Connect::$instance->fetch($sql, [$login]);

		if (empty($res[0]['Id'])) {
			$this->errors['login'] = 'Неверный логин';
		} elseif (strlen($res[0]['Password']) == 32) {
			if (md5($password) != $res[0]['Password']) {
				$this->errors['password'] = 'Неверный пароль';
			}
		} elseif (!password_verify($password, $res[0]['Password'])) {
			$this->errors['password'] = 'Неверный пароль';
		}

		if (!empty(array_filter($this->errors))) {
			return ['status' => 400, 'message' => 'Bad Request', 'data' => ['errors' => $this->errors]]; // Bad Request
		}

		$lifetime = 3600 * 24 * 180;
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'auth',
			'id'  => $res[0]['Id'],
		], $lifetime);

		if ($this->useCookies) {
			Utils::cookies('wepps_token', $token, $lifetime);
		}

		Connect::$instance->query(
			'UPDATE s_Users SET AuthDate=?,AuthIP=?,Password=? WHERE Id=?',
			[date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '', password_hash($password, PASSWORD_BCRYPT), $res[0]['Id']]
		);

		return ['status' => 200, 'message' => 'OK', 'data' => ['token' => $token, 'user' => $res[0]]]; // OK
	}

	/**
	 * Запрос на сброс пароля: создаёт JWT-токен и задачу отправки письма.
	 * Если $useCookies=true — сбрасывает cookie wepps_token.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function requestPasswordReset(string $login): array
	{
		$this->errors = [];
		$sql = 'SELECT * FROM s_Users WHERE Login=? AND IsHidden=0';
		$res = Connect::$instance->fetch($sql, [$login]);

		if (empty($user = $res[0] ?? null)) {
			$this->errors['login'] = 'Неверный логин';
			return $this->result();
		}

		if ($this->useCookies) {
			Utils::cookies('wepps_token', '');
		}

		$lifetime = 3600 * 24;
		$jwt = new Jwt();
		$token = $jwt->token_encode([
			'typ' => 'pass',
			'id'  => $user['Id'],
		], $lifetime);

		$payload = $jwt->token_decode($token);
		(new Tasks())->add('password', [
			'token'     => $token,
			'nameFirst' => $user['NameFirst'],
			'email'     => $user['Email'],
			'exp'       => $payload['payload']['exp'],
		], date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '');
		return $this->result('Запрос на смену пароля отправлен');
	}

	/**
	 * Добавляет сообщение к заказу пользователя.
	 *
	 * @return array{status: int, message: string, data: array}
	 */
	public function addOrdersMessage(int $userId, int $orderId, string $message): array
	{
		if (empty($orderId) || empty($message)) {
			return ['status' => 400, 'message' => 'Bad Request', 'data' => []]; // Bad Request
		}

		if (empty(Connect::$instance->fetch('SELECT Id FROM Orders WHERE Id=? AND UserId=?', [$orderId, $userId]))) {
			return ['status' => 400, 'message' => 'Bad Request', 'data' => []]; // Bad Request
		}

		$arr = Connect::$instance->prepare([
			'Name'    => 'Msg',
			'OrderId' => $orderId,
			'UserId'  => $userId,
			'EType'   => 'msg',
			'EDate'   => date('Y-m-d H:i:s'),
			'EText'   => trim(strip_tags($message)),
		]);
		Connect::$instance->query("INSERT INTO OrdersEvents {$arr['insert']}", $arr['row']);

		$cartUtils = new CartUtils();
		$order = $cartUtils->getOrder($orderId, $userId);

		return ['status' => 200, 'message' => 'OK', 'data' => ['order' => $order]]; // OK
	}
}
