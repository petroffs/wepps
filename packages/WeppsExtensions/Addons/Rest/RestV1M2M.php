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
 * RestV1M2M — M2M API для работы с таблицами через CRUD-операции.
 *
 * Наследует RestV1 (авторизация и профиль). Вызывается динамически через
 * Rest::executeHandler() по конфигу версии 'm2m' из RestConfig.php.
 *
 * Использует упрощённый подход — явные методы для каждой таблицы:
 * - getUsers, postUsers, putUsers, deleteUsers
 * - getOrders, postOrders, putOrders, deleteOrders
 * - getGoods, postGoods, putGoods, deleteGoods (+ категории, статусы, свойства,
 *   значения свойств, вариации, остатки, файлы)
 * - getTasksResult — результат async-задачи из очереди s_Tasks
 *
 * Все методы используют единый helper RestV1M2MUtils для работы с БД.
 * Конфигурация и валидация берутся из s_Config и s_ConfigFields.
 *
 * Особенности:
 * - POST одиночной записи возвращает 201, batch (массив, макс. 100) — 207 Multi-Status
 *   с per-item статусом; batch обрезается до 100 записей в normalizeInput() (MAX_BATCH_SIZE);
 * - PUT — частичное обновление по id (id обязателен);
 * - DELETE — batch-удаление по массиву id {"data": [123, 456, ...]};
 * - через setBefore()/setAfter() навешиваются бизнес-колбэки: проставление
 *   Priority, ParentId/Extension, генерация alias вариаций, загрузка файлов
 *   (base64/url) и т.д.
 */
class RestV1M2M extends RestV1
{
	/**
	 * Максимальный размер batch-массива {"data": [...]} за один запрос.
	 * Сверх лимита записи аккуратно отбрасываются в normalizeInput().
	 */
	private const MAX_BATCH_SIZE = 100;

	/**
	 * Кэш экземпляров RestV1M2MUtils по имени таблицы.
	 * @var array
	 */
	private array $utils = [];

	// ========================================================================
	// USERS
	// ========================================================================

