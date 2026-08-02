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
 * Архитектура методов:
 * - Read: fetch(), item() — выборка данных, возвращают {status, message, data, pagination?}
 * - Write single: add(), set() — изменение одной записи, возвращают {status, message, data}
 * - Write batch: addBatch(), setBatch(), remove() — изменение множества, возвращают 207 Multi-Status с массивом результатов
 * - remove() работает ТОЛЬКО с batch-формат (array $ids), фильтрация — задача вызывающего кода
 *
 * Функциональность:
 * - Callbacks для batch операций: setBefore(), setAfter() — несколько callbacks на операцию
 * - Пагинация с triggers: handlePagination() — автоматические before/after на первой/последней странице
 * - Проверка дублей: checkDuplicate() — по уникальным полям из s_ConfigFields
 * - Валидация: getFieldRules() — типы и обязательность полей из s_ConfigFields
 * - Маппинг: mapApiToDbFields() / getReverseMap() — camelCase API ↔ PascalCase БД
 * - JSON-поля: decodeJsonFields() / getJsonFields() — авто-decode в ответах
 * - Файлы: getFiles(), handleFileCreate(), handleFileUpdate() — управление s_Files
 * - W_Attributes: buildAttributesFromPropertiesValues() — построение структуры свойств
 * - Поиск: updateSearchIndex() — синхронизация поискового индекса
 */
class RestV1M2MUtils
{
	/**
	 * Кэш JSON-полей таблицы. Используется при ЧТЕНИИ (fetch/item) для автоматического
	 * json_decode значений в ответе. Ключ — apiMapping (camelCase), значение — true.
	 * Заполняется лениво из s_ConfigFields (ApiFieldType='json' или FType LIKE '%json%').
	 * Дополнительно кэшируется в Memcached на 1 час.
	 * @var array<string,bool>|null null = ещё не загружен
	 */
	private ?array $jsonFieldsCache = null;

	/**
	 * Кэш обратного маппинга. Используется при ЗАПИСИ (add/set/checkDuplicate) для
	 * преобразования camelCase-ключей входящего API-запроса в PascalCase-поля БД.
	 * Пример: ['color' => 'Field1', 'productname' => 'Name', 'name' => 'Name'].
	 * Заполняется лениво из s_ConfigFields (TableField + ApiMapping) одним SQL на экземпляр.
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
	 * Список полей для выборки через Data::setFields().
	 * Используется при вызове fetch() для ограничения выбираемых полей.
	 * Устанавливается через setFields(), сбрасывается после выполнения fetch().
	 * @var string|null null = используются все поля
	 */
	private ?string $fields = null;

	/**
	 * Параметры для выборки через Data::fetch().
	 * Используется для передачи дополнительных опций (фильтры, сортировка и т.д.) в метод fetch().
	 * Устанавливается через setParams(), сбрасывается после выполнения fetch().
	 * @var array|null
	 */
	private ?array $params = null;

	/**
	 * Порядок сортировки результатов через Data::setOrderBy().
	 * Используется при вызове fetch() и item() для сортировки результатов по указанным полям.
	 * Устанавливается через setOrderBy(), сбрасывается после выполнения fetch().
	 * Пример: 'Priority DESC, Name ASC'
	 * @var string|null null = без сортировки
	 */
	private ?string $orderBy = null;

	/**
	 * Callback вызываемый перед пакетной вставкой/обновлением (addBatch, setBatch, remove).
	 * Получает (items: array, tableName: string, utils: self) где items - массив элементов batch-операции.
	 * Для addBatch/setBatch это записи, для remove() это массив id или ['id' => int] элементов.
	 * Может модифицировать items и вернуть обновленный массив, или вернуть null.
	 * Выполняется ВНУТРИ транзакции; исключение откатит все изменения.
	 * @var array<callable>
	 */
	private array $beforeCallbacks = [];

	/**
	 * Временное хранилище ошибок валидации, выставляемых callback-ами.
	 * Формат: [index => ['status' => int, 'message' => string, 'data' => mixed], ...]
	 * Используется только внутренне для batch-операций addBatch/setBatch/remove.
	 * @var array<int,array>
	 */
	private array $validationErrors = [];

