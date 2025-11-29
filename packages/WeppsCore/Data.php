<?php
namespace WeppsCore;

/**
 * Class Data
 *
 * Предоставляет функциональность для работы с базой данных: выполнение SQL-запросов,
 * пагинация, управление схемами таблиц и операции CRUD (создание, чтение, обновление, удаление).
 * Используется для взаимодействия с таблицами БД через ORM-подобный интерфейс.
 *
 * @package WeppsCore
 * @author Aleksei Petrov
 * @see Connect::fetch() Для выполнения SQL-запросов
 * @see Language::getRows() Для локализации результатов
 */
class Data
{
	/**
	 * Таблица БД, с которой производятся операции
	 * @var string
	 */
	public $tableName;

	/**
	 * Количество строк запроса
	 * @var int
	 */
	public $count = 0;

	/**
	 * Пагинация, заполняется при вызове методов fetchmini/fetch
	 * @var array
	 */
	public $paginator;

	/**
	 * Запрос БД, сформированный в fetchmini/fetch
	 * @var string
	 */
	public $sql;

	/**
	 * Вспомогательный SQL-запрос для подсчёта строк
	 * @var string
	 */
	private $sqlCounter;

	/**
	 * Длина обрезки полей типа area в fetch()
	 * @var int
	 */
	public $truncate = 0;

	/**
	 * Схема текущей таблицы БД
	 * @var array|null
	 */
	private $scheme;

	/**
	 * Перечисление полей таблицы
	 * @var string
	 */
	private $fields = '';

	/**
	 * Дополнительные поля для вывода (например, функции)
	 * @var string
	 */
	private $concat = '';

	/**
	 * JOIN-запросы для сложных выборок
	 * @var string
	 */
	private $join = '';

	/**
	 * Группировка результатов
	 * @var string
	 */
	private $group = '';

	/**
	 * Условия HAVING для SQL-запроса
	 * @var string
	 */
	private $having = '';

	/**
	 * Параметры для подготовленных запросов
	 * @var array
	 */
	private $params = [];

	/**
	 * Язык для локализации данных
	 * @var mixed
	 */
	public $lang;

