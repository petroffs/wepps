<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Tasks;

/**
 * REST обработчик для CLI запросов.
 *
 * Вызывается из командной строки (версия 'cli' в RestConfig.php) через
 * Rest::parseCliRequest(). Предоставляет служебные операции:
 * - removeLogLocal - очистка таблицы s_Tasks и удаление локальных файлов логов;
 * - tasksResult - получение результата задачи из очереди s_Tasks по id;
 * - tasksProcess - обработка отложенных async M2M POST-задач из s_Tasks;
 * - cliTest - тестовый эндпоинт.
 */
class RestCli
{
	/**
	 * Параметры CLI-запроса: version, method, type, params, param, paramValue.
	 * Формируются в Rest::buildSettings() из argv (Request.php скрипт метод param paramValue).
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * Конструктор класса RestCli.
	 *
	 * Сохраняет настройки запроса, переданные из Rest::routeRequest()
	 * (массив buildSettings(): param, paramValue и т.д.).
	 *
	 * @param array $settings Параметры инициализации CLI-запроса
	 */
	public function __construct($settings = [])
	{
		$this->settings = $settings;
	}

	/**
	 * Удалить локальные логи и кэш-файлы.
	 *
	 * Очищает таблицу s_Tasks (TRUNCATE) и удаляет файлы из директории
	 * __DIR__/files/ (если они есть).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function removeLogLocal(): array
	{
		try {
			// Очистка таблицы логов задач
			$sql = "TRUNCATE s_Tasks";
			Connect::$instance->query($sql);

			// Удаление файлов логов
			$directoryPath = __DIR__ . "/files/";

			if (is_dir($directoryPath)) {
				$directoryScan = scandir($directoryPath);

				// Проверка наличия файлов (scandir возвращает минимум . и ..)
				if (count($directoryScan) > 2) {
					exec("rm {$directoryPath}*");
				}
			}

			return [
				'status' => 200,
				'message' => 'Local logs removed successfully',
				'data' => [
					'removed' => 'OK',
					'timestamp' => date('Y-m-d H:i:s')
				]
			];
		} catch (\Exception $e) {
			return [
				'status' => 400,
				'message' => 'Error removing logs: ' . $e->getMessage(),
				'data' => null
			];
		}
	}

	/**
	 * Тестовый метод CLI запроса.
	 *
	 * Возвращает фиксированный ответ об успешном выполнении с временной меткой.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function cliTest(): array
	{
		return [
			'status' => 200,
			'message' => 'CLI test executed',
			'data' => [
				'message' => 'OK',
				'timestamp' => date('Y-m-d H:i:s')
			]
		];
	}

	/**
	 * Получить результат задачи из очереди по ID.
	 *
	 * ID передаётся аргументом CLI: Request.php tasks.result 123
	 * (попадает в $this->settings['paramValue']). Возвращает детали задачи из
	 * таблицы s_Tasks: тип запроса, url, статусы обработки и сохранённый ответ
	 * (http_status + response).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function tasksResult(): array
	{
		$id = (int) ($this->settings['param'] ?? $this->settings['paramValue'] ?? 0);
		if (!$id) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$rows = Connect::$instance->fetch(
			"SELECT Id, Name, LDate, TRequest, Url, IsProcessed, InProgress, BResponse, SResponse FROM s_Tasks WHERE Id = ?",
			[$id]
		);

		if (empty($rows)) {
			return ['status' => 404, 'message' => 'Task not found', 'data' => null];
		}

		$task = $rows[0];

		return [
			'status'  => 200,
			'message' => 'OK',
			'data'    => [
				'id'           => (int) $task['Id'],
				'name'         => $task['Name'],
				'created_at'   => $task['LDate'],
				'type'         => $task['TRequest'],
				'url'          => $task['Url'],
				'is_processed' => (bool) $task['IsProcessed'],
				'in_progress'  => (bool) $task['InProgress'],
				'http_status'  => $task['SResponse'] ? (int) $task['SResponse'] : null,
				'response'     => $task['BResponse'] ? json_decode($task['BResponse'], true) : null,
			],
		];
	}

	/**
	 * Обработать очередь отложенных M2M POST-задач из s_Tasks.
	 *
	 * Выбирает до 50 необработанных задач (IsProcessed=0, TRequest='post',
	 * Url LIKE '/rest/m2m%'). Для каждой задачи восстанавливает контекст REST
	 * (данные тела, GET-параметры, пользователя), создаёт обработчик из payload
	 * задачи и вызывает его метод, затем сохраняет результат в s_Tasks.
	 * Задачи с некорректным payload помечаются ошибкой (400), исключения — 500.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']:
	 *               data = ['processed' => количество обработанных задач]
	 */
	public function tasksProcess(): array
	{
		$tasks = Connect::$instance->fetch(
			"SELECT * FROM s_Tasks WHERE IsProcessed=0 AND TRequest='post' AND Url LIKE '/rest/m2m%' ORDER BY Id ASC LIMIT 50"
		);

		if (empty($tasks)) {
			return ['status' => 200, 'message' => 'No pending tasks', 'data' => ['processed' => 0]];
		}

		$processed   = 0;
		$taskManager = new Tasks();

		foreach ($tasks as $task) {
			$request      = json_decode($task['BRequest'], true);
			$handlerClass = $request['handler'] ?? '';
			$method       = $request['method'] ?? '';

			if (!$handlerClass || !$method || !class_exists($handlerClass)) {
				$taskManager->update((int) $task['Id'], ['error' => 'Invalid task payload'], 400);
				continue;
			}

			try {
				$rest = new Rest([], false);
				$rest->setRequestData($request['data'] ?? null);
				$rest->setRequestGet($request['get'] ?? []);
				$rest->setUser($request['user'] ?? null);

				$handler = new $handlerClass($rest);
				$result  = $handler->$method($request['data'] ?? null);

				$taskManager->update((int) $task['Id'], $result, $result['status'] ?? 200);
				$processed++;
			} catch (\Exception $e) {
				$taskManager->update((int) $task['Id'], ['error' => $e->getMessage()], 500);
			}
		}

		return ['status' => 200, 'message' => 'Tasks processed', 'data' => ['processed' => $processed]];
	}
}