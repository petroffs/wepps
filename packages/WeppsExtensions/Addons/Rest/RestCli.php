<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Utils;

/**
 * REST обработчик для CLI запросов
 * Наследует структуру от Rest класса
 */
class RestCli extends Rest
{
	/**
	 * Флаг отключения родительской инициализации
	 * @var int
	 */
	public int $parent = 0;

	/**
	 * Конструктор класса RestCli
	 * 
	 * @param array $settings Параметры инициализации
	 */
	public function __construct($settings = [])
	{
		parent::__construct($settings);
	}

	/**
	 * Удалить локальные логи и кэш файлы
	 * Очищает таблицу s_Tasks и удаляет файлы логов из директории
	 * 
	 * @return void
	 */
	public function removeLogLocal(): void
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

			$output = [
				'status' => 200,
				'message' => 'Local logs removed successfully',
				'data' => [
					'removed' => 'ok',
					'timestamp' => date('Y-m-d H:i:s')
				]
			];
		} catch (\Exception $e) {
			$output = [
				'status' => 500,
				'message' => 'Error removing logs: ' . $e->getMessage(),
				'data' => null
			];
		}

		$this->sendResponse($output, false);
	}

	/**
	 * Тестовый метод CLI запроса
	 * 
	 * @return void
	 */
	public function cliTest(): void
	{
		$output = [
			'status' => 200,
			'message' => 'CLI test executed',
			'data' => [
				'message' => 'ok',
				'timestamp' => date('Y-m-d H:i:s')
			]
		];

		$this->sendResponse($output, false);
	}
}