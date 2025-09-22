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
class Tasks {
	public function __construct() {

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
			'IP' => ($ip != '') ? $ip : @$_SERVER['REMOTE_ADDR']??'',
			'BRequest' => json_encode($jdata, JSON_UNESCAPED_UNICODE),
			'TRequest' => $type,
		];
		if ($type=='post') {
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
	 * @return array Возвращает массив с идентификатором задачи, ответом и статусом.
	 */
	public function update(int $id,array $response,int $status=200) {
		$row = [
			'InProgress' => 1,
			'IsProcessed' => 1,
			'BResponse' => json_encode($response,JSON_UNESCAPED_UNICODE),
			'SResponse' => $status,
		];
		$prepare = Connect::$instance->prepare($row);
		$sql = "UPDATE s_Tasks set {$prepare['update']} where Id = :Id";
		Connect::$instance->query($sql,array_merge($prepare['row'],['Id'=>$id]));
		return [
			'id' => $id,
			'response' => $response,
			'status' => $status,
		];
	}
}