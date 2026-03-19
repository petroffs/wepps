<?php

namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;

/**
 * RestV1M2MManager - базовый класс для M2M API операций
 * 
 * Предоставляет общую логику для CRUD операций:
 * - Получение списков с пагинацией и фильтрацией (используя Data::fetch)
 * - Получение одной записи
 * - Создание записи
 * - Обновление записи
 * - Удаление записи
 * - Фильтрация полей на основе s_ConfigFields.IsApiAvailable
 * - Работа с JSON полями (декодирование при выводе)
 */
class RestV1M2MManager
{
    /**
     * Имя таблицы в БД
     * Должно быть переопределено в подклассах
     */
    protected string $tableName = '';

    /**
     * Данные из JSON body запроса
     * Заполняется при инициализации
     */
    protected array $requestData = [];

    /**
     * Маппинг входящих ключей (camelCase) на ключи БД (PascalCase)
     * Используется для преобразования входящих данных
     * Переопределяется в подклассах при необходимости
     * ['login' => 'Login', 'password' => 'Password']
     */
    protected array $inputFieldMapping = [];

    /**
     * Конфиг полей с типами для валидации и схемы
     * ['Login' => 'email', 'Password' => 'string', 'Id' => 'int']
     */
    protected array $fieldConfig = [];

    /**
     * JSON поля таблицы (требуют декодирования при выводе)
     * Переопределяется в подклассах при необходимости
     * ['JCart', 'JFav', 'JData']
     */
    protected array $jsonFields = [];

    /**
     * Кэш доступных полей (ключ: TableName)
     */
    private static array $fieldsCache = [];

    /**
     * Кэш маппингов полей (ключ: TableName)
     */
    private static array $mappingCache = [];

    // ========================================================================
    // ИНИЦИАЛИЗАЦИЯ МАППИНГА ПОЛЕЙ
    // ========================================================================

    /**
     * Загрузить маппинг входящих ключей из s_ConfigFields
     * Автоматически строит $inputFieldMapping на основе ApiMapping
     * 
     * @param string $tableName - имя таблицы для загрузки маппинга
     * @return void
     */
    protected function loadInputMappingFromDb(string $tableName): void
    {
        // Использовать кэш если есть
        if (isset(self::$mappingCache[$tableName])) {
            $this->inputFieldMapping = self::$mappingCache[$tableName];
            return;
        }

        // Загрузить из БД
        $data = new Data('s_ConfigFields');
        $conditions = "TableName = ? AND ApiMapping IS NOT NULL AND ApiMapping != ''";
        $data->setParams([$tableName]);
        $result = $data->fetch($conditions, 1000, 1, "Priority");

        $mapping = [];
        if (is_array($result)) {
            foreach ($result as $row) {
                $apiMapping = trim($row['ApiMapping'] ?? '');
                if (!empty($apiMapping)) {
                    // camelCase (ApiMapping) → PascalCase (Field)
                    // Например: 'login' → 'Login', 'data' → 'JData'
                    $mapping[$apiMapping] = $row['Field'];
                }
            }
        }

        // Сохранить в кэш
        self::$mappingCache[$tableName] = $mapping;
        
        $this->inputFieldMapping = $mapping;
    }

    // ========================================================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПОЛЯМИ (встроенные из M2MFieldFilter)
    // ========================================================================

    /**
     * Получить список доступных полей для таблицы в M2M API
     * Читает из s_ConfigFields где IsApiAvailable = 1
     * 
     * @param string $tableName - имя таблицы
     * @return array - ['field1' => true, 'field2' => true, ...]
     */
    protected function getVisibleFields(string $tableName): array
    {
        // Проверить кэш
        if (isset(self::$fieldsCache[$tableName])) {
            return self::$fieldsCache[$tableName];
        }

        $data = new Data('s_ConfigFields');
        $conditions = "TableName = ? AND IsApiAvailable = 1";
        $data->setParams([$tableName]);
        $result = $data->fetch($conditions, 1000, 1, "Priority");

        $fields = [];
        if (is_array($result)) {
            foreach ($result as $row) {
                $fields[$row['Field']] = true;
            }
        }

        // Сохранить в кэш
        self::$fieldsCache[$tableName] = $fields;

        return $fields;
    }

    /**
     * Проверить, доступно ли конкретное поле для таблицы
     */
    protected function isFieldVisible(string $tableName, string $fieldName): bool
    {
        $fields = $this->getVisibleFields($tableName);
        return isset($fields[$fieldName]);
    }

