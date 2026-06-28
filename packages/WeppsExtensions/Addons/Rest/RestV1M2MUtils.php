<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Memcached;
use WeppsCore\Utils;
use WeppsAdmin\Lists\Lists;


/**
 * RestV1M2MUtils - вспомогательный класс для CRUD операций M2M API
 *
 * Один экземпляр — одна таблица (tableName передаётся в конструктор).
 * Инстанцируется через RestV1M2M::getUtils(tableName) с кэшированием по имени таблицы.
 *
 * Обрабатывает:
 * - CRUD: fetch(), item(), add(), set(), remove()
 * - Пакетные операции: addBatch(), setBatch() с before/after callbacks
 * - Callbacks для пакетных операций: setBefore(), setAfter(), handlePagination()
 * - Проверку дублей по уникальному полю: checkDuplicate()
 * - Правила валидации из s_ConfigFields: getFieldRules()
 * - Маппинг camelCase API → PascalCase БД: mapApiToDbFields() / getReverseMap()
 * - Авто-decode JSON-полей в ответах: decodeJsonFields() / getJsonFields()
 * - Файлы из s_Files: getFiles(), handleFileCreate(), handleFileUpdate()
 * - Построение W_Attributes из свойств: buildAttributesFromPropertiesValues()
 * - Обновление поискового индекса: updateSearchIndex()
 */
class RestV1M2MUtils
{
	/**
	 * Кэш JSON-полей таблицы. Используется при ЧТЕНИИ (fetch/item) для автоматического
	 * json_decode значений в ответе. Ключ — apiMapping (camelCase), значение — true.
	 * Заполняется лениво из s_ConfigFields (ApiFieldType='json' или Type LIKE '%json%').
	 * Дополнительно кэшируется в Memcached на 1 час.
	 * @var array<string,bool>|null null = ещё не загружен
	 */
	private ?array $jsonFieldsCache = null;

	/**
	 * Кэш обратного маппинга. Используется при ЗАПИСИ (add/set/checkDuplicate) для
	 * преобразования camelCase-ключей входящего API-запроса в PascalCase-поля БД.
	 * Пример: ['color' => 'Field1', 'productname' => 'Name', 'name' => 'Name'].
	 * Заполняется лениво из s_ConfigFields (Field + ApiMapping) одним SQL на экземпляр.
	 * @var array<string,string>|null null = ещё не загружен
	 */
	private ?array $reverseMapCache = null;

	/**
	 * Кэш уникальных полей таблицы для checkDuplicate.
	 * Заполняется вместе с reverseMapCache из того же SQL-запроса (IsUnique=1).
	 * Формат: [DbField => apiName], например ['Alias' => 'alias', 'Login' => 'login'].
	 * @var array<string,string>|null null = ещё не загружен
	 */
	private ?array $uniqueFieldsCache = null;

	/**
	 * Имя таблицы с которой работает данный экземпляр
	 * @var string
	 */
	private string $tableName;

	/**
	 * Список полей для выборки через Data::setFields()
	 * @var string|null
	 */
	private ?string $fields = null;

	private ?array $params = null;

	/**
	 * Callback вызываемый перед пакетной вставкой/обновлением (addBatch, setBatch).
	 * Получает (items: array, tableName: string) где items - массив отфильтрованных записей.
	 * Может модифицировать items и вернуть обновленный массив, или вернуть null.
	 * Выполняется ВНУТРИ транзакции; исключение откатит все изменения.
	 * @var mixed
	 */
	private mixed $beforeCallback = null;

	/**
	 * Callback вызываемый после пакетной вставки/обновления (addBatch, setBatch).
	 * Получает (results: array, tableName: string) где results - массив результатов операций.
	 * Может выполнять дополнительную обработку (логирование, soft-delete и т.д.).
	 * Выполняется ВНУТРИ транзакции перед COMMIT; исключение откатит все.
	 * @var mixed
	 */
	private mixed $afterCallback = null;

	public function __construct(string $tableName)
	{
		$this->tableName = $tableName;
		$this->jsonFieldsCache = null;
		$this->reverseMapCache = null;
		$this->uniqueFieldsCache = null;
	}

	/**
	 * Установить callback вызываемый перед пакетной вставкой/обновлением (addBatch, setBatch).
	 * Callback получает (items: array, tableName: string) где items - массив отфильтрованных записей.
	 * Может модифицировать items и вернуть обновленный массив, или вернуть null.
	 * Выполняется ВНУТРИ транзакции; исключение откатит все изменения.
	 * 
	 * @param callable $callback fn(array $items, string $tableName): array|null
	 * @return self для цепочки вызовов
	 */
	public function setBefore(callable $callback): self
	{
		$this->beforeCallback = $callback;
		return $this;
	}

