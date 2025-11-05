<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Exception;

/**
 * REST обработчик для работы со списками
 * Наследует структуру от Rest класса
 */
class RestLists extends Rest
{
	/**
	 * Флаг отключения родительской инициализации
	 * @var int
	 */
	public int $parent = 0;

	/**
	 * Конструктор класса RestLists
	 * 
	 * @param array $settings Параметры инициализации
	 */
	public function __construct($settings = [])
	{
		parent::__construct($settings);
	}

	/**
	 * Получить список с поиском и пагинацией
	 * 
	 * @param array $params Параметры запроса (должны содержать 'list' и 'field')
	 * @param array $additionalParams Дополнительные параметры
	 * @return void
	 */
	public function getLists($params = [], $additionalParams = []): void
	{
		$text = $this->get['search'] ?? '';
		$page = (int) ($this->get['page'] ?? 1);

		if ($page < 1) {
			$page = 1;
		}

		// Получение информации о поле из конфигурации
		$list = $params[0] ?? $params['list'] ?? '';
		$field = $params[1] ?? $params['field'] ?? '';

		$sql = "SELECT * FROM s_ConfigFields WHERE TableName = '{$list}' AND Id = '{$field}'";
		$res = Connect::$instance->fetch($sql);

		if (empty($res)) {
			Exception::error(404);
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

		$output = [
			'status' => 200,
			'message' => 'List retrieved successfully',
			'data' => [
				'results' => $res,
				'pagination' => [
					'more' => $pagination
				]
			]
		];

		$this->sendResponse($output);
	}

	/**
	 * Тестовый метод GET запроса
	 * 
	 * @return void
	 */
	public function getTest(): void
	{
		$output = [
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

		$this->sendResponse($output);
	}

	/**
	 * Тестовый метод POST/PUT запроса
	 * 
	 * @return void
	 */
	public function setTest(): void
	{
		$output = [
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

		$this->sendResponse($output);
	}

	/**
	 * Тестовый метод DELETE запроса
	 * 
	 * @return void
	 */
	public function removeTest(): void
	{
		$output = [
			'status' => 200,
			'message' => 'DELETE request processed',
			'data' => [
				'field' => $this->apiParams['param'] ?? $this->settings['param'] ?? '',
				'value' => $this->apiParams['paramValue'] ?? $this->settings['paramValue'] ?? '',
				'removed' => 'ok',
			]
		];

		$this->sendResponse($output);
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
				'message' => 'ok'
			]
		];

		$this->sendResponse($output, false);
	}
}