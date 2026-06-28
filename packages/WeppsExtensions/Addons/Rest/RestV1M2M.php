<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Utils;
use WeppsExtensions\Template\Filters\Filters;
use WeppsExtensions\Products\ProductsUtils;
use WeppsAdmin\Lists\Lists;
use WeppsAdmin\ConfigExtensions\Processing\ProcessingProducts;

/**
 * RestV1M2M - M2M API для работы с таблицами через CRUD операции
 * 
 * Использует упрощённый подход - явные методы для каждой таблицы:
 * - getUsers, postUsers, putUsers, deleteUsers
 * - getProducts, postProducts, putProducts, deleteProducts
 * - getOrders, postOrders, putOrders, deleteOrders
 * 
 * Все методы используют единый helper для работы с БД.
 * Конфигурация берётся из s_Config и s_ConfigFields.
 * Валидация данных берётся из s_ConfigFields через RestV1M2MUtils.
 */
class RestV1M2M extends RestV1
{
	/**
	 * Utils для CRUD операций
	 */
	private array $utils = [];

	// ========================================================================
	// USERS
	// ========================================================================

	public function getUsers(): array
	{
		// GET параметры - служебные (page, limit, search, sort)
		$this->getUtils('s_Users')->setFields('Id,Name,NameFirst,NameSurname,NamePatronymic,IsHidden,UserPermissions,CreateDate,Login,Email,Phone,Comment,Country,Region,City,Address,PostalCode');
		return $this->getUtils('s_Users')->fetch($this->get);
	}

	public function getUsersItem(): array
	{
		$id = $this->getIdFromRequest();
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		$this->getUtils('s_Users')->setFields('Id,Name,NameFirst,NameSurname,NamePatronymic,IsHidden,UserPermissions,CreateDate,Login,Email,Phone,Comment,Country,Region,City,Address,PostalCode');
		return $this->getUtils('s_Users')->item($id);
	}

	public function postUsers(): array
	{
		$records = $this->normalizeInput();
		// Before callback: валидация и подготовка перед вставкой
		$beforeCallback = function(array $items, string $tableName) {
			// $items — это отфильтрованные записи (прошли проверку дублей и валидацию)
			// может быть меньше, чем исходный $records!
			// Можно добавить дополнительную валидацию, логирование, нормализацию данных
			
			// Пример: проверка и отбрасывание исключения откатит всю транзакцию
			// foreach ($items as $item) {
			//     if (!isset($item['email']) || empty($item['email'])) {
			//         throw new \Exception('Email is required for all users');
			//     }
			// }
			if (!empty($this->data['pagination'])) {
				if (($this->data['pagination']['page'] ?? 0) == 1) {
					// На первой странице можно добавить дополнительную обработку
					// Например, логирование или уведомление
					$sql = "UPDATE {$tableName} SET IsHiddenCandidate = 1 WHERE IsHiddenCandidate = 0";
					Connect::$instance->query($sql);
				}
			}
			return $items; // Возвращаем (возможно модифицированные) данные
		};
		
		// After callback: логирование результатов после успешной вставки
		$afterCallback = function(array $results, string $tableName) {
			// Здесь можно отправить уведомления, обновить кэш, залогировать события
			// Находится внутри транзакции, но перед commit()
			
			// Пример: если в before проставили IsHiddenCandidate, 
			// то в after финализируем скрытие (если всё успешно)
			// foreach ($results as $result) {
			//     if (($result['status'] ?? 0) === 201) {
			//         $id = $result['data']['id'] ?? 0;
			//         // Копируем флаг-кандидат в финальный флаг скрытия
			//         Connect::$instance->query(
			//             "UPDATE {$tableName} SET IsHidden = IsHiddenCandidate WHERE Id = ?",
			//             [$id]
			//         );
			//     }
			// }
			
			// Успешно созданные записи с ID: $result['data']['id']
			// foreach ($results as $result) {
			// 	if (($result['status'] ?? 0) === 201) {
			// 		
			// 	}
			// }
			if (!empty($this->data['pagination'])) {
				if (($this->data['pagination']['page'] ?? 0) == ($this->data['pagination']['count'] ?? 1)) {
					// На последней странице можно добавить дополнительную обработку
					// Например, логирование или уведомление
					$sql = "UPDATE {$tableName} SET IsHidden = IsHiddenCandidate WHERE IsHiddenCandidate = 1";
					Connect::$instance->query($sql);
				}
			}
		};
		
		// Установить callback в utils и вызвать create()
		$this->getUtils('s_Users')
			->setBefore($beforeCallback)
			->setAfter($afterCallback);
		
		return $this->create('s_Users', $records);
	}

