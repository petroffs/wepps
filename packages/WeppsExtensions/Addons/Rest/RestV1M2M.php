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
		$utils = $this->getUtils('s_Users');
		$utils->setFields('Id,Guid,Name,NameFirst,NameSurname,NamePatronymic,IsHidden,UserPermissions,CreateDate,Login,Email,Phone,Comment,Country,Region,City,Address,PostalCode');
		return $utils->fetch($this->get);
	}

	public function getUsersItem(): array
	{
		$utils = $this->getUtils('s_Users');
		$utils->setFields('Id,Guid,Name,NameFirst,NameSurname,NamePatronymic,IsHidden,UserPermissions,CreateDate,Login,Email,Phone,Comment,Country,Region,City,Address,PostalCode');
		return $utils->item((int) ($this->get['id'] ?? 0));
	}

	public function postUsers(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_Users');
		['before' => $callbackBefore, 'after' => $callbackAfter] = $utils->handlePagination($this->data['pagination'] ?? null);
		if ($callbackBefore || $callbackAfter) {
			$utils->setBefore($callbackBefore)->setAfter($callbackAfter);
		}

		return $this->create('s_Users', $records);
	}

	public function putUsers(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_Users');
		['before' => $callbackBefore, 'after' => $callbackAfter] = $utils->handlePagination($this->data['pagination'] ?? null);
		if ($callbackBefore || $callbackAfter) {
			$utils->setBefore($callbackBefore)->setAfter($callbackAfter);
		}
		// After callback ВНУТРИ транзакции (перед коммитом)
		// Например, можем создать личные кабинеты и разослать уведомления после обновления пользователей
		// $this->getUtils('s_Users')->setAfter(function ($results, $tableName) {

		// 	if (empty($results['data'])) {
		// 		return;
		// 	}
		// 	foreach ($results['data'] as $value) {

		// 	}
		// });
		return $this->update('s_Users', $records);
	}

	public function deleteUsers(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('s_Users')->remove($ids);
	}

	// ========================================================================
	// ORDERS
	// ========================================================================

	public function getOrders(): array
	{
		$utils = $this->getUtils('Orders');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,IsHidden,UserId,Phone,Email,OStatus,OSum,ODate,ODelivery,OPayment,PostalCode,Address,City,Region,Country,JData,ODeliveryTariff,OPaymentTariff,ODeliveryDiscount,OPaymentDiscount');
		return $utils->fetch($this->get);
	}

	public function getOrdersItem(): array
	{
		$utils = $this->getUtils('Orders');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,IsHidden,UserId,Phone,Email,OStatus,OSum,ODate,ODelivery,OPayment,PostalCode,Address,City,Region,Country,JData,ODeliveryTariff,OPaymentTariff,ODeliveryDiscount,OPaymentDiscount');
		return $utils->item((int) ($this->get['id'] ?? 0));
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
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('Orders')->remove($ids);
	}

	// ========================================================================
	// GOODS
	// ========================================================================

	public function getGoods()
	{
		$utils = $this->getUtils('Products');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,Alias,IsHidden,Priority,NavigatorId,PStatus,Article,Descr,MetaTitle,MetaDescription,MetaKeyword,WeightPack');
		return $utils->fetch($this->get);
	}

	public function getGoodsItem(): array
	{
		$utils = $this->getUtils('Products');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,Alias,IsHidden,Priority,NavigatorId,PStatus,Article,Descr,MetaTitle,MetaDescription,MetaKeyword,WeightPack');
		return $utils->item((int) ($this->get['id'] ?? 0));
	}

	public function postGoods(): array
	{
		$records = $this->normalizeInput();
		return $this->create('Products', $records);
	}

	public function putGoods(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('Products');
		['before' => $callbackBefore, 'after' => $callbackAfter] = $utils->handlePagination($this->data['pagination'] ?? null);
		if ($callbackBefore || $callbackAfter) {
			$utils->setBefore($callbackBefore)->setAfter($callbackAfter);
		}
		return $this->update('Products', $records);
	}

	public function deleteGoods(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());

		/**
		 * ! Используйте setAfter() для удаления связанных данных (например, вариаций, изображений, файлов и т.д.)
		 * Логика зависит от бизнес-требований. 
		 */
		return $this->getUtils('Products')->remove($ids);
	}

	/**
	 * M2M: GET каталог товаров (navigator)
	 */
	public function getGoodsNavigator(): array
	{
		$utils = $this->getUtils('s_Navigator');
		$utils->setOrderBy('t.Priority asc');
		$utils->setFields('Id, Guid, Name, Url, ParentId, Extension');
		$utils->setParams([(Connect::$projectServices['navigator']['catalog'] ?? 0), (Connect::$projectServices['extensions']['catalog'] ?? 0)]);
		return $utils->fetch($this->get, "t.IsHidden = 0 AND t.ParentId = ? AND t.Id not in (?)");
	}

	public function postGoodsNavigator(): array
	{
		$records = $this->normalizeInput();
		$this->getUtils('s_Navigator')->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) {
			if (empty($results)) {
				return null;
			}
			$ids = [];
			foreach ($results as $value) {
				if ($value['status'] === 201 && isset($value['data']['id'])) {
					$ids[] = (int) $value['data']['id'];
				}
			}
			if (empty($ids)) {
				return null;
			}
			$sql = "UPDATE {$tableName} SET ParentId=?, Extension=? where Id in (" . Connect::$instance->in($ids) . ")";
			Connect::$instance->query($sql, [(Connect::$projectServices['navigator']['catalog'] ?? 0), (Connect::$projectServices['extensions']['catalog'] ?? 0), ...$ids]);
		});
		return $this->create('s_Navigator', $records);
	}

	public function putGoodsNavigator(): array
	{
		$records = $this->normalizeInput();

		// Проверяем, допустимы ли идентификаторы из входящего массива
		$this->getUtils('s_Navigator')->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			$ids = array_column($records, 'id');
			$extensionsId = Connect::$projectServices['extensions']['catalog'] ?? 0;
			$catalogId = Connect::$projectServices['navigator']['catalog'] ?? 0;
			$brandsId = Connect::$projectServices['navigator']['brands'] ?? 0;
			$sql = "SELECT Id as id FROM {$tableName} WHERE (Extension != '{$extensionsId}' OR Id in ({$catalogId},{$brandsId})) AND Id IN (" . Connect::$instance->in($ids) . ")";
			$res = Connect::$instance->fetch($sql, $ids);
			if (!empty($res)) {
				$utils->setValidationErrors($this->buildInvalidIdValidationErrors($records, array_column($res, 'id')));
			}
			return $records;
		});

		return $this->update('s_Navigator', $records);
	}

	public function deleteGoodsNavigator(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		$utils = $this->getUtils('s_Navigator');

		// Проверяем, допустимы ли идентификаторы из входящего массива
		$utils->setBefore(function (array $ids, string $tableName, RestV1M2MUtils $utils) {
			$extensionsId = Connect::$projectServices['extensions']['catalog'] ?? 0;
			$catalogId = Connect::$projectServices['navigator']['catalog'] ?? 0;
			$brandsId = Connect::$projectServices['navigator']['brands'] ?? 0;
			$sql = "SELECT Id as id FROM {$tableName} WHERE (Extension != '{$extensionsId}' OR Id in ({$catalogId},{$brandsId})) AND Id IN (" . Connect::$instance->in($ids) . ")";
			$res = Connect::$instance->fetch($sql, $ids);
			if (!empty($res)) {
				$utils->setValidationErrors($this->buildInvalidIdValidationErrors($ids, array_column($res, 'id')));
			}
			return $ids;
		});
		return $utils->remove($ids);
	}

	public function getGoodsStatuses(): array
	{
		$utils = $this->getUtils('s_Vars');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id,Guid,Name,Alias,IsHidden,Priority');
		$utils->setParams(['ПродукцияСтатусы']);
		return $utils->fetch($this->get, 't.VarsGroup = ?');
	}

	public function postGoodsStatuses(): array
	{
		$records = $this->normalizeInput();

		// После создания новых статусов, обновляем их Priority по возрастанию (5, 10, 15, ...)
		$this->getUtils('s_Vars')->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) {
			if (empty($results)) {
				return null;
			}
			$ids = [];
			foreach ($results as $value) {
				if ($value['status'] === 201 && isset($value['data']['id'])) {
					$ids[] = (int) $value['data']['id'];
				}
			}
			if (empty($ids)) {
				return null;
			}
			$sql = "SELECT MAX(Priority) Co FROM {$tableName} WHERE VarsGroup = 'ПродукцияСтатусы'";
			$max = Connect::$instance->fetch($sql)[0]['Co'] ?? 0;
			$max = round($max / 5) * 5;
			foreach ($ids as $id) {
				$max += 5;
				Connect::$instance->query("UPDATE {$tableName} SET VarsGroup='ПродукцияСтатусы',Priority=? where Id=?", [$max, $id]);
			}
		});

		return $this->create('s_Vars', $records);
	}

	public function putGoodsStatuses(): array
	{
		$records = $this->normalizeInput();

		// Проверяем, допустимы ли идентификаторы из входящего массива
		$this->getUtils('s_Vars')->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			$ids = array_column($records, 'id');
			$sql = "SELECT Id as id FROM $tableName WHERE VarsGroup != 'ПродукцияСтатусы' AND Id IN (" . Connect::$instance->in($ids) . ")";
			$res = Connect::$instance->fetch($sql, $ids);
			if (!empty($res)) {
				$utils->setValidationErrors($this->buildInvalidIdValidationErrors($records, array_column($res, 'id')));
			}
			return $records;
		});

		return $this->update('s_Vars', $records);
	}

	public function deleteGoodsStatuses(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		$utils = $this->getUtils('s_Vars');

		// Проверяем, допустимы ли идентификаторы из входящего массива
		$utils->setBefore(function (array $ids, string $tableName, RestV1M2MUtils $utils) {
			$sql = "SELECT Id as id FROM $tableName WHERE VarsGroup != 'ПродукцияСтатусы' AND Id IN (" . Connect::$instance->in($ids) . ")";
			$res = Connect::$instance->fetch($sql, $ids);
			if (!empty($res)) {
				$utils->setValidationErrors($this->buildInvalidIdValidationErrors($ids, array_column($res, 'id')));
			}
			return $ids;
		});
		return $utils->remove($ids);
	}

	/**
	 * M2M: GET доступные фильтры для товаров (свойства и их значения)
	 */
	public function getGoodsAttributes(): array
	{
		$utils = $this->getUtils('s_Properties');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id, Guid, Name, Alias, Priority, PGroup');

		return $utils->fetch($this->get);
	}

	/**
	 * M2M: GET доступные группы фильтров для товаров
	 */
	public function getGoodsAttributesGroups(): array
	{
		$utils = $this->getUtils('s_PropertiesGroups');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id, Guid, Name, Alias, Priority');

		return $utils->fetch($this->get);
	}

	/**
	 * M2M: POST перезаписать все фильтры/свойства
	 * Удаляет отсутствующие, обновляет существующие, добавляет новые
	 */
	public function postGoodsAttributes(): array
	{
		$records = $this->normalizeInput();
		$this->getUtils('s_Properties')->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			foreach ($records as &$value) {
				$value['type'] = 'text-multi';
				$value['group'] = $value['group'] ?? 1;
			}
			//Utils::debug($records, 1);
			return $records;
		})->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) {
			$sql = "SELECT MAX(Priority) Co FROM {$tableName} WHERE PGroup = 1";
			$max = Connect::$instance->fetch($sql)[0]['Co'] ?? 0;
			$max = round($max / 5) * 5;
			foreach ($results as $value) {
				$id = (int) ($value['data']['id'] ?? 0);
				$max += 5;
				Connect::$instance->query("UPDATE {$tableName} SET Priority=? where Id=?", [$max, $id]);
			}
			return $results;
		});

		return $this->create('s_Properties', $records);
	}

	public function putGoodsAttributes(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_Properties');
		['before' => $callbackBefore, 'after' => $callbackAfter] = $utils->handlePagination($this->data['pagination'] ?? null);
		if ($callbackBefore || $callbackAfter) {
			$utils->setBefore($callbackBefore)->setAfter($callbackAfter);
		}
		return $this->update('s_Properties', $records);
	}

	public function deleteGoodsAttributes(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('s_Properties')->remove($ids);
	}

	public function getGoodsAttributesValues(): array
	{
		$list = $this->get['list'] ?? '';
		$listId = (int) ($this->get['listId'] ?? 0);
		$listField = $this->get['listField'] ?? '';

		$conditions = [];
		$params = [];

		if ($list !== '') {
			$conditions[] = 't.TableName = ?';
			$params[] = $list;
		}

		if ($listId > 0) {
			$conditions[] = 't.TableNameId = ?';
			$params[] = $listId;
		}

		if ($listField !== '') {
			$conditions[] = 't.TableNameField = ?';
			$params[] = $listField;
		}

		$utils = $this->getUtils('s_PropertiesValues');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id, GuidSync, Name, Alias, TableName, TableNameId, TableNameField, PValue');

		if (!empty($params)) {
			$utils->setParams($params);
		}

		return $utils->fetch($this->get, $conditions ? implode(' AND ', $conditions) : null);
	}

	public function postGoodsAttributesValues(): array
	{
		$records = $this->normalizeInput();
		$this->getUtils('s_PropertiesValues')->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			foreach ($records as &$value) {
				$value['w_guid'] = Lists::getPropertiesValuesGuid((string) $value['list'], (string) $value['listField'], (string) $value['listId'], (string) $value['attributesId'], (string) $value['value']);
			}
			return $records;
		});
		return $this->create('s_PropertiesValues', $records);
	}

	public function putGoodsAttributesValues(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_PropertiesValues');
		['before' => $callbackBefore, 'after' => $callbackAfter] = $utils->handlePagination($this->data['pagination'] ?? null);
		if ($callbackBefore || $callbackAfter) {
			$utils->setBefore($callbackBefore)->setAfter($callbackAfter);
		}
		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			foreach ($records as &$value) {
				$value['w_guid'] = Lists::getPropertiesValuesGuid((string) $value['list'], (string) $value['listField'], (string) $value['listId'], (string) $value['attributesId'], (string) $value['value']);
			}
			return $records;
		});
		return $this->update('s_PropertiesValues', $records);
	}

	public function deleteGoodsAttributesValues(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('s_PropertiesValues')->remove($ids);
	}

	/**
	 * M2M: GET получить вариации товаров
	 */
	public function getGoodsVariations(): array
	{
		$utils = $this->getUtils('ProductsVariations');
		$utils->setOrderBy('t.ProductsId,t.Priority');
		$utils->setFields('Id, Guid, ProductsId, Field1, Field2, Field3, Field4');

		$conditions = 't.IsHidden = 0';

		if (!empty($this->get['goodsId'])) {
			$conditions .= ' AND t.ProductsId = ?';
			$utils->setParams([(int) $this->get['goodsId']]);
		}

		return $utils->fetch($this->get, $conditions);
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
		$goodsIds = array_unique(array_column($records, 'goodsId'));
		$utils = $this->getUtils('ProductsVariations');
		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) use ($goodsIds) {
			$maxByGoods = [];
			if (!empty($goodsIds)) {
				$sql = "SELECT ProductsId, MAX(Priority) Co FROM {$tableName} WHERE IsHidden = 0 AND ProductsId IN (" . Connect::$instance->in($goodsIds) . ") GROUP BY ProductsId";
				$rows = Connect::$instance->fetch($sql, $goodsIds);
				foreach ($rows as $row) {
					$maxByGoods[(int) $row['ProductsId']] = (int) ($row['Co'] ?? 0);
				}
			}
			$processing = new ProcessingProducts();
			foreach ($records as &$value) {
				$goodsId = (int) ($value['goodsId'] ?? 0);
				$maxByGoods[$goodsId] = ($maxByGoods[$goodsId] ?? 0) + 1;
				$value['name'] = $value['sku'] ?? '';
				$value['alias'] = $processing->getProductsVariationsAlias($goodsId, [$value['color'] ?? '', $value['size'] ?? '', $value['sku'] ?? '']);
				$value['priority'] = $maxByGoods[$goodsId];
			}
			return $records;
		})->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) use ($goodsIds) {
			if (empty($results)) {
				return $results;
			}
			$processing = new ProcessingProducts();
			$processing->rebuildProductsVariations($goodsIds);
			return $results;
		});

		return $this->create('ProductsVariations', $records);
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
		$utils = $this->getUtils('ProductsVariations');
		['before' => $callbackBefore, 'after' => $callbackAfter] = $utils->handlePagination($this->data['pagination'] ?? null);
		if ($callbackBefore || $callbackAfter) {
			$utils->setBefore($callbackBefore)->setAfter($callbackAfter);
		}
		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			$processing = new ProcessingProducts();
			foreach ($records as &$value) {
				$goodsId = (int) ($value['goodsId'] ?? 0);
				$value['name'] = $value['sku'] ?? '';
				$value['alias'] = $processing->getProductsVariationsAlias($goodsId, [$value['color'] ?? '', $value['size'] ?? '', $value['sku'] ?? '']);
			}
			return $records;
		})->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) {
			if (empty($results)) {
				return $results;
			}

			foreach ($results as $value) {
				$skuIds = [];
				if (!isset($value['data']['id']) || $value['status'] !== 200) {
					continue;
				}
				$skuIds[] = (int) $value['data']['id'];
			}

			$sql = "SELECT DISTINCT ProductsId FROM {$tableName} WHERE Id IN (" . Connect::$instance->in($skuIds) . ")";
			$goodsIds = array_column(Connect::$instance->fetch($sql, $skuIds), 'ProductsId');
			$processing = new ProcessingProducts();
			$processing->rebuildProductsVariations($goodsIds);
			return $results;
		});

		return $this->update('ProductsVariations', $records);
	}

	public function deleteGoodsVariations(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		$goodsIds = [];
		$utils = $this->getUtils('ProductsVariations');
		$utils->setBefore(function (array $ids, string $tableName) use (&$goodsIds) {
			if (empty($ids)) {
				return $ids;
			}
			$sql = "SELECT ProductsId FROM {$tableName} WHERE Id IN (" . Connect::$instance->in($ids) . ")";
			$rows = Connect::$instance->fetch($sql, $ids);
			foreach ($rows as $row) {
				$goodsId = (int) $row['ProductsId'];
				$goodsIds[$goodsId] = $goodsId;
			}
			return $ids;
		})->setAfter(function (array $results, string $tableName) use (&$goodsIds) {
			if (empty($results)) {
				return $results;
			}
			if (!empty($goodsIds)) {
				$processing = new ProcessingProducts();
				$processing->rebuildProductsVariations(array_unique($goodsIds));
			}
			return $results;
		});
		return $utils->remove($ids);
	}

	/**
	 * M2M: GET получить остатки товаров
	 */
	public function getGoodsStocks(): array
	{
		$utils = $this->getUtils('ProductsVariations');
		$utils->setOrderBy('t.ProductsId,t.Priority');
		$utils->setFields('Id, Guid, ProductsId, Field4');

		$conditions = 't.IsHidden = 0';

		if (!empty($this->get['goodsId'])) {
			$conditions .= ' AND t.ProductsId = ?';
			$utils->setParams([(int) $this->get['goodsId']]);
		}

		return $utils->fetch($this->get, $conditions);
	}

	/**
	 * M2M: PUT обновление вариаций по id (одна или batch).
	 * Переформировывает alias если изменились color/size/sku.
	 * Проверяет уникальность новых alias перед обновлением.
	 *
	 * Одна запись: ?id=123 или { "data": { "id": 123, "color": "Синий" } }
	 * Batch: { "data": [ { "id": 1, "sku": "NEW" }, { "id": 2, "color": "Зелёный" } ] }
	 */
	public function putGoodsStocks(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('ProductsVariations');
		// Если передана пагинация, то первая страница запускает обнуление всех остатков.
		// Это нужно для полной перезагрузки остатков товара при пакетной синхронизации.
		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			$pagination = $this->data['pagination'] ?? null;
			if (isset($pagination['count']) && isset($pagination['page'])) {
				if ((int) $pagination['page'] == 1) {
					$sql = "UPDATE {$tableName} SET Field4 = 0 WHERE 1";
					Connect::$instance->query($sql);
				}
			}
			return $records;
		});
		$utils->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) {
			if (empty($results)) {
				return $results;
			}
			foreach ($results as &$value) {
				unset($value['data']['guid']);
			}
			return $results;
		});
		return $this->update('ProductsVariations', $records);
	}

	/**
	 * M2M: GET файлы (с постраничной выборкой)
	 */
	public function getFiles(): array
	{
		$utils = $this->getUtils('s_Files');
		$utils->setOrderBy('t.TableNameField, t.TableNameId, t.Priority');
		$utils->setFields('Id, Guid, Name, TableName, TableNameField, TableNameId, Priority, FileDescription, ApiFilter,FileType, FieSize, FileUrl');

		$conditions = [];
		$params = [];

		if (!empty($this->get['goodsId'])) {
			$conditions[] = 't.TableNameId = ?';
			$params[] = (int) $this->get['goodsId'];
		}

		if (!empty($this->get['list'])) {
			$conditions[] = 't.TableName = ?';
			$params[] = $this->get['list'];
		}

		if (!empty($this->get['listId'])) {
			$conditions[] = 't.TableNameId = ?';
			$params[] = (int) $this->get['listId'];
		}

		if (!empty($this->get['filter'])) {
			$conditions[] = 't.ApiFilter = ?';
			$params[] = $this->get['filter'];
		}

		if (!empty($this->get['listField'])) {
			$conditions[] = 't.TableNameField = ?';
			$params[] = $this->get['listField'];
		}

		if (!empty($params)) {
			$utils->setParams($params);
		}

		return $utils->fetch($this->get, $conditions ? implode(' AND ', $conditions) : null);
	}

	public function postFiles(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_Files');
		
		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			// Парсим base64 проверяем на разрешенные типы и размеры, сохраняем в файловую систему и формируем FileUrl
			// foreach ($records as &$value) {
			// 	$value['FileType'] = $value['FileType'] ?? '';
			// 	$value['FieSize'] = $value['FieSize'] ?? 0;
			// 	$value['FileUrl'] = $value['FileUrl'] ?? '';
			// 	$value['ApiFilter'] = $value['ApiFilter'] ?? '';
			// }
			return $records;
		});
		return $this->create('s_Files', $records);
	}

	public function putFiles(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_Files');
		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) {
			// Парсим base64 проверяем на разрешенные типы и размеры, сохраняем в файловую систему и формируем FileUrl
			// foreach ($records as &$value) {
			// 	$value['FileType'] = $value['FileType'] ?? '';
			// 	$value['FieSize'] = $value['FieSize']?? 0;
			// 	$value['FileUrl'] = $value['FileUrl'] ?? '';
			// 	$value['ApiFilter'] = $value['ApiFilter'] ?? '';
			// }
			return $records;
		});
		return $this->update('s_Files', $records);
	}

	public function deleteFiles(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		$uils = $this->getUtils('s_Files');
		$uils->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) {
			// Удаляем файлы из файловой системы
			foreach ($results as $value) {
				if ($value['status'] === 200 && isset($value['data']['id'])) {
					// Удаляем файл из файловой системы, если он существует
					// $fileId = (int) $value['data']['id'];
					// $row = Connect::$instance->fetch("SELECT FileUrl FROM {$tableName} WHERE Id = ?", [$fileId]);
					// if (!empty($row)) {
					// 	$fileUrl = $row[0]['FileUrl'] ?? '';
					// 	if ($fileUrl) {
					// 		$path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($fileUrl, '/');
					// 		if (file_exists($path)) {
					// 			unlink($path);
					// 		}
					// 	}
					// }
				}
			}
			return $results;
		});
		return $this->getUtils('s_Files')->remove($ids);
	}

	// ========================================================================
	// TASKS
	// ========================================================================

	public function getTasksResult(): array
	{
		$id = (int) ($this->get['id'] ?? 0);
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

		if (!empty($results)) {
			ksort($results);
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
				fn($v) => (int) $v > 0
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
					$recordId = (int) ($record['id'] ?? 0);
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

		if (!empty($results)) {
			ksort($results);
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
	 * Примечание: REST конфиг валидация уже пройдена в Rest::executeHandler(),
	 * здесь проверяем только дополнительные правила из БД.
	 *
	 * @param string $tableName
	 * @param array  $record
	 * @param bool   $requireAll true = POST (обязательные поля проверяются), false = PUT (partial update)
	 * @throws \Exception
	 */
	private function validate(string $tableName, array $record, bool $requireAll): void
	{
		// Получаем правила из БД - REST конфиг уже был применен в Rest::executeHandler()
		$rules = $this->getUtils($tableName)->getFieldRules();

		if (empty($rules)) {
			return;
		}

		foreach ($rules as $field => $rule) {
			if ($rule['type'] === 'json') {
				unset($record[$field]); // Исключаем JSON-поля из валидации, они проверяются REST-конфигом
			}
		}

		if (!$requireAll) {
			$rules = array_map(fn($r) => array_merge($r, ['required' => false]), $rules);
		}

		$this->rest->validateData($record, $rules);
	}

	/**
	 * Нормализовать входные данные в массив плоских записей.
	 * - Разворачивает обёртку {"data": ...}.
	 * - Одиночная запись преобразуется в [{...}].
	 *
	 * @return array [{...}] или [] если данных нет
	 */
	private function normalizeInput(): array
	{
		$raw = $this->data ?? [];

		// Распаковываем {"data": ...} если она есть
		if (isset($raw['data']) && is_array($raw['data'])) {
			$raw = $raw['data'];
		}

		if (empty($raw)) {
			return [];
		}
		$records = (isset($raw[0]) && (is_array($raw[0]) || is_int($raw[0]))) ? $raw : [$raw];
		return $records;
	}

	private function buildInvalidIdValidationErrors(array $records, array $invalidIds): array
	{
		$errors = [];
		foreach ($records as $index => $record) {
			$id = is_array($record) ? (int) ($record['id'] ?? 0) : (int) $record;
			if (in_array($id, $invalidIds, true)) {
				$errors[$index] = [
					'status' => 400,
					'message' => 'Invalid id values provided',
					'data' => ['id' => $id],
				];
			}
		}
		return $errors;
	}

	private function normalizeIds(array $records): array
	{
		$ids = [];
		foreach ($records as $record) {
			if (is_scalar($record)) {
				$ids[] = (int) $record;
				continue;
			}
			if (is_array($record) && isset($record['id'])) {
				$ids[] = (int) $record['id'];
			}
		}
		return array_values(array_filter($ids, fn($id) => $id > 0));
	}
}