	/**
	 * Конструктор класса Data
	 *
	 * @param string $tableName Имя таблицы БД
	 */
	public function __construct($tableName = '')
	{
		if ($tableName == "") {
			exit();
		}
		$this->tableName = Utils::trim($tableName);
	}
	/**
	 * Получить набор строк таблицы
	 *
	 * @param string $conditions Условия выборки
	 * @param int $onPage Количество строк на странице
	 * @param int $currentPage Текущая страница
	 * @param string $orderBy Поле для сортировки
	 * @return array Массив строк таблицы
	 */
	public function fetchmini(string $conditions = '', $onPage = 20, $currentPage = 1, $orderBy = "Priority")
	{
		if (empty($conditions)) {
			$conditions = "Id!=0";
		}
		$fields = $this->fields;
		$fields = ($fields != '') ? $fields : '*';
		$concat = $this->concat;
		if ($concat != '') {
			$concat = "," . $concat;
		}
		$formatted = $this->_getFormatted($conditions, $onPage, $currentPage, $orderBy);
		$this->sql = "select $fields $concat from {$this->tableName} where {$formatted['conditions']} {$formatted['orderBy']} {$formatted['limit']}";
		$this->sqlCounter = "select count(*) Co from {$this->tableName} where {$formatted['conditions']}";
		$res = Connect::$instance->fetch($this->sql);
		if ($currentPage > 0) {
			$paginator = $this->_getPaginator($formatted['onPage'], $formatted['currentPage']);
			$this->paginator = $paginator;
		}
		$res = Language::getRows($res, $this->scheme, $this->lang);
		if (count($res) == 0) {
			return [];
		}
		return $res;
	}
	/**
	 * Получить набор строк таблицы с соединением на основе схемы поля
	 *
	 * @param string $conditions Условия выборки
	 * @param int $onPage Количество строк на странице
	 * @param int $currentPage Текущая страница
	 * @param string $orderBy Поле для сортировки
	 * @return array Массив строк таблицы
	 */
	public function fetch(string $conditions = '', $onPage = 20, $currentPage = 0, $orderBy = "t.Priority")
	{
		if (empty($conditions)) {
			$conditions = "t.Id!=0";
		}
		$formatted = $this->_getFormatted($conditions, $onPage, $currentPage, $orderBy);
		if (substr($formatted['conditions'], 0, 2) == 'Id') {
			$formatted['conditions'] = "t." . $formatted['conditions'];
		}
		$settings = $this->getScheme();
		$fields = $joins = "";
		$joinCustom = $this->join;
		$f = 1;
		foreach ($settings as $key => $value) {
			$ex = explode("::", $value[0]['Type'], 4);
			switch ($ex[0]) {
				case "file":
					$fields .= "'f{$f}' as {$key}_Coordinate,group_concat(distinct f{$f}.FileUrl order by f{$f}.Priority separator ':::') as {$key}_FileUrl,\n";
					$joins .= "left join s_Files as f{$f} on f{$f}.TableNameId = t.Id and f{$f}.TableNameField = '{$key}' and f{$f}.TableName = '{$this->tableName}'\n";
					$f++;
					break;
				case "select":
				case "remote":
					$str = "";
					foreach (explode(",", $ex[2]) as $v) {
						$str .= "s{$f}.{$v} as {$key}_{$v},";
					}
					$str = trim($str, ",");
					$fields .= "t.{$key},'s{$f}' as {$key}_Coordinate,$str,\n";
					$joins .= "left join {$ex[1]} as s{$f} on s{$f}.Id = t.{$key}\n";
					$f++;
					break;
				case "select_multi":
				case "remote_multi":
					$fields .= "t.{$key},'sm{$f}' as {$key}_Coordinate,group_concat(distinct sm{$f}.{$ex[2]} order by sm{$f}.Priority separator ':::') as {$key}_{$ex[2]},\n";
					$joins .= "left join s_SearchKeys as sk{$f} on sk{$f}.Name = t.Id and sk{$f}.IsHidden=0 and sk{$f}.Field3 = 'List::{$this->tableName}::{$key}' left join {$ex[1]} as sm{$f} on sm{$f}.Id = sk{$f}.Field1\n";
					$formatted['conditions'] .= "";
					$f++;
					break;
				case "area":
					if ($this->truncate != 0) {
						$fields .= "substr(t.{$key},1,{$this->truncate}) as {$key},";
					} else {
						$fields .= "t.{$key},";
					}
					break;
				case "blob":
					$fields .= "uncompress (t.{$key}) {$key},";
					break;
				default:
					$fields .= "t.{$key},";
					break;
			}
		}
		$fields = trim(trim($fields), ",");
		$concat = $this->concat;
		if ($concat != '') {
			$concat = "," . $concat;
		}
		$group = ($this->group == '') ? 't.Id' : $this->group;
		$having = (!empty($this->having)) ? "having {$this->having}" : '';
		$this->sql = "select $fields $concat from {$this->tableName} as t $joins $joinCustom where {$formatted['conditions']} group by $group $having {$formatted['orderBy']} {$formatted['limit']}";
		$this->sqlCounter = "select count(z.Id) Co from (select t.Id from {$this->tableName} as t $joins $joinCustom where {$formatted['conditions']} group by $group) z";
		$res = Connect::$instance->fetch($this->sql, $this->params);
		if ($currentPage > 0) {
			$paginator = $this->_getPaginator($formatted['onPage'], $formatted['currentPage']);
			$this->paginator = $paginator;
		}
		$res = Language::getRows($res, $this->scheme, $this->lang);
		return $res;
	}
	/**
	 * Вспомогательная функция для обработки исходных переменных
	 *
	 * @param string $conditions Условия выборки
	 * @param int $onPage Количество строк на странице
	 * @param int $currentPage Текущая страница
	 * @param string $orderBy Поле для сортировки
	 * @return array Массив с обработанными параметрами
	 */
	private function _getFormatted(string $conditions, $onPage, $currentPage, $orderBy)
	{
		$onPage = Utils::trim($onPage);
		if ($onPage == '')
			$onPage = 100;
		$currentPage = Utils::trim($currentPage);
		$orderBy = Utils::trim($orderBy);
		if ($orderBy != '')
			$orderBy = "order by $orderBy";
		$currentPage = ((int) $currentPage <= 0) ? 1 : (int) $currentPage;
		$limit = ($currentPage - 1) * $onPage;
		$conditions = (is_numeric($conditions)) ? "Id='{$conditions}'" : $conditions;
		return [
			'conditions' => $conditions,
			'onPage' => $onPage,
			'currentPage' => $currentPage,
			'orderBy' => $orderBy,
			'limit' => "limit {$limit},{$onPage}"
		];
	}
	/**
	 * Пагинация для организации постраничного вывода
	 *
	 * @param int $onPage Количество строк на странице
	 * @param int $currentPage Текущая страница
	 * @return array Массив с данными пагинации
	 */
	private function _getPaginator($onPage, $currentPage)
	{
		$currentPage = ($currentPage <= 0) ? 1 : $currentPage;
		$this->count = 0;
		$dataPages = 1;
		if (!empty($this->sqlCounter)) {
			$res = Connect::$instance->fetch($this->sqlCounter, $this->params);
		} else {
			return [];
		}
		if (!empty($res[0]['Co'])) {
			$dataPages = ceil($res[0]['Co'] / $onPage);
			$this->count = $res[0]['Co'];
		}
		$arr = [];
		$arr['current'] = $currentPage;
		for ($i = 1; $i <= $dataPages; $i++) {
			$arr['pages'][] = $i;
		}
		if ($currentPage < $dataPages) {
			$arr['next'] = $currentPage + 1;
		}
		if ($currentPage > 1) {
			$arr['prev'] = $currentPage - 1;
		}
		if (isset($arr['next']) && $dataPages < $arr['next']) {
			unset($arr['next']);
		}
		if (isset($arr['prev']) && $dataPages < $arr['prev']) {
			$arr['prev'] = $dataPages;
		}
		if ($dataPages == 1) {
			return [];
		}
		$arr['count'] = count($arr['pages']);

		$visiblePages = 5;
		$start = max(1, $currentPage - $visiblePages);
		$end = min($arr['count'], $currentPage + $visiblePages);
		if ($start > 1) {
			$arr['hasStartDots'] = true;
		}
		if ($end < $arr['count']) {
			$arr['hasEndDots'] = true;
		}
		$arr['pagesVisible'] = range($start, $end);
		return $arr;
	}
	/**
	 * Получение схемы полей таблицы
	 *
	 * @param int $renew Флаг для обновления схемы
	 * @return array Массив с данными схемы таблицы
	 */
	public function getScheme($renew = 0)
	{
		if ($this->scheme == null || $renew == 1) {
			$fields = $this->fields;
			$orderBy = "t.Priority";
			if (!empty($fields)) {
				$ids = "'" . str_replace(",", "','", $fields) . "'";
				$fields = " and t.Field in ($ids)";
				$orderBy = "field(t.Field,$ids)";
			}
			$sql = "select
			t.Field,t.Id,t.TableName,t.Name,t.Description,t.Priority,t.Required,t.Type,t.CreateMode,t.ModifyMode,t.IsHidden,t.FGroup
			from s_ConfigFields as t
			where t.TableName = '{$this->tableName}' $fields order by $orderBy";
			$res = Connect::$instance->fetch($sql, [], 'group');
			if (count($res) == 0) {
				http_response_code(404);
				Utils::debug("Указанной таблицы {$this->tableName} не существует", 1);
			}
			$this->scheme = $res;
		}
		return $this->scheme;
	}
	/**
	 * Установка $this->fields
	 * Перечисление полей
	 *
	 * @param string $value Перечисление полей
	 */
	public function setFields($value)
	{
		$this->fields = $value;
	}
	/**
	 * Установка $this->concat
	 * Перечисление дополнительных полей (с функциями, например)
	 *
	 * @param string $value Перечисление дополнительных полей
	 */
	public function setConcat($value)
	{
		$this->concat = $value;
	}

