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
class Logs {
	public function __construct() {

	}
	/**
	 * Класс служит для трекинга выполнения локальных сервисов, включая:
	 * 
	 * Регистрацию входящих запросов (CLI/HTTP).
	 * Хранение метаданных (IP, дата, URL).
	 * Отслеживание статуса обработки и результатов.
	 * Использует паттерн подготовки SQL-запросов через Connect для обеспечения безопасности и гибкости.
	 * 
	 * @param string $name
	 * @param array $jdata
	 * @param string $date
	 * @param string $ip
	 * @param string $type
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
		$insert = Connect::$db->prepare("insert into s_Tasks {$prepare['insert']}");
		$insert->execute($row);
	}
	public function update(int $id,array $response,int $status=200) {
		$row = [
			'InProgress' => 1,
			'IsProcessed' => 1,
			'BResponse' => json_encode($response,JSON_UNESCAPED_UNICODE),
			'SResponse' => $status,
		];
		$prepare = Connect::$instance->prepare($row);
		$sql = "update s_Tasks set {$prepare['update']} where Id = :Id";
		Connect::$instance->query($sql,array_merge($prepare['row'],['Id'=>$id]));
		return [
			'id' => $id,
			'response' => $response,
			'status' => $status,
		];
	}
}