<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Memcached;
use WeppsCore\Utils;

/**
 * RestV1M2MUtils - вспомогательный класс для CRUD операций M2M API
 * 
 * Обрабатывает:
 * - Загрузку конфига из s_Config/s_ConfigFields
 * - Валидацию данных
 * - Работу с JSON полями
 * - Маппинг API ↔ БД
 */
class RestV1M2MUtils
{
	/**
	 * Кэш json-полей для таблиц
	 * @var array<string,array<string,bool>>
	 */
	private array $jsonFieldsCache = [];

	/**
	 * Кэш обратного маппинга API→DB для таблиц (в рамках одного запроса)
	 * @var array<string,array<string,string>>
	 */
	private array $reverseMapCache = [];

	/**
	 * Список полей для выборки через Data::setFields()
	 * @var string|null
	 */
	private ?string $fields = null;

	/**
	 * Маппинг таблиц на уникальные поля для проверки дублирования
	 * @var array<string,string>
	 */
	private const UNIQUE_FIELDS = [
		'Products' => 'Alias',
		's_Users' => 'Login',
		'Orders' => 'Id', // нет уникального поля кроме ID
		'News' => 'Alias',
	];

	/**
	 * Получить список записей
	 * 
	 * @param string $tableName - имя таблицы (e.g. 's_Users', 'Products')
	 * @param array $query - параметры: page, limit, search
	 * @param string|null $fields - список полей для вывода; если не указан, используется установленное через setFields()
	 * @return array - {status, message, data, pagination}
	 */
	public function fetch(string $tableName, array $query, ?string $fields = null): array
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
			$data = new Data($tableName, ['useApiMapping' => true]);
			$fields = $fields ?? $this->fields;
			if ($fields !== null) {
				$data->setFields($fields);
			}