	/**
	 * Установка $this->join
	 * Компоновка left outer join для сложных запросов
	 *
	 * @param string $value Компоновка left outer join
	 */
	public function setJoin($value)
	{
		$this->join = $value;
	}
	/**
	 * Установка параметров для подготовленных запросов
	 *
	 * @param array $params Параметры для подготовленных запросов
	 * @return bool Возвращает true в случае успешной установки параметров
	 */
	public function setParams(array $params): bool
	{
		$this->params = $params;
		return true;
	}
	/**
	 * Установка $this->group
	 * Указание столбца для группировки
	 *
	 * @param string $value Столбец для группировки
	 * @return bool Возвращает true в случае успешной установки группировки
	 */
	public function setGroup($value): bool
	{
		$this->group = $value;
		return true;
	}
	/**
	 * Установка $this->having
	 * Указание условий HAVING для SQL-запроса
	 *
	 * @param string $value Условия HAVING
	 * @return bool Возвращает true в случае успешной установки условий HAVING
	 */
	public function setHaving($value): bool
	{
		$this->having = $value;
		return true;
	}
	/**
	 * Обновление строки
	 *
	 * @param int $id Id строки
	 * @param array $row Массив столбцов и новых значений
	 * @param array $settings Дополнительные настройки
	 * @return mixed Результат выполнения запроса
	 */
	public function set(int $id, array $row, array $settings = [])
	{
		$arr = Connect::$instance->prepare($row, $settings);
		$this->sql = "update {$this->tableName} set {$arr['update']} where Id = '{$id}'";
		return Connect::$instance->query($this->sql, $arr['row']);
	}
	/**
	 * Добавление строки
	 *
	 * @param array $row Массив данных для добавления
	 * @param int $insertOnly Флаг для добавления только строки
	 * @return int Id добавленной строки
	 * @throws \RuntimeException Если обязательное поле пустое
	 */
	public function add(array $row = [], int $insertOnly = 0): int
	{
		$scheme = $this->getScheme();
		$insert = [];
		$update = [];
		foreach ($scheme as $key => $value) {
			if ($value[0]['Required'] == 1) {
				if (empty($row[$key])) {
					throw new \RuntimeException("Field \"$key\" is empty");
				}
				$insert[$key] = $row[$key];
			}
			if (isset($row[$key])) {
				$update[$key] = $row[$key];
			}
		}
		$insert['Priority'] = 0;
		$settings = [
			'Priority' => ['fn' => "(select round((max(Priority)+5)/5)*5 from {$this->tableName} as tb)"]
		];
		$prepare = Connect::$instance->prepare($insert, $settings);
		unset($prepare['row']['Priority']);
		$sql = "insert ignore into {$this->tableName} {$prepare['insert']}";
		Connect::$instance->query($sql, $prepare['row']);
		$id = Connect::$db->lastInsertId();
		if ($insertOnly == 1) {
			return $id;
		}
		if ((int) $id != 0) {
			unset($update['Id']);
			if (empty($update['Priority'])) {
				unset($update['Priority']);
			}
			$prepare = Connect::$instance->prepare($update);
			$sql = "update {$this->tableName} set {$prepare['update']} where Id='{$id}'";
			Connect::$instance->query($sql, $prepare['row']);
		}
		return $id;
	}

	/**
	 * Удаление строки из таблицы и связанных файлов
	 *
	 * @param int $id Идентификатор строки для удаления
	 * @return bool Возвращает true в случае успешного удаления
	 */
	public function remove(int $id): bool
	{
		$sql = "delete from {$this->tableName} where Id = '{$id}'";
		Connect::$instance->query($sql);
		$sql = "delete from s_Files where TableName='{$this->tableName}' and TableNameId='{$id}'";
		Connect::$instance->query($sql);
		return true;
	}
}