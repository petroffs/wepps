<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Tasks;

/**
 * REST обработчик для CLI запросов
 */
class RestCli
{

	/**
	 * Конструктор класса RestCli
	 * 
	 * @param array $settings Параметры инициализации
	 */
	public function __construct($settings = [])
	{
		// Инициализация без наследования
	}

	/**
	 * Удалить локальные логи и кэш файлы
	 * Очищает таблицу s_Tasks и удаляет файлы логов из директории
	 * 
	 * @return void
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
	 * Тестовый метод CLI запроса
	 * 
	 * @return void
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
	 * Обработать очередь async-задач (TRequest='post', IsProcessed=0)
	 *
	 * Для каждой задачи восстанавливает контекст REST-запроса, запускает обработчик
	 * и сохраняет результат в s_Tasks.
	 */
	/**
	 * Получить результат задачи из очереди по ID.
	 * GET-параметр: ?id=
	 */
	public function tasksResult(): array
	{
		$id = (int) ($this->get['id'] ?? 0);
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
				$result  = $handler->$method();

				$taskManager->update((int) $task['Id'], $result, $result['status'] ?? 200);
				$processed++;
			} catch (\Exception $e) {
				$taskManager->update((int) $task['Id'], ['error' => $e->getMessage()], 500);
			}
		}

		return ['status' => 200, 'message' => 'Tasks processed', 'data' => ['processed' => $processed]];
	}
}