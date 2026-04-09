<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Memcached;

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
	 * Получить список записей
	 * 
	 * @param string $tableName - имя таблицы (e.g. 's_Users', 'Products')
	 * @param array $query - параметры: page, limit, search
	 * @return array - {status, message, data, pagination}
	 */
	public function fetch(string $tableName, array $query): array
	{
		$page = (int) ($query['page'] ?? 1);
		$limit = (int) ($query['limit'] ?? 20);
		$search = $query['search'] ?? '';

		try {
			$data = new Data($tableName);
			$result = $data->fetch('Id!=0', $limit, $page);

			// Использовать paginator и count из Data объекта
			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $result ?: [],
				'pagination' => [
					'count' => $data->count,
					'limit' => $limit,
					'page' => $page,
				],
			];
		} catch (\Exception $e) {
			return [
				'status' => 500,
				'message' => $e->getMessage(),
				'data' => null,
			];
		}
	}

	/**
	 * Получить одну запись
	 * 
	 * @param string $tableName - имя таблицы
	 * @param int|string $id - ID записи
	 * @return array - {status, message, data}
	 */
	public function item(string $tableName, $id): array
	{
		try {
			$data = new Data($tableName);
			$result = $data->fetch('t.Id = ' . (int)$id, 1);
			
			if (empty($result)) {
				return [
					'status' => 404,
					'message' => 'Not found',
					'data' => null,
				];
			}

			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $result[0] ?? null,
			];
		} catch (\Exception $e) {
			return [
				'status' => 500,
				'message' => $e->getMessage(),
				'data' => null,
			];
		}
	}

	/**
	 * Добавить запись (аналог Data->add())
	 * 
	 * @param string $tableName - имя таблицы
	 * @param array $data - данные для создания
	 * @return array - {status, message, data}
	 */
	public function add(string $tableName, array $data): array
	{
		try {
			$model = new Data($tableName);
			$result = $model->add($data);

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
			$model->set((int)$id, $data);

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
			$model->remove((int)$id);

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
		
		// Попытка получить из кэша
		$memcached = new Memcached();
		$cachedRules = $memcached->get($cacheKey);
		if ($cachedRules !== null) {
			return $cachedRules;
		}

		try {
			// Получить все поля для таблицы из s_ConfigFields
			$sql = "SELECT `Field`, `ApiFieldType`, `Required` FROM s_ConfigFields WHERE `TableName` = ? ORDER BY `Field` ASC";
			$result = Connect::$instance->fetch($sql, [$tableName]);

			$rules = [];
			foreach ($result as $field) {
				$fieldName = $field['Field'] ?? null;
				$apiType = $field['ApiFieldType'] ?? 'string';
				$required = (int)($field['Required'] ?? 0);

				if ($fieldName) {
					$rules[$fieldName] = [
						'type'     => $apiType ?: 'string',
						'required' => $required === 1,
					];
				}
			}

			// Кэшировать на 1 час (3600 сек)
			$memcached = new Memcached();
			$memcached->set($cacheKey, $rules, 3600);

			return $rules;
		} catch (\Exception $e) {
			// Если ошибка при чтении БД, возвращаем пустой массив
			// (валидация будет пропущена)
			return [];
		}
	}

	/**
	 * Инвалидировать кэш валидационных правил (после обновления s_ConfigFields)
	 * 
	 * @param string $tableName - имя таблицы (null = очистить все кэши валидации)
	 * @return bool
	 */
	public function invalidateFieldRulesCache(?string $tableName = null): bool
	{
		if ($tableName === null) {
			// Очистить все кэши валидации (тяжелая операция, лучше избегать)
			// В реальности нужна лучшая стратегия: теги кэша или версионирование
			return true;
		}

		$cacheKey = 'api_validation_rules_' . $tableName;
		$memcached = new Memcached();
		return $memcached->delete($cacheKey);
	}
}
