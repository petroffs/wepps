<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\Navigator;
use WeppsCore\Utils;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\DeliveryUtils;
use WeppsExtensions\Cart\Payments\PaymentsUtils;
use WeppsExtensions\Profile\ProfileActions;
use WeppsExtensions\Products\ProductsUtils;
use WeppsExtensions\Template\Filters\Filters;

/**
 * REST обработчик для APP-методов API v1
 * Goods, Orders, News, Slides
 */
class RestV1APP extends RestV1
{
	// -------------------------------------------------------------------------
	// HOME
	// -------------------------------------------------------------------------

	/**
	 * GET v1/home — агрегированные данные для главного экрана приложения
	 * Слайды, категории каталога, последние новости, последние товары.
	 * Для авторизованных пользователей — активный заказ (OStatus=1).
	 */
	public function getHome(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */

		// Если пользователь авторизован (auth_optional в конфиге) — загружаем активные заказы
		$activeOrders = [];
		if ($this->rest->getUser()) {
			$activeOrders = $this->getOrders([1, 2])['data'] ?? [];
		}

		// Слайды — используем getSlides()
		$slides = $this->getSlides()['data'];

		// Категории каталога — используем getGoodsCategories()
		$categories = $this->getGoodsCategories()['data'];

		// Последние новости — используем getNews() с нужными параметрами
		$this->get['page'] = 1;
		$this->get['limit'] = 5;
		$this->get['search'] = '';
		$news = $this->getNews()['data'];

		// Последние товары — используем getGoods() с нужными параметрами
		$this->get['sort'] = '';
		$this->get['category'] = 0;
		$goods = $this->getGoods()['data'];

		// Избранные товары — доступны если пользователь авторизован
		$goodsFavorites = $this->rest->getUser() ? $this->getGoodsFavorites()['data'] : [];

		// Метрики корзины — работают для всех (анонимные через cookie, авторизованные через JCart)
		$cartMetrics = $this->getCartMetrics()['data'];

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => [
				'slides' => $slides,
				'categories' => $categories,
				'news' => $news,
				'goods' => $goods,
				'goods_favorites' => $goodsFavorites,
				'active_orders' => $activeOrders,
				'cart_metrics' => $cartMetrics,
			],
		];
	}

	// -------------------------------------------------------------------------
	// GOODS
	// -------------------------------------------------------------------------

	/**
	 * GET v1/goods — список товаров (или один товар если передан 'id')
	 */
	public function getGoods(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = $this->get['id'] ?? '';
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int) ($this->get['limit'] ?? 20)));

		$productsUtils = new ProductsUtils();
		$navigator = new Navigator("/catalog/");

		// Если передан ID, ищем по ID, иначе используем фильтры и категорию
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
			];
		} else {
			// Инициализируем Navigator для работы getConditions()
			if (!empty($this->get['category'])) {
				$navigator->content['Id'] = (int) $this->get['category'];
			}
			$productsUtils->setNavigator($navigator, 'Products');

			$filters = new Filters($this->get);
			$params = $filters->getParams();
			$sorting = $productsUtils->getSorting();
			$conditions = $productsUtils->getConditions($params, true);

			$settings = [
				'pages' => $limit,
				'page' => $page,
				'sorting' => $sorting['conditions'],
				'conditions' => $conditions,
			];
		}

		$result = $productsUtils->getProducts($settings);

		// Загружаем атрибуты для всех товаров одним вызовом
		$filterResult = [];
		$ids = array_column($result['rows'], 'Id');
		
		if (!empty($ids)) {
			$placeholders = Connect::$instance->in($ids);
			$filters = new Filters();
			$filtersByCompositeKey = $filters->getFilters([
				'conditions' => "t.IsHidden=0 AND pv.TableName='Products' AND pv.TableNameId IN ($placeholders)",
				'params' => $ids,
			]);
			
			// Перегруппируем compositeKey в структуру [ProductId => [PropertyId => rows]]
			$filterResult = $filters->groupByProductId($filtersByCompositeKey);
		}

		// Унифицированный цикл: атрибуты и URLs для обоих случаев
		foreach ($result['rows'] as &$row) {
			if (!empty($filterResult)) {
				// Распределяем атрибуты по товарам
				$row['W_Attributes'] = $this->_buildAttributesFromPropertiesValues($filterResult[$row['Id']] ?? null);
			}

			// обработка URLs (для обоих случаев)
			if (!empty($row['Images_FileUrl'])) {
				$row['Images_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $row['Images_FileUrl'];
			}
			$row['Url'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . $row['Url'];
		}

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $result['rows'],
			'pagination' => ['count' => $result['count'], 'limit' => $limit, 'page' => $page],
		];
	}

	/**
	 * GET v1/goods.item — товар по id или alias
	 * Вызывает getGoods() с параметром 'id' для унификации обработки
	 */
	public function getGoodsItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = $this->get['id'] ?? '';

		if (empty($id)) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		// Вызываем getGoods() с фильтрацией по ID — единая логика обработки товара
		$result = $this->getGoods();

		if (empty($result['data'])) {
			return ['status' => 404, 'message' => 'Item not found', 'data' => null];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $result['data'][0]];
	}

	/**
	 * GET v1/goods.categories — список категорий товаров
	 */
	public function getGoodsCategories(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$res = Connect::$instance->fetch(
			"SELECT Id, Name, Url, ParentDir, Extension FROM s_Navigator WHERE IsHidden = 0 AND ParentDir = ? AND Id not in (?) ORDER BY Priority DESC",
			[Connect::$projectServices['navigator']['catalog'] ?? 0, Connect::$projectServices['navigator']['brands'] ?? 0]
		);

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}

	/**
	 * GET v1/goods.filters — доступные свойства/значения для фильтрации
	 */
	public function getGoodsFilters(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
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
	 * GET v1/goods.favorites — избранные товары текущего пользователя
	 */
	public function getGoodsFavorites(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();

		$jfav = json_decode($user['JFav'] ?? '', true)['items'] ?? [];
		if (empty($jfav)) {
			return ['status' => 200, 'message' => 'OK', 'data' => []];
		}

		$ids = array_column($jfav, 'id');
		$in = Connect::$instance->in($ids);
		$productsUtils = new ProductsUtils();
		// Инициализируем Navigator для REST контекста
		$navigator = new Navigator("/catalog/");
		$productsUtils->setNavigator($navigator, 'Products');

		$result = $productsUtils->getProducts([
			'pages' => 100,
			'page' => 1,
			'sorting' => 't.Priority desc',
			'conditions' => ['conditions' => "t.IsHidden=0 AND t.Id IN ($in)", 'params' => $ids],
		]);
		$rows = $result['rows'] ?? [];
		foreach ($rows as &$row) {
			if (!empty($row['Images_FileUrl'])) {
				$row['Images_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $row['Images_FileUrl'];
			}
		}
		unset($row);

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $rows,
			'pagination' => ['count' => $result['count'], 'limit' => 100, 'page' => 1],
		];
	}

	/**
	 * POST v1/goods — создание товара
	 */
	public function postGoods($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$name = $data['data']['name'] ?? '';
		$price = (float) ($data['data']['price'] ?? 0);
		$category = (int) ($data['data']['category'] ?? 0);

		Connect::$instance->query(
			"INSERT INTO Products (Name, Price, NavigatorId, IsHidden, Priority) VALUES (?, ?, ?, 0, 0)",
			[$name, $price, $category]
		);
		$id = Connect::$db->lastInsertId();

		return ['status' => 200, 'message' => 'Goods item created', 'data' => ['id' => (int) $id]];
	}

	/**
	 * PUT v1/goods — обновление товара
	 */
	public function putGoods($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int) ($data['data']['id'] ?? 0);
		$name = $data['data']['name'] ?? null;
		$price = isset($data['data']['price']) ? (float) $data['data']['price'] : null;

		$set = [];
		$params = [];

		if ($name !== null) {
			$set[] = 'Name = ?';
			$params[] = $name;
		}
		if ($price !== null) {
			$set[] = 'Price = ?';
			$params[] = $price;
		}

		if (empty($set)) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		$params[] = $id;
		Connect::$instance->query("UPDATE Products SET " . implode(', ', $set) . " WHERE Id = ?", $params);

		return ['status' => 200, 'message' => 'Goods item updated', 'data' => null];
	}

	/**
	 * DELETE v1/goods — удаление товара по id
	 */
	public function deleteGoods(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int) ($this->get['id'] ?? 0);

		Connect::$instance->query("UPDATE Products SET IsHidden = 1 WHERE Id = ?", [$id]);

		return ['status' => 200, 'message' => 'Goods item deleted', 'data' => null];
	}

	// -------------------------------------------------------------------------
	// ORDERS
	// -------------------------------------------------------------------------

	/**
	 * GET v1/orders — список заказов пользователя
	 */
	public function getOrders(?array $statuses = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int) ($this->get['limit'] ?? 20)));

		$profileActions = new ProfileActions(false);
		$result = $profileActions->getOrdersList($user['Id'], $page, $limit, $statuses);

		// Обработка JSON в слое адаптера REST
		if (!empty($result['orders'])) {
			foreach ($result['orders'] as $key => &$row) {
				$jdata = json_decode($row['JData'] ?? '{}', true);
				if (!empty($jdata['items'])) {
					foreach ($jdata['items'] as $k => &$item) {
						$jdata['items'][$k]['url'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . $item['url'];
						$jdata['items'][$k]['image'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $item['image'];
					}
				}
				//$jpositions = json_decode($row['JPositions'] ?? '[]', true);
				$result['orders'][$key]['JData'] = $jdata;
				// $result['orders'][$key]['JPositions'] = $jpositions;
				unset($result['orders'][$key]['JPositions']);
			}
		}

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $result['orders'],
			'pagination' => ['count' => $result['count'], 'limit' => $limit, 'page' => $page],
		];
	}

	/**
	 * GET v1/orders.item — заказ по id
	 */
	public function getOrdersItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$id = (int) ($this->get['id'] ?? 0);

		if ($id <= 0) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$profileActions = new ProfileActions(false);
		$order = $profileActions->getFullOrder($id, $user['Id']);

		if (empty($order)) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}

		// Обработка JSON в слое REST адаптера
		$jdata = json_decode($order['JData'] ?? '{}', true);
		if (!empty($jdata['items'])) {
			foreach ($jdata['items'] as $k => &$item) {
				$jdata['items'][$k]['url'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . $item['url'];
				$jdata['items'][$k]['image'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $item['image'];
			}
		}
		$jpositions = json_decode($order['JPositions'] ?? '[]', true);

		// Трансформируем в ответ
		$response = array_merge([
			'name' => $order['Name'] ?? '',
			'date' => $order['ODate'] ?? '',
			'phone' => $order['Phone'] ?? '',
			'email' => $order['Email'] ?? '',
			'status' => $order['OStatus'] ?? '',
			'address' => $order['Address'] ?? '',
			'postalCode' => $order['PostalCode'] ?? '',
			'messages' => $order['W_Messages'] ?? [],
		], $order);
		$response['JData'] = $jdata;
		$response['JPositions'] = $jpositions;

		return ['status' => 200, 'message' => 'OK', 'data' => $response];
	}

	/**
	 * PUT v1/orders.status — обновление статуса заказа
	 */
	public function putOrdersStatus($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$id = (int) ($data['data']['id'] ?? 0);
		$status = $data['data']['status'] ?? '';

		$res = Connect::$instance->fetch("SELECT Id FROM Orders WHERE Id = ? AND UserId = ? AND IsHidden = 0", [$id, $user['Id']]);
		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}

		Connect::$instance->query("UPDATE Orders SET OStatus = ? WHERE Id = ?", [$status, $id]);

		return ['status' => 200, 'message' => 'Order status updated', 'data' => null];
	}

	/**
	 * DELETE v1/orders — отмена заказа по id
	 * 
	 * Отмена возможна только для заказов в статусе "новый" или "в обработке". Статус "отменён" — 6.
	 * Желательно, настроить уведомления (через Tasks) или транспорт данных в учетную систему, 
	 * чтобы видеть эти изменения статусов. 
	 * Либо, если нужно полностью удалять заказ, можно добавить дополнительный флаг IsDeleted и 
	 * фильтровать заказы с IsDeleted=0 в методах получения.
	 */
	public function deleteOrders(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$id = (int) ($this->get['id'] ?? 0);
		$cancellableStatuses = [1, 2]; // допустимые статусы для отмены: 1 - новый, 2 - в обработке
		$cansledStatus = 6; // статус "отменён"

		$res = Connect::$instance->fetch("SELECT Id, OStatus FROM Orders WHERE Id = ? AND UserId = ? AND IsHidden = 0 AND OStatus != ?", [$id, $user['Id'], $cansledStatus]);
		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}
		if (!in_array((int) $res[0]['OStatus'], $cancellableStatuses)) {
			return ['status' => 400, 'message' => 'Only new or processing orders can be cancelled', 'data' => null];
		}

		Connect::$instance->query("UPDATE Orders SET OStatus = ? WHERE Id = ?", [$cansledStatus, $id]);

		return ['status' => 200, 'message' => 'Order cancelled', 'data' => null];
	}

	// -------------------------------------------------------------------------
	// CART
	// -------------------------------------------------------------------------

	/**
	 * GET v1/cart.metrics — счётчик позиций корзины (работает для анонимных и авторизованных)
	 */
	public function getCartMetrics(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$cartUtils = $this->_newCartUtils();
		return ['status' => 200, 'message' => 'OK', 'data' => $cartUtils->getCartMetrics()];
	}

	/**
	 * GET v1/cart — корзина текущего пользователя
	 */
	public function getCart(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'OK', 'data' => $cartUtils->getCartSummary()];
	}

	/**
	 * POST v1/cart — добавить товар в корзину (или обновить кол-во, если уже есть)
	 */
	/**
	 * POST v1/cart — добавить товар в корзину
	 * Параметры: id (может быть "325" или "325-555"), quantity (опционально, по умолчанию 1)
	 */
	public function postCart($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = trim($data['data']['id'] ?? '');
		$quantity = max(1, (int) ($data['data']['quantity'] ?? 1));

		// Валидация товара
		$productsUtils = new \WeppsExtensions\Products\ProductsUtils();
		$validation = $productsUtils->validateProductId($id);
		if (!$validation['exists']) {
			return ['status' => 404, 'message' => $validation['message'], 'data' => null];
		}

		$cartUtils = $this->_newCartUtils();
		$cartUtils->add($id, $quantity);
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'Cart updated', 'data' => $cartUtils->getCartSummary()];
	}

	/**
	 * PUT v1/cart — обновить количество и активность товара в корзине
	 * Параметры: id, quantity, active (опционально, 0 или 1 по умолчанию 1)
	 */
	public function putCart($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = trim($data['data']['id'] ?? '');
		$quantity = max(1, (int) ($data['data']['quantity'] ?? 1));
		$active = (int) ($data['data']['active'] ?? 1);

		// Валидация товара
		$productsUtils = new \WeppsExtensions\Products\ProductsUtils();
		$validation = $productsUtils->validateProductId($id);
		if (!$validation['exists']) {
			return ['status' => 404, 'message' => $validation['message'], 'data' => null];
		}

		$cartUtils = $this->_newCartUtils();
		$cartUtils->edit($id, $quantity);
		// Устанавливаем активность товара
		if ($active !== 1) {
			$cartUtils->setActive($id, $active);
		}
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'Cart updated', 'data' => $cartUtils->getCartSummary()];
	}

	/**
	 * DELETE v1/cart — удалить товар из корзины по ?id=...
	 */
	public function deleteCart(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = trim($this->get['id'] ?? '');

		// Валидация товара
		$productsUtils = new \WeppsExtensions\Products\ProductsUtils();
		$validation = $productsUtils->validateProductId($id);
		if (!$validation['exists']) {
			return ['status' => 404, 'message' => $validation['message'], 'data' => null];
		}

		$cartUtils = $this->_newCartUtils();
		$cartUtils->remove($id);
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'Item removed', 'data' => $cartUtils->getCartSummary()];
	}

	/**
	 * POST v1/cart.placeOrder — оформить заказ из текущей корзины
	 * Контактные данные берутся из профиля пользователя.
	 * Валидация deliveryOperations происходит в addOrder().
	 */
	public function postCartPlaceOrder($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		$cartSummary = $cartUtils->getCartSummary();

		if (empty($cartSummary['items'])) {
			return ['status' => 400, 'message' => 'Cart is empty', 'data' => null];
		}

		if (empty($cartSummary['delivery']['deliveryId']) || $cartSummary['delivery']['deliveryId'] === '0') {
			return ['status' => 400, 'message' => 'Delivery method not selected', 'data' => null];
		}

		if (empty($cartSummary['payments']['paymentsId'])) {
			return ['status' => 400, 'message' => 'Payment method not selected', 'data' => null];
		}

		// addOrder() выполняет полную валидацию и создание заказа
		// Загружаем параметры доставки из корзины и преобразуем обратно в operations-* ключи
		$cart = $cartUtils->getCart();
		$operationsData = [];
		if (!empty($cart['deliveryOperations']) && is_array($cart['deliveryOperations'])) {
			foreach ($cart['deliveryOperations'] as $op) {
				if ($op['id'] === (string)$cartSummary['delivery']['deliveryId'] && !empty($op['data'])) {
					foreach ($op['data'] as $key => $value) {
						$operationsData["operations-{$key}"] = $value;
					}
					break;
				}
			}
		}
		$orderData = array_merge($data['data'] ?? [], $operationsData, ['form' => 'placeOrder']);
		$result = $cartUtils->addOrder($orderData);

		// Если есть ошибка валидации
		if (!empty($result['errors'])) {
			return ['status' => 400, 'message' => 'Validation error', 'data' => $result['errors']];
		}

		if (empty($result['id'])) {
			return ['status' => 400, 'message' => 'Failed to create order', 'data' => null];
		}

		return ['status' => 200, 'message' => 'Order created', 'data' => $result];
	}

	/**
	 * GET v1/cart.city — поиск городов по строке запроса (?q=Мос)
	 */
	public function getCartCity(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$q = trim($this->get['q'] ?? '');
		if (strlen($q) < 2) {
			return ['status' => 400, 'message' => 'Query too short', 'data' => null];
		}
		$deliveryUtils = new DeliveryUtils();
		$cities = $deliveryUtils->getCitiesByQuery($q, 1, 20);
		return ['status' => 200, 'message' => 'OK', 'data' => $cities];
	}

	/**
	 * GET v1/cart.delivery — доступные способы доставки для города (?citiesId=123)
	 */
	public function getCartDelivery(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$citiesId = trim($this->get['citiesId'] ?? '');
		if ($citiesId === '') {
			return ['status' => 400, 'message' => 'citiesId is required', 'data' => null];
		}
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		$deliveryUtils = new DeliveryUtils();
		$delivery = $deliveryUtils->getTariffsByCitiesId($citiesId, $cartUtils);
		return ['status' => 200, 'message' => 'OK', 'data' => $delivery];
	}

	/**
	 * GET v1/cart.checkout — доступные способы доставки и оплаты
	 */
	public function getCartCheckout(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'OK', 'data' => $cartUtils->getCheckoutData()];
	}

	/**
	 * PUT v1/cart.city — выбрать город доставки (шаг 1 оформления)
	 * Аналог case "delivery" в веб-версии Cart/Request.php.
	 * Сохраняет город только если для него есть доступные способы доставки.
	 */
	public function putCartCity($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$citiesId = trim($data['data']['citiesId'] ?? '');
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		$deliveryUtils = new DeliveryUtils();
		$delivery = $deliveryUtils->getTariffsByCitiesId($citiesId, $cartUtils);
		if (empty($delivery)) {
			return ['status' => 400, 'message' => 'No delivery options for this city', 'data' => null];
		}
		$cartUtils->setCartCitiesId($citiesId);
		return ['status' => 200, 'message' => 'OK', 'data' => $delivery];
	}

	/**
	 * PUT v1/cart.delivery — выбрать способ доставки (шаг 2 оформления)
	 * Аналог case "payments" в веб-версии Cart/Request.php.
	 * Сохраняет deliveryId через setCartDelivery только если для него есть способы оплаты.
	 */
	public function putCartDelivery($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$deliveryId = trim($data['data']['deliveryId'] ?? '');
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		$paymentsUtils = new PaymentsUtils();
		$payments = $paymentsUtils->getByDeliveryId($deliveryId, $cartUtils);
		if (empty($payments)) {
			return ['status' => 400, 'message' => 'No payment options for this delivery', 'data' => null];
		}
		$cartUtils->setCartDelivery($deliveryId);
		return ['status' => 200, 'message' => 'OK', 'data' => $payments];
	}

	/**
	 * PUT v1/cart.payment — выбрать способ оплаты
	 */
	public function putCartPayment($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$paymentsId = trim($data['data']['paymentsId'] ?? '');
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartPayments($paymentsId);
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'OK', 'data' => $cartUtils->getCheckoutData()];
	}

	/**
	 * PUT v1/cart.deliveryOperations — сохранить выбранное ПВЗ или адрес доставки
	 * Аналог case "deliveryOperations" в веб-версии Cart/Request.php.
	 * Сохраняет параметры доставки (выбранную точку выдачи или адрес) в корзину.
	 * Полная валидация происходит в addOrder() при оформлении заказа.
	 */
	public function putCartDeliveryOperations($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$cartUtils = $this->_newCartUtils();
		$cartUtils->setCartSummary();
		$cartSummary = $cartUtils->getCartSummary();

		// Если есть способ доставки с требованием операций - проверяем валидацию
		if (!empty($cartSummary['delivery']['extension'])) {
			$className = "\WeppsExtensions\\Cart\\Delivery\\{$cartSummary['delivery']['extension']}\\{$cartSummary['delivery']['extension']}";
			/**
			 * @var \WeppsExtensions\Cart\Delivery\Delivery $class
			 */
			$class = new $className($cartSummary['delivery']['settings'] ?? [], $cartUtils);
			$errors = $class->getErrors($data['data'] ?? []);
			
			// Фильтруем ошибки operations
			$operationErrors = [];
			foreach ($errors as $key => $error) {
				if (strpos($key, 'operations-') === 0 && !empty($error)) {
					$operationErrors[$key] = $error;
				}
			}
			
			if (!empty($operationErrors)) {
				return ['status' => 400, 'message' => 'Validation errors', 'data' => $operationErrors];
			}
		}

		// Сохраняем данные
		$deliveryUtils = new DeliveryUtils();
		$deliveryUtils->setOperations($data['data'] ?? [], $cartUtils);
		
		return ['status' => 200, 'message' => 'OK', 'data' => null];
	}

	/**
	 * CartUtils требует пользователя через Connect::$projectData['user']
	 * (аналогично веб-версии). Данные пользователя уже содержат JCart и JFav,
	 * т.к. authenticateBearerToken делает SELECT * FROM s_Users.
	 */
	private function _newCartUtils(): CartUtils
	{
		Connect::$projectData['user'] = $this->rest->getUser();
		return new CartUtils();
	}

	/**
	 * Преобразует массив свойств в структуру W_Attributes для API
	 * 
	 * @param array|null $propertiesData Массив данных о свойствах grouped по PropertyId: [PropertyId => rows]
	 * @return array|null Отформатированный массив W_Attributes или null
	 */
	private function _buildAttributesFromPropertiesValues(?array $propertiesData): ?array
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

	// -------------------------------------------------------------------------
	// NEWS
	// -------------------------------------------------------------------------

	/**
	 * GET v1/news — список новостей
	 */
	public function getNews(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int) ($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';

		$conditions = "t.IsHidden = 0";
		$params = [];

		if ($search !== '') {
			$conditions .= " AND t.Name LIKE ?";
			$params[] = '%' . $search . '%';
		}

		$obj = new Data("News");
		if (!empty($params)) {
			$obj->setParams($params);
		}
		$res = $obj->fetch($conditions, $limit, $page, "t.NDate desc");

		if (!empty($res)) {
			foreach ($res as &$row) {
				if (!empty($row['Images_FileUrl'])) {
					$row['Images_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $row['Images_FileUrl'];
				}
			}
			unset($row);
		}

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $res,
			'pagination' => ['count' => $obj->count, 'limit' => $limit, 'page' => $page],
		];
	}

	/**
	 * GET v1/news.item — новость по id
	 */
	public function getNewsItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int) ($this->get['id'] ?? 0);

		$obj = new Data("News");
		$obj->setParams([$id]);
		$res = $obj->fetch("t.Id = ? AND t.IsHidden = 0", 1, 1);

		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'News item not found', 'data' => null];
		}


		if (!empty($res[0]['Images_FileUrl'])) {
			$res[0]['Images_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $res[0]['Images_FileUrl'];
		}


		return ['status' => 200, 'message' => 'OK', 'data' => $res[0]];
	}

	// -------------------------------------------------------------------------
	// SLIDES
	// -------------------------------------------------------------------------

	/**
	 * GET v1/slides — список активных слайдов
	 */
	public function getSlides(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$obj = new Data("Slides");
		$res = $obj->fetch("t.IsHidden = 0", 1000, 1, "t.Priority desc");

		if (!empty($res)) {
			foreach ($res as $key => &$row) {
				if (!empty($row['Image_FileUrl'])) {
					$res[$key]['Image_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/slide' . $row['Image_FileUrl'];
				}
				if (!empty($row['ImageMobile_FileUrl'])) {
					$res[$key]['ImageMobile_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/slide' . $row['ImageMobile_FileUrl'];
				}
			}
			unset($row);
		}

		return [
			'status' => 200,
			'message' => 'OK',
			'data' => $res ?? [],
			'pagination' => ['count' => $obj->count, 'limit' => 1000, 'page' => 1],
		];
	}

	// -------------------------------------------------------------------------
	// ORDERS MESSAGES
	// -------------------------------------------------------------------------

	/**
	 * GET v1/orders.messages — получить сообщения по заказу
	 */
	public function getOrdersMessages(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$orderId = (int) ($this->get['id'] ?? 0);

		$actions = new ProfileActions(false);
		return $actions->getOrderMessages($user['Id'], $orderId);
	}

	/**
	 * POST v1/orders.messages — добавить сообщение к заказу
	 */
	public function postOrdersMessages($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$orderId = (int) ($data['data']['id'] ?? 0);
		$message = $data['data']['message'] ?? '';

		$actions = new ProfileActions(false);
		return $actions->addOrdersMessage($user['Id'], $orderId, $message);
	}
}
