<?php
namespace WeppsCore;

/**
 * Класс служит для трекинга выполнения локальных сервисов, включая:
 * 
 * Регистрацию входящих запросов (CLI/HTTP).
 * Хранение метаданных (IP, дата, URL).
 * Отслеживание статуса обработки и результатов.
 * Использует паттерн подготовки SQL-запросов через Connect для обеспечения безопасности и гибкости.
 * 
 */
class Tasks
{
	public function __construct()
	{

	}
	/**
	 * Добавляет новую задачу в систему трекинга.
	 *
	 * @param string $name Название задачи.
	 * @param array $jdata Данные задачи в формате массива.
	 * @param string $date Дата создания задачи. Если не указана, используется текущая дата и время.
	 * @param string $ip IP-адрес, с которого поступил запрос. Если не указан, используется IP из $_SERVER.
	 * @param string $type Тип запроса (cli, http, post и т.д.). По умолчанию 'cli'.
	 * @return void
	 */
	public function add(string $name, array $jdata, string $date = '', string $ip = '', string $type = 'cli')
	{
		$row = [
			'Name' => $name,
			'LDate' => ($date != '') ? $date : date('Y-m-d H:i:s'),
			'IP' => ($ip != '') ? $ip : @$_SERVER['REMOTE_ADDR'] ?? '',
			'BRequest' => json_encode($jdata, JSON_UNESCAPED_UNICODE),
			'TRequest' => $type,
		];
		if ($type == 'post') {
			$row['Url'] = @$_SERVER['REQUEST_URI'];
		}
		$prepare = Connect::$instance->prepare($row);
		$insert = Connect::$db->prepare("INSERT into s_Tasks {$prepare['insert']}");
		$insert->execute($row);
	}
	/**
	 * Обновляет статус и результат выполнения задачи.
	 *
	 * @param int $id Идентификатор задачи.
	 * @param array $response Ответ задачи в формате массива.
	 * @param int $status HTTP-статус ответа. По умолчанию 200.
	 * @return array Возвращает массив с результатом обновления.
	 */
	public function update(int $id, array $response, int $status = 200): array
	{
		$row = [
			'InProgress' => 1,
			'IsProcessed' => 1,
			'BResponse' => json_encode($response, JSON_UNESCAPED_UNICODE),
			'SResponse' => $status,
		];
		$prepare = Connect::$instance->prepare($row);
		$sql = "UPDATE s_Tasks set {$prepare['update']} where Id = :Id";
		Connect::$instance->query($sql, array_merge($prepare['row'], ['Id' => $id]));

		return [
			'status' => 200,
			'message' => 'Задача обновлена',
			'data' => [
				'id' => $id,
				'response' => $response,
				'http_status' => $status
			]
		];
	}

	/**
	 * Очищает таблицу s_Tasks, если нет задач к выполнению.
	 * Удаляет только обработанные задачи, оставляя необработанные.
	 *
	 * @return array Результат операции.
	 */
	public function cleanup(): array
	{
		// Проверяем наличие необработанных задач
		$sql = "SELECT COUNT(*) as cnt FROM s_Tasks WHERE IsProcessed=0";
		$result = Connect::$instance->fetch($sql);

		if (!empty($result) && $result[0]['cnt'] > 0) {
			return [
				'status' => 400,
				'message' => 'Есть необработанные задачи',
				'data' => [
					'pending_tasks' => (int) $result[0]['cnt']
				]
			];
		}

		// Полная очистка таблицы с сбросом автоинкремента
		$sql = "TRUNCATE s_Tasks";
		Connect::$instance->query($sql);

		return [
			'status' => 200,
			'message' => 'Таблица очищена от обработанных задач',
			'data' => null
		];
	}

	/**
	 * Удаляет записи из таблицы s_Tasks по возрасту и статусу.
	 *
	 * @param int $days Возраст логов в днях для удаления.
	 * @param int|null $status HTTP-статус для фильтрации (200, 400, 404 и т.д.). Если null, удаляются все статусы.
	 * @return array Результат операции.
	 */
	public function removeOld(int $days = 7, ?int $status = null): array
	{
		if ($days <= 0) {
			return [
				'status' => 400,
				'message' => 'Количество дней должно быть больше 0',
				'data' => null
			];
		}

		$params = ['days' => $days];
		$statusFilter = '';

		if ($status !== null) {
			$statusFilter = " AND SResponse = :status";
			$params['status'] = $status;
		}

		// Получаем количество записей для удаления
		$sqlCount = "SELECT COUNT(*) as cnt FROM s_Tasks 
					 WHERE IsProcessed=1 
					 AND LDate < DATE_SUB(NOW(), INTERVAL :days DAY)
					 {$statusFilter}";
		$result = Connect::$instance->fetch($sqlCount, $params);
		$count = !empty($result) ? (int) $result[0]['cnt'] : 0;

		if ($count === 0) {
			return [
				'status' => 200,
				'message' => 'Нет записей для удаления',
				'data' => [
					'deleted' => 0,
					'filter' => [
						'days' => $days,
						'status' => $status
					]
				]
			];
		}

		// Удаляем записи
		$sqlDelete = "DELETE FROM s_Tasks 
					  WHERE IsProcessed=1 
					  AND LDate < DATE_SUB(NOW(), INTERVAL :days DAY)
					  {$statusFilter}";
		Connect::$instance->query($sqlDelete, $params);

		return [
			'status' => 200,
			'message' => "Удалено записей: {$count}",
			'data' => [
				'deleted' => $count,
				'filter' => [
					'days' => $days,
					'status' => $status
				]
			]
		];
	}
}