	/**
	 * Установить callback вызываемый после пакетной вставки/обновления (addBatch, setBatch).
	 * Callback получает (results: array, tableName: string) где results - массив результатов операций.
	 * Может выполнять дополнительную обработку (логирование, soft-delete и т.д.).
	 * Выполняется ВНУТРИ транзакции перед COMMIT; исключение откатит все.
	 * 
	 * @param callable $callback fn(array $results, string $tableName): void
	 * @return self для цепочки вызовов
	 */
	public function setAfter(callable $callback): self
	{
		$this->afterCallback = $callback;
		return $this;
	}

	/**
	 * Создать callbacks для обработки пакетных операций с учётом пагинации.
	 * 
	 * Поддерживает паттерн:
	 * - Before callback на первой странице: подготовка перед первой партией (например, маркировка кандидатов на удаление)
	 * - After callback на последней странице: финализация после последней партии (например, финализация скрытия)
	 * 
	 * Если pagination не содержит 'page' и 'count', callbacks вернут null.
	 * 
	 * @param array|null $pagination {page: int, count: int} или null
	 * @return array {before: callable|null, after: callable|null}
	 */
	public function handlePagination(?array $pagination): array
	{
		$currentPage = (int) ($pagination['page'] ?? 0);
		$totalPages = (int) ($pagination['count'] ?? 0);

		// Если pagination не содержит нужных данных, вернуть пустые callbacks
		if ($currentPage === 0 || $totalPages === 0) {
			return ['before' => null, 'after' => null];
		}

		// Before callback: вызывается на первой странице
		$beforeCallback = $currentPage === 1
			? function(array $items, string $tableName) {
				// На первой странице пакета
				// Пример: маркировка кандидатов на скрытие
				Connect::$instance->query(
					"UPDATE {$tableName} SET IsHiddenCandidate = 1 WHERE IsHiddenCandidate = 0"
				);
				return $items;
			}
			: null;

		// After callback: вызывается на последней странице
		$afterCallback = $currentPage === $totalPages
			? function(array $results, string $tableName) {
				// На последней странице пакета
				// Пример: финализация скрытия
				Connect::$instance->query(
					"UPDATE {$tableName} SET IsHidden = IsHiddenCandidate WHERE IsHiddenCandidate = 1"
				);
			}
			: null;

		return [
			'before' => $beforeCallback,
			'after' => $afterCallback,
		];
	}