	/**
	 * GET m2m/users — список пользователей.
	 *
	 * GET-параметры: page, limit, search, sort (добавляются автоматически
	 * в query_validation). Поля выбираются из s_Config.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getUsers(): array
	{
		// GET параметры - служебные (page, limit, search, sort)
		$utils = $this->getUtils('s_Users');
		$utils->setFields('Id,Guid,Name,NameFirst,NameSurname,NamePatronymic,IsHidden,UserPermissions,CreateDate,Login,Email,Phone,Comment,Country,Region,City,Address,PostalCode');
		return $utils->fetch($this->get);
	}

	/**
	 * GET m2m/users.item — пользователь по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function getUsersItem(): array
	{
		$utils = $this->getUtils('s_Users');
		$utils->setFields('Id,Guid,Name,NameFirst,NameSurname,NamePatronymic,IsHidden,UserPermissions,CreateDate,Login,Email,Phone,Comment,Country,Region,City,Address,PostalCode');
		return $utils->item((int) ($this->get['id'] ?? 0));
	}

	/**
	 * POST m2m/users — создание пользователя(ей).
	 *
	 * Поддерживает одиночный объект или batch (массив, макс. 100).
	 * Возвращает 201 для одиночного или 207 для batch с per-item статусом.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * PUT m2m/users — обновление пользователя(ей) по id.
	 *
	 * ID передаётся в теле JSON {"id": 123, ...}. Частичное
	 * обновление: поля из POST-валидации наследуются как необязательные.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * DELETE m2m/users — удаление пользователя(ей) по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function deleteUsers(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('s_Users')->remove($ids);
	}

	// ========================================================================
	// ORDERS
	// ========================================================================

	/**
	 * GET m2m/orders — список заказов (по убыванию Id).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getOrders(): array
	{
		$utils = $this->getUtils('Orders');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,IsHidden,UserId,Phone,Email,OStatus,OSum,ODate,ODelivery,OPayment,PostalCode,Address,City,Region,Country,JData,ODeliveryTariff,OPaymentTariff,ODeliveryDiscount,OPaymentDiscount');
		return $utils->fetch($this->get);
	}

	/**
	 * GET m2m/orders.item — заказ по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function getOrdersItem(): array
	{
		$utils = $this->getUtils('Orders');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,IsHidden,UserId,Phone,Email,OStatus,OSum,ODate,ODelivery,OPayment,PostalCode,Address,City,Region,Country,JData,ODeliveryTariff,OPaymentTariff,ODeliveryDiscount,OPaymentDiscount');
		return $utils->item((int) ($this->get['id'] ?? 0));
	}

	/**
	 * POST m2m/orders — создание заказа(ов).
	 *
	 * Batch (массив, макс. 100) возвращает 207 с per-item статусом.
	 * Поддерживает вложенные data.items[] для позиций заказа.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function postOrders(): array
	{
		$records = $this->normalizeInput();
		return $this->create('Orders', $records);
	}

	/**
	 * PUT m2m/orders — обновление заказа(ов) по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function putOrders(): array
	{
		$records = $this->normalizeInput();
		return $this->update('Orders', $records);
	}

	/**
	 * DELETE m2m/orders — удаление заказа(ов) по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function deleteOrders(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('Orders')->remove($ids);
	}

	// ========================================================================
	// GOODS
	// ========================================================================

	/**
	 * GET m2m/goods — список товаров (по убыванию Id).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getGoods()
	{
		$utils = $this->getUtils('Products');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,Alias,IsHidden,Priority,NavigatorId,PStatus,Article,Descr,MetaTitle,MetaDescription,MetaKeyword,WeightPack');
		return $utils->fetch($this->get);
	}

	/**
	 * GET m2m/goods.item — товар по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function getGoodsItem(): array
	{
		$utils = $this->getUtils('Products');
		$utils->setOrderBy('Id desc');
		$utils->setFields('Id,Guid,Name,Alias,IsHidden,Priority,NavigatorId,PStatus,Article,Descr,MetaTitle,MetaDescription,MetaKeyword,WeightPack');
		return $utils->item((int) ($this->get['id'] ?? 0));
	}

	/**
	 * POST m2m/goods — создание товара(ов).
	 *
	 * Поддерживает одиночный объект или batch (массив, макс. 100).
	 * Возвращает 201 для одиночного или 207 для batch с per-item статусом.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function postGoods(): array
	{
		$records = $this->normalizeInput();
		return $this->create('Products', $records);
	}

	/**
	 * PUT m2m/goods — обновление товара(ов) по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * DELETE m2m/goods — удаление товара(ов) по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}. Для удаления связанных данных
	 * (вариаций, изображений, файлов) используйте setAfter() — логика зависит
	 * от бизнес-требований.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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
	 * GET m2m/goods.navigator — список категорий каталога (разделы навигатора).
	 *
	 * Возвращает разделы первого уровня каталога (ParentId = catalog) кроме
	 * раздела брендов. Сортировка по Priority (возрастание).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getGoodsNavigator(): array
	{
		$utils = $this->getUtils('s_Navigator');
		$utils->setOrderBy('t.Priority asc');
		$utils->setFields('Id, Guid, Name, Url, ParentId, Extension');
		$utils->setParams([(Connect::$projectServices['navigator']['catalog'] ?? 0), (Connect::$projectServices['extensions']['catalog'] ?? 0)]);
		return $utils->fetch($this->get, "t.IsHidden = 0 AND t.ParentId = ? AND t.Id not in (?)");
	}

	/**
	 * POST m2m/goods.navigator — создание категории(й) каталога.
	 *
	 * After-колбэк проставляет созданным разделам ParentId = catalog и
	 * Extension = catalog.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * PUT m2m/goods.navigator — обновление категории(й) каталога по id.
	 *
	 * Before-колбэк проверяет допустимость id: обновлять можно только разделы
	 * каталога (Extension = catalog), кроме служебных (catalog/brands).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * DELETE m2m/goods.navigator — удаление категории(й) каталога по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}. Before-колбэк проверяет
	 * допустимость id (аналогично putGoodsNavigator).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * GET m2m/goods.statuses — статусы товаров.
	 *
	 * Возвращает элементы s_Vars группы «ПродукцияСтатусы», сортировка по Priority.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getGoodsStatuses(): array
	{
		$utils = $this->getUtils('s_Vars');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id,Guid,Name,Alias,IsHidden,Priority');
		$utils->setParams(['ПродукцияСтатусы']);
		return $utils->fetch($this->get, 't.VarsGroup = ?');
	}

	/**
	 * POST m2m/goods.statuses — создание статуса(ов) товаров.
	 *
	 * After-колбэк присваивает новым статусам группу «ПродукцияСтатусы» и
	 * Priority по возрастанию с шагом 5 (5, 10, 15, ...).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * PUT m2m/goods.statuses — обновление статуса(ов) по id.
	 *
	 * Before-колбэк отклоняет id, не принадлежащие группе «ПродукцияСтатусы».
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * DELETE m2m/goods.statuses — удаление статуса(ов) по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}. Before-колбэк аналогичен
	 * putGoodsStatuses.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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
	 * GET m2m/goods.attributes — свойства (атрибуты) товаров.
	 *
	 * Возвращает свойства фильтрации из s_Properties, сортировка по Priority.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getGoodsAttributes(): array
	{
		$utils = $this->getUtils('s_Properties');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id, Guid, Name, Alias, Priority, PGroup');

		return $utils->fetch($this->get);
	}

	/**
	 * GET m2m/goods.attributesGroups — группы свойств товаров.
	 *
	 * Возвращает группы из s_PropertiesGroups, сортировка по Priority.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
	public function getGoodsAttributesGroups(): array
	{
		$utils = $this->getUtils('s_PropertiesGroups');
		$utils->setOrderBy('Priority asc');
		$utils->setFields('Id, Guid, Name, Alias, Priority');

		return $utils->fetch($this->get);
	}

	/**
	 * POST m2m/goods.attributes — создание свойства(й) товаров.
	 *
	 * Before-колбэк принудительно проставляет type='text-multi' и group=1
	 * (по умолчанию). After-колбэк присваивает Priority по возрастанию с шагом 5.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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

	/**
	 * PUT m2m/goods.attributes — обновление свойства(й) по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * DELETE m2m/goods.attributes — удаление свойства(й) по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function deleteGoodsAttributes(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('s_Properties')->remove($ids);
	}

	/**
	 * GET m2m/goods.attributesValues — значения свойств товаров.
	 *
	 * GET-параметры фильтрации: list (TableName), listId (TableNameId),
	 * listField (TableNameField). Возвращает элементы s_PropertiesValues.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
	 */
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

