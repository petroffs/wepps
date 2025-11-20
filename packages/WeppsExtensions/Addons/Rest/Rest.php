<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Users;
use WeppsCore\Utils;
use WeppsCore\Validator;
use WeppsExtensions\Addons\Jwt\Jwt;


/**
 * Класс для обработки REST API запросов
 *
 * Предоставляет маршрутизацию, валидацию, аутентификацию и структурированные ответы для REST API.
 * Поддерживает различные версии API, типы запросов (GET, POST, PUT, DELETE, CLI) и кастомные ответы.
 *
 * Основные возможности:
 * - Автоматическая маршрутизация по версии, методу и типу запроса
 * - Валидация входных данных (JSON body и GET параметры)
 * - Аутентификация через Bearer токены
 * - Гибкое логирование запросов и ответов
 * - Поддержка кастомных ответов без стандартной структуры
 *
 * @package WeppsExtensions\Addons\Rest
 * @author Wepps Platform Team
 * @version 1.0
 */
class Rest
{
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
	 * Массив настроек API (версия, метод, тип, параметры)
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * HTTP заголовки запроса
	 * @var array|null
	 */
	protected ?array $headers = null;

	/**
	 * Тело HTTP запроса (raw JSON)
	 * @var string
	 */
	public string $request = '';

	/**
	 * Тело HTTP ответа (JSON)
	 * @var string
	 */
	public string $response = '';

	/**
	 * HTTP статус код ответа (200, 404, 500 и т.д.)
	 * @var int
	 */
	public int $status = 200;

	/**
	 * Флаг логирования запроса (1 - логировать, 0 - не логировать)
	 * @var int
	 */
	public int $log = 1;

	/**
	 * Версия API (v1, v2, cli и т.д.)
	 * @var string
	 */
	protected string $version = 'v1';

	/**
	 * Метод API (getList, test, createUser и т.д.)
	 * @var string
	 */
	protected string $method = '';

	/**
	 * Параметры запроса в виде массива ключ-значение
	 * Пример: ['id' => '123', 'filter' => 'active']
	 * @var array
	 */
	protected array $params = [];

	/**
	 * Тип HTTP запроса (GET, POST, DELETE, PUT, CLI)
	 * @var string
	 */
	protected string $type = 'GET';

	/**
	 * Флаг кастомного ответа (без стандартной структуры status/message/data)
	 * @var bool
	 */
	protected bool $customResponse = false;

	/**
	 * Данные аутентифицированного пользователя
	 * @var array|null
	 */
	protected ?array $user = null;

	/**
	 * Конфигурация API методов
	 * @var array
	 */
	protected array $config;

	/**
	 * Получить GET параметры
	 * @return array
	 */
	public function &getGet(): array
	{
		return $this->get;
	}

	/**
	 * Получить POST параметры
	 * @return array
	 */
	public function &getPost(): array
	{
		return $this->post;
	}

	/**
	 * Получить данные из тела запроса
	 * @return array|null
	 */
	public function &getData(): ?array
	{
		return $this->data;
	}

	/**
	 * Получить HTTP заголовки
	 * @return array|null
	 */
	/**
	 * Получить заголовки запроса
	 *
	 * @return array|null Заголовки HTTP запроса или null для CLI
	 */
	public function getHeaders(): ?array
	{
		return $this->headers;
	}

	/**
	 * Получить настройки API
	 *
	 * @return array Массив настроек запроса (версия, метод, тип, параметры)
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}

	/**
	 * Получить параметры запроса
	 *
	 * @return array Параметры из URL пути запроса
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * Получить данные пользователя
	 *
	 * @return array|null Данные аутентифицированного пользователя или null
	 */
	public function getUser(): ?array
	{
		return $this->user;
	}

	/**
	 * Обработать запрос вручную (для тестирования)
	 *
	 * Выполняет маршрутизацию и обработку запроса без отправки HTTP ответа.
	 * Полезно для тестирования или внутреннего использования.
	 *
	 * @return array Стандартизированный ответ API с status, message, data
	 */
	public function process(): array
	{
		$handler = $this->routeRequest();

		if (empty($handler)) {
			return [
				'status' => 404,
				'message' => 'Endpoint not found',
				'data' => null,
			];
		} else {
			$handlerObject = $handler['data'];
			$config = $handler['config'];
			$this->customResponse = $config['custom_response'] ?? false;
			return $this->executeHandler($handlerObject, $config);
		}
	}

