<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\DeliveryUtils;
use WeppsExtensions\Cart\Payments\PaymentsUtils;
use WeppsExtensions\Products\ProductsUtils;
use WeppsExtensions\Template\Filters\Filters;

/**
 * REST обработчик для APP-методов API v1
 * Goods, Orders, News, Slides
 */
class RestV1APP extends RestV1
{
	// -------------------------------------------------------------------------
	// GOODS
	// -------------------------------------------------------------------------

	/**
	 * GET v1/goods — список товаров
	 */
	public function getGoods(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int) ($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';
		$category = (int) ($this->get['category'] ?? 0);

		switch ($this->get['sort'] ?? '') {
			case 'priceasc':
				$sorting = 't.Price asc';
				break;
			case 'pricedesc':
				$sorting = 't.Price desc';
				break;
			case 'nameasc':
				$sorting = 't.Name asc';
				break;
			default:
				$sorting = 't.Priority desc';
				break;
		}

		$conditions = 't.IsHidden=0';
		$params = [];

		if ($category > 0) {
			$conditions .= ' AND t.NavigatorId = ?';
			$params[] = $category;
		}
		if ($search !== '') {
			$conditions .= ' AND t.Name LIKE ?';
			$params[] = '%' . $search . '%';
		}

		// Фильтрация по свойствам: f_1=red|blue, f_2=xl
		foreach ($this->get as $key => $value) {
			if (substr($key, 0, 2) !== 'f_' || $value === '' || $value === null) {
				continue;
			}
			$propId = (int) substr($key, 2);
			if ($propId <= 0) {
				continue;
			}
			$aliases = explode('|', $value);
			$placeholders = rtrim(str_repeat('?,', count($aliases)), ',');
			$conditions .= " AND t.Id in (select distinct TableNameId from s_PropertiesValues where IsHidden=0 and TableName='Products' and Name=? and Alias in ($placeholders))";
			$params[] = $propId;
			$params = array_merge($params, $aliases);
		}

		$productsUtils = new ProductsUtils();
		$result = $productsUtils->getProducts([
			'pages' => $limit,
			'page' => $page,
			'sorting' => $sorting,
			'conditions' => ['conditions' => $conditions, 'params' => $params],
		]);

		// Получаем бренды (свойство id=1) одним запросом для всей страницы
		$brandsByProduct = [];
		$ids = array_column($result['rows'], 'Id');
		if (!empty($ids)) {
			$placeholders = rtrim(str_repeat('?,', count($ids)), ',');
			$brandsByProduct = Connect::$instance->fetch(
				"SELECT pv.TableNameId, pv.Name, pv.PValue, pv.Alias, p.Name as PropertyName FROM s_PropertiesValues pv LEFT JOIN s_Properties p ON p.Id = pv.Name AND p.IsHidden=0 WHERE pv.IsHidden=0 AND pv.TableName='Products' AND pv.Name=1 AND pv.TableNameId IN ($placeholders)",
				$ids,
				'group'
			);
		}

		foreach ($result['rows'] as &$row) {
			if (!empty($row['Images_FileUrl'])) {
				$row['Images_FileUrl'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $row['Images_FileUrl'];
			}
			if (!empty($row['W_Variations'])) {
				$row['W_Variations'] = $productsUtils->getVariationsArray($row['W_Variations']);
			}
			$brands = $brandsByProduct[$row['Id']] ?? null;
			if ($brands) {
				$byProp = [];
				foreach ($brands as $b) {
					$byProp[$b['Name']][] = $b;
				}
				$row['W_Attributes'] = array_values(array_map(
					fn($id, $rows) => [
						'id' => $id,
						'name' => $rows[0]['PropertyName'] ?? '',
						'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue']], $rows),
					],
					array_keys($byProp),
					array_values($byProp)
				));
			} else {
				$row['W_Attributes'] = null;
			}
		}
		unset($row);

		return ['status' => 200, 'message' => 'OK', 'data' => $result['rows'], 'count' => $result['count']];
	}

	/**
	 * GET v1/goods.item — товар по id или alias
	 */
	public function getGoodsItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = $this->get['id'] ?? '';

		if (empty($id)) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$productsUtils = new ProductsUtils();
		$item = $productsUtils->getProductsItem($id);

		if (!empty($item['W_Attributes'])) {
			$item['W_Attributes'] = array_values(array_map(
				fn($id, $rows) => [
					'id' => $id,
					'name' => $rows[0]['PropertyName'] ?? '',
					'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue'], 'count' => (int) $r['Co']], $rows),
				],
				array_keys($item['W_Attributes']),
				array_values($item['W_Attributes'])
			));
		}

		if (empty($item)) {
			return ['status' => 404, 'message' => 'Item not found', 'data' => null];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $item];
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
			$grouped[$id] = [
				'id' => (int) $id,
				'name' => $rows[0]['PropertyName'] ?? '',
				'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue'], 'count' => (int) $r['Co']], $rows),
			];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $grouped];
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
		$id = Connect::$instance->db->lastInsertId();

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
	public function getOrders(?int $id = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$page = max(1, (int) ($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int) ($this->get['limit'] ?? 20)));

		$obj = new Data("Orders");

		if ($id > 0) {
			$obj->setParams([$id, $user['Id']]);
			$res = $obj->fetch("t.Id = ? AND t.UserId = ? AND t.IsHidden = 0", 1, 1, "t.Id desc");
		} else {
			$obj->setParams([$user['Id']]);
			$res = $obj->fetch("t.UserId = ? AND t.IsHidden = 0", $limit, $page, "t.Id desc");
		}

		if (!empty($res)) {
			foreach ($res as $key => &$row) {
				$jdata = json_decode($row['JData'], true);
				if (!empty($jdata['items'])) {
					foreach ($jdata['items'] as $k => &$item) {
						$jdata['items'][$k]['url'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . $item['url'];
						$jdata['items'][$k]['image'] = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/pic/mediumv' . $item['image'];
					}
				}
				$jpositions =  json_decode($row['JPositions'], true);
				$res[$key]['JData'] = $jdata;
				$res[$key]['JPositions'] = $jpositions;
			}
		}	

		return ['status' => 200, 'message' => 'OK', 'data' => $res, 'count' => $obj->count];
	}

	/**
	 * GET v1/orders.item — заказ по id
	 */
	public function getOrdersItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int) ($this->get['id'] ?? 0);

		if ($id <= 0) {
			return ['status' => 400, 'message' => 'id required', 'data' => null];
		}

		$result = $this->getOrders($id);

		if (empty($result['data'][0])) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $result['data'][0]];
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
	 */
	public function deleteOrders(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$id = (int) ($this->get['id'] ?? 0);

		$res = Connect::$instance->fetch("SELECT Id FROM Orders WHERE Id = ? AND UserId = ? AND IsHidden = 0", [$id, $user['Id']]);
		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}

		Connect::$instance->query("UPDATE Orders SET IsHidden = 1 WHERE Id = ?", [$id]);

		return ['status' => 200, 'message' => 'Order cancelled', 'data' => null];
	}

	// -------------------------------------------------------------------------
	// CART
	// -------------------------------------------------------------------------

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
	public function postCart($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = trim($data['data']['id'] ?? '');
		$quantity = max(1, (int) ($data['data']['quantity'] ?? 1));
		$cartUtils = $this->_newCartUtils();
		$cartUtils->add($id, $quantity);
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'Cart updated', 'data' => $cartUtils->getCartSummary()];
	}

	/**
	 * PUT v1/cart — обновить количество товара в корзине
	 */
	public function putCart($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = trim($data['data']['id'] ?? '');
		$quantity = max(1, (int) ($data['data']['quantity'] ?? 1));
		$cartUtils = $this->_newCartUtils();
		$cartUtils->edit($id, $quantity);
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
		$cartUtils = $this->_newCartUtils();
		$cartUtils->remove($id);
		$cartUtils->setCartSummary();
		return ['status' => 200, 'message' => 'Item removed', 'data' => $cartUtils->getCartSummary()];
	}

	/**
	 * POST v1/cart.place_order — оформить заказ из текущей корзины
	 * Контактные данные берутся из профиля пользователя.
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

		$result = $cartUtils->addOrder();

		if (empty($result['id'])) {
			return ['status' => 400, 'message' => 'No active items in cart', 'data' => null];
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
	 * CartUtils требует пользователя через Connect::$projectData['user']
	 * (аналогично веб-версии). Данные пользователя уже содержат JCart и JFav,
	 * т.к. authenticateBearerToken делает SELECT * FROM s_Users.
	 */
	private function _newCartUtils(): CartUtils
	{
		Connect::$projectData['user'] = $this->rest->getUser();
		return new CartUtils();
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

		return ['status' => 200, 'message' => 'OK', 'data' => $res, 'count' => $obj->count];
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

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}
}
