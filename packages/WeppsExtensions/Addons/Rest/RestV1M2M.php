<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Utils;
use WeppsExtensions\Template\Filters\Filters;
use WeppsExtensions\Products\ProductsUtils;

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
	private ?RestV1M2MUtils $utils = null;

	/**
	 * Получить utils (lazy init)
	 */
	protected function getUtils(): RestV1M2MUtils
	{
		if ($this->utils === null) {
			$this->utils = new RestV1M2MUtils();
		}
		return $this->utils;
	}

	/**
	 * Валидировать GET параметры на основе s_ConfigFields
	 * 
	 * @param string $tableName - имя таблицы для получения правил
	 * @param array $data - данные для валидации
	 * @throws \Exception если валидация не пройдена
	 */
	protected function validateQueryParams(string $tableName, array $data): void
	{
		$rules = $this->getUtils()->getFieldRules($tableName);
		if (empty($rules)) {
			// Если правил нет в БД, используем ослабленную валидацию
			return;
		}

		// Валидация через родительский класс Rest
		$this->rest->validateData($data, $rules);
	}

	/**
	 * Валидировать POST данные на основе s_ConfigFields
	 * 
	 * @param string $tableName - имя таблицы для получения правил
	 * @param array $data - данные для валидации
	 * @throws \Exception если валидация не пройдена
	 */
	protected function validatePostData(string $tableName, array $data): void
	{
		$rules = $this->getUtils()->getFieldRules($tableName);
		if (empty($rules)) {
			// Если правил нет в БД, используем ослабленную валидацию
			return;
		}

		// Валидация через родительский класс Rest
		$this->rest->validateData($data, $rules);
	}

	// ========================================================================
	// USERS
	// ========================================================================

	public function getUsers(): array
	{
		// GET параметры - служебные (page, limit, search, sort)
		return $this->getUtils()->fetch('s_Users', $this->get);
	}

	public function getUsersItem(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->item('s_Users', $id);
	}

	public function postUsers($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		// Валидировать POST данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('s_Users', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}

		return $this->getUtils()->add('s_Users', $data);
	}

	public function putUsers($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		$id = $data['id'] ?? $data['Id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		// Валидировать PUT данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('s_Users', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}

		return $this->getUtils()->set('s_Users', $id, $data);
	}

	public function deleteUsers(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->remove('s_Users', $id);
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
			$filtersByCompositeKey = $filtersObj->getFilters([
				'conditions' => "t.IsHidden=0 AND pv.TableName='Products' AND pv.TableNameId IN ($placeholders)",
				'params' => $ids,
			]);

			// Перегруппируем compositeKey в структуру [ProductId => [PropertyId => rows]]
			$filterResult = $filtersObj->groupByProductId($filtersByCompositeKey);
		}
		// Распределяем атрибуты по товарам
		foreach ($rows as &$row) {
			$row['W_Attributes'] = $this->getUtils()->buildAttributesFromPropertiesValues($filterResult[$row['id']] ?? null);
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
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		// Используем логику getGoods() для обогащения W_Attributes
		return $this->getGoods();
	}

	public function postGoods($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		// Валидировать POST данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Products', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}

		return $this->getUtils()->add('Products', $data);
	}

	public function putGoods($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		$id = $data['id'] ?? $data['Id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		// Валидировать PUT данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Products', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}

		return $this->getUtils()->set('Products', $id, $data);
	}

	public function deleteGoods(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->remove('Products', $id);
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
		foreach ($result as $id => $rows) {
			$grouped[] = [
				'id' => (int) $id,
				'name' => $rows[0]['PropertyName'] ?? '',
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
		$data = $this->extractRequestData($data);
		if (!$data) {
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

		// Если передан ID товара, получить его запасы
		if ($goodsId > 0) {
			$res = Connect::$instance->fetch(
				"SELECT Id, Name, Amount FROM Products WHERE Id = ?",
				[$goodsId]
			);
			if (empty($res)) {
				return ['status' => 404, 'message' => 'Goods not found', 'data' => null];
			}
			return ['status' => 200, 'message' => 'OK', 'data' => $res];
		}

		// Иначе вернуть запасы всех товаров
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Amount FROM Products WHERE IsDeleted = 0 ORDER BY Name"
		);
		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}

	/**
	 * M2M: GET цены товаров
	 */
	public function getGoodsPrices(): array
	{
		$goodsId = (int) ($this->get['goods_id'] ?? 0);

		// Если передан ID товара, получить его цены
		if ($goodsId > 0) {
			$res = Connect::$instance->fetch(
				"SELECT Id, Name, Price, PriceOut FROM Products WHERE Id = ?",
				[$goodsId]
			);
			if (empty($res)) {
				return ['status' => 404, 'message' => 'Goods not found', 'data' => null];
			}
			return ['status' => 200, 'message' => 'OK', 'data' => $res];
		}

		// Иначе вернуть цены всех товаров
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Price, PriceOut FROM Products WHERE IsDeleted = 0 ORDER BY Name"
		);
		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}

	/**
	 * M2M: GET изображения товаров (с постраничной выборкой)
	 */
	public function getGoodsImages(): array
	{
		$goodsId = (int) ($this->get['goods_id'] ?? 0);
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = (int) ($this->get['limit'] ?? 100);
		if ($limit > 1000)
			$limit = 1000;
		if ($limit < 1)
			$limit = 100;

		$offset = ($page - 1) * $limit;

		// Если передан ID товара, получить его изображения
		if ($goodsId > 0) {
			$res = Connect::$instance->fetch(
				"SELECT Id, TableNameId as goods_id, Name, InnerName, FileUrl FROM s_Files 
				 WHERE TableName = 'Products' AND TableNameId = ? 
				 ORDER BY Id DESC 
				 LIMIT ? OFFSET ?",
				[$goodsId, $limit, $offset]
			);
			// Получить общее количество
			$count = Connect::$instance->fetch(
				"SELECT COUNT(*) as total FROM s_Files WHERE TableName = 'Products' AND TableNameId = ?",
				[$goodsId]
			);
			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $res ?? [],
				'pagination' => [
					'page' => $page,
					'limit' => $limit,
					'total' => (int) ($count[0]['total'] ?? 0),
					'pages' => (int) ceil(($count[0]['total'] ?? 0) / $limit),
				]
			];
		}

		// Иначе вернуть все изображения товаров с пагинацией
		$res = Connect::$instance->fetch(
			"SELECT Id, TableNameId as goods_id, Name, InnerName, FileUrl FROM s_Files 
			 WHERE TableName = 'Products' 
			 ORDER BY TableNameId DESC, Id DESC 
			 LIMIT ? OFFSET ?",
			[$limit, $offset]
		);
		// Получить общее количество
		$count = Connect::$instance->fetch(
			"SELECT COUNT(*) as total FROM s_Files WHERE TableName = 'Products'"
		);
		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $res ?? [],
			'pagination' => [
				'page' => $page,
				'limit' => $limit,
				'total' => (int) ($count[0]['total'] ?? 0),
				'pages' => (int) ceil(($count[0]['total'] ?? 0) / $limit),
			]
		];
	}

	/**
	 * M2M: POST добавить изображение товару
	 */
	public function postGoodsImages($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$goodsId = $data['goods_id'] ?? $data['TableNameId'] ?? 0;
		if (!$goodsId) {
			return ['status' => 400, 'message' => 'goods_id required', 'data' => null];
		}

		$name = $data['name'] ?? $data['Name'] ?? '';
		$fileUrl = null;
		$fileName = $data['file_name'] ?? '';

		// Приоритет: file_url > file_base64 > multipart
		// 1. Проверить file_url
		if (!empty($data['file_url'])) {
			$fileUrl = $data['file_url'];
		}
		// 2. Проверить file_base64
		elseif (!empty($data['file_base64'])) {
			if (!$fileName) {
				return ['status' => 400, 'message' => 'file_name required for base64', 'data' => null];
			}
			$binaryData = base64_decode($data['file_base64'], true);
			if (!$binaryData) {
				return ['status' => 400, 'message' => 'Invalid base64 data', 'data' => null];
			}
			$fileUrl = $this->saveUploadedFile($binaryData, $fileName, $goodsId);
			if (!$fileUrl) {
				return ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
			}
		}
		// 3. Проверить multipart файл
		elseif (!empty($_FILES['file'])) {
			$file = $_FILES['file'];
			if ($file['error'] !== UPLOAD_ERR_OK) {
				return ['status' => 400, 'message' => 'File upload error', 'data' => null];
			}
			$binaryData = file_get_contents($file['tmp_name']);
			if (!$binaryData) {
				return ['status' => 400, 'message' => 'Failed to read file', 'data' => null];
			}
			$fileUrl = $this->saveUploadedFile($binaryData, $file['name'], $goodsId);
			if (!$fileUrl) {
				return ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
			}
		} else {
			return ['status' => 400, 'message' => 'file_url, file_base64 or multipart file required', 'data' => null];
		}

		// Генерировать InnerName (путь к сохранённому файлу)
		$innerName = str_replace('/pic/lists/Products/', '', $fileUrl);

		// Вставить новое изображение
		$id = Connect::$instance->insert('s_Files', [
			'TableName' => 'Products',
			'TableNameId' => $goodsId,
			'Name' => $name ?: $fileName,
			'InnerName' => $innerName,
			'FileUrl' => $fileUrl,
		]);

		if (!$id) {
			return ['status' => 400, 'message' => 'Failed to add image', 'data' => null];
		}

		return ['status' => 201, 'message' => 'Image added', 'data' => ['id' => $id]];
	}

	/**
	 * M2M: PUT обновить изображение товара
	 */
	public function putGoodsImages($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$imageId = $data['id'] ?? 0;
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		// Проверить существование изображения
		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = 'Products'",
			[$imageId]
		);
		if (empty($existing)) {
			return ['status' => 404, 'message' => 'Image not found', 'data' => null];
		}

		// Подготовить данные для обновления
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

		// Обновить изображение
		$result = Connect::$instance->query(
			"UPDATE s_Files SET " . implode(', ', $updates) . " WHERE Id = ?",
			$params
		);

		if ($result <= 0) {
			return ['status' => 400, 'message' => 'Failed to update image', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Image updated', 'data' => ['id' => $imageId]];
	}

	/**
	 * M2M: GET изображения вариаций товаров (с постраничной выборкой)
	 */
	public function getGoodsImagesVariations(): array
	{
		$goodsvId = (int) ($this->get['goodsv_id'] ?? 0);
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = (int) ($this->get['limit'] ?? 100);
		if ($limit > 1000)
			$limit = 1000;
		if ($limit < 1)
			$limit = 100;

		$offset = ($page - 1) * $limit;

		// Если передан ID вариации, получить его изображения
		if ($goodsvId > 0) {
			$res = Connect::$instance->fetch(
				"SELECT Id, TableNameId as goodsv_id, Name, InnerName, FileUrl FROM s_Files 
				 WHERE TableName = 'ProductsVariations' AND TableNameId = ? 
				 ORDER BY Id DESC 
				 LIMIT ? OFFSET ?",
				[$goodsvId, $limit, $offset]
			);
			// Получить общее количество
			$count = Connect::$instance->fetch(
				"SELECT COUNT(*) as total FROM s_Files WHERE TableName = 'ProductsVariations' AND TableNameId = ?",
				[$goodsvId]
			);
			return [
				'status' => 200,
				'message' => 'OK',
				'data' => $res ?? [],
				'pagination' => [
					'page' => $page,
					'limit' => $limit,
					'total' => (int) ($count[0]['total'] ?? 0),
					'pages' => (int) ceil(($count[0]['total'] ?? 0) / $limit),
				]
			];
		}

		// Иначе вернуть все изображения вариаций с пагинацией
		$res = Connect::$instance->fetch(
			"SELECT Id, TableNameId as goodsv_id, Name, InnerName, FileUrl FROM s_Files 
			 WHERE TableName = 'ProductsVariations' 
			 ORDER BY TableNameId DESC, Id DESC 
			 LIMIT ? OFFSET ?",
			[$limit, $offset]
		);
		// Получить общее количество
		$count = Connect::$instance->fetch(
			"SELECT COUNT(*) as total FROM s_Files WHERE TableName = 'ProductsVariations'"
		);
		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $res ?? [],
			'pagination' => [
				'page' => $page,
				'limit' => $limit,
				'total' => (int) ($count[0]['total'] ?? 0),
				'pages' => (int) ceil(($count[0]['total'] ?? 0) / $limit),
			]
		];
	}

	/**
	 * M2M: POST добавить изображение вариации товара
	 */
	public function postGoodsImagesVariations($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$goodsvId = $data['goodsv_id'] ?? $data['TableNameId'] ?? 0;
		if (!$goodsvId) {
			return ['status' => 400, 'message' => 'goodsv_id required', 'data' => null];
		}

		$name = $data['name'] ?? $data['Name'] ?? '';
		$fileUrl = null;
		$fileName = $data['file_name'] ?? '';

		// Приоритет: file_url > file_base64 > multipart
		// 1. Проверить file_url
		if (!empty($data['file_url'])) {
			$fileUrl = $data['file_url'];
		}
		// 2. Проверить file_base64
		elseif (!empty($data['file_base64'])) {
			if (!$fileName) {
				return ['status' => 400, 'message' => 'file_name required for base64', 'data' => null];
			}
			$binaryData = base64_decode($data['file_base64'], true);
			if (!$binaryData) {
				return ['status' => 400, 'message' => 'Invalid base64 data', 'data' => null];
			}
			$fileUrl = $this->saveUploadedFileVariation($binaryData, $fileName, $goodsvId);
			if (!$fileUrl) {
				return ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
			}
		}
		// 3. Проверить multipart файл
		elseif (!empty($_FILES['file'])) {
			$file = $_FILES['file'];
			if ($file['error'] !== UPLOAD_ERR_OK) {
				return ['status' => 400, 'message' => 'File upload error', 'data' => null];
			}
			$binaryData = file_get_contents($file['tmp_name']);
			if (!$binaryData) {
				return ['status' => 400, 'message' => 'Failed to read file', 'data' => null];
			}
			$fileUrl = $this->saveUploadedFileVariation($binaryData, $file['name'], $goodsvId);
			if (!$fileUrl) {
				return ['status' => 400, 'message' => 'Failed to save file', 'data' => null];
			}
		} else {
			return ['status' => 400, 'message' => 'file_url, file_base64 or multipart file required', 'data' => null];
		}

		// Генерировать InnerName (путь к сохранённому файлу)
		$innerName = str_replace('/pic/lists/ProductsVariations/', '', $fileUrl);

		// Вставить новое изображение
		$id = Connect::$instance->insert('s_Files', [
			'TableName' => 'ProductsVariations',
			'TableNameId' => $goodsvId,
			'Name' => $name ?: $fileName,
			'InnerName' => $innerName,
			'FileUrl' => $fileUrl,
		]);

		if (!$id) {
			return ['status' => 400, 'message' => 'Failed to add image', 'data' => null];
		}

		return ['status' => 201, 'message' => 'Image added', 'data' => ['id' => $id]];
	}

	/**
	 * M2M: PUT обновить изображение вариации товара
	 */
	public function putGoodsImagesVariations($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		$imageId = $data['id'] ?? 0;
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		// Проверить существование изображения
		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = 'ProductsVariations'",
			[$imageId]
		);
		if (empty($existing)) {
			return ['status' => 404, 'message' => 'Image not found', 'data' => null];
		}

		// Подготовить данные для обновления
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

		// Обновить изображение
		$result = Connect::$instance->query(
			"UPDATE s_Files SET " . implode(', ', $updates) . " WHERE Id = ?",
			$params
		);

		if ($result <= 0) {
			return ['status' => 400, 'message' => 'Failed to update image', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Image updated', 'data' => ['id' => $imageId]];
	}

	/**
	 * M2M: DELETE удалить изображение товара
	 */
	public function deleteGoodsImages(): array
	{
		$imageId = (int) ($this->get['id'] ?? 0);
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		// Проверить существование изображения
		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = 'Products'",
			[$imageId]
		);
		if (empty($existing)) {
			return ['status' => 404, 'message' => 'Image not found', 'data' => null];
		}

		// Удалить изображение
		$result = Connect::$instance->query(
			"DELETE FROM s_Files WHERE Id = ? AND TableName = 'Products'",
			[$imageId]
		);

		if ($result <= 0) {
			return ['status' => 400, 'message' => 'Failed to delete image', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Image deleted', 'data' => ['id' => $imageId]];
	}

	/**
	 * M2M: DELETE удалить изображение вариации товара
	 */
	public function deleteGoodsImagesVariations(): array
	{
		$imageId = (int) ($this->get['id'] ?? 0);
		if (!$imageId) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		// Проверить существование изображения
		$existing = Connect::$instance->fetch(
			"SELECT Id FROM s_Files WHERE Id = ? AND TableName = 'ProductsVariations'",
			[$imageId]
		);
		if (empty($existing)) {
			return ['status' => 404, 'message' => 'Image not found', 'data' => null];
		}

		// Удалить изображение
		$result = Connect::$instance->query(
			"DELETE FROM s_Files WHERE Id = ? AND TableName = 'ProductsVariations'",
			[$imageId]
		);

		if ($result <= 0) {
			return ['status' => 400, 'message' => 'Failed to delete image', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Image deleted', 'data' => ['id' => $imageId]];
	}

	/**
	 * M2M: PUT обновление запасов товара
	 */
	public function putGoodsStocks($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
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
		$data = $this->extractRequestData($data);
		if (!$data) {
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
		return $this->getUtils()->fetch('Orders', $this->get);
	}

	public function getOrdersItem(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->item('Orders', $id);
	}

	public function postOrders($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}

		// Валидировать POST данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Orders', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}

		return $this->getUtils()->add('Orders', $data);
	}

	public function putOrders($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		$id = $data['id'] ?? $data['Id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}

		// Валидировать PUT данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Orders', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}

		return $this->getUtils()->set('Orders', $id, $data);
	}

	public function deleteOrders(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->remove('Orders', $id);
	}

	// ========================================================================
	// UTILITIES
	// ========================================================================

	protected function extractRequestData($data): array
	{
		if ($data && isset($data['data']) && is_array($data['data'])) {
			return $data['data'];
		}

		$input = file_get_contents('php://input');
		if ($input) {
			$decoded = @json_decode($input, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}

		return [];
	}

	/**
	 * Сохранить загруженный файл (из base64 или multipart)
	 * @param string $binaryData - бинарные данные файла
	 * @param string $fileName - имя файла (с расширением)
	 * @param int $goodsId - ID товара
	 * @return string|null - путь к файлу или null если ошибка
	 */
	private function saveUploadedFile(string $binaryData, string $fileName, int $goodsId): ?string
	{
		// Определить расширение
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		if (!$ext) {
			return null;
		}

		// Генерировать InnerName (хеш имени + расширение)
		$innerName = md5(uniqid($goodsId . '_', true)) . '.' . strtolower($ext);

		// Путь для сохранения: /pic/lists/Products/{goods_id}/
		$dir = __DIR__ . '/../../../pic/lists/Products/' . $goodsId;
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0755, true)) {
				return null;
			}
		}

		// Сохранить файл
		$filePath = $dir . '/' . $innerName;
		if (file_put_contents($filePath, $binaryData) === false) {
			return null;
		}

		// Вернуть путь в формате /pic/lists/Products/{goods_id}/{innerName}
		return '/pic/lists/Products/' . $goodsId . '/' . $innerName;
	}

	/**
	 * Сохранить загруженный файл вариации (из base64 или multipart)
	 * @param string $binaryData - бинарные данные файла
	 * @param string $fileName - имя файла (с расширением)
	 * @param int $goodsvId - ID вариации товара
	 * @return string|null - путь к файлу или null если ошибка
	 */
	private function saveUploadedFileVariation(string $binaryData, string $fileName, int $goodsvId): ?string
	{
		// Определить расширение
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		if (!$ext) {
			return null;
		}

		// Генерировать InnerName (хеш имени + расширение)
		$innerName = md5(uniqid($goodsvId . '_', true)) . '.' . strtolower($ext);

		// Путь для сохранения: /pic/lists/ProductsVariations/{goodsv_id}/
		$dir = __DIR__ . '/../../../pic/lists/ProductsVariations/' . $goodsvId;
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0755, true)) {
				return null;
			}
		}

		// Сохранить файл
		$filePath = $dir . '/' . $innerName;
		if (file_put_contents($filePath, $binaryData) === false) {
			return null;
		}

		// Вернуть путь в формате /pic/lists/ProductsVariations/{goodsv_id}/{innerName}
		return '/pic/lists/ProductsVariations/' . $goodsvId . '/' . $innerName;
	}
}


