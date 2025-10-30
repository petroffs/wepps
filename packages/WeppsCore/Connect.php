<?php
namespace WeppsCore;

use PDO;

/**
 * Класс Connect предоставляет функциональность для работы с базой данных и кэшированием.
 * Он использует PDO для подключения к базе данных и Memcached для кэширования запросов.
 */
class Connect
{
	/**
	 * @var PDO|null Экземпляр PDO для работы с базой данных.
	 */
	public static $db;
	/**
	 * @var Connect|null Экземпляр класса Connect.
	 */
	public static $instance;
	/**
	 * @var array|null Информация о проекте.
	 */
	public static $projectInfo;
	/**
	 * @var array|null Настройки разработки проекта.
	 */
	public static $projectDev;
	/**
	 * @var array|null Настройки базы данных проекта.
	 */
	public static $projectDB;
	/**
	 * @var array|null Настройки сервисов проекта.
	 */
	public static $projectServices;
	/**
	 * @var array|null Данные проекта.
	 */
	public static $projectData;
	/**
	 * @var int Счетчик запросов.
	 */
	public $count;
	/**
	 * @var \PDOStatement|null Объект PDOStatement для выполнения запросов.
	 */
	private $sth;
	/**
	 * @var Memcached|null Объект Memcached для кэширования.
	 */
	private $memcached;
	/**
	 * Конструктор класса Connect.
	 *
	 * @param array $projectSettings Настройки проекта.
	 */
	private function __construct($projectSettings = array())
	{
		self::$projectInfo = $projectSettings['Info'];
		self::$projectDev = $projectSettings['Dev'];
		self::$projectDB = $projectSettings['DB'];
		self::$projectServices = $projectSettings['Services'];
		self::$projectData = [];
		try {
			$connectionString = "{$projectSettings['DB']['driver']}:host={$projectSettings['DB']['host']}:{$projectSettings['DB']['port']};dbname={$projectSettings['DB']['dbname']};charset={$projectSettings['DB']['charset']}";
			$db = new PDO($connectionString, $projectSettings['DB']['user'], $projectSettings['DB']['password']);
			if ($projectSettings['Dev']['debug'] == 1) {
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			// Явно устанавливаем кодировку соединения для поддержки эмодзи и специальных символов
			$db->exec("SET NAMES {$projectSettings['DB']['charset']} COLLATE {$projectSettings['DB']['charset']}_unicode_ci");
			self::$db = &$db;
		} catch (\Exception $e) {
			$s = 0;
			if (php_sapi_name() == 'cli') {
				$s = 3;
			}
			if (Connect::$projectDev['debug'] == 1) {
				Utils::debug($e->getMessage(), $s);
			} else {
				Utils::debug("connect error", $s);
			}
			exit();
		}
		$this->memcached = new Memcached();
	}
	/**
	 * Деструктор класса Connect.
	 */
	public function __destruct()
	{
		self::$db = null;
	}
	/**
	 * Получает экземпляр класса Connect.
	 *
	 * @param array $projectSettings Настройки проекта.
	 * @return Connect Экземпляр класса Connect.
	 */
	public static function getInstance($projectSettings)
	{
		if (empty(self::$instance)) {
			self::$instance = new Connect($projectSettings);
		}
		return self::$instance;
	}
	/**
	 * Выполняет SQL-запрос и возвращает результат.
	 *
	 * @param string $sql SQL-запрос.
	 * @param array $params Параметры запроса.
	 * @param string $group Группировка результатов.
	 * @return array Результат запроса.
	 * @throws \Exception Если произошла ошибка при выполнении запроса.
	 */
	public function fetch($sql, $params = [], $group = ''): array
	{
		$this->count++;
		try {
			$isCache = 0;
			$cacheExpire = Connect::$projectServices['memcached']['expire'];
			if (strstr($sql, 'join ')) {
				$isCache = 1;
			}
			$key = md5($sql . implode(';', $params));
			if (!empty($this->memcached) && $isCache == 1 && !empty($res = $this->memcached->get($key))) {
				return $res;
			}
			$sth = self::$db->prepare($sql);
			$sth->execute($params);
			if ($group == 'group') {
				$res = $sth->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
			} else {
				$res = $sth->fetchAll(PDO::FETCH_ASSOC);
			}
			if (!empty($this->memcached) && $isCache == 1) {
				$this->memcached->set($key, $res);
			}
			return $res;
		} catch (\Exception $e) {
			Exception::display($e);
			return [];
		}
	}
	/**
	 * Выполняет SQL-запрос и возвращает количество затронутых строк.
	 *
	 * @param string $sql SQL-запрос.
	 * @param array $params Параметры запроса.
	 * @return int Количество затронутых строк.
	 * @throws \Exception Если произошла ошибка при выполнении запроса.
	 */
	public function query(string $sql, array $params = [])
	{
		$this->count++;
		try {
			if (empty($params)) {
				$state = self::$db->query($sql);
				return $state->rowCount();
			} else {
				$this->sth = self::$db->prepare($sql);
				$this->sth->execute($params);
				return $this->sth->rowCount();
			}
		} catch (\Exception $e) {
			Exception::display($e);
			return 0;
		}
	}
	/**
	 * Вставляет данные в таблицу.
	 *
	 * @param string $tableName Название таблицы.
	 * @param array $row Данные для вставки.
	 * @param array $settings Настройки вставки.
	 * @return int ID вставленной записи.
	 */
	public function insert($tableName, $row, $settings = [])
	{
		$arr = self::prepare($row);
		$sql = "insert ignore into $tableName (Priority) select round((max(Priority)+5)/5)*5 from $tableName on duplicate key update Id=last_insert_id(`Id`)";
		self::$instance->query($sql);
		$id = self::$db->lastInsertId();
		if ((int) $id != 0) {
			$arr = self::prepare($row, $settings);
			$sql = "update ignore $tableName set {$arr['update']} where Id='{$id}'";
			self::query($sql, $arr['row']);
		}
		$sql = "update $tableName set {$arr['update']} where Id='{$id}'";
		self::$instance->query($sql, $arr['row']);
		return $id;
	}
	/**
	 * Генерирует регулярное выражение для поиска ID.
	 *
	 * @param string $id ID для поиска.
	 * @return string Регулярное выражение.
	 */
	public function selectRegx(string $id = ''): string
	{
		return (string) '(,+|^)' . $id . '(,+|$)';
	}
	/**
	 * Закрывает соединение с базой данных.
	 *
	 * @param int $exit Флаг выхода из скрипта.
	 */
	public function close($exit = 1)
	{
		self::$db = null;
		if ($exit == 1) {
			exit();
		}
	}
	/**
	 * Подготавливает данные для вставки или обновления.
	 *
	 * @param array $row Данные для подготовки.
	 * @param array $settings Настройки подготовки.
	 * @return array Подготовленные данные.
	 */
	public function prepare($row = [], $settings = [])
	{
		$insert = $insert2 = $update = $select = "";
		$keys = array_keys($row);
		$insert = '(' . implode(',', $keys) . ') values ';
		foreach ($keys as $value) {
			if (!empty($settings[$value]['fn'])) {
				$insert2 .= "{$settings[$value]['fn']},";
				$update .= "{$value} = {$settings[$value]['fn']}, ";
				$select .= "{$settings[$value]['fn']} {$value}, ";
			} else {
				$insert2 .= ":{$value},";
				$update .= "{$value} = :{$value}, ";
				$select .= ":{$value} {$value}, ";
			}
			if (!empty($settings[$value]['rm'])) {
				unset($row[$value]);
			}
		}
		$insert .= '(' . trim($insert2, ',') . ')';
		$update = trim($update, ', ');
		$select = trim($select, ', ');
		$output = [
			"insert" => $insert,
			"update" => $update,
			"select" => $select,
			'row' => $row
		];
		return $output;
	}
	/**
	 * Генерирует строку для использования в SQL-запросе IN.
	 *
	 * @param array $in Массив значений для запроса IN.
	 * @return string Строка для запроса IN.
	 */
	public function in(array $in): string
	{
		return str_repeat('?,', count($in) - 1) . '?';
	}
	/**
	 * Выполняет транзакцию.
	 *
	 * @param callable $func Функция для выполнения в транзакции.
	 * @param array $args Аргументы для функции.
	 * @return array Результат выполнения функции.
	 * @throws \Exception Если произошла ошибка при выполнении транзакции.
	 */
	public function transaction(callable $func, array $args): array
	{
		Connect::$db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
		try {
			Connect::$db->beginTransaction();
			if (Connect::$db->inTransaction()) {
				$response = $func($args);
			}
			Connect::$db->commit();
			Connect::$db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
			return $response;
		} catch (\Exception $e) {
			Connect::$db->rollBack();
			echo "Error. See debug.conf";
			Utils::debug($e, 21);
			return [];
		}
	}
	/**
	 * Активирует или деактивирует кэширование.
	 *
	 * @param string $isActive Флаг активации кэширования.
	 */
	public function cached($isActive = 'auto')
	{
		$this->memcached = new Memcached($isActive);
	}
}