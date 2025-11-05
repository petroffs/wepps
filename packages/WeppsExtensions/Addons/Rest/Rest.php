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
	protected string $apiVersion = 'v1';

	/**
	 * Метод API (getList, test, createUser и т.д.)
	 * @var string
	 */
	protected string $apiMethod = '';

	/**
	 * Параметры запроса в виде массива ключ-значение
	 * Пример: ['id' => '123', 'filter' => 'active']
	 * @var array
	 */
	protected array $apiParams = [];

	/**
	 * Тип HTTP запроса (GET, POST, DELETE, PUT, CLI)
	 * @var string
	 */
	protected string $apiType = 'GET';

	/**
	 * Утилиты для работы с REST запросами
	 * @var RestUtils
	 */
	protected RestUtils $restUtils;
	public function __construct($settings = [])
	{
		$this->restUtils = new RestUtils();
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
			// Извлечение данных из структурированного ответа
			$this->status = $handler['status'];
			$handlerObject = $handler['data'];

			// Вызов метода обработчика в зависимости от типа и метода запроса
			$this->executeHandler($handlerObject);
		}
		return;
	}

	/**
	 * Выполнить обработчик запроса
	 * 
	 * @param object $handler Объект обработчика
	 */
	private function executeHandler($handler): void
	{
		$methodMap = [
			'post' => [
				'test' => 'setTest',
			],
			'get' => [
				'getList' => 'getLists',
				'test' => 'getTest',
			],
			'delete' => [
				'test' => 'removeTest',
			],
			'put' => [
				'test' => 'setTest',
			],
			'cli' => [
				'removeLogLocal' => 'removeLogLocal',
				'test' => 'cliTest',
			],
		];

		$method = $methodMap[$this->apiType][$this->apiMethod] ?? null;

		if ($method && method_exists($handler, $method)) {
			if ($this->apiType === 'get' && $this->apiMethod === 'getList') {
				$handler->$method($this->apiParams, $this->apiParams);
			} else {
				$handler->$method();
			}
		}
	}

	/**
	 * Маршрутизация API запроса
	 * 
	 * @return array|null Массив с [status, message, data] или null если не найдено
	 */
	private function routeRequest(): ?array
	{
		// Использование свойств класса вместо массива settings
		$handlers = $this->getApiHandlers();

		if (isset($handlers[$this->apiVersion][$this->apiType][$this->apiMethod])) {
			return $handlers[$this->apiVersion][$this->apiType][$this->apiMethod];
		}

		return null;
	}

	/**
	 * Получить структуру обработчиков API
	 * Структура позволяет легко добавлять новые версии, методы и типы запросов
	 * 
	 * @return array
	 */
	private function getApiHandlers(): array
	{
		return [
			'v1' => [
				'post' => $this->createApiHandlers('post'),
				'get' => $this->createApiHandlers('get'),
				'delete' => $this->createApiHandlers('delete'),
				'put' => $this->createApiHandlers('put'),
				'cli' => $this->createApiHandlers('cli'),
			],
			'v2' => [
				'post' => $this->createApiHandlers('post'),
				'get' => $this->createApiHandlers('get'),
				'delete' => $this->createApiHandlers('delete'),
				'put' => $this->createApiHandlers('put'),
			],
			'cli' => [
				'cli' => $this->createApiHandlers('cli'),
			],
		];
	}

	/**
	 * Создать обработчики для конкретного типа запроса
	 * Возвращает структурированные данные [status, message, data]
	 * 
	 * @param string $type Тип запроса (GET, POST, DELETE, PUT, CLI)
	 * @return array
	 */
	private function createApiHandlers(string $type): array
	{
		$handlers = [];

		switch ($type) {
			case 'post':
				$handlers = [
					'test' => [
						'status' => 200,
						'message' => 'POST request processed',
						'data' => new RestLists(),
					],
				];
				break;
			case 'get':
				$handlers = [
					'getList' => [
						'status' => 200,
						'message' => 'List retrieved successfully',
						'data' => new RestLists(),
					],
					'test' => [
						'status' => 200,
						'message' => 'GET request processed',
						'data' => new RestLists(),
					],
				];
				break;
			case 'delete':
				$handlers = [
					'test' => [
						'status' => 200,
						'message' => 'DELETE request processed',
						'data' => new RestLists(),
					],
				];
				break;
			case 'put':
				$handlers = [
					'test' => [
						'status' => 200,
						'message' => 'PUT request processed',
						'data' => new RestLists(),
					],
				];
				break;
			case 'cli':
				$handlers = [
					'removeLogLocal' => [
						'status' => 200,
						'message' => 'Local log removed',
						'data' => new RestCli($this->settings),
					],
					'test' => [
						'status' => 200,
						'message' => 'CLI test executed',
						'data' => new RestCli($this->settings),
					],
				];
				break;
		}

		return $handlers;
	}
	private function getSettings($settings = [])
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
		$this->apiType = strtolower($_SERVER['REQUEST_METHOD']);

		// Парсинг URL параметров: /api/v1/method/param/value
		$params = (!isset($this->get['params'])) ? "" : $this->get['params'];
		$this->parseApiRequest($params);

		// Utils::debug($this->headers, 3);
		// Utils::debug('API Version: ' . $this->apiVersion, 3);
		// Utils::debug('API Method: ' . $this->apiMethod, 3);
		// Utils::debug('API Type: ' . $this->apiType, 3);
		// Utils::debug('API Type: ' . $this->apiType, 3);
		// Utils::debug($this->apiParams, 31);

		if (!empty($this->request)) {
			$validate = $this->restUtils->validateJson($this->request);
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
	private function parseApiRequest(string $params): void
	{
		$parts = explode("/", trim($params, "/"));

		// Извлечение версии API (v1, v2, и т.д.)
		$this->apiVersion = (!empty($parts[0])) ? $parts[0] : 'v1';

		// Извлечение метода API
		$this->apiMethod = (!empty($parts[1])) ? $parts[1] : '';

		// Остальные параметры
		$this->apiParams = [];
		if (count($parts) > 2) {
			for ($i = 2; $i < count($parts); $i += 2) {
				$key = $parts[$i];
				$value = (isset($parts[$i + 1])) ? $parts[$i + 1] : null;
				$this->apiParams[$key] = $value;
			}
		}
	}

	/**
	 * Парсинг CLI запроса
	 * 
	 * @param array $settings Параметры CLI
	 */
	private function parseCliRequest(array $settings): void
	{
		$this->apiVersion = 'cli';
		$this->apiMethod = @$settings['cli'][1] ?? '';
		$this->apiType = 'cli';
		$this->apiParams = [];
	}

	/**
	 * Построение массива настроек
	 * 
	 * @return array
	 */
	private function buildSettings(): array
	{
		return [
			'version' => $this->apiVersion,
			'method' => $this->apiMethod,
			'type' => $this->apiType,
			'params' => $this->apiParams,
			'param' => $this->apiParams[0] ?? '',
			'paramValue' => $this->apiParams[1] ?? ''
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
			'Name' => $this->apiMethod,
			'Url' => $this->url ?? '',
			'TRequest' => $this->apiType,
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