	/**
	 * POST m2m/goods.attributesValues — создание значения(й) свойств.
	 *
	 * Before-колбэк генерирует w_guid через Lists::getPropertiesValuesGuid().
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * PUT m2m/goods.attributesValues — обновление значения(й) свойств по id.
	 *
	 * Before-колбэк (после обработки пагинации) перегенерирует w_guid.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * DELETE m2m/goods.attributesValues — удаление значения(й) свойств по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function deleteGoodsAttributesValues(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		return $this->getUtils('s_PropertiesValues')->remove($ids);
	}

	/**
	 * GET m2m/goods.variations — вариации товаров (sku, цвет, размер, остатки).
	 *
	 * GET-параметры: goodsId (id товара). Поля Field1..Field4 соответствуют
	 * цвету/размеру/sku/остаткам.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
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
	 * POST m2m/goods.variations — создание вариаций товаров (одна или batch).
	 *
	 * Сгруппировывает по goodsId и вызывает upsertVariations() batch-ом.
	 * Не скрывает существующие вариации — только добавляет новые.
	 *
	 * Валидация по RestConfig уже выполнена в Rest::executeHandler() перед вызовом метода!
	 * Формат тела: { "data": [ { "goodsId": 723, "sku": "SKU001", "color": "Красный", "size": "42", "stocks": "10" }, ... ] }
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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
	 * PUT m2m/goods.variations — обновление вариаций по id (одна или batch).
	 *
	 * Переформировывает alias, если изменились color/size/sku. Проверяет
	 * уникальность новых alias перед обновлением.
	 *
	 * Одна запись: { "data": { "id": 123, "color": "Синий" } }
	 * Batch: { "data": [ { "id": 1, "sku": "NEW" }, { "id": 2, "color": "Зелёный" } ] }
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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

	/**
	 * DELETE m2m/goods.variations — удаление вариаций по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}. Before-колбэк собирает ProductsId
	 * удаляемых вариаций, after — пересобирает вариации товаров
	 * (rebuildProductsVariations).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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
	 * GET m2m/goods.stocks — остатки товаров (sku, количества).
	 *
	 * GET-параметры: goodsId (id товара). Остатки хранятся в поле Field4
	 * таблицы ProductsVariations.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
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
	 * PUT m2m/goods.stocks — обновление остатков товаров по id.
	 *
	 * Если передана пагинация, то первая страница запускает обнуление всех
	 * остатков (Field4 = 0). Это нужно для полной перезагрузки остатков товара
	 * при пакетной синхронизации. After-колбэк убирает guid из ответа.
	 *
	 * Одна запись: { "data": { "id": 123, "stocks": 10 } }
	 * Batch: { "data": [ { "id": 1, "stocks": 5 }, { "id": 2, "stocks": 0 } ] }
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
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
	 * GET m2m/files — файлы (с постраничной выборкой).
	 *
	 * GET-параметры фильтрации: goodsId / listId (TableNameId), list (TableName),
	 * listField (TableNameField), filter (ApiFilter). Сортировка по
	 * TableNameField, TableNameId, Priority.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data', 'pagination']
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

	/**
	 * POST m2m/files — создание файлов.
	 *
	 * Поддерживает одиночный объект или batch (массив, макс. 100). Загрузка
	 * через base64 или url. Before-колбэк выполняет загрузку файла во временную
	 * папку (prepareUploadFromBase64 / prepareUploadFromUrl), определяет
	 * приоритет внутри группы (list|listField|listId) и финализирует имя файла
	 * через Lists::getUploadFileName(). After-колбэк удаляет временные файлы,
	 * а при ошибке — и загруженный файл.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function postFiles(): array
	{
		$records = $this->normalizeInput();
		$utils = $this->getUtils('s_Files');
		$uploadContext = [];

		$utils->setBefore(function (array $records, string $tableName, RestV1M2MUtils $utils) use (&$uploadContext) {
			$priorityGroups = [];
			$groups = [];
			foreach ($records as $record) {
				$list = $record['list'] ?? '';
				$listField = $record['listField'] ?? '';
				$tableNameId = (int) ($record['listId'] ?? 0);
				if ($list === '' || $listField === '' || $tableNameId === 0) {
					continue;
				}

				$groupKey = "{$list}|{$listField}|{$tableNameId}";
				if (!isset($groups[$groupKey])) {
					$groups[$groupKey] = [$list, $listField, $tableNameId];
				}
			}

			if (!empty($groups)) {
				$where = implode(' OR ', array_fill(0, count($groups), 'TableName = ? AND TableNameField = ? AND TableNameId = ?'));
				$params = [];
				foreach ($groups as $group) {
					$params = array_merge($params, $group);
				}
				$rows = Connect::$instance->fetch(
					"SELECT TableName, TableNameField, TableNameId, MAX(Priority) AS max_priority FROM {$tableName} WHERE {$where} GROUP BY TableName, TableNameField, TableNameId",
					$params
				);
				foreach ($rows as $row) {
					$groupKey = "{$row['TableName']}|{$row['TableNameField']}|{$row['TableNameId']}";
					$priorityGroups[$groupKey] = (int) ($row['max_priority'] ?? 0);
				}
			}

			foreach ($records as $index => &$record) {
				$base64 = trim((string) ($record['base64'] ?? ''));
				$url = trim((string) ($record['url'] ?? ''));
				$uploadTemp = null;

				if ($base64 !== '') {
					$uploadTemp = $utils->prepareUploadFromBase64($base64, $record['name'] ?? 'file');
				}

				if ($uploadTemp === null && $url !== '') {
					$uploadTemp = $utils->prepareUploadFromUrl($url, $record['name'] ?? 'file');
				}

				if ($uploadTemp === null || isset($uploadTemp['error'])) {
					$utils->setValidationErrors([
						$index => [
							'status' => 400,
							'message' => $uploadTemp['error'] ?? 'Either valid base64 or valid url required',
							'data' => $record['guid'] ?? null,
						],
					]);
					continue;
				}

				$list = $record['list'];
				$listField = $record['listField'];
				$tableNameId = (int) $record['listId'];
				$groupKey = "{$list}|{$listField}|{$tableNameId}";
				$priorityGroups[$groupKey] = ($priorityGroups[$groupKey] ?? 0) + 1;
				$record['priority'] = $priorityGroups[$groupKey];

				$upload = [
					'path' => $uploadTemp['path'],
					'name' => $uploadTemp['name'],
					'type' => $uploadTemp['type'],
					'size' => $uploadTemp['size'],
					'guid' => $record['guid'],
				];

				$upload = Lists::getUploadFileName($upload, $list, $listField, $tableNameId);

				$record['name'] = $record['name'] ?? $uploadTemp['name'];
				$record['type'] = $upload['type'];
				$record['size'] = $upload['size'];
				$record['url'] = $upload['url'];
				$record['innerName'] = $upload['inner'];
				$record['ext'] = $upload['ext'];

				$uploadContext[$index] = [
					'upload_temp' => $uploadTemp,
					'destination' => Connect::$projectDev['root'] . $upload['url'],
					'fileUrl' => $upload['url'],
				];
				$record['base64'] = '';
				//unset($record['base64']);
			}
			return $records;
		})->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) use (&$uploadContext) {
			foreach ($results as $index => &$result) {
				$context = $uploadContext[$index] ?? null;
				if (empty($context['upload_temp'])) {
					continue;
				}

				@unlink($context['upload_temp']['path']);

				if ($result['status'] === 201 && !empty($result['data']['id'])) {
					$result['data']['url'] = $context['fileUrl'];
				} elseif (!empty($context['destination']) && is_file($context['destination'])) {
					@unlink($context['destination']);
				}
			}

			return $results;
		});

		return $this->create('s_Files', $records);
	}

	/**
	 * PUT m2m/files — обновление файлов по id.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function putFiles(): array
	{
		$records = $this->normalizeInput();
		return $this->update('s_Files', $records);
	}

	/**
	 * DELETE m2m/files — удаление файлов по id.
	 *
	 * Формат тела: {"data": [123, 456, ...]}. Before-колбэк собирает пути
	 * физических файлов (FileUrl), after — удаляет файлы с диска после
	 * успешного удаления записей.
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
	public function deleteFiles(): array
	{
		$ids = $this->normalizeIds($this->normalizeInput());
		$utils = $this->getUtils('s_Files');
		$fileUrls = [];
		$utils->setBefore(function (array $ids, string $tableName, RestV1M2MUtils $utils) use (&$fileUrls) {
			if (empty($ids)) {
				return $ids;
			}

			$rows = Connect::$instance->fetch(
				"SELECT Id, FileUrl FROM {$tableName} WHERE Id IN (" . Connect::$instance->in($ids) . ")",
				$ids
			);
			foreach ($rows as $row) {
				$fileId = (int) ($row['Id'] ?? 0);
				$fileUrl = $row['FileUrl'] ?? '';
				if ($fileId && $fileUrl !== '') {
					$fileUrls[$fileId] = Connect::$projectDev['root'] . $fileUrl;
				}
			}
			return $ids;
		});
		$utils->setAfter(function (array $results, string $tableName, RestV1M2MUtils $utils) use (&$fileUrls) {
			foreach ($results as $value) {
				if ($value['status'] !== 200 || empty($value['data']['id'])) {
					continue;
				}
				$fileId = (int) $value['data']['id'];
				$path = $fileUrls[$fileId] ?? '';
				if ($path && is_file($path)) {
					@unlink($path);
				}
			}
			return $results;
		});
		return $utils->remove($ids);
	}

	// ========================================================================
	// TASKS
	// ========================================================================

	/**
	 * GET m2m/tasks.result — результат async-задачи из очереди s_Tasks.
	 *
	 * GET-параметр: ?id= (обязательный). Возвращает детали задачи: тип запроса,
	 * url, статусы обработки и сохранённый ответ (http_status + response).
	 *
	 * @return array Ответ в формате ['status', 'message', 'data']
	 */
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