    /**
     * Фильтровать результат - оставить только доступные поля (одна строка)
     */
    protected function filterRow(array $row, string $tableName): array
    {
        $visibleFields = $this->getVisibleFields($tableName);

        if (empty($visibleFields)) {
            return []; // Нет доступных полей для таблицы
        }

        return array_intersect_key($row, $visibleFields);
    }

    /**
     * Фильтровать результаты - оставить только доступные поля (несколько строк)
     */
    protected function filterRows(array $rows, string $tableName): array
    {
        $filtered = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $filtered[] = $this->filterRow($row, $tableName);
            }
        }
        return $filtered;
    }

    /**
     * Валидировать поля входящих данных
     * Проверить, что пользователь не пытается установить недоступные поля
     * 
     * @param array $data - данные для создания/обновления
     * @param string $tableName - имя таблицы
     * @return array - ['valid' => true/false, 'errors' => [...]]
     */
    protected function validateInputFields(array $data, string $tableName): array
    {
        $visibleFields = $this->getVisibleFields($tableName);
        $errors = [];

        foreach (array_keys($data) as $field) {
            if (!isset($visibleFields[$field])) {
                $errors[] = "Поле '{$field}' недоступно для таблицы '{$tableName}' в M2M API";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    // ========================================================================
    // МЕТОДЫ ДЛЯ НОРМАЛИЗАЦИИ ДАННЫХ
    // ========================================================================

    /**
     * Нормализовать входящие ключи из camelCase в PascalCase (БД)
     * Использует $inputFieldMapping для маппинга
     * 
     * @param array $data - входящие данные
     * @return array - с нормализованными ключами
     */
    protected function normalizeInputKeys(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            // Проверить маппинг
            if (isset($this->inputFieldMapping[$key])) {
                $normalizedKey = $this->inputFieldMapping[$key];
            } else {
                // По умолчанию ucfirst (camelCase → PascalCase)
                $normalizedKey = ucfirst($key);
            }
            $normalized[$normalizedKey] = $value;
        }
        return $normalized;
    }

    /**
     * Нормализовать выходящие ключи из PascalCase в camelCase (для API)
     * Использует обратный маппинг из $inputFieldMapping
     * 
     * @param array $data - данные из БД (PascalCase)
     * @return array - с нормализованными ключами (camelCase)
     */
    protected function normalizeOutputKeys(array $data): array
    {
        // Построить обратный маппинг: PascalCase (БД) → camelCase (API)
        $reverseMapping = array_flip($this->inputFieldMapping);
        
        $normalized = [];
        foreach ($data as $key => $value) {
            // Проверить обратный маппинг
            if (isset($reverseMapping[$key])) {
                $normalizedKey = $reverseMapping[$key];
            } else {
                // По умолчанию lcfirst (PascalCase → camelCase)
                $normalizedKey = lcfirst($key);
            }
            $normalized[$normalizedKey] = $value;
        }
        return $normalized;
    }

    /**
     * Нормализовать выходящие строки
     * Применяет normalizeOutputKeys к массиву строк
     * 
     * @param array $rows - массив строк из БД
     * @return array - нормализованные строки
     */
    protected function normalizeOutputRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $normalized[] = $this->normalizeOutputKeys($row);
            }
        }
        return $normalized;
    }

    // ========================================================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С JSON ПОЛЯМИ
    // ========================================================================

    /**
     * Получить данные из JSON body запроса
     * Заполняет свойство $requestData
     */
    protected function loadRequestData(): void
    {
        if (!empty($this->requestData)) {
            return; // Уже загружены
        }

        $input = file_get_contents('php://input');
        if ($input) {
            $decoded = @json_decode($input, true);
            if (is_array($decoded)) {
                $this->requestData = $decoded;
            }
        }
    }

    /**
     * Установить данные запроса (для использования из RestV1M2M)
     */
    public function setRequestData(array $data): void
    {
        $this->requestData = $data;
    }

    // ========================================================================
    // CRUD МЕТОДЫ
    // ========================================================================

    /**
     * Получить список записей с пагинацией и фильтрацией
     * 
     * Использует Data::fetch для получения данных со связками
     * 
     * @param string $conditions - SQL WHERE условия (с ? плейсхолдерами)
     * @param array $params - параметры для подстановки в условия
     * @param int $page - номер страницы (по умолчанию 1)
     * @param int $limit - записей на странице (по умолчанию 20, макс 100)
     * @param string $orderBy - SQL ORDER BY условие
     * @return array - {status, message, data: {items, paginator, count}}
     */
    protected function getList(
        string $conditions = '',
        array $params = [],
        int $page = 1,
        int $limit = 20,
        string $orderBy = ''
    ): array {
        if (!$this->tableName) {
            return [
                'status' => 500,
                'message' => 'Table name not configured',
                'data' => null,
            ];
        }

        // Валидировать limit
        $limit = min($limit, 100);
        $page = max(1, $page);

        $data = new Data($this->tableName);

        if (!empty($params)) {
            $data->setParams($params);
        }

        // Получить записи вместе со связками из s_ConfigFields
        $rows = $data->fetch($conditions, $limit, $page, $orderBy);

        // Фильтровать результаты - оставить только доступные поля
        $items = $this->filterRows($rows ?? [], $this->tableName);

        // Декодировать JSON поля если нужно
        $items = $this->decodeJsonFields($items);

        // Нормализовать выходные ключи: PascalCase → camelCase (из ApiMapping в БД)
        $items = $this->normalizeOutputRows($items);

        // Построить пагинатор
        $paginator = $this->buildPaginator($page, $limit, $data->count);

        return [
            'status' => 200,
            'message' => 'Success',
            'data' => [
                'items' => $items,
                'page' => $page,
                'pages' => $paginator['total_pages'],
                'paginator' => $paginator,
                'count' => $data->count,
            ],
        ];
    }

    /**
     * Получить одну запись по ID
     * 
     * @param int|string $id - ID записи
     * @return array - {status, message, data: {...record...}}
     */
    protected function getItem($id): array
    {
        if (!$this->tableName) {
            return [
                'status' => 500,
                'message' => 'Table name not configured',
                'data' => null,
            ];
        }

        $data = new Data($this->tableName);
        $data->setParams([$id]);
        $rows = $data->fetch('t.Id = ?', 1, 1);

        if (empty($rows[0])) {
            return [
                'status' => 404,
                'message' => 'Record not found',
                'data' => null,
            ];
        }

        // Фильтровать результат - оставить только доступные поля
        $item = $this->filterRow($rows[0], $this->tableName);

        // Декодировать JSON поля если нужно
        $item = $this->decodeJsonFieldsInRow($item);

        // Нормализовать выходные ключи: PascalCase → camelCase (из ApiMapping в БД)
        $item = $this->normalizeOutputKeys($item);

        return [
            'status' => 200,
            'message' => 'Success',
            'data' => $item,
        ];
    }

    /**
     * Создать новую запись
     * 
     * @param array $data - данные для создания
     * @return array - {status, message, data: {id: ...}}
     */
    protected function create(array $data): array
    {
        if (!$this->tableName) {
            return [
                'status' => 500,
                'message' => 'Table name not configured',
                'data' => null,
            ];
        }

        if (empty($data)) {
            return [
                'status' => 400,
                'message' => 'No data provided',
                'data' => null,
            ];
        }

        // Нормализовать входящие ключи (camelCase → PascalCase)
        $data = $this->normalizeInputKeys($data);

        // Валидировать поля
        $validation = $this->validateInputFields($data, $this->tableName);
        if (!$validation['valid']) {
            return [
                'status' => 400,
                'message' => 'Invalid fields: ' . implode(', ', $validation['errors']),
                'data' => null,
            ];
        }

        // Закодировать JSON поля если нужно
        $data = $this->encodeJsonFields($data);

        // Создать запись прямо через PDO
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->tableName} ({$columns}) VALUES ({$placeholders})";

        $sth = Connect::$db->prepare($sql);
        $result = $sth->execute(array_values($data));

        if (!$result) {
            return [
                'status' => 400,
                'message' => 'Failed to create record',
                'data' => null,
            ];
        }

        return [
            'status' => 201,
            'message' => 'Record created',
            'data' => [
                'id' => Connect::$db->lastInsertId(),
            ],
        ];
    }

    /**
     * Обновить запись по ID
     * 
     * @param int|string $id - ID записи
     * @param array $data - данные для обновления
     * @return array - {status, message, data: null}
     */
    protected function update($id, array $data): array
    {
        if (!$this->tableName) {
            return [
                'status' => 500,
                'message' => 'Table name not configured',
                'data' => null,
            ];
        }

        if (empty($data)) {
            return [
                'status' => 400,
                'message' => 'No data provided',
                'data' => null,
            ];
        }

        // Нормализовать входящие ключи (camelCase → PascalCase)
        $data = $this->normalizeInputKeys($data);

        // Валидировать поля
        $validation = $this->validateInputFields($data, $this->tableName);
        if (!$validation['valid']) {
            return [
                'status' => 400,
                'message' => 'Invalid fields: ' . implode(', ', $validation['errors']),
                'data' => null,
            ];
        }

        // Проверить существование записи
        $checkData = new Data($this->tableName);
        $checkData->setParams([$id]);
        $existing = $checkData->fetch('t.Id = ?', 1, 1);
        
        if (empty($existing[0])) {
            return [
                'status' => 404,
                'message' => 'Record not found',
                'data' => null,
            ];
        }

        // Закодировать JSON поля если нужно
        $data = $this->encodeJsonFields($data);

        // Обновить запись прямо через PDO
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$this->tableName} SET {$sets} WHERE Id = ?";

        $values = array_values($data);
        $values[] = $id;

        $sth = Connect::$db->prepare($sql);
        $result = $sth->execute($values);

        if (!$result) {
            return [
                'status' => 400,
                'message' => 'Failed to update record',
                'data' => null,
            ];
        }

        return [
            'status' => 200,
            'message' => 'Record updated',
            'data' => null,
        ];
    }

    /**
     * Удалить запись по ID
     * 
     * @param int|string $id - ID записи
     * @return array - {status, message, data: null}
     */
    protected function delete($id): array
    {
        if (!$this->tableName) {
            return [
                'status' => 500,
                'message' => 'Table name not configured',
                'data' => null,
            ];
        }

        // Проверить существование записи
        $checkData = new Data($this->tableName);
        $checkData->setParams([$id]);
        $existing = $checkData->fetch('t.Id = ?', 1, 1);
        
        if (empty($existing[0])) {
            return [
                'status' => 404,
                'message' => 'Record not found',
                'data' => null,
            ];
        }

        // Удалить запись прямо через PDO
        $sql = "DELETE FROM {$this->tableName} WHERE Id = ?";
        $sth = Connect::$db->prepare($sql);
        $result = $sth->execute([$id]);

        if (!$result) {
            return [
                'status' => 400,
                'message' => 'Failed to delete record',
                'data' => null,
            ];
        }

        return [
            'status' => 200,
            'message' => 'Record deleted',
            'data' => null,
        ];
    }

    /**
     * Декодировать JSON поля в одной строке
     * 
     * Используется для преобразования JSON в массивы при выводе
     * 
     * @param array $row - одна строка данных
     * @return array
     */
    protected function decodeJsonFieldsInRow(array $row): array
    {
        foreach ($this->jsonFields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $decoded = @json_decode($row[$field], true);
                if ($decoded !== null) {
                    $row[$field] = $decoded;
                }
            }
        }
        return $row;
    }

    /**
     * Декодировать JSON поля в нескольких строках
     * 
     * @param array $rows - несколько строк данных
     * @return array
     */
    protected function decodeJsonFields(array $rows): array
    {
        foreach ($rows as &$row) {
            $row = $this->decodeJsonFieldsInRow($row);
        }
        return $rows;
    }

    /**
     * Закодировать JSON поля перед сохранением
     * 
     * Используется для преобразования массивов в JSON при сохранении
     * 
     * @param array $data - данные для сохранения
     * @return array
     */
    protected function encodeJsonFields(array $data): array
    {
        foreach ($this->jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
            }
        }
        return $data;
    }

    // ========================================================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ========================================================================

    /**
     * Построить объект пагинатора
     * 
     * @param int $currentPage
     * @param int $perPage
     * @param int $total
     * @return array
     */
    protected function buildPaginator(int $currentPage, int $perPage, int $total): array
    {
        $totalPages = max(1, ceil($total / $perPage));

        return [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $currentPage < $totalPages,
            'has_prev' => $currentPage > 1,
        ];
    }

    /**
     * Очистить кэш полей (для тестирования или обновления конфига)
     */
    protected static function clearFieldsCache(): void
    {
        self::$fieldsCache = [];
    }

    /**
     * Получить имя таблицы
     */
    protected function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Установить JSON поля для декодирования
     */
    protected function setJsonFields(array $fields): void
    {
        $this->jsonFields = $fields;
    }
}