	public function putUsers(): array
	{
		$records = $this->normalizeInput();
		return $this->update('s_Users', $records);
	}

	public function deleteUsers(): array
	{
		$ids = $this->getNormalizedIds();
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils('s_Users')->remove($ids);
	}

	// ========================================================================
	// GOODS
	// ========================================================================

	public function getGoods(): array
	{
		$id = $this->get['id'] ?? '';
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int) ($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';

		$productsUtils = new ProductsUtils();
		$navigator = new Navigator("/catalog/");

		// Если передан ID, ищем по ID, иначе используем поиск и фильтры
		if (!empty($id)) {
			$productsUtils->setNavigator($navigator, 'Products');
			$isNumericId = (strlen((int) $id) == strlen($id));
			$conditions = $isNumericId ? "t.Id = ?" : "binary t.Alias = ?";

			$settings = [
				'limit' => 1,
				'page' => 1,
				'sorting' => 't.Priority desc',
				'conditions' => [
					'conditions' => $conditions,
					'params' => [$id],
				],
				'useApiMapping' => true,
			];
		} else {
			// Инициализируем Navigator для работы getConditions()
			if (!empty($this->get['category'])) {
				$navigator->content['Id'] = (int) $this->get['category'];
			}
			$productsUtils->setNavigator($navigator, 'Products');

			// Условия WHERE и параметры для фильтрации
			$conditions = "t.IsHidden=0";
			$params = [];

			if (!empty($search)) {
				$conditions = "t.IsHidden=0 and lower(t.Name) like lower(?)";
				$params[] = $search . "%";
			}

			$filters = new Filters($this->get);
			$filterParams = $filters->getParams();
			$conditionsWithFilters = $productsUtils->getConditions($filterParams, true, $conditions, $params);

			// Сортировка
			$sorting = match ($this->get['sort'] ?? '') {
				'priceasc' => 't.Price asc',
				'pricedesc' => 't.Price desc',
				'nameasc' => 't.Name asc',
				default => 't.Priority desc',
			};

			$settings = [
				'limit' => $limit,
				'page' => $page,
				'sorting' => $sorting,
				'conditions' => $conditionsWithFilters,
				'useApiMapping' => true,
			];
		}

		$result = $productsUtils->getProducts($settings);
		$rows = $result['rows'] ?? [];

		// Получаем все атрибуты для всех товаров одним вызовом Filters
		// Filters::getFilters() возвращает с группировкой по compositeKey "PropertyId-Alias"
		// Нужно трансформировать в [ProductId => [PropertyId => rows]] через buildAttributesForProducts()
		$filterResult = [];
		$ids = array_column($rows, 'id');

		if (!empty($ids)) {
			$placeholders = Connect::$instance->in($ids);
			$filtersObj = new Filters();
			$rawFilters = $filtersObj->getFilters([
				'conditions' => "t.IsHidden=0 AND pv.TableName='Products' AND pv.TableNameId IN ($placeholders)",
				'params' => $ids,
			]);
			// Трансформируем в структуру [ProductId => [PropertyId => rows]]
			$filterResult = $filtersObj->buildAttributesForProducts($rawFilters);
		}

		// Распределяем атрибуты по товарам
		foreach ($rows as &$row) {
			$row['W_Attributes'] = $this->getUtils('Products')->buildAttributesFromPropertiesValues($filterResult[$row['id']] ?? null);
		}
		unset($row);
		//Utils::debug(,1);
		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $rows,
			'pagination' => ['count' => $result['paginator']['count'], 'limit' => $limit, 'page' => $page],
		];
	}

	public function getGoodsItem(): array
	{
		$id = $this->getIdFromRequest();
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		// Используем логику getGoods() для обогащения W_Attributes
		return $this->getGoods();
	}

	public function postGoods(): array
	{
		$records = $this->normalizeInput();
		return $this->create('Products', $records);
	}

	public function putGoods(): array
	{
		$records = $this->normalizeInput();
		return $this->update('Products', $records);
	}

	public function deleteGoods(): array
	{
		$ids = $this->getNormalizedIds();
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		/**
		 * ! Можем удалять связанные данные, например, вариации товара при удалении его изображения. Логика зависит от бизнес-требований. 
		 */

		// Например, если нужно удалить связанные вариации)
		// $variationIds = Connect::$instance->fetch(
		// 	"SELECT Id FROM ProductsVariations WHERE ProductId IN (...)",
		// 	$ids
		// );
		// $variationIds = array_column($variationIds, 'Id');

		// Удалить вариации через utils (будет 1 запрос на проверку)
		// if (!empty($variationIds)) {
		// 	$this->getUtils('ProductsVariations')->remove($variationIds);
		// }

		return $this->getUtils('Products')->remove($ids);
	}

	/**
	 * M2M: GET получить вариации товаров
	 */
	public function getGoodsVariations(): array
	{
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(5000, max(1, (int) ($this->get['limit'] ?? 5000)));
		$goodsId = (int) ($this->get['goods_id'] ?? 0);

		$data = new Data('ProductsVariations', ['useApiMapping' => true]);
		$data->setFields('Id,ProductsId,Field1,Field2,Field3,FIeld4');

		// Условия WHERE
		$conditions = 'IsHidden = 0';
		$params = [];

		// Если передан goodsId, фильтруем по товару
		if ($goodsId > 0) {
			$conditions .= ' AND ProductsId = ?';
			$params[] = $goodsId;
		}

		if (!empty($params)) {
			$data->setParams($params);
		}

		$result = $data->fetch($conditions, $limit, $page, 't.Priority desc');

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $result ?: [],
			'pagination' => [
				'count' => $data->paginator['count'] ?? 1,
				'limit' => $limit,
				'page' => $page,
			],
		];
	}

	/**
	 * M2M: POST создание вариаций товаров (одна или batch).
	 * Сгруппировывает по goodsId и вызывает upsertVariations() batch-ом.
	 * Не скрывает существующие вариации — только добавляет новые.
	 *
	 * Валидация по RestConfig уже выполнена в Rest::executeHandler() перед вызовом метода!
	 * Формат тела: { "data": [ { "goodsId": 723, "sku": "SKU001", "color": "Красный", "size": "42", "stocks": "10" }, ... ] }
	 */
	public function postGoodsVariations(): array
	{
		$records = $this->normalizeInput();

		// Сгруппировать по goodsId
		$byGoodsId = [];
		foreach ($records as $record) {
			$goodsId = (int) ($record['goodsId'] ?? 0);
			if (!$goodsId) {
				return ['status' => 400, 'message' => 'goodsId required', 'data' => null];
			}
			if (!isset($byGoodsId[$goodsId])) {
				$byGoodsId[$goodsId] = [];
			}
			// Преобразовать в индексированный формат [color, size, sku, stocks]
			$byGoodsId[$goodsId][] = [
				trim($record['color'] ?? ''),
				trim($record['size'] ?? ''),
				trim($record['sku'] ?? ''),
				trim($record['stocks'] ?? ''),
			];
		}

		// Batch-обновление для каждого товара
		$processing = new ProcessingProducts();
		$created = [];
		$updated = [];
		foreach ($byGoodsId as $goodsId => $variations) {
			$results = $processing->upsertVariations($goodsId, $variations, false); // false = не скрывать старые
			foreach ($results as $result) {
				if ($result['action'] === 'created') {
					$created[] = $result['id'];
				} else {
					$updated[] = $result['id'];
				}
			}
		}

		return ['status' => 201, 'message' => 'Variations processed', 'data' => ['created' => $created, 'updated' => $updated]];
	}

	/**
	 * M2M: PUT обновление вариаций по id (одна или batch).
	 * Переформировывает alias если изменились color/size/sku.
	 * Проверяет уникальность новых alias перед обновлением.
	 *
	 * Одна запись: ?id=123 или { "data": { "id": 123, "color": "Синий" } }
	 * Batch: { "data": [ { "id": 1, "sku": "NEW" }, { "id": 2, "color": "Зелёный" } ] }
	 */
	public function putGoodsVariations(): array
	{
		$records = $this->normalizeInput();

		// Используем ProcessingProducts для обновления с переформированием alias
		$processing = new ProcessingProducts();
		$results = $processing->updateVariations($records);

		if (empty($results['updated']) && empty($results['skipped']) && empty($results['conflict']) && empty($results['notFound'])) {
			return ['status' => 400, 'message' => 'No variations found', 'data' => null];
		}

		// Одиночное обновление
		if (count($records) === 1) {
			$recordId = (int) ($records[0]['id'] ?? 0);
			if (isset($results['notFound']) && in_array($recordId, $results['notFound'])) {
				return ['status' => 404, 'message' => 'Variation not found', 'data' => null];
			}
			if (isset($results['conflict']) && in_array($recordId, $results['conflict'])) {
				return ['status' => 409, 'message' => 'Alias already in use', 'data' => null];
			}
			if (isset($results['skipped']) && in_array($recordId, $results['skipped'])) {
				return ['status' => 200, 'message' => 'No changes', 'data' => ['id' => $recordId]];
			}
			if (isset($results['updated']) && in_array($recordId, $results['updated'])) {
				return ['status' => 200, 'message' => 'Variation updated', 'data' => ['id' => $recordId]];
			}
		}

		// Batch-обновление
		return [
			'status' => 200,
			'message' => 'Variations processed',
			'data' => $results,
		];
	}

	public function deleteGoodsVariations(): array
	{
		$ids = $this->getNormalizedIds();
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		return $this->getUtils('ProductsVariations')->remove($ids);
	}

	/**
	 * M2M: GET запасы товаров (доступность на складах)
	 */
	public function getGoodsStocks(): array
	{
		// GET параметры - служебные (page, limit, search, sort)
		$obj = $this->getUtils('ProductsVariations');
		$obj->setFields('Id,ProductsId,Field4');
		if (!empty($this->get['goodsId'])) {
			$obj->setParams([(int) $this->get['goodsId']]);
			$conditions = 't.ProductsId = ?';
		}
		return $obj->fetch($this->get, $conditions ?? null);
	}

	/**
	 * M2M: PUT обновление запасов товара (одна или batch).
	 * Обновляет Field4 (stocks) в ProductsVariations по id.
	 * Использует универсальный метод update().
	 *
	 * Валидация по RestConfig уже выполнена в Rest::executeHandler() перед вызовом метода!
	 * Одна запись: { "data": { "id": 123, "stocks": 10 } }
	 * Batch: { "data": [ { "id": 1, "stocks": 5 }, { "id": 2, "stocks": 10 } ] }
	 */
	public function putGoodsStocks(): array
	{
		$records = $this->normalizeInput();
		return $this->update('ProductsVariations', $records);
	}

	/**
	 * M2M: GET цены товаров
	 */
	public function getGoodsPrices(): array
	{
		$goodsId = (int) ($this->get['goods_id'] ?? 0);
		['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->calculatePagination(500);
		$limit = (int) $limit;
		$offset = (int) $offset;

		// Формируем условие WHERE
		$conditions = "IsHidden = 0";
		$params = [];
		if ($goodsId > 0) {
			$conditions .= " AND Id = ?";
			$params[] = $goodsId;
		}

		// Получить данные с пагинацией
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Price, PriceBefore, Article FROM Products 
			 WHERE {$conditions}
			 ORDER BY Name 
			 LIMIT {$offset}, {$limit}",
			$params
		);

		// Получить общее количество
		$countRes = Connect::$instance->fetch(
			"SELECT COUNT(*) as total FROM Products WHERE {$conditions}",
			$params
		);
		$total = (int) ($countRes[0]['total'] ?? 0);

		if (empty($res) && $total === 0) {
			return ['status' => 404, 'message' => 'Goods not found', 'data' => null];
		}

		// Приводим цены к float
		foreach ($res as &$row) {
			if (isset($row['Price'])) {
				$row['Price'] = (float) $row['Price'];
			}
			if (isset($row['PriceBefore'])) {
				$row['PriceBefore'] = (float) $row['PriceBefore'];
			}
		}
		unset($row);

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $res ?? [],
			'pagination' => [
				'count' => $total,
				'limit' => $limit,
				'page' => $page,
			]
		];
	}

	/**
	 * M2M: PUT обновление цен товара
	 */
	public function putGoodsPrices(): array
	{
		$data = $this->normalizeInput()[0] ?? [];

		$goodsId = $data['goods_id'] ?? $data['id'] ?? 0;
		if (!$goodsId) {
			return ['status' => 400, 'message' => 'goods_id required', 'data' => null];
		}

		// Подготовить значения цен
		$updates = [];
		$params = [];

		if (isset($data['price'])) {
			$updates[] = 'Price = ?';
			$params[] = (float) $data['price'];
		}

		if (isset($data['price_out'])) {
			$updates[] = 'PriceOut = ?';
			$params[] = (float) $data['price_out'];
		}

		if (empty($updates)) {
			return ['status' => 400, 'message' => 'price or price_out required', 'data' => null];
		}

		// Добавить ID в параметры
		$params[] = $goodsId;

		// Обновить цены в Products
		$updatedCount = Connect::$instance->query(
			"UPDATE Products SET " . implode(', ', $updates) . " WHERE Id = ?",
			$params
		);

		if ($updatedCount <= 0) {
			return ['status' => 400, 'message' => 'Failed to update prices', 'data' => null];
		}

		// Вернуть обновленные данные
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Price, PriceOut FROM Products WHERE Id = ?",
			[$goodsId]
		);

		return ['status' => 200, 'message' => 'Prices updated', 'data' => $res[0] ?? null];
	}

	/**
	 * M2M: GET каталог товаров (категории)
	 */
	public function getGoodsCategories(): array
	{
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Url, ParentDir, Extension FROM s_Navigator WHERE IsHidden = 0 AND ParentDir = ? AND Id not in (?) ORDER BY Priority DESC",
			[Connect::$projectServices['navigator']['catalog'] ?? 0, Connect::$projectServices['navigator']['brands'] ?? 0]
		);

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}

	/**
	 * M2M: GET доступные фильтры для товаров (свойства и их значения)
	 */
	public function getGoodsFilters(): array
	{
		$category = (int) ($this->get['category'] ?? 0);
		$search = $this->get['search'] ?? '';

		$conditions = 't.IsHidden=0';
		$params = [];

		if ($category > 0) {
			$conditions .= ' AND t.NavigatorId = ?';
			$params[] = $category;
		}
		if ($search !== '') {
			$conditions .= ' AND lower(t.Name) LIKE lower(?)';
			$params[] = $search . '%';
		}

		$filters = new Filters();
		$result = $filters->getFilters(['conditions' => $conditions, 'params' => $params]);
		$grouped = [];
		foreach ($result as $rows) {
			$grouped[] = [
				'id' => (int) $rows[0]['PId'] ?? 0,
				'name' => $rows[0]['PName'] ?? '',
				'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue'], 'count' => (int) $r['Co']], $rows),
			];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $grouped];
	}

	/**
	 * M2M: POST перезаписать все фильтры/свойства
	 * Удаляет отсутствующие, обновляет существующие, добавляет новые
	 */
	public function patchGoodsFilters(): array
	{
		$data = $this->normalizeInput()[0] ?? [];

		$filtersList = $data['data'] ?? $data ?? [];
		if (empty($filtersList)) {
			return ['status' => 400, 'message' => 'data array required', 'data' => null];
		}

		// Получить текущие фильтры из БД
		$existing = Connect::$instance->fetch(
			"SELECT Id, Name FROM s_Properties ORDER BY Id"
		);
		$existingMap = [];
		foreach ($existing as $item) {
			$existingMap[(int) $item['Id']] = $item['Name'];
		}

		// Новые ID из переданного списка
		$newIds = [];

		// Обновить/добавить фильтры
		foreach ($filtersList as $filter) {
			$id = (int) ($filter['id'] ?? 0);
			$name = $filter['name'] ?? '';

			if (empty($name)) {
				continue;
			}

			if ($id > 0) {
				// Обновить существующий
				if (isset($existingMap[$id])) {
					Connect::$instance->query(
						"UPDATE s_Properties SET Name = ? WHERE Id = ?",
						[$name, $id]
					);
				}
				$newIds[] = $id;
			} else {
				// Добавить новый
				$newId = Connect::$instance->insert('s_Properties', [
					'Name' => $name,
					'Priority' => 0,
					'IsHidden' => 0,
				]);
				if ($newId) {
					$newIds[] = $newId;
				}
			}
		}

		// Удалить фильтры, которых нет в новом списке
		foreach ($existingMap as $id => $name) {
			if (!in_array($id, $newIds)) {
				Connect::$instance->query(
					"DELETE FROM s_Properties WHERE Id = ?",
					[$id]
				);
			}
		}

		return ['status' => 200, 'message' => 'Filters updated', 'data' => null];
	}

	/**
	 * M2M: GET изображения товаров (с постраничной выборкой)
	 */
	public function getGoodsImages(): array
	{
		return $this->getUtils('Products')->getFiles('Images', $this->get);
	}

	/**
	 * M2M: POST добавить изображение товару
	 */
	public function postGoodsImages(): array
	{
		return $this->getUtils('Products')->handleFileCreate($this->normalizeInput()[0] ?? []);
	}

	/**
	 * M2M: PUT обновить изображение товара
	 */
	public function putGoodsImages(): array
	{
		return $this->getUtils('Products')->handleFileUpdate($this->normalizeInput()[0] ?? []);
	}

	/**
	 * M2M: GET изображения вариаций товаров (с постраничной выборкой)
	 */
	public function getGoodsImagesVariations(): array
	{
		return $this->getUtils('Products')->getFiles('ImagesV', $this->get);
	}

	/**
	 * M2M: POST добавить изображение вариации товара
	 */
	public function postGoodsImagesVariations(): array
	{
		return $this->getUtils('ProductsVariations')->handleFileCreate($this->normalizeInput()[0] ?? []);
	}

	/**
	 * M2M: PUT обновить изображение вариации товара
	 */
	public function putGoodsImagesVariations(): array
	{
		return $this->getUtils('ProductsVariations')->handleFileUpdate($this->normalizeInput()[0] ?? []);
	}

	/**
	 * M2M: DELETE удалить изображение товара
	 */
	public function deleteGoodsImages(): array
	{
		$ids = $this->getNormalizedIds();
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils('s_Files')->remove($ids, 'Products');
	}

	/**
	 * M2M: DELETE удалить изображение вариации товара
	 */
	public function deleteGoodsImagesVariations(): array
	{
		$ids = $this->getNormalizedIds();
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils('s_Files')->remove($ids, 'ProductsVariations');
	}

	// ========================================================================
	// ORDERS
	// ========================================================================

	public function getOrders(): array
	{
		$this->getUtils('Orders')->setFields('Id,Name,IsHidden,UserId,Phone,Email,OStatus,OSum,ODate,ODelivery,OPayment,PostalCode,Address,City,Region,Country,JData,ODeliveryTariff,OPaymentTariff,ODeliveryDiscount,OPaymentDiscount');
		$result = $this->getUtils('Orders')->fetch($this->get);
		return $result;
	}

	public function getOrdersItem(): array
	{
		$id = $this->getIdFromRequest();
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		$this->getUtils('Orders')->setFields('Id,Name,IsHidden,UserId,Phone,Email,OStatus,OSum,ODate,ODelivery,OPayment,PostalCode,Address,City,Region,Country,JData,ODeliveryTariff,OPaymentTariff,ODeliveryDiscount,OPaymentDiscount');
		return $this->getUtils('Orders')->item($id);
	}

	public function getTasksResult(): array
	{
		$id = $this->getIdFromRequest();
		if (!$id) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$rows = Connect::$instance->fetch(
			"SELECT Id, Name, LDate, TRequest, Url, IsProcessed, InProgress, BResponse, SResponse FROM s_Tasks WHERE Id = ?",
			[$id]
		);

		if (empty($rows)) {
			return ['status' => 404, 'message' => 'Task not found', 'data' => null];
		}

		$task = $rows[0];

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => [
				'id' => (int) $task['Id'],
				'name' => $task['Name'],
				'created_at' => $task['LDate'],
				'type' => $task['TRequest'],
				'url' => $task['Url'],
				'is_processed' => (bool) $task['IsProcessed'],
				'in_progress' => (bool) $task['InProgress'],
				'http_status' => $task['SResponse'] ? (int) $task['SResponse'] : null,
				'response' => $task['BResponse'] ? json_decode($task['BResponse'], true) : null,
			],
		];
	}

	public function postOrders(): array
	{
		$records = $this->normalizeInput();
		return $this->create('Orders', $records);
	}

	public function putOrders(): array
	{
		$records = $this->normalizeInput();
		return $this->update('Orders', $records);
	}

	public function deleteOrders(): array
	{
		$ids = $this->getNormalizedIds();
		if (empty($ids)) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils('Orders')->remove($ids);
	}

	// ========================================================================
	// UTILITIES
	// ========================================================================

	protected function getUtils(string $tableName): RestV1M2MUtils
	{
		if (!isset($this->utils[$tableName])) {
			$this->utils[$tableName] = new RestV1M2MUtils($tableName);
		}
		return $this->utils[$tableName];
	}

	/**
	 * Создать записи (одну или пакет).
	 *
	 * @param string $tableName
	 * @param array  $records [плоские записи из normalizeInput()]
	 * @return array
	 */
	protected function create(string $tableName, array $records): array
	{
		$errors = [];
		$valid = [];

		foreach ($records as $index => $record) {
			try {
				$this->validate($tableName, $record, true);
				$valid[$index] = $record;
			} catch (\Exception $e) {
				$errors[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
			}
		}

		$results = $errors;
		if (!empty($valid)) {
			$results += $this->getUtils($tableName)->addBatch($valid);
		}

		$this->getUtils($tableName)->updateSearchIndex($results);

		if (count($records) === 1) {
			return $results[0] ?? ['status' => 400, 'message' => 'No result', 'data' => null];
		}

		return ['status' => 207, 'message' => 'Multi-Status', 'data' => $results];
	}

	/**
	 * Обновить записи (одну или пакет).
	 *
	 * @param string $tableName
	 * @param array  $records [плоские записи с 'id' из normalizeInput()]
	 * @return array
	 */
	protected function update(string $tableName, array $records): array
	{
		$errors = [];
		$valid = [];

		foreach ($records as $index => $record) {
			$id = (int) ($record['id'] ?? $record['Id'] ?? 0);
			if (!$id) {
				$errors[$index] = ['status' => 400, 'message' => 'id required', 'data' => null];
				continue;
			}
			try {
				$this->validate($tableName, $record, false);
				$valid[$index] = $record;
			} catch (\Exception $e) {
				$errors[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
			}
		}

		// Проверяем существование всех ID перед обновлением
		if (!empty($valid)) {
			$ids = array_filter(
				array_column($valid, 'id'),
				fn($v) => (int)$v > 0
			);

			if (!empty($ids)) {
				$placeholders = Connect::$instance->in($ids);
				$existing = Connect::$instance->fetch(
					"SELECT Id FROM $tableName WHERE Id IN ($placeholders)",
					$ids
				);
				$existingIds = array_column($existing, 'Id');

				// Для не найденных ID добавляем в ошибки
				foreach ($valid as $index => $record) {
					$recordId = (int)($record['id'] ?? 0);
					if ($recordId && !in_array($recordId, $existingIds)) {
						$errors[$index] = ['status' => 404, 'message' => 'Record not found', 'data' => null];
						unset($valid[$index]);
					}
				}
			}
		}

		$results = $errors;
		if (!empty($valid)) {
			$results += $this->getUtils($tableName)->setBatch($valid);
		}

		$this->getUtils($tableName)->updateSearchIndex($results);

		if (count($records) === 1) {
			return $results[0] ?? ['status' => 400, 'message' => 'No result', 'data' => null];
		}

		return ['status' => 207, 'message' => 'Multi-Status', 'data' => $results];
	}


	/**
	 * Helper: расчет параметров пагинации из GET параметров
	 */
	private function calculatePagination(int $maxLimit = 100): array
	{
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = (int) ($this->get['limit'] ?? 100);
		if ($limit > $maxLimit) {
			$limit = $maxLimit;
		}
		if ($limit < 1) {
			$limit = 100;
		}

		$offset = ($page - 1) * $limit;

		return [
			'page' => $page,
			'limit' => $limit,
			'offset' => $offset,
		];
	}

	/**
	 * Валидировать запись по правилам из s_ConfigFields.
	 *
	 * @param string $tableName
	 * @param array  $record
	 * @param bool   $requireAll true = POST (обязательные поля проверяются), false = PUT (partial update)
	 * @throws \Exception
	 */
	private function validate(string $tableName, array $record, bool $requireAll): void
	{
		$rules = $this->getUtils($tableName)->getFieldRules();
		if (empty($rules)) {
			return;
		}

		if (!$requireAll) {
			$rules = array_map(fn($r) => array_merge($r, ['required' => false]), $rules);
		}

		$this->rest->validateData($record, $rules);
	}

	/**
	 * Получить ID из GET-параметра ?id=
	 */
	private function getIdFromRequest(): int
	{
		return (int) ($this->get['id'] ?? 0);
	}

	/**
	 * Получить нормализованный массив ID из:
	 * 1. GET параметра ?id= (одиночный ID)
	 * 2. Body параметра {"data": [1, 2, 3]} (массив ID в едином формате)
	 * 
	 * @return array массив ID (может быть пустым)
	 */
	private function getNormalizedIds(): array
	{
		// Сначала проверяем GET параметр ?id=
		$id = (int) ($this->get['id'] ?? 0);
		if ($id > 0) {
			return [$id];
		}

		// Развёртываем {"data": [1, 2, 3]}
		$raw = $this->data ?? [];
		if (isset($raw['data']) && is_array($raw['data'])) {
			$raw = $raw['data'];
		}
		if (empty($raw)) {
			return [];
		}

		// Преобразуем в целые числа и фильтруем
		return array_filter(
			array_map(fn($v) => (int) $v, $raw),
			fn($v) => $v > 0
		);
	}

	/**
	 * Нормализовать входные данные в массив плоских записей.
	 * - Разворачивает обёртку {"data": ...}.
	 * - Одиночная запись преобразуется в [{...}].
	 * - Для PUT-одиночного без 'id' в теле: подхватывает ?id= из GET.
	 *
	 * @return array [{...}] или [] если данных нет
	 */
	private function normalizeInput(): array
	{
		$raw = $this->data ?? [];
		if (isset($raw['data']) && is_array($raw['data'])) {
			$raw = $raw['data'];
		}
		if (empty($raw)) {
			return [];
		}

		$records = (isset($raw[0]) && is_array($raw[0])) ? $raw : [$raw];

		// Для одиночного PUT без id в теле запроса: подхватить ?id= из GET
		if (count($records) === 1 && !isset($records[0]['id']) && !isset($records[0]['Id'])) {
			$id = $this->getIdFromRequest();
			if ($id) {
				$records[0]['id'] = $id;
			}
		}

		return $records;
	}
}