			$result = $data->fetch($conditions, $limit, $page);
			$result = $this->decodeJsonFields($result, $tableName);

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
	 * @param string $tableName - имя таблицы
	 * @param int|string $id - ID записи
	 * @param string|null $fields - список полей для вывода; если не указан, используется установленное через setFields()
	 * @return array - {status, message, data}
	 */
	public function item(string $tableName, $id, ?string $fields = null): array
	{
		$response = $this->fetch($tableName, ['id' => $id, 'page' => 1, 'limit' => 1], $fields);

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
	 * @param string $tableName - имя таблицы
	 * @param array $data - данные для создания
	 * @param bool $skipDuplicateCheck - пропустить проверку дублирования (если уже проверено в batch)
	 * @return array - {status, message, data}
	 */
	public function add(string $tableName, array $data, bool $skipDuplicateCheck = false): array
	{
		try {
			// Проверить дублирование по уникальному полю (если не пропущено)
			if (!$skipDuplicateCheck) {
				$duplicateError = $this->checkDuplicate($tableName, $data, false);
				if ($duplicateError) {
					return $duplicateError;
				}
			}

			$model = new Data($tableName);
			$mappedData = $this->mapApiToDbFields($tableName, $data);
			$result = $model->add($mappedData);

			if ((int) $result === 0) {
				return [
					'status' => 409,
					'message' => 'Insert ignored (duplicate key or constraint violation)',
					'data' => null,
				];
			}

			return [
				'status' => 201,
				'message' => 'Created',
				'data' => ['id' => $result],
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
	 * @param string $tableName - имя таблицы
	 * @param int|string $id - ID записи
	 * @param array $data - данные для обновления
	 * @return array - {status, message, data}
	 */
	public function set(string $tableName, $id, array $data): array
	{
		try {
			$model = new Data($tableName);
			$model->set((int) $id, $data);

			return [
				'status' => 200,
				'message' => 'Updated',
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
	 * Удалить запись (аналог Data->remove())
	 * 
	 * @param string $tableName - имя таблицы
	 * @param int|string $id - ID записи
	 * @return array - {status, message, data}
	 */
	public function remove(string $tableName, $id): array
	{
		try {
			$model = new Data($tableName);
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
	 * Проверить дублирование для одной или нескольких записей
	 * 
	 * Возвращает:
	 * - null или массив ошибки для одной записи (isBatch=false)
	 * - массив [index => errorArray или null] для массива записей (isBatch=true)
	 * 
	 * @param string $tableName - имя таблицы
	 * @param array $data - одна запись или массив записей
	 * @param bool $isBatch - true если это массив записей
	 * @return mixed - результат проверки
	 */
	public function checkDuplicate(string $tableName, array $data, bool $isBatch = false): mixed
	{
		// Получить уникальное поле для этой таблицы
		$uniqueField = self::UNIQUE_FIELDS[$tableName] ?? null;
		if (!$uniqueField || $uniqueField === 'Id') {
			// Если нет уникального поля или это только ID, пропускаем проверку
			return $isBatch ? array_fill_keys(array_keys($data), null) : null;
		}

		// Обернуть single запись в массив для единой обработки
		$dataList = $isBatch ? $data : [0 => $data];

		// Собрать все значения уникального поля со всех элементов
		$uniqueValues = [];
		$valueIndexMap = []; // Маппинг значения -> индексы элементов где оно встречается
		
		foreach ($dataList as $index => $item) {
			if (!is_array($item)) {
				continue;
			}

			// Найти значение в данных и привести к нижнему регистру
			$value = $this->extractFieldValue($item, $uniqueField);
			if ($value !== null && $value !== '') {
				$valueLower = strtolower((string)$value);
				$uniqueValues[] = $valueLower;
				if (!isset($valueIndexMap[$valueLower])) {
					$valueIndexMap[$valueLower] = [];
				}
				$valueIndexMap[$valueLower][] = $index;
			}
		}

		// Инициализировать результат
		$result = array_fill_keys(array_keys($dataList), null);

		if (empty($uniqueValues)) {
			return $isBatch ? $result : ($result[0] ?? null);
		}

		// Проверить дублирование для всех значений
		$duplicates = $this->validateDuplicateValues($tableName, $uniqueField, $uniqueValues);
		
		// Отметить индексы с дублированием
		foreach ($duplicates as $existingValue) {
			$existingValueLower = strtolower((string)$existingValue);
			if (isset($valueIndexMap[$existingValueLower])) {
				foreach ($valueIndexMap[$existingValueLower] as $index) {
					$originalValue = $this->extractFieldValue($dataList[$index], $uniqueField) ?? '';
					$result[$index] = [
						'status' => 409,
						'message' => "Duplicate $uniqueField: $originalValue",
						'data' => null,
					];
				}
			}
		}

		// Вернуть результат в нужном формате
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
	 * @param string $tableName - имя таблицы
	 * @param string $uniqueField - имя уникального поля
	 * @param array $values - массив значений для проверки (в нижнем регистре)
	 * @return array - массив найденных значений (которые уже существуют в БД)
	 */
	/**
	 * Конвертировать данные из camelCase API ключей в PascalCase DB ключи
	 * используя ApiMapping из s_ConfigFields
	 *
	 * @param string $tableName - имя таблицы
	 * @param array $data - данные с camelCase ключами
	 * @return array - данные с PascalCase ключами
	 */
	private function mapApiToDbFields(string $tableName, array $data): array
	{
		try {
			$reverseMap = $this->getReverseMap($tableName);

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
	 * @param string $tableName
	 * @return array<string,string>
	 */
	private function getReverseMap(string $tableName): array
	{
		if (isset($this->reverseMapCache[$tableName])) {
			return $this->reverseMapCache[$tableName];
		}

		$sql = "SELECT `Field`, `ApiMapping` FROM s_ConfigFields WHERE `TableName` = ?";
		$result = Connect::$instance->fetch($sql, [$tableName]);

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

		$this->reverseMapCache[$tableName] = $reverseMap;
		return $reverseMap;
	}

	private function validateDuplicateValues(string $tableName, string $uniqueField, array $values): array
	{
		if (empty($values)) {
			return [];
		}

		try {
			// Сделать один запрос для проверки всех значений с LOWER()
			$placeholders = implode(',', array_fill(0, count($values), '?'));
			$existingRows = Connect::$instance->fetch(
				"SELECT $uniqueField FROM $tableName WHERE LOWER($uniqueField) IN ($placeholders)",
				$values
			);

			// Вернуть найденные значения (оригинальный регистр из БД)
			$existingValues = [];
			foreach ($existingRows as $row) {
				$existingValues[] = $row[$uniqueField];
			}

			return $existingValues;
		} catch (\Exception $e) {
			// Если ошибка при проверке, игнорируем и даем идти дальше
			// БД выдаст ошибку если понадобится
			return [];
		}
	}

	/**
	 * Получить валидационные правила из s_ConfigFields
	 * 
	 * Читает таблицу s_ConfigFields и строит правила валидации на основе:
	 * - ApiFieldType (int, string, email, date, float, guid)
	 * - Required (обязательность поля)
	 * 
	 * @param string $tableName - имя таблицы для которой получить правила
	 * @return array - ассоциативный массив {fieldName => {type, required}}
	 * @example ['id' => ['type' => 'int', 'required' => true], 'email' => ['type' => 'email', 'required' => false]]
	 */
	public function getFieldRules(string $tableName): array
	{
		$cacheKey = 'api_validation_rules_' . $tableName;

		// Попытка получить из кэша (системный кэш - всегда включен)
		$memcached = new Memcached('auto', true);
		$cachedRules = $memcached->get($cacheKey);
		if ($cachedRules !== null) {
			return $cachedRules;
		}

		try {
			// Получить все поля для таблицы из s_ConfigFields
			$sql = "SELECT `Field`, `ApiMapping`, `ApiFieldType`, `Required` FROM s_ConfigFields WHERE `TableName` = ? ORDER BY `Field` ASC";
			$result = Connect::$instance->fetch($sql, [$tableName]);
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
	 * @param string $tableName - имя таблицы в s_Files.TableName
	 * @param string $field - значение TableNameField (например 'Images' или 'ImagesV')
	 * @param array $query - массив GET-параметров, включает goods_id, page, limit
	 * @return array
	 */
	public function getFiles(string $tableName, string $field, array $query): array
	{
		$url = Connect::$projectDev['protocol'] . Connect::$projectDev['host'];
		$goodsId = (int) ($query['goods_id'] ?? 0);
		$page = max(1, (int) ($query['page'] ?? 1));
		$limit = min(1000, max(1, (int) ($query['limit'] ?? 1000)));
		$offset = ($page - 1) * $limit;

		$conditions = "TableName = ? AND TableNameField = ?";
		$params = [$tableName, $field];
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
	 * @param string $tableName Имя таблицы
	 * @return array
	 */
	private function decodeJsonFields(array $rows, string $tableName): array
	{
		if (empty($rows)) {
			return $rows;
		}

		$fields = $this->getJsonFields($tableName);
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
	 * @param string $tableName
	 * @return array<string,bool>
	 */
	private function getJsonFields(string $tableName): array
	{
		if (isset($this->jsonFieldsCache[$tableName])) {
			return $this->jsonFieldsCache[$tableName];
		}

		$cacheKey = 'json_fields_' . $tableName;
		$memcached = new Memcached('auto', true);
		$cached = $memcached->get($cacheKey);
		if ($cached !== null) {
			$this->jsonFieldsCache[$tableName] = $cached;
			return $cached;
		}

		try {
			// Получить JSON поля напрямую из s_ConfigFields
			$sql = "SELECT Field, ApiFieldType, ApiMapping, Type FROM s_ConfigFields WHERE TableName = ? AND (ApiFieldType = 'json' OR Type LIKE '%json%')";
			$result = Connect::$instance->fetch($sql, [$tableName]);

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
			$this->jsonFieldsCache[$tableName] = $jsonFields;

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