	/**
	 * Получить (и закэшировать) экземпляр RestV1M2MUtils для таблицы.
	 *
	 * @param string $tableName Имя таблицы (например 's_Users', 'Products', 'Orders')
	 * @return RestV1M2MUtils Экземпляр утилит для работы с таблицей
	 */
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
	 * Валидирует каждую запись (POST — все обязательные поля), выполняет
	 * batch-вставку через RestV1M2MUtils::addBatch() и обновляет поисковый
	 * индекс. Для одной записи возвращает результат записи (201/400 и т.д.),
	 * для batch — 207 Multi-Status с per-item статусами.
	 *
	 * @param string $tableName Имя таблицы
	 * @param array  $records  Плоские записи из normalizeInput()
	 * @return array Результат создания
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
	 * Требует id в каждой записи. Валидирует (PUT — частичное обновление,
	 * обязательные поля не требуются), проверяет существование id в таблице
	 * (несуществующие — 404) и выполняет batch-обновление через
	 * RestV1M2MUtils::setBatch(). Для одной записи возвращает результат (200/400
	 * и т.д.), для batch — 207 Multi-Status с per-item статусами.
	 *
	 * @param string $tableName Имя таблицы
	 * @param array  $records  Плоские записи с 'id' из normalizeInput()
	 * @return array Результат обновления
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
	 * Helper: расчёт параметров пагинации из GET-параметров.
	 *
	 * @param int $maxLimit Максимально допустимый limit (по умолчанию 100)
	 * @return array ['page' => int, 'limit' => int, 'offset' => int]
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
	 * Примечание: REST-конфиг валидация уже пройдена в Rest::executeHandler(),
	 * здесь проверяем только дополнительные правила из БД. JSON-поля исключаются
	 * из проверки (они валидируются REST-конфигом).
	 *
	 * @param string $tableName Имя таблицы
	 * @param array  $record   Запись
	 * @param bool   $requireAll true = POST (обязательные поля проверяются), false = PUT (partial update)
	 * @throws \Exception При ошибке валидации
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
	 *
	 * - Разворачивает обёртку {"data": ...};
	 * - Одиночная запись преобразуется в [{...}];
	 * - Массив id (DELETE) остаётся массивом чисел;
	 * - Batch-массив обрезается до $maxBatchSize записей.
	 *
	 * @param int $maxBatchSize Максимальное число записей в batch (по умолчанию MAX_BATCH_SIZE = 100)
	 * @return array [{...}] или [] если данных нет
	 */
	private function normalizeInput(int $maxBatchSize = self::MAX_BATCH_SIZE): array
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

		// Аккуратно ограничиваем batch до $maxBatchSize (макс. 100 записей)
		if (count($records) > $maxBatchSize) {
			$records = array_slice($records, 0, $maxBatchSize);
		}

		return $records;
	}


	/**
	 * Собрать ошибки валидации для записей с недопустимыми id.
	 *
	 * Используется в before-колбэках для отклонения записей, чьи id не прошли
	 * проверку (например, принадлежность группе/расширению).
	 *
	 * @param array $records     Записи (массив ассоциативных массивов или id)
	 * @param array $invalidIds  Список недопустимых id
	 * @return array Ошибки вида [index => ['status' => 400, 'message' => ..., 'data' => ...]]
	 */
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

	/**
	 * Извлечь плоский список id из нормализованных записей (для DELETE).
	 *
	 * Поддерживает и скалярные id ({"data": [1, 2, 3]}), и объекты с ключом id
	 * ({"data": [{"id": 1}, {"id": 2}]}). Возвращает только id > 0.
	 *
	 * @param array $records Нормализованные записи
	 * @return array Список int id
	 */
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