	/**
	 * Callbacks вызываемые после пакетной вставки/обновления (addBatch, setBatch).
	 * Каждый callback получает (results: array, tableName: string) где results - массив результатов операций.
	 * Может выполнять дополнительную обработку (логирование, soft-delete и т.д.).
	 * Выполняются ВНУТРИ транзакции перед COMMIT; исключение откатит все.
	 * @var array<callable>
	 */
	private array $afterCallbacks = [];

	public function __construct(string $tableName)
	{
		$this->tableName = $tableName;
		$this->jsonFieldsCache = null;
		$this->reverseMapCache = null;
		$this->uniqueFieldsCache = null;
		$this->beforeCallbacks = [];
		$this->afterCallbacks = [];
	}

	/**
	 * Установить callback вызываемый перед пакетной вставкой/обновлением (addBatch, setBatch, remove).
	 * Callback получает (items: array, tableName: string, utils: self) где items - массив элементов batch-операции.
	 * Для addBatch/setBatch это записи, для remove() это массив id или ['id' => int] элементов.
	 * Может модифицировать items и вернуть обновленный массив, или вернуть null.
	 * Выполняется ВНУТРИ транзакции; исключение откатит все изменения.
	 * Можно вызвать несколько раз для добавления нескольких callbacks.
	 * 
	 * @param callable $callback fn(array $items, string $tableName, self $utils): array|null
	 * @return self для цепочки вызовов
	 */
	public function setBefore(callable $callback): self
	{
		$this->beforeCallbacks[] = $callback;
		return $this;
	}

	/**
	 * Установить callback вызываемый после пакетной вставки/обновления (addBatch, setBatch).
	 * Callback получает (results: array, tableName: string, utils: self) где results - массив результатов операций.
	 * Может выполнить дополнительную обработку (логирование, soft-delete и т.д.).
	 * Если возвращает массив, он заменяет текущие результаты.
	 * Выполняются ВНУТРИ транзакции перед COMMIT; исключение откатит все.
	 * Можно вызвать несколько раз для добавления нескольких callbacks.
	 * 
	 * @param callable $callback fn(array $results, string $tableName, self $utils): array|null
	 * @return self для цепочки вызовов
	 */
	public function setAfter(callable $callback): self
	{
		$this->afterCallbacks[] = $callback;
		return $this;
	}

	private function runBeforeCallbacks(array $items): array
	{
		foreach ($this->beforeCallbacks as $callback) {
			$result = $callback($items, $this->tableName, $this);
			if (is_array($result)) {
				$items = $result;
			}
		}
		return $items;
	}

	/**
	 * Установить встроенные validation errors по индексам batch-операции.
	 *
	 * Формат: [index => ['status' => int, 'message' => string, 'data' => mixed], ...]
	 * Это позволяет callback-ам разнести ошибки по отдельным элементам без изменения структуры items.
	 *
	 * @param array $errors
	 * @return self
	 */
	public function setValidationErrors(array $errors): self
	{
		$this->validationErrors = $errors;
		return $this;
	}

	private function getValidationErrors(array $items): array
	{
		$errors = $this->validationErrors;
		$this->validationErrors = [];
		$normalized = [];

		foreach ($errors as $index => $error) {
			$normalized[$index] = is_array($error)
				? [
					'status' => $error['status'] ?? 400,
					'message' => $error['message'] ?? 'Validation error',
					'data' => $error['data'] ?? null,
				]
				: ['status' => 400, 'message' => (string) $error, 'data' => null];
		}

		return ['records' => $items, 'errors' => $normalized];
	}

