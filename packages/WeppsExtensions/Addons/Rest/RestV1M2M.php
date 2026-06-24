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
		$result = $this->getUtils('s_Users')->fetch($this->get);
		return $result;
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

	public function postUsers($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		return $this->create('s_Users', $records);
	}

	public function putUsers($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
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
				'pages' => 1,
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
				'pages' => $limit,
				'page' => $page,
				'sorting' => $sorting,
				'conditions' => $conditionsWithFilters,
				'useApiMapping' => true,
			];
		}

		$result = $productsUtils->getProducts($settings);
		$rows = $result['rows'] ?? [];

		// Получаем все атрибуты для всех товаров одним вызовом Filters
		// Filters::getFilters() возвращает с группировкой по compositeKey "ProductId-PropertyId"
		$filterResult = [];
		$ids = array_column($rows, 'id');

		if (!empty($ids)) {
			$placeholders = Connect::$instance->in($ids);
			$filtersObj = new Filters();
			$filterResult = $filtersObj->getFilters([
				'conditions' => "t.IsHidden=0 AND pv.TableName='Products' AND pv.TableNameId IN ($placeholders)",
				'params' => $ids,
			]);

			// Перегруппируем compositeKey в структуру [ProductId => [PropertyId => rows]]
			// $filterResult = $filtersObj->groupByProductId($filtersByCompositeKey);
		}
		// Распределяем атрибуты по товарам
		foreach ($rows as &$row) {
			$row['W_Attributes'] = $this->getUtils('Products')->buildAttributesFromPropertiesValues($filterResult[$row['id']] ?? null);
		}
		unset($row);

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $rows,
			'pagination' => ['count' => $result['count'], 'limit' => $limit, 'page' => $page],
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

	public function postGoods($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		return $this->create('Products', $records);
	}

	public function putGoods($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		return $this->update('Products', $records);
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
				'count' => $data->count,
				'limit' => $limit,
				'page' => $page,
			],
		];
	}

	/**
	 * M2M: POST создание вариаций товаров (одна или batch).
	 * Не скрывает существующие вариации — только добавляет новые.
	 * При дубликате (Alias) возвращает 409 с id существующей.
	 *
	 * Валидация по RestConfig уже выполнена в Rest::executeHandler() перед вызовом метода!
	 * Формат тела: { "data": [ { "goodsId": 723, "name": "Вариант", "sku": "SKU001", "color": "Красный", "size": "42" }, ... ] }
	 */
	public function postGoodsVariations($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$processing = new ProcessingProducts();
		$results = [];

		// Данные уже валидированы в Rest::executeHandler()
		foreach ($records as $index => $record) {
			$goodsId = (int) ($record['goodsId'] ?? 0);
			$results[$index] = $processing->createVariation($goodsId, $record);
		}

		if (count($records) === 1) {
			return $results[0];
		}

		$hasErrors = !empty(array_filter($results, fn($r) => ($r['status'] ?? 200) >= 400 && ($r['status'] ?? 200) !== 409));
		return [
			'status' => $hasErrors ? 207 : 201,
			'message' => 'Multi-Status',
			'data' => $results,
		];
	}

	/**
	 * M2M: PUT обновление вариаций по id (одна или batch).
	 * Обновляет конкретные вариации по их ID — без скрытия остальных.
	 *
	 * Одна запись: ?id=123 или { "data": { "id": 123, "color": "Синий" } }
	 * Batch: { "data": [ { "id": 1, "sku": "NEW" }, { "id": 2, "color": "Зелёный" } ] }
	 */
	public function putGoodsVariations($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		return $this->update('ProductsVariations', $records);
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
	public function patchGoodsFilters($data = null): array
	{
		$data = $this->normalizeInput()[0] ?? [];
		if (empty($data)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

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
	 * M2M: GET запасы товаров (доступность на складах)
	 */
	public function getGoodsStocks(): array
	{
		$goodsId = (int) ($this->get['goods_id'] ?? 0);
		['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->calculatePagination(500);
		$limit = (int) $limit;
		$offset = (int) $offset;

		$skuConfig = Connect::$projectServices['api-m2m']['sku'];
		$fieldsList = [];
		foreach ($skuConfig as $field => $alias) {
			$fieldsList[] = "$field $alias";
		}
		$fields = implode(', ', $fieldsList);

		// Формируем условие WHERE
		$conditions = "IsHidden = 0";
		$params = [];
		if ($goodsId > 0) {
			$conditions .= " AND ProductsId = ?";
			$params[] = $goodsId;
		}

		// Получить данные с пагинацией
		$res = Connect::$instance->fetch(
			"SELECT Id, ProductsId GoodsId, {$fields}
			 FROM ProductsVariations
			 WHERE {$conditions}
			 ORDER BY Id DESC
			 LIMIT {$offset}, {$limit}",
			$params
		);

		// Получить общее количество
		$countRes = Connect::$instance->fetch(
			"SELECT COUNT(*) as total
			 FROM ProductsVariations
			 WHERE {$conditions}",
			$params
		);
		$total = (int) ($countRes[0]['total'] ?? 0);

		if (empty($res) && $total === 0) {
			return ['status' => 404, 'message' => 'Goods not found', 'data' => null];
		}

		// Приводим 'stocks' к float
		foreach ($res as &$row) {
			if (isset($row['stocks'])) {
				$row['stocks'] = (float) $row['stocks'];
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
	 * M2M: GET изображения товаров (с постраничной выборкой)
	 */
	public function getGoodsImages(): array
	{
		return $this->getUtils('Products')->getFiles('Images', $this->get);
	}

	/**
	 * M2M: POST добавить изображение товару
	 */
	public function postGoodsImages($data = null): array
	{
		return $this->handleFileCreate('Products', $this->normalizeInput()[0] ?? []);
	}

	/**
	 * M2M: PUT обновить изображение товара
	 */
	public function putGoodsImages($data = null): array
	{
		return $this->handleFileUpdate('Products', $this->normalizeInput()[0] ?? []);
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
	public function postGoodsImagesVariations($data = null): array
	{
		return $this->handleFileCreate('ProductsVariations', $this->normalizeInput()[0] ?? []);
	}

	/**
	 * M2M: PUT обновить изображение вариации товара
	 */
	public function putGoodsImagesVariations($data = null): array
	{
		return $this->handleFileUpdate('ProductsVariations', $this->normalizeInput()[0] ?? []);
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

	/**
	 * M2M: PUT обновление запасов товара
	 */
	public function putGoodsStocks($data = null): array
	{
		$data = $this->normalizeInput()[0] ?? [];
		if (empty($data)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$goodsId = $data['goods_id'] ?? $data['id'] ?? 0;
		if (!$goodsId) {
			return ['status' => 400, 'message' => 'goods_id required', 'data' => null];
		}

		$amount = $data['amount'] ?? null;
		if ($amount === null) {
			return ['status' => 400, 'message' => 'amount required', 'data' => null];
		}

		// Обновить Amount в Products
		$updated = Connect::$instance->query(
			"UPDATE Products SET Amount = ? WHERE Id = ?",
			[(float) $amount, $goodsId]
		);

		if ($updated <= 0) {
			return ['status' => 400, 'message' => 'Failed to update stocks', 'data' => null];
		}

		// Вернуть обновленные данные
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Amount FROM Products WHERE Id = ?",
			[$goodsId]
		);

		return ['status' => 200, 'message' => 'Stocks updated', 'data' => $res[0] ?? null];
	}

	/**
	 * M2M: PUT обновление цен товара
	 */
	public function putGoodsPrices($data = null): array
	{
		$data = $this->normalizeInput()[0] ?? [];
		if (empty($data)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

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

	public function postOrders($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		return $this->create('Orders', $records);
	}

	public function putOrders($data = null): array
	{
		$records = $this->normalizeInput();
		if (empty($records)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
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

		$this->updateSearchIndex($tableName, $results);

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

		$results = $errors;
		if (!empty($valid)) {
			$results += $this->getUtils($tableName)->setBatch($valid);
		}

		$this->updateSearchIndex($tableName, $results);

		if (count($records) === 1) {
			return $results[0] ?? ['status' => 400, 'message' => 'No result', 'data' => null];
		}

		return ['status' => 207, 'message' => 'Multi-Status', 'data' => $results];
	}

	/**
	 * Синхронизировать таблицу постранично (upsert + скрытие отсутствующих).
	 *
	 * Используется вместе с normalizeSyncInput().
	 * Формат запроса: {"data": [...], "pagination": {"page": 1, "count": 5}}
	 *
	 * @param string $tableName
	 * @param array  $records    [плоские записи из normalizeSyncInput()]
	 * @param array  $pagination ['page' => int, 'count' => int]
	 * @return array
	 */
	protected function sync(string $tableName, array $records, array $pagination): array
	{
		$errors = [];
		$valid = [];

		foreach ($records as $index => $record) {
			try {
				$this->validate($tableName, $record, false);
				$valid[$index] = $record;
			} catch (\Exception $e) {
				$errors[$index] = ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
			}
		}

		$results = $errors;
		if (!empty($valid)) {
			$results += $this->getUtils($tableName)->syncBatch($valid, $pagination);
		}

		$this->updateSearchIndex($tableName, $results);

		if (count($records) === 1) {
			return $results[0] ?? ['status' => 400, 'message' => 'No result', 'data' => null];
		}

		return ['status' => 207, 'message' => 'Multi-Status', 'data' => $results];
	}

	/**
	 * Обновить поисковый индекс для созданных или обновлённых записей.	 *
	 * @param string $tableName
	 * @param array $result
	 */
	private function updateSearchIndex(string $tableName, array $result): void
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
				$searchSql .= Lists::setSearchIndex($tableName, $item['data']['id']);
			}
		}
		if (!empty($searchSql)) {
			Connect::$db->exec($searchSql);
		}
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
	 * Получить массив ID из:
	 * 1. GET параметра ?id= (одиночный ID)
	 * 2. Body параметра {"ids": [1, 2, 3]} (массив ID)
	 * 
	 * @deprecated Используйте getNormalizedIds() вместо этого - новый формат с {"data": [...]}
	 * @return array массив ID (может быть пустым)
	 */
	private function getIdsFromRequest(): array
	{
		// Сначала проверяем GET параметр ?id=
		$id = (int) ($this->get['id'] ?? 0);
		if ($id > 0) {
			return [$id];
		}
		if (empty($ids = ($this->data['ids'] ?? []))) {
			return [];
		}

		// Если это массив ID - вернуть как есть
		if (isset($ids) && is_array($ids)) {
			return array_filter(
				array_map(fn($v) => (int) $v, $ids),
				fn($v) => $v > 0
			);
		}

		return [];
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

	/**
	 * Нормализовать входные данные для sync-операции.
	 * Дополнительно извлекает ключ pagination из тела запроса.
	 *
	 * Ожидаемый формат тела: {"data": [...], "pagination": {"page": 1, "count": 5}}
	 *
	 * @return array ['records' => [...], 'pagination' => array|null]
	 */
	private function normalizeSyncInput(): array
	{
		$raw = $this->data ?? [];
		if (empty($raw)) {
			return ['records' => [], 'pagination' => null];
		}

		$pagination = (isset($raw['pagination']) && is_array($raw['pagination']))
			? $raw['pagination']
			: null;

		$data = $raw;
		if (isset($raw['data']) && is_array($raw['data'])) {
			$data = $raw['data'];
		}
		$records = (isset($data[0]) && is_array($data[0])) ? $data : [$data];

		if (count($records) === 1 && !isset($records[0]['id']) && !isset($records[0]['Id'])) {
			$id = $this->getIdFromRequest();
			if ($id) {
				$records[0]['id'] = $id;
			}
		}

		return ['records' => $records, 'pagination' => $pagination];
	}

	/**
	 * Сохранить загруженный файл (из base64 или multipart)
	 * @param string $binaryData - бинарные данные файла
	 * @param string $fileName - имя файла (с расширением)
	 * @param int $goodsId - ID товара
	 * @return string|null - путь к файлу или null если ошибка
	 */
	private function saveFile(string $binary, string $fileName, string $tableName, int $entityId): ?string
	{
		$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if (!$ext) {
			return null;
		}

		$innerName = md5(uniqid($entityId . '_', true)) . '.' . $ext;
		$dir = __DIR__ . '/../../../pic/lists/' . $tableName . '/' . $entityId;

		if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
			return null;
		}

		if (file_put_contents($dir . '/' . $innerName, $binary) === false) {
			return null;
		}

		return '/pic/lists/' . $tableName . '/' . $entityId . '/' . $innerName;
	}

	/**
	 * Сохранить загруженный файл вариации (из base64 или multipart)
	 * @param string $binaryData - бинарные данные файла
	 * @param string $fileName - имя файла (с расширением)
	 * @param int $goodsvId - ID вариации товара
	 * @return string|null - путь к файлу или null если ошибка
	 */
	private function resolveFileUrl(array $data, string $tableName, int $entityId): string|array
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
			$url = $this->saveFile($binary, $fileName, $tableName, $entityId);
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
			$url = $this->saveFile($binary, $file['name'], $tableName, $entityId);
			return $url ?? ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
		}

		return ['status' => 400, 'message' => 'file_url, file_base64 or multipart file required', 'data' => null];
	}

	private function handleFileCreate(string $tableName, array $data): array
	{
		if (empty($data)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$entityId = (int) ($data['goods_id'] ?? $data['TableNameId'] ?? 0);
		if (!$entityId) {
			return ['status' => 400, 'message' => 'goods_id required', 'data' => null];
		}

		$fileUrl = $this->resolveFileUrl($data, $tableName, $entityId);
		if (is_array($fileUrl)) {
			return $fileUrl;
		}

		$name = $data['name'] ?? $data['Name'] ?? $data['file_name'] ?? '';
		$innerName = str_replace('/pic/lists/' . $tableName . '/', '', $fileUrl);

		$id = Connect::$instance->insert('s_Files', [
			'TableName' => $tableName,
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

	private function handleFileUpdate(string $tableName, array $data): array
	{
		if (empty($data)) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$imageId = (int) ($data['id'] ?? 0);
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = ?",
			[$imageId, $tableName]
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

	private function handleFileDelete(string $tableName): array
	{
		$imageId = $this->getIdFromRequest();
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = ?",
			[$imageId, $tableName]
		);
		if (empty($existing)) {
			return ['status' => 404, 'message' => 'Image not found', 'data' => null];
		}

		$result = Connect::$instance->query(
			"DELETE FROM s_Files WHERE Id = ? AND TableName = ?",
			[$imageId, $tableName]
		);

		if ($result <= 0) {
			return ['status' => 400, 'message' => 'Failed to delete image', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Image deleted', 'data' => ['id' => $imageId]];
	}
}


