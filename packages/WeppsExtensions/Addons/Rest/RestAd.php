<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Utils;

/**
 * REST обработчик для работы со списками админки.
 *
 * Вызывается динамически через Rest::executeHandler() по конфигу версии 'v0'/'ad'
 * из RestConfig.php. Предоставляет поиск по спискам админки с пагинацией
 * (getListItems) и тестовые эндпоинты. Авторизация — общий метод
 * POST /rest/v1/auth.login.
 *
 * Методы получают данные через ссылки на get/post/data экземпляра Rest
 * и возвращают стандартный массив ответа ['status', 'message', 'data'].
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
	 * Конструктор класса RestAd.
	 *
	 * Сохраняет экземпляр Rest и устанавливает ссылки на его GET/POST параметры
	 * и парсированные данные JSON-тела запроса.
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
	 * Получить список с поиском и пагинацией.
	 *
	 * GET-параметры: list (имя списка/таблицы), field (id поля из s_ConfigFields),
	 * search (строка поиска), page (номер страницы). Поле конфигурируется в
	 * s_ConfigFields (FType вида List::Table::Field::Condition). Использует
	 * custom_response: возвращает ['results' => [...], 'pagination' => ['more' => bool]]
	 * без стандартной обёртки status/message/data.
	 *
	 * @return array Результаты поиска с пагинацией (custom_response)
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

		$ex = explode('::', $res[0]['FType']);
		$list = $ex[1] ?? '';
		$field = $ex[2] ?? '';
		$condition = $ex[3] ?? '';

		// Добавление условия поиска
		$searchCondition = '';
		$params = [];
		if (mb_strlen($text) > 0) {
			$searchCondition = " AND t.{$field} LIKE ?";
			$params[] = '%' . $text . '%';
		}

		$limit = 10;
		$offset = ($page - 1) * $limit;
		$sql = "SELECT t.Id id, CONCAT(t.{$field}, ' (', t.Id, ')') text 
		        FROM {$list} t 
		        WHERE {$condition}{$searchCondition} 
		        ORDER BY t.{$field} 
		        LIMIT $offset, $limit";

		$res = Connect::$instance->fetch($sql, $params);
		$pagination = !empty($res);

		return [
			'results' => $res,
			'pagination' => [
				'more' => $pagination
			]
		];
	}

	/**
	 * Тестовый метод GET запроса.
	 *
	 * Возвращает фиксированный набор тестовых данных.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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
	 * Тестовый метод POST/PUT запроса.
	 *
	 * Принимает входные данные (не используются) и возвращает фиксированный
	 * набор тестовых данных.
	 *
	 * @param array|null $data Входные данные тела запроса
	 * @return array Ответ в формате ['status', 'message', 'data']
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
	 * Тестовый метод DELETE запроса.
	 *
	 * Возвращает переданные параметры (param / paramValue) из параметров
	 * запроса или настроек как подтверждение удаления.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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
	 * Тестовый метод CLI запроса.
	 *
	 * Вызывается из командной строки (версия 'cli'), возвращает фиксированный
	 * ответ об успешном выполнении.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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