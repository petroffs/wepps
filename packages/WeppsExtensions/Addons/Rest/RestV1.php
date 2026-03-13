<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsExtensions\Addons\Jwt\Jwt;

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

		$res = Connect::$instance->fetch("SELECT Id, Login, Password FROM s_Users WHERE Login = ? AND IsHidden = 0", [$login]);

		if (empty($res[0]) || !password_verify($password, $res[0]['Password'] ?? '')) {
			return ['status' => 401, 'message' => 'Invalid credentials', 'data' => null];
		}

		$user = $res[0];
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
	 * POST v1/profile — регистрация нового пользователя
	 */
	public function postProfile($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));
		$password = $data['data']['password'] ?? '';
		$name = $data['data']['name'] ?? '';
		$phone = $data['data']['phone'] ?? '';

		$exists = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Login = ?", [$login]);
		if (!empty($exists[0])) {
			return ['status' => 409, 'message' => 'User with this email already exists', 'data' => null];
		}

		$hash = password_hash($password, PASSWORD_DEFAULT);
		Connect::$instance->query(
			"INSERT INTO s_Users (Login, Password, Name, Phone, IsHidden) VALUES (?, ?, ?, ?, 0)",
			[$login, $hash, $name, $phone]
		);

		return ['status' => 200, 'message' => 'Registration successful', 'data' => null];
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
			'id' => $user['Id'],
			'login' => $user['Login'],
			'name' => $user['Name'] ?? '',
			'phone' => $user['Phone'] ?? '',
		]];
	}

	/**
	 * PUT v1/profile — обновление профиля
	 */
	public function putProfile($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$name = $data['data']['name'] ?? null;
		$phone = $data['data']['phone'] ?? null;
		$email = $data['data']['email'] ?? null;

		$set = [];
		$params = [];

		if ($name !== null) { $set[] = 'Name = ?'; $params[] = $name; }
		if ($phone !== null) { $set[] = 'Phone = ?'; $params[] = $phone; }
		if ($email !== null) { $set[] = 'Login = ?'; $params[] = strtolower($email); }

		if (empty($set)) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		$params[] = $user['Id'];
		Connect::$instance->query("UPDATE s_Users SET " . implode(', ', $set) . " WHERE Id = ?", $params);

		return ['status' => 200, 'message' => 'Profile updated', 'data' => null];
	}

	/**
	 * PUT v1/profile.password — смена пароля
	 */
	public function putProfilePassword($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$passwordOld = $data['data']['password_old'] ?? '';
		$passwordNew = $data['data']['password_new'] ?? '';

		$res = Connect::$instance->fetch("SELECT Password FROM s_Users WHERE Id = ?", [$user['Id']]);

		if (empty($res[0]) || !password_verify($passwordOld, $res[0]['Password'])) {
			return ['status' => 401, 'message' => 'Current password is incorrect', 'data' => null];
		}

		$hash = password_hash($passwordNew, PASSWORD_DEFAULT);
		Connect::$instance->query("UPDATE s_Users SET Password = ? WHERE Id = ?", [$hash, $user['Id']]);

		return ['status' => 200, 'message' => 'Password changed', 'data' => null];
	}

	/**
	 * DELETE v1/profile — удаление аккаунта
	 */
	public function deleteProfile(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		Connect::$instance->query("UPDATE s_Users SET IsHidden = 1 WHERE Id = ?", [$user['Id']]);

		return ['status' => 200, 'message' => 'Account deleted', 'data' => null];
	}
}