	/**
	 * Получить список записей
	 * 
	 * @param array $query - параметры: page, limit, search
	 * @param string|null $conditions - дополнительные условия WHERE (без WHERE)
	 * @return array - {status, message, data, pagination}
	 */
	public function fetch(array $query, ?string $conditions = null): array
	{

		if ($conditions !== null) {
			$conditions = $conditions . 'AND ';
		} else {
			$conditions = '';
		}

		$page = (int) ($query['page'] ?? 1);
		$limit = (int) ($query['limit'] ?? 20);
		$id = isset($query['id']) ? (int) $query['id'] : null;
		// $search = $query['search'] ?? '';

		$conditions .= 't.Id!=0';
		$skipPagination = false; // флаг для пропуска пагинации при запросе по ID
		if ($id !== null && $id > 0) {
			$conditions .= 't.Id = ' . $id;
			$limit = 1;
			$page = 0; // передаём 0 чтобы пропустить пагинацию
			$skipPagination = true;
		}
		try {
			$data = new Data($this->tableName, ['useApiMapping' => true]);
			if ($this->fields !== null) {
				$data->setFields($this->fields);
			}
			if ($this->params !== null) {
				$data->setParams($this->params);
			}
			$result = $data->fetch($conditions, $limit, $page);
			$result = $this->decodeJsonFields($result);
			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $result ?: [],
				'pagination' => [
					'count' => $skipPagination ? count($result) : $data->paginator['count']??1,
					'limit' => $limit,
					'page' => $skipPagination ? 1 : $page,
				],
			];
		} catch (\Exception $e) {
			return [
				'status' => 500,
				'message' => $e->getMessage(),
				'data' => null,
			];
		} finally {
			$this->fields = null;
		}
	}

	/**
	 * Получить одну запись
	 * 
	 * @param int|string $id - ID записи
	 * @param string|null $fields - список полей для вывода; если не указан, используется установленное через setFields()
	 * @return array - {status, message, data}
	 */
	public function item($id, ?string $fields = null): array
	{
		$response = $this->fetch(['id' => $id, 'page' => 1, 'limit' => 1], $fields);

		if ($response['status'] !== 200) {
			return $response;
		}

		if (empty($response['data'])) {
			return [
				'status' => 404,
				'message' => 'Not found',
				'data' => null,
			];
		}

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $response['data'][0],
		];
	}

	/**
	 * Добавить одну запись.
	 *
	 * @param array $record - плоский массив полей
	 * @return array - {status, message, data}
	 */
	public function add(array $record): array
	{
		$dupe = $this->checkDuplicate([$record], false);
		if ($dupe[0] ?? null) {
			return $dupe[0];
		}

		try {
			$mapped = $this->mapApiToDbFields($record);
			$model = new Data($this->tableName);
			$id = $model->add($mapped);

			if ((int) $id === 0) {
				return ['status' => 409, 'message' => 'Duplicate key or constraint violation', 'data' => null];
			}

			return ['status' => 201, 'message' => 'Created', 'data' => ['id' => $id]];
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	/**
	 * Пакетная вставка записей.
	 *
	 * @param array $records [index => плоский массив полей]
	 * @return array          [index => {status, data}]
	 */
	public function addBatch(array $records): array
	{
		$results = [];
		$toInsert = [];

		$dupeErrors = $this->checkDuplicate($records, false);
		foreach ($records as $index => $record) {
			if ($dupeErrors[$index] ?? null) {
				$results[$index] = $dupeErrors[$index];
				continue;
			}
			$toInsert[$index] = $record;
		}

		if (empty($toInsert)) {
			return $results;
		}

		try {
			$batch = Connect::$instance->transaction(
				function($args) {
					// Before callback ВНУТРИ транзакции
					if ($this->beforeCallback !== null) {
						$callback = $this->beforeCallback;
						$args['items'] = $callback($args['items'], $this->tableName) ?? $args['items'];
					}

					// Выполнить пакетную вставку
					$results = $this->executeBatchInsert($args['items']);

					// After callback ВНУТРИ транзакции (перед коммитом)
					if ($this->afterCallback !== null) {
						$callback = $this->afterCallback;
						$callback($results, $this->tableName);
					}
					return $results;
				},
				['items' => $toInsert]
			);
			$results += $batch;
		} catch (\Exception $e) {
			foreach ($toInsert as $index => $_) {
				$results[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
			}
		}

		return $results;
	}

	/**
	 * Обновить одну запись.
	 *
	 * @param int $id   ID записи
	 * @param array $data плоский массив полей для обновления
	 * @return array - {status, message, data}
	 */
	public function set(int $id, array $data): array
	{
		$dupe = $this->checkDuplicate([array_merge($data, ['id' => $id])], true);
		if ($dupe[0] ?? null) {
			return $dupe[0];
		}

		try {
			$mapped = $this->mapApiToDbFields($data);
			$model = new Data($this->tableName);
			$model->set($id, $mapped);

			return ['status' => 200, 'message' => 'Updated', 'data' => ['id' => $id]];
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	/**
	 * Пакетное обновление записей.
	 *
	 * @param array $items [index => плоский массив полей включая 'id']
	 * @return array        [index => {status, data}]
	 */
	public function setBatch(array $items): array
	{
		$results = [];
		$toUpdate = [];

		foreach ($items as $index => $record) {
			if (!isset($record['id']) && !isset($record['Id'])) {
				$results[$index] = ['status' => 400, 'message' => 'id required', 'data' => null];
				continue;
			}
			$toUpdate[$index] = $record;
		}

		if (!empty($toUpdate)) {
			$dupeErrors = $this->checkDuplicate($toUpdate, true);
			foreach ($toUpdate as $index => $_) {
				if ($dupeErrors[$index] ?? null) {
					$results[$index] = $dupeErrors[$index];
					unset($toUpdate[$index]);
				}
			}
		}

		if (empty($toUpdate)) {
			return $results;
		}

		try {
			$batch = Connect::$instance->transaction(
				function($args) {
					// Before callback ВНУТРИ транзакции
					if ($this->beforeCallback !== null) {
						$callback = $this->beforeCallback;
						$args['items'] = $callback($args['items'], $this->tableName) ?? $args['items'];
					}
					$results = $this->executeBatchUpdate($args['items']);

					// After callback ВНУТРИ транзакции (перед коммитом)
					if ($this->afterCallback !== null) {
						$callback = $this->afterCallback;
						$callback($results, $this->tableName);
					}
					return $results;
				},
				['items' => $toUpdate]
			);
			$results += $batch;
		} catch (\Exception $e) {
			foreach ($toUpdate as $index => $_) {
				$results[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
			}
		}

		return $results;
	}


	/**
	 * Удалить записи (одну или пакет).
	 *
	 * @param array  $ids ID записей
	 * @param string $filterTableName опционально: фильтр по TableName (для s_Files)
	 * @return array - {status, message, data} для одного или {status, data: [{...}]} для batch
	 */
	public function remove(array $ids, string $filterTableName = ''): array
	{
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'No IDs provided', 'data' => null];
		}

		// Одиночное удаление
		if (count($ids) === 1) {
			$id = $ids[0];
			try {
				if (!empty($filterTableName)) {
					// Для s_Files с фильтром по TableName
					$existing = Connect::$instance->fetch(
						"SELECT Id FROM {$this->tableName} WHERE Id = ? AND TableName = ?",
						[$id, $filterTableName]
					);
					if (empty($existing)) {
						return ['status' => 404, 'message' => 'Record not found', 'data' => ['id' => $id]];
					}
					$result = Connect::$instance->query(
						"DELETE FROM {$this->tableName} WHERE Id = ? AND TableName = ?",
						[$id, $filterTableName]
					);
				} else {
					// Обычное удаление — сначала проверяем существование
					$existing = Connect::$instance->fetch(
						"SELECT Id FROM {$this->tableName} WHERE Id = ?",
						[$id]
					);
					if (empty($existing)) {
						return ['status' => 404, 'message' => 'Already deleted or not found', 'data' => ['id' => $id]];
					}
					$model = new Data($this->tableName);
					$model->remove($id);
					$result = 1;
				}
				return $result > 0
					? ['status' => 200, 'message' => 'Deleted', 'data' => ['id' => $id]]
					: ['status' => 400, 'message' => 'Failed to delete', 'data' => ['id' => $id]];
			} catch (\Exception $e) {
				return ['status' => 400, 'message' => $e->getMessage(), 'data' => ['id' => $id]];
			}
		}

		// Batch удаление — получить все существующие IDs одним запросом
		$results = [];

		// Получить все существующие IDs одним запросом перед циклом
		$in = Connect::$instance->in($ids);
		if (!empty($filterTableName)) {
			// Для s_Files с фильтром
			$existingRows = Connect::$instance->fetch(
				"SELECT Id FROM {$this->tableName} WHERE Id IN ($in) AND TableName = ?",
				array_merge($ids, [$filterTableName])
			);
		} else {
			// Для обычных таблиц
			$existingRows = Connect::$instance->fetch(
				"SELECT Id FROM {$this->tableName} WHERE Id IN ($in)",
				$ids
			);
		}

		// Преобразовать в ассоциативный массив для быстрого поиска O(1)
		$existingIds = array_flip(array_column($existingRows, 'Id'));

		foreach ($ids as $index => $id) {
			try {
				// Проверяем наличие в уже полученном списке
				if (!isset($existingIds[$id])) {
					$results[$index] = ['status' => 404, 'message' => 'Already deleted or not found', 'data' => ['id' => $id]];
					continue;
				}

				// Удаляем запись
				if (!empty($filterTableName)) {
					$result = Connect::$instance->query(
						"DELETE FROM {$this->tableName} WHERE Id = ? AND TableName = ?",
						[$id, $filterTableName]
					);
				} else {
					$model = new Data($this->tableName);
					$model->remove($id);
					$result = 1;
				}

				$results[$index] = $result > 0
					? ['status' => 200, 'message' => 'Deleted', 'data' => ['id' => $id]]
					: ['status' => 400, 'message' => 'Failed to delete', 'data' => ['id' => $id]];
			} catch (\Exception $e) {
				$results[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => ['id' => $id]];
			}
		}

		return ['status' => 207, 'message' => 'Multi-Status', 'data' => $results];
	}

	// =========================================================================
	// BATCH EXECUTION (private)
	// =========================================================================

	private function executeBatchInsert(array $items): array
	{
		$results = [];
		$groups = [];

		foreach ($items as $index => $record) {
			$mapped = $this->mapApiToDbFields($record);
			unset($mapped['Id'], $mapped['id']);
			$hasPriority = array_key_exists('Priority', $mapped);
			ksort($mapped);
			$sig = implode(',', array_keys($mapped));
			$groups[$sig][] = ['index' => $index, 'data' => $mapped, 'hasPriority' => $hasPriority];
		}

		foreach ($groups as $group) {
			$settings = [];
			if (empty($group[0]['hasPriority'])) {
				$settings['Priority'] = [
					'fn' => "(select round((max(Priority)+5)/5)*5 from {$this->tableName} as tb)",
					'rm' => true,
				];
			}
			$prepared = Connect::$instance->prepare($group[0]['data'], $settings);
			$sql = "INSERT IGNORE INTO {$this->tableName} {$prepared['insert']}";
			$sth = Connect::$db->prepare($sql);

			foreach ($group as $item) {
				$params = $item['data'];
				if (!empty($settings['Priority']['rm'])) {
					unset($params['Priority']);
				}
				try {
					$sth->execute($params);
					$id = (int) Connect::$db->lastInsertId();
					$results[$item['index']] = $id === 0
						? ['status' => 409, 'message' => 'Duplicate insert', 'data' => null]
						: ['status' => 201, 'message' => 'Created', 'data' => ['id' => $id]];
				} catch (\Exception $e) {
					$results[$item['index']] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
				}
			}
		}

		return $results;
	}

	private function executeBatchUpdate(array $items): array
	{
		$results = [];
		$groups = [];

		foreach ($items as $index => $record) {
			$id = (int) ($record['id'] ?? $record['Id'] ?? 0);
			$data = $record;
			unset($data['id'], $data['Id']);
			$mapped = $this->mapApiToDbFields($data);
			unset($mapped['Id'], $mapped['id']);
			ksort($mapped);

			if (empty($mapped)) {
				$results[$index] = ['status' => 400, 'message' => 'Nothing to update', 'data' => null];
				continue;
			}

			$sig = implode(',', array_keys($mapped));
			$groups[$sig][] = ['index' => $index, 'id' => $id, 'data' => $mapped];
		}

		foreach ($groups as $group) {
			$prepared = Connect::$instance->prepare($group[0]['data']);
			$sql = "UPDATE {$this->tableName} SET {$prepared['update']} WHERE Id = :Id";
			$sth = Connect::$db->prepare($sql);

			foreach ($group as $item) {
				$params = $item['data'];
				$params['Id'] = $item['id'];
				try {
					$sth->execute($params);
					$results[$item['index']] = ['status' => 200, 'message' => 'Updated', 'data' => ['id' => $item['id']]];
				} catch (\Exception $e) {
					$results[$item['index']] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
				}
			}
		}

		return $results;
	}

	/**
	 * Проверить конфликт уникального поля для набора записей.
	 * Всегда возвращает [index => null | error array].
	 *
	 * @param array $records  [index => плоский массив полей]
	 * @param bool  $forUpdate true = исключить собственные ID записей из проверки (PUT)
	 * @return array           [index => null | {status:409, ...}]
	 */
	public function checkDuplicate(array $records, bool $forUpdate): array
	{
		$uniqueFields = $this->getUniqueFields(); // [DbField => apiName]
		$result = array_fill_keys(array_keys($records), null);

		if (empty($uniqueFields)) {
			return $result;
		}

		$updateIds = [];
		if ($forUpdate) {
			foreach ($records as $item) {
				if (!is_array($item))
					continue;
				$id = (int) ($item['id'] ?? $item['Id'] ?? 0);
				if ($id > 0)
					$updateIds[] = $id;
			}
		}

		foreach ($uniqueFields as $dbField => $apiName) {
			$uniqueValues = [];
			$valueIndexMap = [];

			foreach ($records as $index => $item) {
				if (!is_array($item) || ($result[$index] ?? null))
					continue;
				$value = $this->extractFieldValue($item, $dbField);
				if ($value !== null && $value !== '') {
					$lower = strtolower((string) $value);
					$uniqueValues[] = $lower;
					$valueIndexMap[$lower][] = $index;
				}
			}

			if (empty($uniqueValues))
				continue;

			$inValues = Connect::$instance->in($uniqueValues);
			$params = $uniqueValues;
			$excludeClause = '';

			if ($forUpdate && !empty($updateIds)) {
				$inIds = Connect::$instance->in($updateIds);
				$excludeClause = " AND Id NOT IN ($inIds)";
				$params = array_merge($params, $updateIds);
			}

			try {
				$rows = Connect::$instance->fetch(
					"SELECT Id, {$dbField} FROM {$this->tableName} WHERE LOWER({$dbField}) IN ($inValues){$excludeClause}",
					$params
				);
			} catch (\Exception $e) {
				continue;
			}

			foreach ($rows as $row) {
				$lower = strtolower((string) $row[$dbField]);
				foreach ($valueIndexMap[$lower] ?? [] as $index) {
					if ($result[$index] !== null)
						continue;
					$original = $this->extractFieldValue($records[$index], $dbField) ?? $lower;
					$result[$index] = [
						'status' => 409,
						'message' => "Duplicate {$apiName}: {$original}",
						'data' => ['id' => (int) $row['Id']],
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Извлечь значение поля из данных (с учетом регистра)
	 * 
	 * @param array $data - данные
	 * @param string $fieldName - имя поля
	 * @return mixed - значение поля или null
	 */
	private function extractFieldValue(array $data, string $fieldName): mixed
	{
		foreach ($data as $key => $val) {
			if (strtolower($key) === strtolower($fieldName)) {
				return $val;
			}
		}
		return null;
	}

	/**
	 * Конвертировать данные из camelCase API ключей в PascalCase DB ключи
	 * используя ApiMapping из s_ConfigFields
	 *
	 * @param array $data - данные с camelCase ключами
	 * @return array - данные с PascalCase ключами
	 */
	public function mapApiToDbFields(array $data): array
	{
		try {
			$reverseMap = $this->getReverseMap();

			$mapped = [];
			foreach ($data as $key => $value) {
				$dbKey = $reverseMap[strtolower($key)] ?? null;
				if ($dbKey !== null) {
					$mapped[$dbKey] = $value;
				}
			}
			return $mapped;
		} catch (\Exception $e) {
			// Если маппинг не удался, вернуть данные как есть
			return $data;
		}
	}

	/**
	 * Получить обратный маппинг apiKey(lowercase) => dbField для таблицы.
	 * Результат кэшируется в памяти на время запроса — один SQL на таблицу,
	 * сколько бы элементов в batch не было.
	 *
	 * @return array<string,string>
	 */
	private function getReverseMap(): array
	{
		if ($this->reverseMapCache !== null) {
			return $this->reverseMapCache;
		}

		$sql = "SELECT `Field`, `ApiMapping`, `IsUnique` FROM s_ConfigFields WHERE `TableName` = ?";
		$result = Connect::$instance->fetch($sql, [$this->tableName]);

		$reverseMap = [];
		$uniqueFields = [];
		foreach ($result as $row) {
			$dbField = $row['Field'];
			$apiKey = $row['ApiMapping'] ?? null;
			if ($apiKey) {
				$reverseMap[strtolower($apiKey)] = $dbField;
			}
			// Fallback: lowercased DB field name -> DB field
			$reverseMap[strtolower($dbField)] = $dbField;

			if (!empty($row['IsUnique'])) {
				$uniqueFields[$dbField] = $apiKey ? strtolower($apiKey) : strtolower($dbField);
			}
		}

		$this->reverseMapCache = $reverseMap;
		$this->uniqueFieldsCache = $uniqueFields;
		return $reverseMap;
	}

	/**
	 * Получить уникальные поля таблицы из s_ConfigFields (IsUnique=1).
	 * Кэшируется вместе с reverseMapCache — дополнительный SQL не нужен.
	 *
	 * @return array<string,string> [DbField => apiName]
	 */
	private function getUniqueFields(): array
	{
		if ($this->uniqueFieldsCache === null) {
			$this->getReverseMap();
		}
		return $this->uniqueFieldsCache ?? [];
	}

	/**
	 * Получить валидационные правила из s_ConfigFields
	 * 
	 * Читает таблицу s_ConfigFields и строит правила валидации на основе:
	 * - ApiFieldType (int, string, email, date, float, guid)
	 * - Required (обязательность поля)
	 * 
	 * @return array - ассоциативный массив {fieldName => {type, required}}
	 * @example ['id' => ['type' => 'int', 'required' => true], 'email' => ['type' => 'email', 'required' => false]]
	 */
	public function getFieldRules(): array
	{
		$cacheKey = 'api_validation_rules_' . $this->tableName;

		// Попытка получить из кэша (системный кэш - всегда включен)
		$memcached = new Memcached('auto', true);
		$cachedRules = $memcached->get($cacheKey);
		if ($cachedRules !== null) {
			return $cachedRules;
		}

		try {
			// Получить все поля для таблицы из s_ConfigFields
			$sql = "SELECT `Field`, `ApiMapping`, `ApiFieldType`, `Required` FROM s_ConfigFields WHERE `TableName` = ? ORDER BY `Field` ASC";
			$result = Connect::$instance->fetch($sql, [$this->tableName]);
			$rules = [];
			foreach ($result as $field) {
				$fieldName = $field['ApiMapping'] ?? null;
				$apiType = $field['ApiFieldType'] ?? 'string';
				$required = (int) ($field['Required'] ?? 0);

				if ($fieldName) {
					$rules[$fieldName] = [
						'type' => $apiType ?: 'string',
						'required' => $required === 1,
					];
				}
			}

			// Кэшировать на 1 час (3600 сек)
			$memcached = new Memcached('auto', true);
			$memcached->set($cacheKey, $rules, 3600);

			return $rules;
		} catch (\Exception $e) {
			// Если ошибка при чтении БД, возвращаем пустой массив
			// (валидация будет пропущена)
			return [];
		}
	}

	/**
	 * Установить поля для выборки через Data::setFields()
	 *
	 * @param string $fields Перечисление полей через запятую
	 * @return $this
	 */
	public function setFields(string $fields): self
	{
		$this->fields = $fields;
		return $this;
	}

	/**
	 * Установить параметры для выборки через Data::fetch()
	 *
	 * @param array $params Параметры для выборки
	 * @return $this
	 */
	public function setParams(array $params): self
	{
		$this->params = $params;
		return $this;
	}

	/**
	 * Получить файлы из s_Files по TableName и TableNameField
	 *
	 * @param string $field - значение TableNameField (например 'Images' или 'ImagesV')
	 * @param array $query - массив GET-параметров, включает goods_id, page, limit
	 * @return array
	 */
	public function getFiles(string $field, array $query): array
	{
		$url = Connect::$projectDev['protocol'] . Connect::$projectDev['host'];
		$goodsId = (int) ($query['goods_id'] ?? 0);
		$page = max(1, (int) ($query['page'] ?? 1));
		$limit = min(1000, max(1, (int) ($query['limit'] ?? 1000)));
		$offset = ($page - 1) * $limit;

		$conditions = "TableName = ? AND TableNameField = ?";
		$params = [$this->tableName, $field];
		if ($goodsId > 0) {
			$conditions .= " AND TableNameId = ?";
			$params[] = $goodsId;
		}

		$res = Connect::$instance->fetch(
			"SELECT Id, TableNameId as goods_id, Name, InnerName, CONCAT('{$url}', FileUrl) as FileUrl, APIFilter `Filter` FROM s_Files 
			 WHERE {$conditions}
			 ORDER BY Priority 
			 LIMIT {$offset}, {$limit}",
			$params
		);

		$countRes = Connect::$instance->fetch(
			"SELECT COUNT(*) as total FROM s_Files WHERE {$conditions}",
			$params
		);
		$total = (int) ($countRes[0]['total'] ?? 0);

		if (empty($res) && $total === 0) {
			return ['status' => 404, 'message' => 'Images not found', 'data' => null];
		}

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $res ?? [],
			'pagination' => [
				'count' => $total,
				'limit' => $limit,
				'page' => $page,
			],
		];
	}

	/**
	 * Декодирует JSON-поля в результатах на основе схемы Data
	 *	
	 * @param array $rows Строки результата
	 * @return array
	 */
	private function decodeJsonFields(array $rows): array
	{
		if (empty($rows)) {
			return $rows;
		}

		$fields = $this->getJsonFields();
		if (empty($fields)) {
			return $rows;
		}
		foreach ($rows as &$row) {
			
			foreach (array_keys($fields) as $fieldName) {
				if (!isset($row[$fieldName]) || !is_string($row[$fieldName])) {
					continue;
				}

				$decoded = json_decode($row[$fieldName], true);
				if (json_last_error() === JSON_ERROR_NONE) {
					$row[$fieldName] = $decoded;
				}
			}
		}

		return $rows;
	}

	/**
	 * Получить список полей таблицы, которые нужно декодировать как JSON
	 *
	 * @return array<string,bool>
	 */
	private function getJsonFields(): array
	{
		if ($this->jsonFieldsCache !== null) {
			return $this->jsonFieldsCache;
		}

		$cacheKey = 'json_fields_' . $this->tableName;
		$memcached = new Memcached('auto', true);
		$cached = $memcached->get($cacheKey);
		if ($cached !== null) {
			$this->jsonFieldsCache = $cached;
			return $cached;
		}

		try {
			// Получить JSON поля напрямую из s_ConfigFields
			$sql = "SELECT Field, ApiFieldType, ApiMapping, Type FROM s_ConfigFields WHERE TableName = ? AND (ApiFieldType = 'json' OR Type LIKE '%json%')";
			$result = Connect::$instance->fetch($sql, [$this->tableName]);

			$jsonFields = [];
			foreach ($result as $field) {
				$fieldName = $field['Field'] ?? null;
				$apiMapping = $field['ApiMapping'] ?: $fieldName;
				if ($fieldName && $apiMapping) {
					$jsonFields[$apiMapping] = true;
				}
			}

			// Кэшировать на 1 час
			$memcached = new Memcached('auto', true);
			$memcached->set($cacheKey, $jsonFields, 3600);
			$this->jsonFieldsCache = $jsonFields;

			return $jsonFields;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Преобразует массив свойств в структуру W_Attributes для API
	 * 
	 * Входные данные от buildAttributesForProducts: [PropertyId => [rows для этого свойства]]
	 * Возвращает отформатированный массив W_Attributes для всех свойств товара
	 * 
	 * @param array|null $propertiesData [PropertyId => [rows]] структура из buildAttributesForProducts
	 * @return array|null Отформатированный массив W_Attributes или null
	 */
	public function buildAttributesFromPropertiesValues(?array $propertiesData): ?array
	{
		if (empty($propertiesData)) {
			return null;
		}

		// $propertiesData имеет структуру [PropertyId => [row1, row2, ...]]
		// Преобразуем в формат W_Attributes
		$attributes = [];

		foreach ($propertiesData as $propId => $rows) {
			if (!is_array($rows) || empty($rows)) {
				continue;
			}

			// Берём первую строку как основание (в ней PId и PName одинаковые для всех rows)
			$firstRow = $rows[0];
			$propName = $firstRow['PName'] ?? '';

			// Собираем значения для этого свойства
			$values = array_map(
				fn($r) => ['alias' => $r['Alias'] ?? '', 'value' => $r['PValue'] ?? ''],
				$rows
			);

			$attributes[] = [
				'id' => (int) ($firstRow['PId'] ?? 0),
				'name' => $propName,
				'values' => $values,
			];
		}

		return !empty($attributes) ? $attributes : null;
	}

	/**
	 * Обновить поисковый индекс для созданных или обновлённых записей.
	 * @param array $result результаты CRUD операций
	 */
	public function updateSearchIndex(array $result): void
	{
		$items = [];
		if (isset($result['status'], $result['data'])) {
			if ($result['status'] === 207 && is_array($result['data'])) {
				$items = $result['data'];
			} else {
				$items = [$result];
			}
		} else {
			$items = $result;
		}

		$searchSql = '';
		foreach ($items as $item) {
			$status = (int) ($item['status'] ?? 0);
			if (($status === 200 || $status === 201) && isset($item['data']['id'])) {
				$searchSql .= Lists::setSearchIndex($this->tableName, $item['data']['id']);
			}
		}
		if (!empty($searchSql)) {
			Connect::$db->exec($searchSql);
		}
	}

	/**
	 * Сохранить загруженный файл (из base64 или multipart)
	 * @param string $binary - бинарные данные файла
	 * @param string $fileName - имя файла (с расширением)
	 * @param int $entityId - ID сущности (товара, вариации и т.д.)
	 * @return string|null - путь к файлу или null если ошибка
	 */
	public function saveFile(string $binary, string $fileName, int $entityId): ?string
	{
		$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if (!$ext) {
			return null;
		}

		$innerName = md5(uniqid($entityId . '_', true)) . '.' . $ext;
		$dir = __DIR__ . '/../../../pic/lists/' . $this->tableName . '/' . $entityId;

		if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
			return null;
		}

		if (file_put_contents($dir . '/' . $innerName, $binary) === false) {
			return null;
		}

		return '/pic/lists/' . $this->tableName . '/' . $entityId . '/' . $innerName;
	}

	/**
	 * Разрешить file_url из разных источников (file_url, file_base64 или multipart)
	 * @param array $data - данные с одним из источников
	 * @param int $entityId - ID сущности
	 * @return string|array - URL файла или error array
	 */
	public function resolveFileUrl(array $data, int $entityId): string|array
	{
		$fileName = $data['file_name'] ?? '';

		if (!empty($data['file_url'])) {
			return $data['file_url'];
		}

		if (!empty($data['file_base64'])) {
			if (!$fileName) {
				return ['status' => 400, 'message' => 'file_name required for base64', 'data' => null];
			}
			$binary = base64_decode($data['file_base64'], true);
			if (!$binary) {
				return ['status' => 400, 'message' => 'Invalid base64 data', 'data' => null];
			}
			$url = $this->saveFile($binary, $fileName, $entityId);
			return $url ?? ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
		}

		if (!empty($_FILES['file'])) {
			$file = $_FILES['file'];
			if ($file['error'] !== UPLOAD_ERR_OK) {
				return ['status' => 400, 'message' => 'File upload error', 'data' => null];
			}
			$binary = file_get_contents($file['tmp_name']);
			if (!$binary) {
				return ['status' => 400, 'message' => 'Failed to read file', 'data' => null];
			}
			$url = $this->saveFile($binary, $file['name'], $entityId);
			return $url ?? ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
		}

		return ['status' => 400, 'message' => 'file_url, file_base64 or multipart file required', 'data' => null];
	}

	/**
	 * Создать новый файл (запись в s_Files)
	 * @param array $data - данные с file_url/file_base64/multipart и goods_id
	 * @return array - {status, message, data}
	 */
	public function handleFileCreate(array $data): array
	{
		$entityId = (int) ($data['goods_id'] ?? $data['TableNameId'] ?? 0);
		if (!$entityId) {
			return ['status' => 400, 'message' => 'goods_id required', 'data' => null];
		}

		$fileUrl = $this->resolveFileUrl($data, $entityId);
		if (is_array($fileUrl)) {
			return $fileUrl;
		}

		$name = $data['name'] ?? $data['Name'] ?? $data['file_name'] ?? '';
		$innerName = str_replace('/pic/lists/' . $this->tableName . '/', '', $fileUrl);

		$id = Connect::$instance->insert('s_Files', [
			'TableName' => $this->tableName,
			'TableNameId' => $entityId,
			'Name' => $name,
			'InnerName' => $innerName,
			'FileUrl' => $fileUrl,
		]);

		if (!$id) {
			return ['status' => 400, 'message' => 'Failed to add image', 'data' => null];
		}

		return ['status' => 201, 'message' => 'Image added', 'data' => ['id' => $id]];
	}

	/**
	 * Обновить существующий файл
	 * @param array $data - данные с id и полями для обновления
	 * @return array - {status, message, data}
	 */
	public function handleFileUpdate(array $data): array
	{
		$imageId = (int) ($data['id'] ?? 0);
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = ?",
			[$imageId, $this->tableName]
		);
		if (empty($existing)) {
			return ['status' => 404, 'message' => 'Image not found', 'data' => null];
		}

		$updates = [];
		$params = [];

		if (isset($data['name']) || isset($data['Name'])) {
			$updates[] = 'Name = ?';
			$params[] = $data['name'] ?? $data['Name'] ?? '';
		}
		if (isset($data['inner_name']) || isset($data['InnerName'])) {
			$updates[] = 'InnerName = ?';
			$params[] = $data['inner_name'] ?? $data['InnerName'] ?? '';
		}
		if (isset($data['file_url']) || isset($data['FileUrl'])) {
			$updates[] = 'FileUrl = ?';
			$params[] = $data['file_url'] ?? $data['FileUrl'] ?? '';
		}

		if (empty($updates)) {
			return ['status' => 400, 'message' => 'Nothing to update', 'data' => null];
		}

		$params[] = $imageId;
		$result = Connect::$instance->query(
			"UPDATE s_Files SET " . implode(', ', $updates) . " WHERE Id = ?",
			$params
		);

		if ($result <= 0) {
			return ['status' => 400, 'message' => 'Failed to update image', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Image updated', 'data' => ['id' => $imageId]];
	}
}
