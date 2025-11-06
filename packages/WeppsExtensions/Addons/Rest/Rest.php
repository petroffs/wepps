<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Utils;


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
	 * Флаг родительского класса (1 - продолжить, 0 - остановить выполнение)
	 * @var int
	 */
	public int $parent = 1;

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
	 * Конфигурация API методов
	 * @var array
	 */
	protected array $config;

	public function __construct($settings = [])
	{
		$this->config = RestConfig::getConfig();
		$this->settings = $this->getSettings($settings);
		if ($this->parent == 0) {
			return;
		}

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
			$result = $this->executeHandler($handlerObject);
			$this->sendResponse($result);
		}
		return;
	}	/**
		 * Выполнить обработчик запроса
		 * 
		 * @param object $handler Объект обработчика
		 * @return array Результат выполнения метода
		 */
	private function executeHandler($handler): array
	{
		try {
			$config = $this->getConfig($this->version, $this->type, $this->method);
			if ($config) {
				// Валидация входных данных, если задана
				if (isset($config['validation']) && $this->data) {
					$this->validateData($this->data['data'] ?? [], $config['validation']);
				}
				if (method_exists($handler, $config['method'])) {
					return $handler->{$config['method']}($this->data);
				}
			}
			return ['status' => 404, 'message' => 'Method not found', 'data' => null];
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => 'Validation error: ' . $e->getMessage(), 'data' => null];
		}
	}

	/**
	 * Маршрутизация API запроса
	 * 
	 * @return array|null Массив с [data, message] или null если не найдено
	 */
	private function routeRequest(): ?array
	{
		$config = $this->getConfig($this->version, $this->type, $this->method);
		if ($config) {
			$class = $config['class'];
			$instance = ($class === RestCli::class) ? new $class($this->settings) : new $class();
			return [
				'data' => $instance,
				'note' => $config['note'],
			];
		}

		return null;
	}

	/**
	 * Валидация входных данных на основе правил
	 * Поддерживает объекты и массивы объектов
	 * 
	 * @param array $data Входные данные
	 * @param array $rules Правила валидации
	 * @throws \Exception Если валидация не пройдена
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
				switch ($rule['type']) {
					case 'int':
						if (!is_int($value)) {
							throw new \Exception("Field '$key' must be integer");
						}
						break;
					case 'string':
						if (!is_string($value)) {
							throw new \Exception("Field '$key' must be string");
						}
						break;
					case 'date':
						if (!strtotime($value)) {
							throw new \Exception("Field '$key' must be valid date");
						}
						break;
					case 'email':
						if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
							throw new \Exception("Field '$key' must be valid email");
						}
						break;
					case 'phone':
						$cleaned = preg_replace('/\D/', '', $value);
						if (strlen($cleaned) !== 10) {
							throw new \Exception("Field '$key' must be 10 digits after cleaning");
						}
						break;
					case 'guid':
						if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
							throw new \Exception("Field '$key' must be valid GUID");
						}
						break;
					case 'barcode':
						if (!$this->isValidEAN13($value)) {
							throw new \Exception("Field '$key' must be valid EAN13 barcode");
						}
						break;
					// Добавить другие типы по необходимости
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
	 * Проверка валидности EAN13 штрих-кода
	 * 
	 * @param string $ean13 Код для проверки
	 * @return bool Результат проверки
	 */
	private function isValidEAN13(string $ean13): bool
	{
		// Должно быть ровно 13 цифр
		if (!preg_match('/^\d{13}$/', $ean13)) {
			return false;
		}

		$sum = 0;
		for ($i = 0; $i < 12; $i++) {
			$digit = (int) $ean13[$i];
			$sum += $i % 2 === 0 ? $digit : $digit * 3;
		}

		$checkDigit = (10 - ($sum % 10)) % 10;
		return (int) $ean13[12] === $checkDigit;
	}
	private function validateJson(string $string): array
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
	 * Получить конфигурацию для конкретного метода
	 * 
	 * @param string $version Версия API
	 * @param string $type Тип запроса
	 * @param string $method Метод
	 * @return array|null Конфигурация или null
	 */
	private function getConfig(string $version, string $type, string $method): ?array
	{
		return $this->config[$version][$type][$method] ?? null;
	}

	/**
	 * Получить структуру обработчиков API
	 * Структура позволяет легко добавлять новые версии, методы и типы запросов
	 * 
	 * @return array
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


	protected function getSettings($settings = [])
	{
		if (php_sapi_name() === 'cli') {
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
		//Utils::debug($this->get, 3);
		// Парсинг URL параметров: ?params=v1/method/param/value
		$params = $this->get['params'] ?? '';
		$this->parseRequest($params);


		// Utils::debug($this->headers, 3);
		// Utils::debug('API Version: ' . $this->version, 3);
		// Utils::debug('API Method: ' . $this->method, 3);
		// Utils::debug('API Type: ' . $this->type, 3);
		//Utils::debug($this->params, 31);

		if (!empty($this->request)) {
			$validate = $this->validateJson($this->request);
			if ($validate['status'] == 200) {
				$this->data = &$validate['data'];
			} else {
				$this->status = $validate['status'];
				return $this->sendResponse(['message' => $validate['message']]);
			}
		}
		return $this->buildSettings();
	}

	/**
	 * Парсинг API запроса из URL
	 * Формат: /api/v1/methodName/param/value
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
	 * @param array $settings Параметры CLI
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
	 * @return array
	 */
	private function buildSettings(): array
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
	 * @param array $output Выходные данные [status, message, data] или сообщение об ошибке
	 * @param bool $print Выводить ли ответ клиенту
	 * @return string JSON ответ
	 */
	protected function sendResponse($output, $print = true)
	{
		// Если это массив со структурированными данными
		if (is_array($output) && isset($output['status'])) {
			$this->status = $output['status'];
			$responseData = [
				'status' => $output['status'],
				'message' => $output['message'] ?? '',
				'data' => $output['data'] ?? null,
			];
		} else {
			// Если это просто данные, оборачиваем их
			$responseData = [
				'status' => $this->status ?? 200,
				'message' => is_array($output) && isset($output['message']) ? $output['message'] : '',
				'data' => $output,
			];
		}

		http_response_code($this->status);
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
	 * @param array $logData Данные логирования
	 * @return bool Результат сохранения
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