	private function runAfterCallbacks(array $results): array
	{
		foreach ($this->afterCallbacks as $callback) {
			$result = $callback($results, $this->tableName, $this);
			if (is_array($result)) {
				$results = $result;
			}
		}
		return $results;
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

		// Before callback: вызывается на каждой странице
		$beforeCallback = function (array $items, string $tableName, self $utils) use ($currentPage) {
			// На первой странице: маркировка всех записей как кандидатов на скрытие
			if ($currentPage === 1) {
				Connect::$instance->query(
					"UPDATE {$tableName} SET IsHiddenCandidate = 1 WHERE IsHiddenCandidate = 0 or IsHiddenCandidate IS NULL"
				);
			}
			// На каждой странице: сброс флага для записей, которые мы обновляем
			$ids = array_filter(array_column($items, 'id'), fn($v) => (int) $v > 0);
			if (!empty($ids)) {
				$in = Connect::$instance->in($ids);
				Connect::$instance->query(
					"UPDATE {$tableName} SET IsHiddenCandidate = 0 WHERE Id IN ($in)",
					$ids
				);
			}
			return $items;
		};

		// After callback: вызывается на каждой странице
		$afterCallback = function (array $results, string $tableName, self $utils) use ($currentPage, $totalPages) {
			// Только на последней странице: финализация скрытия
			if ($currentPage !== $totalPages) {
				return;
			}
			Connect::$instance->query(
				"UPDATE {$tableName} SET IsHidden = IsHiddenCandidate WHERE IsHiddenCandidate = 1"
			);
			Connect::$instance->query(
				"UPDATE {$tableName} SET IsHiddenCandidate = 0 WHERE IsHiddenCandidate = 1"
			);
		};

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
			$conditions = $conditions . ' AND ';
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
			$conditions .= ' AND t.Id = ' . $id;
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
			if ($this->orderBy !== null) {
				$orderBy = $this->orderBy;
			}
			$result = $data->fetch($conditions, $limit, $page, $orderBy ?? null);
			$result = $this->decodeJsonFields($result);
			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $result ?: [],
				'pagination' => [
					'count' => $skipPagination ? count($result) : $data->paginator['count'] ?? 1,
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
			$this->orderBy = null;
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
		$inputIndexes = array_keys($records);

		$dupeErrors = $this->checkDuplicate($records, false);
		foreach ($records as $index => $record) {
			if ($dupeErrors[$index] ?? null) {
				$results[$index] = $dupeErrors[$index];
				continue;
			}
			$toInsert[$index] = $record;
		}

		if (!empty($toInsert)) {
			$items = $this->runBeforeCallbacks($toInsert);
			$validation = $this->getValidationErrors($items);
			$validatedRecords = $validation['records'];
			$recordsErrors = $validation['errors'];
			$validItems = [];

			foreach ($validatedRecords as $index => $record) {
				if (isset($recordsErrors[$index])) {
					$results[$index] = $recordsErrors[$index];
					continue;
				}
				$validItems[$index] = $record;
			}

			if (!empty($validItems)) {
				$batchResults = $this->executeBatchInsert($validItems);
				foreach ($batchResults as $index => $result) {
					$results[$index] = $result;
				}
			}
		}

		$ordered = [];
		foreach ($inputIndexes as $index) {
			if (isset($results[$index])) {
				$ordered[$index] = $results[$index];
			}
		}

		return $ordered;
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
		$toUpdate = $items;

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
				function ($args) {
					$args['items'] = $this->runBeforeCallbacks($args['items']);
					$validation = $this->getValidationErrors($args['items']);
					$records = $validation['records'];
					$recordsErrors = $validation['errors'];
					$results = $recordsErrors;
					$validItems = [];

					foreach ($records as $index => $record) {
						if (isset($recordsErrors[$index])) {
							continue;
						}
						$validItems[$index] = $record;
					}

					// Выполнить пакетное обновление только по валидным записям
					if (!empty($validItems)) {
						$results += $this->executeBatchUpdate($validItems);
					}

					// After callbacks ВНУТРИ транзакции (перед коммитом)
					$results = $this->runAfterCallbacks($results);
					$ordered = [];
					foreach ($records as $index => $_) {
						if (isset($results[$index])) {
							$ordered[$index] = $results[$index];
						}
					}
					return $ordered;
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
	 * Удалить записи (batch).
	 *
	 * @param array $ids ID записей
	 * @return array - {status, message, data: [{...}]}
	 */
	public function remove(array $ids): array
	{
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'No IDs provided', 'data' => null];
		}

		$records = $this->runBeforeCallbacks($ids);
		$validation = $this->getValidationErrors($records);
		$records = $validation['records'];
		$recordsErrors = $validation['errors'];
		$results = $recordsErrors;

		$validItems = [];
		foreach ($records as $index => $item) {
			if (isset($recordsErrors[$index])) {
				continue;
			}

			$id = (int) $item;
			if ($id === 0) {
				$results[$index] = ['status' => 404, 'message' => 'Already deleted or not found', 'data' => ['id' => $id]];
				continue;
			}

			$validItems[$index] = $id;
		}

		if (!empty($validItems)) {
			$results += $this->executeBatchRemove($validItems);
		}

		foreach ($this->afterCallbacks as $callback) {
			$callback($results, $this->tableName, $this);
		}

		$ordered = [];
		foreach ($records as $index => $_) {
			if (isset($results[$index])) {
				$ordered[$index] = $results[$index];
			}
		}

		return ['status' => 207, 'message' => 'Multi-Status', 'data' => $ordered];
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
			$guid = $this->extractFieldValue($record, 'guid');
			unset($mapped['Id'], $mapped['id']);
			ksort($mapped);
			$sig = implode(',', array_keys($mapped));
			$groups[$sig][] = ['index' => $index, 'data' => $mapped, 'guid' => $guid];
		}

		try {
			$results = Connect::$instance->transaction(
				function ($args) {
					$results = [];
					foreach ($args['groups'] as $group) {
						$prepared = Connect::$instance->prepare($group[0]['data']);
						$sql = "INSERT INTO {$this->tableName} {$prepared['insert']} ON DUPLICATE KEY UPDATE `Id` = LAST_INSERT_ID(`Id`)";
						$sth = Connect::$db->prepare($sql);

						foreach ($group as $item) {
							$params = $item['data'];

							// JSON-кодируем массивы перед сохранением в БД
							foreach ($params as $key => $value) {
								if (is_array($value)) {
									$params[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
								}
							}

							$sth->execute($params);
							$affectedRows = $sth->rowCount();
							$id = (int) Connect::$db->lastInsertId();
							$responseData = ['id' => $id, 'guid' => $item['guid']];

							if ($affectedRows === 0 && $id > 0) {
								// Дубликат — запись с таким уникальным ключом уже существует
								$results[$item['index']] = [
									'status' => 409,
									'message' => 'Record not created: duplicate unique key or conflicting existing data',
									'data' => ['id' => $id, 'guid' => $item['guid']]
								];
							} elseif ($id > 0) {
								$results[$item['index']] = ['status' => 201, 'message' => 'Created', 'data' => $responseData];
							} else {
								$results[$item['index']] = ['status' => 400, 'message' => 'Failed to insert record', 'data' => null];
							}
						}
					}

					return $this->runAfterCallbacks($results);
				},
				['groups' => $groups]
			);
		} catch (\Exception $e) {
			foreach ($items as $index => $_) {
				if (!isset($results[$index])) {
					$results[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
				}
			}
		} finally {
			$this->resetAutoIncrement();
		}

		return $results;
	}

	private function resetAutoIncrement(): void
	{
		try {
			Connect::$instance->query("ALTER TABLE `{$this->tableName}` AUTO_INCREMENT = 1");
		} catch (\Exception $e) {
			// Игнорируем ошибки сброса AUTO_INCREMENT
		}
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
			$guid = $this->extractFieldValue($record, 'guid');
			unset($mapped['Id'], $mapped['id']);
			ksort($mapped);

			if (empty($mapped)) {
				$results[$index] = ['status' => 400, 'message' => 'Nothing to update', 'data' => null];
				continue;
			}

			$sig = implode(',', array_keys($mapped));
			$groups[$sig][] = ['index' => $index, 'id' => $id, 'data' => $mapped, 'guid' => $guid];
		}

		foreach ($groups as $group) {
			$prepared = Connect::$instance->prepare($group[0]['data']);
			$sql = "UPDATE {$this->tableName} SET {$prepared['update']} WHERE Id = :Id";
			$sth = Connect::$db->prepare($sql);

			foreach ($group as $item) {
				$params = $item['data'];

				// JSON-кодируем массивы перед сохранением в БД
				foreach ($params as $key => $value) {
					if (is_array($value)) {
						$params[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
					}
				}

				$params['Id'] = $item['id'];
				try {
					$sth->execute($params);
					$responseData = ['id' => $item['id'], 'guid' => $item['guid']];
					$results[$item['index']] = ['status' => 200, 'message' => 'Updated', 'data' => $responseData];
				} catch (\Exception $e) {
					$results[$item['index']] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
				}
			}
		}

		return $results;
	}

	private function executeBatchRemove(array $items): array
	{
		$results = [];
		$uniqueIds = array_values(array_unique($items));
		$placeholders = Connect::$instance->in($uniqueIds);
		$existingRows = Connect::$instance->fetch(
			"SELECT Id FROM {$this->tableName} WHERE Id IN ($placeholders)",
			$uniqueIds
		);
		$existingIds = array_flip(array_column($existingRows, 'Id'));

		$model = new Data($this->tableName);
		$resolved = [];
		foreach ($items as $index => $id) {
			if (!isset($existingIds[$id])) {
				$results[$index] = ['status' => 404, 'message' => 'Already deleted or not found', 'data' => ['id' => $id]];
				continue;
			}

			if (!isset($resolved[$id])) {
				try {
					$model->remove($id);
					$resolved[$id] = ['status' => 200, 'message' => 'Deleted', 'data' => ['id' => $id]];
				} catch (\Exception $e) {
					$resolved[$id] = ['status' => 400, 'message' => $e->getMessage(), 'data' => ['id' => $id]];
				}
			}

			$results[$index] = $resolved[$id];
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
		$result = array_fill_keys(array_keys($records), null);
		if (empty($records)) {
			return $result;
		}

		$uniqueFields = $this->getUniqueFields(); // [DbField => apiName]
		if (empty($uniqueFields)) {
			return $result;
		}

		$updateIds = [];
		if ($forUpdate) {
			foreach ($records as $item) {
				if (!is_array($item)) {
					continue;
				}
				$id = (int) ($item['id'] ?? $item['Id'] ?? 0);
				if ($id > 0) {
					$updateIds[] = $id;
				}
			}
		}

		foreach ($uniqueFields as $dbField => $apiName) {
			$uniqueValues = [];
			$valueIndexMap = [];

			foreach ($records as $index => $item) {
				if (!is_array($item) || ($result[$index] ?? null)) {
					continue;
				}
				$value = $this->extractFieldValue($item, $apiName);
				if ($value !== null && $value !== '') {
					$lower = strtolower((string) $value);
					$uniqueValues[] = $lower;
					$valueIndexMap[$lower][] = ['index' => $index, 'original' => $value, 'guid' => $this->extractFieldValue($item, 'guid')];
				}
			}

			if (empty($uniqueValues)) {
				continue;
			}

			// Проверка дубликатов в текущем batch по уникальному полю
			foreach ($valueIndexMap as $entries) {
				if (count($entries) <= 1) {
					continue;
				}
				$original = $entries[0]['original'];
				foreach ($entries as $entry) {
					$result[$entry['index']] = [
						'status' => 409,
						'message' => "Duplicate key constraint: {$apiName} = {$original}",
						'data' => ['guid' => $entry['guid'], $apiName => $original],
					];
				}
			}

			$inValues = Connect::$instance->in($uniqueValues);
			$params = $uniqueValues;
			$excludeClause = '';

			if ($forUpdate && !empty($updateIds)) {
				$inIds = Connect::$instance->in($updateIds);
				$excludeClause = " AND `Id` NOT IN ($inIds)";
				$params = array_merge($params, $updateIds);
			}

			try {
				$rows = Connect::$instance->fetch(
					"SELECT `Id`, `{$dbField}` FROM `{$this->tableName}` WHERE `{$dbField}` IN ($inValues){$excludeClause}",
					$params
				);
			} catch (\Exception $e) {
				continue;
			}

			foreach ($rows as $row) {
				$lower = strtolower((string) $row[$dbField]);
				foreach ($valueIndexMap[$lower] ?? [] as $entry) {
					$index = $entry['index'];
					if ($result[$index] !== null) {
						continue;
					}
					$result[$index] = [
						'status' => 409,
						'message' => "Duplicate key constraint: {$apiName} = {$entry['original']}",
						'data' => ['id' => (int) $row['Id'], 'guid' => $entry['guid'], $apiName => $entry['original']],
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

		$sql = "SELECT `TableField`, `ApiMapping`, `IsUnique` FROM s_ConfigFields WHERE `TableName` = ?";
		$result = Connect::$instance->fetch($sql, [$this->tableName]);

		$reverseMap = [];
		$uniqueFields = [];
		foreach ($result as $row) {
			$dbField = $row['TableField'];
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
			$sql = "SELECT `TableField`, `ApiMapping`, `ApiFieldType`, `IsRequired` FROM s_ConfigFields WHERE `TableName` = ? ORDER BY `TableField` ASC";
			$result = Connect::$instance->fetch($sql, [$this->tableName]);
			$rules = [];
			foreach ($result as $field) {
				$fieldName = $field['ApiMapping'] ?? null;
				$apiType = $field['ApiFieldType'] ?? 'string';
				$required = (int) ($field['IsRequired'] ?? 0);

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
	 * Установить порядок сортировки для выборки
	 *
	 * @param string $orderBy Порядок сортировки (например: 'Priority DESC, Name ASC')
	 * @return $this для цепочки вызовов
	 */
	public function setOrderBy(string $orderBy): self
	{
		$this->orderBy = $orderBy;
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
			$sql = "SELECT TableField, ApiFieldType, ApiMapping, FType FROM s_ConfigFields WHERE TableName = ? AND (ApiFieldType = 'json' OR FType LIKE '%json%')";
			$result = Connect::$instance->fetch($sql, [$this->tableName]);

			$jsonFields = [];
			foreach ($result as $field) {
				$fieldName = $field['TableField'] ?? null;
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

	public function prepareUploadFromBase64(string $base64, string $fileName): array
	{
		$binary = base64_decode($base64, true);
		if ($binary === false) {
			return ['error' => 'Invalid base64 data'];
		}

		$mime = $this->detectMimeType($binary);
		if ($mime === null) {
			return ['error' => 'Unable to detect mime type'];
		}

		$ext = $this->resolveExtensionByMime($mime, $fileName);
		if ($ext === null) {
			return ['error' => 'Unsupported mime type: ' . $mime];
		}

		$tmpPath = $this->saveTempFile($binary, $ext);
		if ($tmpPath === null) {
			return ['error' => 'Failed to save temporary file'];
		}

		return [
			'path' => $tmpPath,
			'name' => $fileName ?: 'file.' . $ext,
			'type' => $mime,
			'size' => strlen($binary),
		];
	}

	public function prepareUploadFromUrl(string $url, string $fileName): array
	{
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return ['error' => 'Invalid url'];
		}

		$binary = @file_get_contents($url);
		if ($binary === false || $binary === '') {
			return ['error' => 'Unable to download file from url'];
		}

		$mime = $this->detectMimeType($binary);
		if ($mime === null) {
			return ['error' => 'Unable to detect mime type'];
		}

		$ext = $this->resolveExtensionByMime($mime, $fileName ?: basename(parse_url($url, PHP_URL_PATH) ?: 'file'));
		if ($ext === null) {
			return ['error' => 'Unsupported mime type: ' . $mime];
		}

		$tmpPath = $this->saveTempFile($binary, $ext);
		if ($tmpPath === null) {
			return ['error' => 'Failed to save temporary file'];
		}

		return [
			'path' => $tmpPath,
			'name' => $fileName ?: basename(parse_url($url, PHP_URL_PATH) ?: 'file.' . $ext),
			'type' => $mime,
			'size' => strlen($binary),
		];
	}

	private function saveTempFile(string $binary, string $ext): ?string
	{
		$root = rtrim(Connect::$projectDev['root'] ?? '', '/\\');
		$dir = $root . '/packages/WeppsExtensions/Template/Forms/uploads';
		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return null;
		}

		$tmpPath = $dir . '/wepps_upload_' . uniqid('', true) . '.' . $ext;
		if (file_put_contents($tmpPath, $binary) === false) {
			return null;
		}

		return $tmpPath;
	}

	private function detectMimeType(string $binary): ?string
	{
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo !== false) {
				$mime = finfo_buffer($finfo, $binary);
				finfo_close($finfo);
				if ($mime !== false) {
					return $mime;
				}
			}
		}

		if (function_exists('getimagesizefromstring')) {
			$info = @getimagesizefromstring($binary);
			if (!empty($info['mime'])) {
				return $info['mime'];
			}
		}

		return null;
	}

	private function resolveExtensionByMime(string $mime, string $fileName = ''): ?string
	{
		$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if ($ext !== '') {
			return $ext;
		}

		switch ($mime) {
			case 'image/jpeg':
			case 'image/pjpeg':
				return 'jpg';
			case 'image/png':
				return 'png';
			case 'image/gif':
				return 'gif';
			case 'image/webp':
				return 'webp';
			case 'image/svg+xml':
				return 'svg';
			case 'application/pdf':
				return 'pdf';
			case 'application/zip':
				return 'zip';
			case 'application/json':
				return 'json';
			case 'text/plain':
				return 'txt';
			case 'audio/mpeg':
			case 'audio/mp3':
				return 'mp3';
			case 'video/mp4':
				return 'mp4';
			default:
				return null;
		}
	}
}
