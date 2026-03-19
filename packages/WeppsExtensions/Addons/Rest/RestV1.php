<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Memcached;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\Messages\Mail\Mail;
use WeppsExtensions\Profile\ProfileActions;

/**
 * REST обработчик для API v1
 * Auth, Profile
 */
class RestV1
{
	/**
	 * Режим двухэтапной авторизации.
	 * false — auth.login сразу возвращает access+refresh токены.
	 * true  — auth.login возвращает confirm_token; токены выдаются только после auth.confirm.
	 */
	protected const CONFIRM_AUTH = false;

	protected Rest $rest;

	/**
	 * GET параметры запроса
	 * @var array
	 */
	protected array $get = [];

	/**
	 * POST параметры запроса
	 * @var array
	 */
	protected array $post = [];

	/**
	 * Парсированные данные из тела JSON запроса
	 * @var array|null
	 */
	protected ?array $data = null;

	public function __construct(Rest $rest)
	{
		$this->rest = $rest;
		$this->get = &$rest->getGet();
		$this->post = &$rest->getPost();
		$this->data = &$rest->getData();
	}

	// -------------------------------------------------------------------------
	// AUTH
	// -------------------------------------------------------------------------

	/**
	 * POST v1/auth.login — аутентификация и выдача JWT токена
	 */
	public function postAuthLogin($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));
		$password = $data['data']['password'] ?? '';

		$actions = new ProfileActions(false);
		$signInResult = $actions->signIn($login, $password);

		if ($signInResult['status'] !== 200) {
			return ['status' => 401, 'message' => 'Invalid credentials', 'data' => null];
		}

		$user = $signInResult['data']['user'];
		$jwt = new Jwt();

		if (self::CONFIRM_AUTH) {
			$code = random_int(100000, 999999);
			$confirmLifetime = 600; // 10 минут
			$confirmToken = $jwt->token_encode(['typ' => 'confirm', 'id' => $user['Id'], 'code' => $code], $confirmLifetime);
			$confirmData = $jwt->token_decode($confirmToken);

			$tasks = new \WeppsCore\Tasks();
			$tasks->add('rest-auth-confirm', [
				'token' => $confirmToken,
				'code'  => $code,
				'email' => $user['Login'],
				'exp'   => $confirmData['payload']['exp'],
			]);

			return ['status' => 200, 'message' => 'Confirmation required', 'data' => [
				'confirm_token' => $confirmToken,
				'confirm_exp'   => $confirmData['payload']['exp'],
			]];
		}

		$accessLifetime = 3600; // 1 час
		$refreshLifetime = 2592000; // 30 дней

		$accessToken = $jwt->token_encode(['typ' => 'auth', 'id' => $user['Id']], $accessLifetime);
		$refreshToken = $jwt->token_encode(['typ' => 'refresh', 'id' => $user['Id']], $refreshLifetime);
		$accessData = $jwt->token_decode($accessToken);
		$refreshData = $jwt->token_decode($refreshToken);

		return ['status' => 200, 'message' => 'Login successful', 'data' => [
			'access_token'  => $accessToken,
			'access_exp'    => $accessData['payload']['exp'],
			'refresh_token' => $refreshToken,
			'refresh_exp'   => $refreshData['payload']['exp'],
		]];
	}

	/**
	 * POST v1/register — инициация регистрации нового пользователя.
	 * Отправляет письмо со ссылкой подтверждения; аккаунт создаётся через register.confirm.
	 */
	public function postRegister($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login          = strtolower(trim($data['data']['login'] ?? ''));
		$phone          = Utils::phone($data['data']['phone'] ?? '')['num'] ?? '';
		$nameSurname    = $data['data']['nameSurname'] ?? '';
		$nameFirst      = $data['data']['nameFirst'] ?? '';
		$namePatronymic = $data['data']['namePatronymic'] ?? '';

		$actions = new ProfileActions(false);
		return $actions->register($login, $phone, $nameSurname, $nameFirst, $namePatronymic);
	}

	/**
	 * POST v1/register.confirm — подтверждение регистрации по токену из письма.
	 * Клиент передаёт token (из ссылки в письме), password и password2.
	 * После успеха аккаунт создан, возвращается пара access+refresh токенов.
	 */
	public function postRegisterConfirm($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$token     = $data['data']['token'] ?? '';
		$password  = $data['data']['password'] ?? '';
		$password2 = $data['data']['password2'] ?? '';

		$actions = new ProfileActions(false);
		$result = $actions->confirmReg($token, $password, $password2);

		if ($result['status'] !== 200) {
			return $result;
		}

		$login = $result['data']['login'] ?? '';

		return $this->postAuthLogin(['data' => ['login' => $login, 'password' => $password]]);
	}

	/**
	 * POST v1/auth.refresh — обновление пары токенов по refresh token
	 */
	public function postAuthRefresh($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$refreshToken = $data['data']['refresh_token'] ?? '';

		$jwt = new Jwt();
		$decoded = $jwt->token_decode($refreshToken);

		if ($decoded['status'] !== 200) {
			return ['status' => 401, 'message' => 'Invalid or expired refresh token', 'data' => null];
		}
		if (($decoded['payload']['typ'] ?? '') !== 'refresh') {
			return ['status' => 401, 'message' => 'Invalid token type', 'data' => null];
		}

		$userId = (int)($decoded['payload']['id'] ?? 0);
		$res = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Id = ? AND IsHidden = 0", [$userId]);
		if (empty($res[0])) {
			return ['status' => 401, 'message' => 'User not found or inactive', 'data' => null];
		}

		$accessLifetime = 3600;
		$refreshLifetime = 2592000;

		$accessToken = $jwt->token_encode(['typ' => 'auth', 'id' => $userId], $accessLifetime);
		$newRefreshToken = $jwt->token_encode(['typ' => 'refresh', 'id' => $userId], $refreshLifetime);
		$accessData = $jwt->token_decode($accessToken);
		$refreshData = $jwt->token_decode($newRefreshToken);

		return ['status' => 200, 'message' => 'Token refreshed', 'data' => [
			'access_token' => $accessToken,
			'access_exp' => $accessData['payload']['exp'],
			'refresh_token' => $newRefreshToken,
			'refresh_exp' => $refreshData['payload']['exp'],
		]];
	}

	/**
	 * POST v1/auth.confirm — подтверждение входа через confirm_token из письма.
	 *
	 * Работает только при CONFIRM_AUTH = true в auth.login.
	 * Клиент получает confirm_token из ответа auth.login и сохраняет его.
	 * Письмо содержит ссылку (?token=...) и 6-значный код для ручного ввода.
	 * Оба варианта передают confirm_token; code — опциональная дополнительная проверка.
	 */
	public function postAuthConfirm($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$token = $data['data']['token'] ?? '';
		$code  = isset($data['data']['code']) ? (int)$data['data']['code'] : null;

		$jwt = new Jwt();
		$decoded = $jwt->token_decode($token);

		if ($decoded['status'] !== 200) {
			return ['status' => 401, 'message' => 'Invalid or expired confirmation token', 'data' => null];
		}
		if (($decoded['payload']['typ'] ?? '') !== 'confirm') {
			return ['status' => 401, 'message' => 'Invalid token type', 'data' => null];
		}
		if ($code !== null && (int)($decoded['payload']['code'] ?? 0) !== $code) {
			return ['status' => 401, 'message' => 'Invalid confirmation code', 'data' => null];
		}

		$userId = (int)($decoded['payload']['id'] ?? 0);
		$res = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Id = ? AND IsHidden = 0", [$userId]);
		if (empty($res[0])) {
			return ['status' => 401, 'message' => 'User not found or inactive', 'data' => null];
		}

		$accessLifetime = 3600;
		$refreshLifetime = 2592000;

		$accessToken  = $jwt->token_encode(['typ' => 'auth',    'id' => $userId], $accessLifetime);
		$refreshToken = $jwt->token_encode(['typ' => 'refresh', 'id' => $userId], $refreshLifetime);
		$accessData   = $jwt->token_decode($accessToken);
		$refreshData  = $jwt->token_decode($refreshToken);

		return ['status' => 200, 'message' => 'Login confirmed', 'data' => [
			'access_token'  => $accessToken,
			'access_exp'    => $accessData['payload']['exp'],
			'refresh_token' => $refreshToken,
			'refresh_exp'   => $refreshData['payload']['exp'],
		]];
	}

	/**
	 * POST v1/auth.password-reset — запрос на восстановление пароля.
	 * Отправляет письмо со ссылкой и токеном для установки нового пароля.
	 */
	public function postAuthPasswordReset($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));

		$actions = new ProfileActions(false);
		return $actions->requestPasswordReset($login);
	}

	// -------------------------------------------------------------------------
	// PROFILE
	// -------------------------------------------------------------------------

	/**
	 * POST v1/auth.logout — завершение сессии
	 * Токены stateless, сервер их не хранит — клиент должен удалить оба токена из локального хранилища.
	 */
	public function postAuthLogout(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		return ['status' => 200, 'message' => 'Logged out successfully', 'data' => null];
	}

	/**
	 * GET v1/profile — профиль текущего пользователя
	 */
	public function getProfile(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();

		return ['status' => 200, 'message' => 'OK', 'data' => [
			'id'              => (int) $user['Id'],
			'login'           => $user['Login'],
			'email'           => $user['Email'] ?? '',
			'name'            => $user['Name'] ?? '',
			'nameSurname'     => $user['NameSurname'] ?? '',
			'nameFirst'       => $user['NameFirst'] ?? '',
			'namePatronymic'  => $user['NamePatronymic'] ?? '',
			'phone'           => $user['Phone'] ?? '',
			'city'            => $user['City'] ?? '',
			'address'         => $user['Address'] ?? '',
		]];
	}

	/**
	 * PUT v1/profile — обновление профиля
	 */
	/**
	 * PUT v1/profile — обновление ФИО и адреса.
	 * Email и телефон изменяются через отдельные эндпоинты с кодом подтверждения.
	 */
	public function putProfile($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();

		if (empty($data['data'])) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		// Проверяем наличие хотя бы одного поля ФИО
		if (!isset($data['data']['nameSurname']) && !isset($data['data']['nameFirst']) && !isset($data['data']['namePatronymic'])) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		$profileActions = new ProfileActions(false);
		$result = $profileActions->changeName(
			$user['Id'],
			$data['data']['nameSurname'] ?? $user['NameSurname'] ?? '',
			$data['data']['nameFirst'] ?? $user['NameFirst'] ?? '',
			$data['data']['namePatronymic'] ?? $user['NamePatronymic'] ?? ''
		);

		return [
			'status'  => $result['status'],
			'message' => $result['status'] === 200 ? 'Profile updated' : $result['message'],
			'data'    => null
		];
	}

	/**
	 * PUT v1/profile.email — 2-шаговая смена e-mail.
	 * Шаг 1 (без code): валидирует email, отправляет код на новый адрес.
	 * Шаг 2 (с code): проверяет код, обновляет Email и Login.
	 */
	public function putProfileEmail($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user  = $this->rest->getUser();
		$email = strtolower(trim($data['data']['email'] ?? ''));
		$code  = trim($data['data']['code'] ?? '');

		$profileActions = new ProfileActions(false);
		return $profileActions->changeEmail($user['Id'], $email, $code);
	}

	/**
	 * PUT v1/profile.phone — 2-шаговая смена телефона.
	 * Шаг 1 (без code): валидирует номер, отправляет код на e-mail пользователя.
	 * Шаг 2 (с code): проверяет код, обновляет Phone.
	 */
	public function putProfilePhone($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user  = $this->rest->getUser();
		$phone = Utils::phone($data['data']['phone'] ?? '')['num'] ?? '';
		$code  = trim($data['data']['code'] ?? '');

		$profileActions = new ProfileActions(false);
		return $profileActions->changePhone($user['Id'], $user['Email'], $phone, $code);
	}

	/**
	 * GET v1/profile.settings — настройки пользователя
	 * Возвращает данные из JSettings с подстановкой значений по умолчанию.
	 */
	public function getProfileSettings(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$saved = json_decode($user['JSettings'] ?? '', true) ?? [];

		$defaults = [
			'theme'                   => 'auto',
			'notificationsOrders'     => true,
			'notificationsPromotions' => false,
		];

		return ['status' => 200, 'message' => 'OK', 'data' => array_merge($defaults, $saved)];
	}

	/**
	 * PUT v1/profile.settings — обновление настроек пользователя
	 * Принимает частичное обновление — только переданные ключи перезаписываются.
	 */
	public function putProfileSettings($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$current = json_decode($user['JSettings'] ?? '', true) ?? [];

		$allowed = ['theme', 'notificationsOrders', 'notificationsPromotions'];
		foreach ($allowed as $key) {
			if (array_key_exists($key, $data['data'] ?? [])) {
				$current[$key] = $data['data'][$key];
			}
		}

		Connect::$instance->query("UPDATE s_Users SET JSettings = ? WHERE Id = ?", [json_encode($current, JSON_UNESCAPED_UNICODE), $user['Id']]);

		return ['status' => 200, 'message' => 'Settings updated', 'data' => null];
	}

	/**
	 * PUT v1/profile.password — 2-шаговая смена пароля с подтверждением по e-mail.
	 * Шаг 1 (без code): валидирует пароль, отправляет код на e-mail.
	 * Шаг 2 (с code): проверяет код, обновляет пароль.
	 */
	public function putProfilePassword($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$passwordNew = $data['data']['password_new'] ?? '';
		$passwordNew2 = $data['data']['password_new2'] ?? '';
		$code = trim($data['data']['code'] ?? '');

		$profileActions = new ProfileActions(false);
		return $profileActions->changePassword($user['Id'], $passwordNew, $passwordNew2, $code);
	}

	/**
	 * DELETE v1/profile — удаление аккаунта (2-step: word confirmation → code confirmation)
	 * После успешного удаления (статус 200) клиент должен удалить обе токены из локального хранилища.
	 */
	public function deleteProfile($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$word = trim($data['data']['word'] ?? '');
		$code = trim($data['data']['code'] ?? '');

		$profileActions = new ProfileActions(false);
		$result = $profileActions->remove($user['Id'], $user['Login'], $user['Email'], $word, $code);

		// При успешном удалении указываем клиенту удалить токены
		if ($result['status'] === 200) {
			$result['message'] = 'Account deleted. Please remove both access_token and refresh_token from local storage.';
		}

		return $result;
	}
}