	/**
	 * Конструктор класса Rest
	 *
	 * Инициализирует REST API обработчик с настройками, маршрутизирует запрос
	 * и выполняет соответствующий обработчик, если autoProcess включен.
	 *
	 * @param array $settings Дополнительные настройки для переопределения
	 * @param bool $autoProcess Автоматически обработать запрос (по умолчанию true)
	 * @param bool $forceWebMode Принудительно использовать веб-режим (по умолчанию false)
	 */
	public function __construct($settings = [], $autoProcess = true, $forceWebMode = false)
	{
		$this->config = RestConfig::getConfig();
		$this->settings = $this->setSettings($settings, $forceWebMode);

		if ($autoProcess) {
			// Маршрутизация по версии, методу и типу запроса
			$handler = $this->routeRequest();

			if (empty($handler)) {
				$this->status = 404;
				$this->sendResponse([
					'status' => 404,
					'message' => 'Endpoint not found',
					'data' => null,
				]);
			} else {
				$handlerObject = $handler['data'];
				$config = $handler['config'];
				$this->customResponse = $config['custom_response'] ?? false;
				$result = $this->executeHandler($handlerObject, $config);
				$this->sendResponse($result);
			}
		}
		return;
	}	/**
	 * Выполнить обработчик запроса
	 *
	 * Выполняет обработчик с валидацией данных, аутентификацией и вызовом метода.
	 * Поддерживает кастомные ответы и логирование по конфигурации.
	 *
	 * @param object $handler Объект обработчика с методами API
	 * @param array|null $config Конфигурация метода (валидация, аутентификация, логирование)
	 * @return array Стандартизированный ответ API с status, message, data
	 * @throws \Exception При ошибках валидации или выполнения
	 */
	private function executeHandler($handler, $config = null): array
	{
		try {
			if (!$config) {
				$config = $this->getConfig($this->version, $this->type, $this->method);
			}
			if (!$config) {
				return ['status' => 404, 'message' => 'Method not found', 'data' => null];
			}

			// Настройка логирования для метода
			$this->log = $config['log'] ?? $this->log;

			// Проверка аутентификации, если требуется
			if (!empty($config['auth_required'])) {
				$this->authenticateBearerToken();
			}

			// Валидация входных данных, если задана
			if (isset($config['validation']) && $this->data) {
				$this->validateData($this->data['data'] ?? [], $config['validation']);
			}

			// Валидация GET-параметров, если задана
			if (isset($config['query_validation'])) {
				$queryData = [];
				foreach ($config['query_validation'] as $key => $rule) {
					$queryData[$key] = $this->get[$key] ?? null;
				}
				$this->validateData($queryData, $config['query_validation']);
			}

			if (!method_exists($handler, $config['method'])) {
				return ['status' => 404, 'message' => 'Method not found', 'data' => null];
			}

			if ($this->customResponse) {
				return $handler->{$config['method']}($this->data);
			}

			return $handler->{$config['method']}($this->data);
		} catch (\Exception $e) {
			$status = $e->getCode() ?: 400;
			$this->status = $status;
			return ['status' => $status, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	/**
	 * Проверка Bearer токена аутентификации
	 *
	 * Извлекает и валидирует JWT токен из заголовка Authorization.
	 * Проверяет тип токена, наличие ID пользователя и его активность в БД.
	 *
	 * @throws \Exception Если токен отсутствует, некорректный или пользователь не найден
	 */
	private function authenticateBearerToken(): void
	{
		$headers = $this->headers ?? [];
		$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

		if (empty($authHeader)) {
			throw new \Exception('Authorization header is required', 401);
		}

		$jwt = new Jwt();
		$bearer = $jwt->bearer();

		if ($bearer['status'] != 200) {
			throw new \Exception('Authentication failed: ' . $bearer['message'], $bearer['status']);
		}

		if ($bearer['payload']['typ'] != 'auth') {
			throw new \Exception('Invalid token payload: auth expected', 401);
		} elseif (empty($bearer['payload']['id'])) {
			throw new \Exception('Invalid token payload: id expected', 401);
		}

		if ($this->version == 'wepps') {
			$sql = "SELECT * from s_Users where Id=? and DisplayOff=0 and ShowAdmin=1";
			$res = Connect::$instance->fetch($sql, [$bearer['payload']['id']]);
			if (empty($res[0]['Id'])) {
				throw new \Exception('User not found or inactive', 401);
			}

			// Сохраняем данные пользователя при успешной аутентификации
			$this->user = $res[0];
			#Utils::debug($this->user, 31);
		}
	}

	/**
	 * Маршрутизация API запроса
	 *
	 * Определяет класс обработчика на основе версии, типа и метода запроса,
	 * создает экземпляр обработчика с соответствующими параметрами.
	 *
	 * @return array|null Массив с ['data' => объект обработчика, 'config' => конфиг, 'note' => описание] или null если не найдено
	 */
	private function routeRequest(): ?array
	{
		$config = $this->getConfig($this->version, $this->type, $this->method);
		if (!$config) {
			return null;
		}

		$class = $config['class'];
		if ($class === RestCli::class) {
			$instance = new $class($this->settings);
		} elseif ($class === RestAd::class) {
			$instance = new $class($this);
		} else {
			$instance = new $class($this->get, $this->post, $this->data, $this->headers);
		}
		return [
			'data' => $instance,
			'config' => $config,
			'note' => $config['note'],
		];
	}

	/**
	 * Валидация входных данных на основе правил
	 *
	 * Поддерживает объекты и массивы объектов. Проверяет обязательность полей,
	 * типы данных и отсутствие лишних полей.
	 *
	 * @param array $data Входные данные для валидации
	 * @param array $rules Правила валидации с ключами 'required' и 'type'
	 * @throws \Exception Если валидация не пройдена (обязательное поле отсутствует, неверный тип, лишнее поле)
	 */
	private function validateData(array $data, array $rules): void
	{
		// Если data - массив объектов, валидируем каждый элемент
		if (is_array($data) && isset($data[0]) && is_array($data[0])) {
			foreach ($data as $item) {
				$this->validateData($item, $rules);
			}
			return;
		}

		// Валидация как объекта
		foreach ($rules as $key => $rule) {
			$value = $data[$key] ?? null;
			if ($rule['required'] && $value === null) {
				throw new \Exception("Field '$key' is required");
			}
			if ($value !== null) {
				if (!$this->validateType($value, $rule['type'])) {
					throw new \Exception("Field '$key' must be {$rule['type']}");
				}
			}
		}

		// Проверка на лишние поля
		$allowedKeys = array_keys($rules);
		foreach ($data as $key => $value) {
			if (!in_array($key, $allowedKeys)) {
				throw new \Exception("Unexpected field '$key'");
			}
		}
	}

	/**
	 * Валидация значения по типу
	 *
	 * Использует класс Validator для проверки значения на соответствие типу.
	 * Поддерживает: int, int2, float, float2, string, date, email, phone, guid, barcode.
	 *
	 * @param mixed $value Значение для проверки
	 * @param string $type Тип для проверки (int, string, email, etc.)
	 * @return bool true если значение соответствует типу, false иначе
	 */
	private function validateType($value, string $type): bool
	{
		switch ($type) {
			case 'int':
				$errorMessage = 'must be an integer';
				return Validator::isInt($value, $errorMessage) === '';
			case 'int2':
				$errorMessage = 'must be an integer or numeric string';
				return Validator::isInt2($value, $errorMessage) === '';
			case 'float':
				$errorMessage = 'must be a float or integer';
				return Validator::isFloat($value, $errorMessage) === '';
			case 'float2':
				$errorMessage = 'must be a number or numeric string';
				return Validator::isFloat2($value, $errorMessage) === '';
			case 'string':
				$errorMessage = 'must be a string';
				return Validator::isString($value, $errorMessage) === '';
			case 'date':
				$errorMessage = 'must be a valid date';
				return Validator::isDate($value, $errorMessage) === '';
			case 'email':
				$errorMessage = 'must be a valid email address';
				return Validator::isEmail($value, $errorMessage) === '';
			case 'phone':
				$errorMessage = 'must be a valid phone number (10 digits)';
				return Validator::isPhone($value, $errorMessage) === '';
			case 'guid':
				$errorMessage = 'must be a valid GUID';
				return Validator::isGuid($value, $errorMessage) === '';
			case 'barcode':
				$errorMessage = 'must be a valid EAN13 barcode';
				return Validator::isBarcode($value, $errorMessage) === '';
			default:
				return false;
		}
	}

	/**
	 * Валидация JSON строки
	 *
	 * Удаляет BOM, проверяет на дублирующиеся ключи, декодирует JSON
	 * и обрабатывает возможные ошибки декодирования.
	 *
	 * @param string $string JSON строка для валидации
	 * @return array Массив с 'status', 'message' и опционально 'data' при успехе
	 */
	protected function validateJson(string $string): array
	{
		// Удаление BOM
		if (0 === strpos(bin2hex($string), 'efbbbf')) {
			$string = substr($string, 3);
		}

		// Проверка на дублирующиеся ключи
		$keys = [];
		preg_match_all('/"([^"]+)"\s*:/', $string, $matches);
		foreach ($matches[1] as $key) {
			if (in_array($key, $keys)) {
				return ['status' => 400, 'message' => 'Duplicate key found: ' . $key];
			}
			$keys[] = $key;
		}

		$result = json_decode($string, true);

		// switch and check possible JSON errors
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$error = ''; // JSON is valid // No error has occurred
				break;
			case JSON_ERROR_DEPTH:
				$error = 'The maximum stack depth has been exceeded.';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Invalid or malformed JSON.';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Control character error, possibly incorrectly encoded.';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON.';
				break;
			// PHP >= 5.3.3
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_RECURSION:
				$error = 'One or more recursive references in the value to be encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_INF_OR_NAN:
				$error = 'One or more NAN or INF values in the value to be encoded.';
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE:
				$error = 'A value of a type that cannot be encoded was given.';
				break;
			default:
				$error = 'Unknown JSON error occured.';
				break;
		}

		if ($error !== '') {
			return ['status' => 400, 'message' => $error];
		}
		// everything is OK
		return ['status' => 200, 'message' => 'OK', 'data' => $result];
	}

	/**
	 * Получить конфигурацию для метода API
	 *
	 * Извлекает конфигурацию обработчика из массива config по версии, типу и методу.
	 *
	 * @param string $version Версия API (например, 'v1', 'wepps')
	 * @param string $type Тип запроса ('get', 'post', 'put', 'delete')
	 * @param string $method Название метода API
	 * @return array|null Конфигурация метода или null если не найдена
	 */
	private function getConfig(string $version, string $type, string $method): ?array
	{
		return $this->config[$version][$type][$method] ?? null;
	}

	/**
	 * Получить структуру обработчиков API
	 *
	 * Создает экземпляры классов-обработчиков для всех настроенных методов API.
	 * Структура позволяет легко добавлять новые версии, методы и типы запросов.
	 *
	 * @return array Многомерный массив с экземплярами обработчиков [$version][$type][$method] => instance
	 */
	private function getHandlers(): array
	{
		$handlers = [];
		foreach ($this->config as $version => $types) {
			$handlers[$version] = [];
			foreach ($types as $type => $methods) {
				$handlers[$version][$type] = [];
				foreach ($methods as $methodName => $config) {
					$class = $config['class'];
					$handlers[$version][$type][$methodName] = ($class === RestCli::class) ? new $class($this->settings) : new $class();
				}
			}
		}
		return $handlers;
	}


	/**
	 * Настройка параметров запроса
	 *
	 * Определяет режим работы (CLI или веб), парсит входные данные,
	 * валидирует JSON тело запроса и собирает настройки.
	 *
	 * @param array $settings Дополнительные настройки для CLI режима
	 * @param bool $forceWebMode Принудительно использовать веб-режим в CLI
	 * @return array Собранные настройки запроса
	 */
	protected function setSettings($settings = [], $forceWebMode = false)
	{
		if (php_sapi_name() === 'cli' && !$forceWebMode) {
			$this->headers = null;
			$this->parseCliRequest($settings);
			return $this->buildSettings();
		}
		$this->get = $_GET;
		$this->post = $_POST;
		$this->root = Connect::$projectDev['root'];
		$this->url = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . $_SERVER['REQUEST_URI'];
		$this->headers = apache_request_headers();
		$this->request = file_get_contents('php://input');
		$this->type = strtolower($_SERVER['REQUEST_METHOD']);

		// Парсинг URL параметров: ?params=v1/method/param/value
		$params = $this->get['params'] ?? '';
		$this->parseRequest($params);

		if (!empty($this->request)) {
			$validate = $this->validateJson($this->request);
			if ($validate['status'] != 200) {
				$this->status = $validate['status'];
				$this->sendResponse(['message' => $validate['message']]);
			}

			$this->data = &$validate['data'];
		}
		return $this->buildSettings();
	}

	/**
	 * Парсинг API запроса из URL
	 *
	 * Разбирает строку параметров URL для извлечения версии API и метода.
	 * Формат: /api/v1/methodName/param/value или params=v1/methodName
	 *
	 * @param string $params Строка параметров из URL
	 */
	private function parseRequest(string $params): void
	{
		$parts = explode("/", trim($params, "/"));
		// Пропустить 'rest' если есть
		if ($parts[0] === 'rest') {
			array_shift($parts);
		}

		// Извлечение версии API (v1, v2, и т.д.)
		$this->version = (!empty($parts[0])) ? $parts[0] : 'v1';

		// Извлечение метода API
		$this->method = (!empty($parts[1])) ? $parts[1] : '';
	}

	/**
	 * Парсинг CLI запроса
	 *
	 * Устанавливает параметры для запросов из командной строки.
	 *
	 * @param array $settings Параметры CLI с командой и аргументами
	 */
	private function parseCliRequest(array $settings): void
	{
		$this->version = 'cli';
		$this->method = @$settings['cli'][1] ?? '';
		$this->type = 'cli';
		$this->params = [];
	}

	/**
	 * Построение массива настроек
	 *
	 * Собирает все распарсенные параметры запроса в единый массив настроек.
	 *
	 * @return array Массив с версией, методом, типом и параметрами запроса
	 */
	protected function buildSettings(): array
	{
		return [
			'version' => $this->version,
			'method' => $this->method,
			'type' => $this->type,
			'params' => $this->params,
			'param' => $this->params[0] ?? '',
			'paramValue' => $this->params[1] ?? ''
		];
	}

	/**
	 * Отправить структурированный ответ клиенту
	 *
	 * Форматирует данные в JSON, устанавливает HTTP статус, заголовки,
	 * нормализует данные, логирует запрос и выводит ответ.
	 * Поддерживает кастомные ответы без обертки.
	 *
	 * @param array $output Выходные данные или структурированный ответ [status, message, data]
	 * @param bool $print Выводить ли ответ клиенту (true - выводит и завершает скрипт)
	 * @return string JSON ответ
	 */
	protected function sendResponse(array $output, bool $print = true)
	{
		#Utils::debug('sendResponse customResponse: ' . ($this->customResponse ? 'true' : 'false'), 31);

		// Если кастомный ответ, отправляем данные напрямую
		if ($this->customResponse) {
			$this->response = $this->getJson($this->normalizeData($output, false));
			if ($print) {
				http_response_code($this->status);
				header('X-API-Status: ' . $this->status);
				header('Content-Type: application/json; charset=utf-8');
				echo $this->response;
				exit();
			}
			return $this->response;
		}

		// Если это массив со структурированными данными
		if (is_array($output) && isset($output['status'])) {
			$this->status = $output['status'];
			$responseData = [
				'status' => $output['status'],
				'message' => $output['message'] ?? '',
				'data' => $this->normalizeData($output['data'] ?? null),
			];

		} else {
			// Если это просто данные, оборачиваем их
			$responseData = [
				'status' => $this->status ?? 200,
				'message' => is_array($output) && isset($output['message']) ? $output['message'] : '',
				'data' => $this->normalizeData($output),
			];
		}

		http_response_code($this->status);
		header('X-API-Status: ' . $this->status);
		$this->response = $this->getJson($responseData);

		// Логирование запроса
		$this->logRequest($responseData);

		if ($print == true) {
			header('Content-Type: application/json; charset=utf-8');
			echo $this->response;
			exit();
		}
		return $this->response;
	}

	/**
	 * Логирование API запроса и ответа
	 * Записывает в таблицы s_Tasks (для задач) или s_Logs (для логирования)
	 * 
	 * @param array $responseData Данные ответа [status, message, data]
	 * @param string $logType Тип логирования: 'task' или 'log'
	 * @return bool Результат логирования
	 */
	/**
	 * Логирование API запроса
	 *
	 * Сохраняет информацию о запросе в базу данных, если логирование включено.
	 * Собирает данные запроса, ответа, заголовки и статус.
	 *
	 * @param array $responseData Данные ответа для логирования
	 * @param string $logType Тип лога ('log' для s_Logs или 'task' для s_Tasks)
	 * @return bool Успешность сохранения лога
	 */
	protected function logRequest($responseData, $logType = 'log'): bool
	{
		if ($this->log == 0) {
			return false;
		}

		$headers = $this->formatHeaders($this->headers ?? []);
		$responseHeaders = $this->formatHeaders(apache_response_headers() ?? []);

		$logData = [
			'Name' => $this->method,
			'Url' => $this->url ?? '',
			'TRequest' => $this->type,
			'LDate' => date("Y-m-d H:i:s"),
			'BRequest' => $this->request,
			'BResponse' => $this->getJson($responseData),
			'HRequest' => $headers,
			'HResponse' => $responseHeaders,
			'SResponse' => $this->status,
			'IP' => (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'localhost',
		];

		if ($logType === 'task') {
			return $this->saveTaskLog($logData);
		} else {
			return $this->saveLog($logData);
		}
	}

	/**
	 * Сохранить задачу в таблицу s_Tasks
	 *
	 * Сохраняет данные логирования в таблицу задач с использованием подготовленных запросов.
	 *
	 * @param array $logData Данные логирования для сохранения
	 * @return bool true при успешном сохранении, false при ошибке
	 */
	protected function saveTaskLog($logData): bool
	{
		try {
			$prepare = Connect::$instance->prepare($logData);
			$sql = "INSERT IGNORE INTO s_Tasks {$prepare['insert']}";
			Connect::$instance->query($sql, $prepare['row']);
			return true;
		} catch (\Exception $e) {
			Utils::debug("Error saving task log: " . $e->getMessage(), 31);
			return false;
		}
	}

	/**
	 * Сохранить логирование в таблицу s_Logs
	 * 
	 * @param array $logData Данные логирования
	 * @return bool Результат сохранения
	 */
	protected function saveLog($logData): bool
	{
		try {
			$prepare = Connect::$instance->prepare($logData);
			$sql = "INSERT IGNORE INTO s_Logs {$prepare['insert']}";
			Connect::$instance->query($sql, $prepare['row']);
			return true;
		} catch (\Exception $e) {
			Utils::debug("Error saving log: " . $e->getMessage(), 31);
			return false;
		}
	}

	/**
	 * Нормализация данных для консистентной сериализации в JSON
	 *
	 * Обеспечивает, что 'data' всегда массив в JSON, для кастомных ответов сохраняет структуру.
	 * Преобразует скаляры и null в массивы, ассоциативные массивы оборачивает при необходимости.
	 *
	 * @param mixed $data Данные для нормализации
	 * @param bool $forDataField Флаг, что это для поля 'data' (требует оборачивания ассоциативных массивов)
	 * @return array Нормализованные данные
	 */
	private function normalizeData($data, bool $forDataField = false): array
	{
		if (is_array($data)) {
			// Для поля 'data' оборачиваем ассоциативные массивы в массив
			if ($forDataField && !empty($data) && !is_numeric(key($data))) {
				return [$data];
			}
			// Для кастомных ответов или индексированных массивов возвращаем как есть
			return $data;
		}
		// Если не массив, оборачиваем в массив
		return [$data];
	}
	/**
	 * Форматировать заголовки в строку
	 * 
	 * @param array $headers Массив заголовков
	 * @return string Отформатированная строка заголовков
	 */
	private function formatHeaders($headers): string
	{
		if (empty($headers)) {
			return '';
		}

		$formatted = '';
		foreach ($headers as $key => $value) {
			$formatted .= "{$key} => {$value}\n";
		}
		return $formatted;
	}
	/**
	 * Кодировать массив в JSON строку
	 *
	 * @param array $array Данные для кодирования
	 * @return string JSON строка с поддержкой Unicode
	 */
	protected function getJson($array = []): string
	{
		return (string) json_encode($array, JSON_UNESCAPED_UNICODE);
	}
}

if (!function_exists('apache_request_headers')) {
	function apache_request_headers()
	{
		$arh = [];
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if (preg_match($rx_http, $key)) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = [];
				$rx_matches = explode('_', $arh_key);
				if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
					foreach ($rx_matches as $ak_key => $ak_val)
						$rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return ($arh);
	}
}

if (!function_exists('apache_response_headers')) {
	function apache_response_headers()
	{
		return null;
	}
}