<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsCore\Validator;

/**
 * REST обработчик для работы со списками админки
 */
class RestAd
{
	private Rest $rest;

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

	/**
	 * Конструктор класса RestAd
	 * 
	 * @param Rest $rest Экземпляр Rest с данными и методами
	 */
	public function __construct(Rest $rest)
	{
		$this->rest = $rest;
		$this->get = &$rest->getGet();
		$this->post = &$rest->getPost();
		$this->data = &$rest->getData();
	}

	/**
	 * Аутентификация пользователя через GET-параметры и генерация JWT токена
	 * 
	 * @return array Результат аутентификации с токеном или сообщением об ошибке
	 */
	public function getToken()
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = $this->get['login'] ?? '';
		$password = $this->get['password'] ?? '';

		if (empty($login) || empty($password)) {
			return ['status' => 400, 'message' => 'Login and password are required', 'data' => null];
		}

		// Если логин является email, приводим к нижнему регистру
		$errorMessage = 'Invalid email format';
		$error = Validator::isEmail($login, $errorMessage);

		if (!empty($error)) {
			return ['status' => 401, 'message' => $errorMessage, 'data' => null];
		}

		$login = strtolower($login);

		// Проверка в таблице s_Users
		$res = Connect::$instance->fetch("SELECT Id, Login, Password FROM s_Users WHERE Login = ?", [$login]);

		if (empty($res) || empty($res[0]) || !password_verify($password, $res[0]['Password'] ?? '')) {
			return ['status' => 401, 'message' => 'Invalid credentials', 'data' => null];
		}

		$user = $res[0];

		$jwt = new Jwt();
		$lifetime = 86200;
		$token = $jwt->token_encode([
			'typ' => 'auth',
			'id' => $user['Id']
		], $lifetime);
		$tokenData = $jwt->token_decode($token);

		return ['status' => 200, 'message' => 'Login successful', 'data' => ['token' => $token, 'exp' => $tokenData['payload']['exp']]];
	}

	/**
	 * Получить список с поиском и пагинацией
	 * @return array
	 */
	public function getListItems(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$text = $this->get['search'] ?? '';
		$page = (int) ($this->get['page'] ?? 1);

		if ($page < 1) {
			$page = 1;
		}

		// Получение информации о поле из конфигурации
		$list = $this->get['list'] ?? '';
		$field = $this->get['field'] ?? '';

		$sql = "SELECT * FROM s_ConfigFields WHERE TableName = ? AND Id = ?";
		$res = Connect::$instance->fetch($sql, [$list, $field]);

		if (empty($res)) {
			return [
				'status' => 404,
				'message' => 'Field not found',
				'data' => null
			];
		}

		$ex = explode('::', $res[0]['Type']);
		$list = $ex[1] ?? '';
		$field = $ex[2] ?? '';
		$condition = $ex[3] ?? '';

		// Добавление условия поиска
		if (mb_strlen($text) > 0) {
			$condition .= " AND t.{$field} LIKE '%{$text}%'";
		}

		$limit = 10;
		$offset = ($page - 1) * $limit;
		$sql = "SELECT t.Id id, CONCAT(t.{$field}, ' (', t.Id, ')') text 
		        FROM {$list} t 
		        WHERE {$condition} 
		        ORDER BY t.{$field} 
		        LIMIT {$offset}, {$limit}";

		$res = Connect::$instance->fetch($sql);
		$pagination = !empty($res);

		return [
				'results' => $res,
				'pagination' => [
					'more' => $pagination
				]
			];
	}

	/**
	 * Тестовый метод GET запроса
	 * 
	 * @return void
	 */
	public function getTest(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		return [
			'status' => 200,
			'message' => 'GET request processed',
			'data' => [
				[
					'id' => 1,
					'title' => 'test 1',
					'test' => 'test get'
				]
			]
		];
	}

	/**
	 * Тестовый метод POST/PUT запроса
	 * 
	 * @param array|null $data Входные данные
	 * @return array
	 */
	public function setTest($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		#Utils::debug($this->d, 31);
		return [
			'status' => 200,
			'message' => 'POST request processed',
			'data' => [
				[
					'id' => 1,
					'title' => 'test 1',
					'test' => 'test set'
				],
				[
					'id' => 2,
					'title' => 'test 2',
					'test' => 'test set'
				],
			]
		];
	}

	/**
	 * Тестовый метод DELETE запроса
	 * 
	 * @return void
	 */
	public function removeTest(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		return [
			'status' => 200,
			'message' => 'DELETE request processed',
			'data' => [
				'field' => $this->rest->getParams()['param'] ?? $this->rest->getSettings()['param'] ?? '',
				'value' => $this->rest->getParams()['paramValue'] ?? $this->rest->getSettings()['paramValue'] ?? '',
				'removed' => 'ok',
			]
		];
	}

	/**
	 * Тестовый метод CLI запроса
	 * 
	 * @return void
	 */
	public function cliTest(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		return [
			'status' => 200,
			'message' => 'CLI test executed',
			'data' => [
				'message' => 'ok'
			]
		];
	}
}