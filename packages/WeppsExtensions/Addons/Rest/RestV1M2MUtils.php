<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Memcached;
use WeppsCore\Utils;


/**
 * RestV1M2MUtils - вспомогательный класс для CRUD операций M2M API
 *
 * Один экземпляр — одна таблица (tableName передаётся в конструктор).
 * Инстанцируется через RestV1M2M::getUtils(tableName) с кэшированием по имени таблицы.
 *
 * Обрабатывает:
 * - CRUD: fetch(), item(), add(), set(), remove()
 * - Проверку дублей по уникальному полю: checkDuplicate()
 * - Правила валидации из s_ConfigFields: getFieldRules()
 * - Маппинг camelCase API → PascalCase БД: mapApiToDbFields() / getReverseMap()
 * - Авто-decode JSON-полей в ответах: decodeJsonFields() / getJsonFields()
 * - Файлы из s_Files: getFiles()
 * - Построение W_Attributes из свойств: buildAttributesFromPropertiesValues()
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
	 * Имя таблицы с которой работает данный экземпляр
	 * @var string
	 */
	private string $tableName;

	/**
	 * Список полей для выборки через Data::setFields()
	 * @var string|null
	 */
	private ?string $fields = null;

	public function __construct(string $tableName)
	{
		$this->tableName = $tableName;
		$this->jsonFieldsCache = null;
		$this->reverseMapCache = null;
	}

	/**
	 * Маппинг таблиц на уникальные поля для проверки дублирования
	 * @var array<string,string>
	 */
	private const UNIQUE_FIELDS = [
		'Products'           => 'Alias',
		'ProductsVariations' => 'Alias',
		's_Users'            => 'Login',
		'Orders'             => 'Id', // нет уникального поля кроме ID
		'News'               => 'Alias',
	];

	/**
	 * Получить список записей
	 * 
	 * @param array $query - параметры: page, limit, search
	 * @param string|null $fields - список полей для вывода; если не указан, используется установленное через setFields()
	 * @return array - {status, message, data, pagination}
	 */
	public function fetch(array $query, ?string $fields = null): array
	{
		$page = (int) ($query['page'] ?? 1);
		$limit = (int) ($query['limit'] ?? 20);
		$id = isset($query['id']) ? (int) $query['id'] : null;
		// $search = $query['search'] ?? '';

		$conditions = 'Id!=0';
		$skipPagination = false; // флаг для пропуска пагинации при запросе по ID
		if ($id !== null && $id > 0) {
			$conditions = 't.Id = ' . $id;
			$limit = 1;
			$page = 0; // передаём 0 чтобы пропустить пагинацию
			$skipPagination = true;
		}

		try {
			$data = new Data($this->tableName, ['useApiMapping' => true]);
			$fields = $fields ?? $this->fields;
			if ($fields !== null) {
				$data->setFields($fields);
			}

			$result = $data->fetch($conditions, $limit, $page);
			$result = $this->decodeJsonFields($result);

			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $result ?: [],
				'pagination' => [
					'count' => $skipPagination ? count($result) : $data->count,
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
			$this->resetFields();
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
	 * Добавить запись (аналог Data->add())
	 * 
	 * @param array $data - данные для создания
	 * @param bool $skipDuplicateCheck - пропустить проверку дублирования (если уже проверено в batch)
	 * @return array - {status, message, data}
	 */
	public function add(array $data, bool $skipDuplicateCheck = false): array
	{
		try {
			// Проверить дублирование по уникальному полю (если не пропущено)
			if (!$skipDuplicateCheck) {
				$duplicateError = $this->checkDuplicate($data, false);
				if ($duplicateError) {
					return $duplicateError;
				}
			}

			$mappedData = $this->mapApiToDbFields($data);

			// INSERT + UPDATE выполняются атомарно: если UPDATE упадёт,
			// INSERT откатывается и частичная запись не остаётся в БД
			$transactionResult = Connect::$instance->transaction(
				function ($args) {
					$model = new Data($args['tableName']);
					return ['id' => $model->add($args['data'])];
				},
				['tableName' => $this->tableName, 'data' => $mappedData]
			);
			$id = $transactionResult['id'];

			if ((int) $id === 0) {
				return [
					'status' => 409,
					'message' => 'Insert ignored (duplicate key or constraint violation)',
					'data' => null,
				];
			}

			return [
				'status' => 201,
				'message' => 'Created',
				'data' => ['id' => $id],
			];
		} catch (\Exception $e) {
			return [
				'status' => 400,
				'message' => $e->getMessage(),
				'data' => null,
			];
		}
	}

	/**
	 * Обновить запись (аналог Data->set())
	 *
	 * @param int|string $id - ID записи
	 * @param array $data - данные для обновления
	 * @param bool $skipDuplicateCheck - пропустить проверку дублирования (если уже проверено в batch)
	 * @return array - {status, message, data}
	 */
	public function set($id, array $data, bool $skipDuplicateCheck = false): array
	{
		try {
			// Проверить дублирование по уникальному полю (если не пропущено)
			if (!$skipDuplicateCheck) {
				$duplicateError = $this->checkDuplicate(array_merge($data, ['id' => $id]), false, true);
				if ($duplicateError) {
					return $duplicateError;
				}
			}

			$mappedData = $this->mapApiToDbFields($data);
			$model = new Data($this->tableName);
			$model->set((int) $id, $mappedData);

			return [
				'status' => 200,
				'message' => 'Updated',
				'data' => ['id' => (int) $id],
			];
		} catch (\Exception $e) {
			return [
				'status' => 400,
				'message' => $e->getMessage(),
				'data' => null,
			];
		}
	}

	/**
	 * Удалить запись (аналог Data->remove())
	 * 
	 * @param int|string $id - ID записи
	 * @return array - {status, message, data}
	 */
	public function remove($id): array
	{
		try {
			$model = new Data($this->tableName);
			$model->remove((int) $id);

			return [
				'status' => 200,
				'message' => 'Deleted',
				'data' => null,
			];
		} catch (\Exception $e) {
			return [
				'status' => 400,
				'message' => $e->getMessage(),
				'data' => null,
			];
		}
	}

	/**
	 * Проверить дублирование по уникальному полю таблицы.
	 *
	 * Режим POST (forUpdate=false): проверяет, существует ли значение в БД.
	 * Режим PUT  (forUpdate=true):  то же, но исключает сами обновляемые записи
	 *                               (ищет конфликт только с ДРУГИМИ записями).
	 *
	 * Возвращает:
	 * - isBatch=false: null или массив ошибки {status:409, ...}
	 * - isBatch=true:  map [index => null | {status:409, message, data:{id}}]
	 *
	 * @param array $data      - одна запись или массив записей
	 * @param bool  $isBatch   - true если массив записей
	 * @param bool  $forUpdate - true для PUT: исключить обновляемые id из проверки
	 * @return mixed
	 */
	public function checkDuplicate(array $data, bool $isBatch = false, bool $forUpdate = false): mixed
	{
		$uniqueField = self::UNIQUE_FIELDS[$this->tableName] ?? null;
		if (!$uniqueField || $uniqueField === 'Id') {
			return $isBatch ? array_fill_keys(array_keys($data), null) : null;
		}

		$dataList      = $isBatch ? $data : [0 => $data];
		$result        = array_fill_keys(array_keys($dataList), null);
		$uniqueValues  = [];
		$updateIds     = [];
		$valueIndexMap = [];

		foreach ($dataList as $index => $item) {
			if (!is_array($item)) {
				continue;
			}
			if ($forUpdate) {
				$id = (int) ($item['id'] ?? $item['Id'] ?? 0);
				if ($id > 0) {
					$updateIds[] = $id;
				}
			}
			$value = $this->extractFieldValue($item, $uniqueField);
			if ($value !== null && $value !== '') {
				$valueLower = strtolower((string) $value);
				$uniqueValues[] = $valueLower;
				$valueIndexMap[$valueLower][] = $index;
			}
		}

		if (empty($uniqueValues)) {
			return $isBatch ? $result : ($result[0] ?? null);
		}

		$inValues      = Connect::$instance->in($uniqueValues);
		$params        = $uniqueValues;
		$excludeClause = '';

		if ($forUpdate && !empty($updateIds)) {
			$inIds         = Connect::$instance->in($updateIds);
			$excludeClause = " AND Id NOT IN ($inIds)";
			$params        = array_merge($params, $updateIds);
		}

		try {
			$rows = Connect::$instance->fetch(
				"SELECT Id, {$uniqueField} FROM {$this->tableName} WHERE LOWER({$uniqueField}) IN ($inValues){$excludeClause}",
				$params
			);
		} catch (\Exception $e) {
			return $isBatch ? $result : ($result[0] ?? null);
		}

		foreach ($rows as $row) {
			$valueLower = strtolower((string) $row[$uniqueField]);
			foreach ($valueIndexMap[$valueLower] ?? [] as $index) {
				$originalValue = $this->extractFieldValue($dataList[$index], $uniqueField) ?? $valueLower;
				$result[$index] = [
					'status'  => 409,
					'message' => "Duplicate {$uniqueField}: {$originalValue}",
					'data'    => ['id' => (int) $row['Id']],
				];
			}
		}

		return $isBatch ? $result : ($result[0] ?? null);
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
	 * Проверить существование значений в БД (базовый метод)
	 * Используется как checkDuplicate так и checkBatchDuplicates
	 * 
	 * @param string $uniqueField - имя уникального поля
	 * @param array $values - массив значений для проверки (в нижнем регистре)
	 * @return array - массив найденных значений (которые уже существуют в БД)
	 */
	/**
	 * Конвертировать данные из camelCase API ключей в PascalCase DB ключи
	 * используя ApiMapping из s_ConfigFields
	 *
	 * @param array $data - данные с camelCase ключами
	 * @return array - данные с PascalCase ключами
	 */
	private function mapApiToDbFields(array $data): array
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

		$sql = "SELECT `Field`, `ApiMapping` FROM s_ConfigFields WHERE `TableName` = ?";
		$result = Connect::$instance->fetch($sql, [$this->tableName]);

		$reverseMap = [];
		foreach ($result as $row) {
			$dbField = $row['Field'];
			$apiKey = $row['ApiMapping'] ?? null;
			if ($apiKey) {
				$reverseMap[strtolower($apiKey)] = $dbField;
			}
			// Fallback: lowercased DB field name -> DB field
			$reverseMap[strtolower($dbField)] = $dbField;
		}

		$this->reverseMapCache = $reverseMap;
		return $reverseMap;
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
	 * Сбросить ранее установленные поля выборки
	 *
	 * @return $this
	 */
	private function resetFields(): self
	{
		$this->fields = null;
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
	 * @param array|null $propertiesData Массив данных о свойствах (grouped по ID или rows)
	 * @return array|null Отформатированный массив W_Attributes или null
	 */
	public function buildAttributesFromPropertiesValues(?array $propertiesData): ?array
	{
		if (empty($propertiesData)) {
			return null;
		}

		// Входные данные: [PropertyId => rows] (после filtersByCompositeKey())
		$grouped = [];
		foreach ($propertiesData as $propId => $rows) {
			if (!is_array($rows) || empty($rows)) {
				continue;
			}
			$grouped[$propId] = $rows;
		}

		return array_values(array_map(
			fn($propId, $rows) => [
				'id' => (int) $propId,
				'name' => $rows[0]['PropertyName'] ?? '',
				'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue']], $rows),
			],
			array_keys($grouped),
			array_values($grouped)
		));
	}
}
