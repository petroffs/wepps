<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Data;
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
		$page = max(1, (int)($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int)($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';
		$category = (int)($this->get['category'] ?? 0);

		switch ($this->get['sort'] ?? '') {
			case 'priceasc':  $sorting = 't.Price asc'; break;
			case 'pricedesc': $sorting = 't.Price desc'; break;
			case 'nameasc':   $sorting = 't.Name asc'; break;
			default:          $sorting = 't.Priority desc'; break;
		}

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

		// Фильтрация по свойствам: f_1=red|blue, f_2=xl
		foreach ($this->get as $key => $value) {
			if (substr($key, 0, 2) !== 'f_' || $value === '' || $value === null) {
				continue;
			}
			$propId = (int)substr($key, 2);
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
			'pages'      => $limit,
			'page'       => $page,
			'sorting'    => $sorting,
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
				foreach ($brands as $b) { $byProp[$b['Name']][] = $b; }
				$row['W_Attributes'] = array_values(array_map(
					fn($id, $rows) => [
						'id'     => $id,
						'name'   => $rows[0]['PropertyName'] ?? '',
						'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue']], $rows),
					],
					array_keys($byProp), array_values($byProp)
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
					'id'     => $id,
					'name'   => $rows[0]['PropertyName'] ?? '',
					'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue'], 'count' => (int)$r['Co']], $rows),
				],
				array_keys($item['W_Attributes']), array_values($item['W_Attributes'])
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
			[Connect::$projectServices['navigator']['catalog']??0, Connect::$projectServices['navigator']['brands']??0]
		);

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}

	/**
	 * GET v1/goods.filters — доступные свойства/значения для фильтрации
	 */
	public function getGoodsFilters(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$category = (int)($this->get['category'] ?? 0);
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

		$grouped = array_values(array_map(
			fn($id, $rows) => [
				'id'     => $id,
				'name'   => $rows[0]['PropertyName'] ?? '',
				'values' => array_map(fn($r) => ['alias' => $r['Alias'], 'value' => $r['PValue'], 'count' => (int)$r['Co']], $rows),
			],
			array_keys($result), array_values($result)
		));

		return ['status' => 200, 'message' => 'OK', 'data' => $grouped];
	}

	/**
	 * POST v1/goods — создание товара
	 */
	public function postGoods($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$name = $data['data']['name'] ?? '';
		$price = (float)($data['data']['price'] ?? 0);
		$category = (int)($data['data']['category'] ?? 0);

		Connect::$instance->query(
			"INSERT INTO Products (Name, Price, NavigatorId, IsHidden, Priority) VALUES (?, ?, ?, 0, 0)",
			[$name, $price, $category]
		);
		$id = Connect::$instance->db->lastInsertId();

		return ['status' => 200, 'message' => 'Goods item created', 'data' => ['id' => (int)$id]];
	}

	/**
	 * PUT v1/goods — обновление товара
	 */
	public function putGoods($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int)($data['data']['id'] ?? 0);
		$name = $data['data']['name'] ?? null;
		$price = isset($data['data']['price']) ? (float)$data['data']['price'] : null;

		$set = [];
		$params = [];

		if ($name !== null) { $set[] = 'Name = ?'; $params[] = $name; }
		if ($price !== null) { $set[] = 'Price = ?'; $params[] = $price; }

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
		$id = (int)($this->get['id'] ?? 0);

		Connect::$instance->query("UPDATE Products SET IsHidden = 1 WHERE Id = ?", [$id]);

		return ['status' => 200, 'message' => 'Goods item deleted', 'data' => null];
	}

	// -------------------------------------------------------------------------
	// ORDERS
	// -------------------------------------------------------------------------

	/**
	 * GET v1/orders — список заказов пользователя
	 */
	public function getOrders(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$page = max(1, (int)($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int)($this->get['limit'] ?? 20)));

		$obj = new Data("Orders");
		$obj->setParams([$user['Id']]);
		$res = $obj->fetch("t.UserId = ? AND t.IsHidden = 0", $limit, $page, "t.Id desc");

		return ['status' => 200, 'message' => 'OK', 'data' => $res, 'count' => $obj->count];
	}

	/**
	 * GET v1/orders.item — заказ по id
	 */
	public function getOrdersItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$id = (int)($this->get['id'] ?? 0);

		$obj = new Data("Orders");
		$obj->setParams([$id, $user['Id']]);
		$res = $obj->fetch("t.Id = ? AND t.UserId = ? AND t.IsHidden = 0", 1, 1);

		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $res[0]];
	}

	/**
	 * POST v1/orders — создание заказа
	 */
	public function postOrders($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$name = $data['data']['name'] ?? '';
		$phone = $data['data']['phone'] ?? '';
		$email = $data['data']['email'] ?? '';
		$positions = $data['data']['positions'] ?? '';

		Connect::$instance->query(
			"INSERT INTO Orders (Name, OPhone, OEmail, JPositions, UserId, IsHidden, Priority) VALUES (?, ?, ?, ?, ?, 0, 0)",
			[$name, $phone, $email, $positions, $user['Id']]
		);
		$id = Connect::$instance->db->lastInsertId();

		return ['status' => 200, 'message' => 'Order created', 'data' => ['id' => (int)$id]];
	}

	/**
	 * PUT v1/orders.status — обновление статуса заказа
	 */
	public function putOrdersStatus($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$id = (int)($data['data']['id'] ?? 0);
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
		$id = (int)($this->get['id'] ?? 0);

		$res = Connect::$instance->fetch("SELECT Id FROM Orders WHERE Id = ? AND UserId = ? AND IsHidden = 0", [$id, $user['Id']]);
		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'Order not found', 'data' => null];
		}

		Connect::$instance->query("UPDATE Orders SET IsHidden = 1 WHERE Id = ?", [$id]);

		return ['status' => 200, 'message' => 'Order cancelled', 'data' => null];
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
		$page = max(1, (int)($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int)($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';

		$conditions = "t.IsHidden = 0";
		$params = [];

		if ($search !== '') {
			$conditions .= " AND lower(t.Name) LIKE lower(?)";
			$params[] = '%' . $search . '%';
		}

		$obj = new Data("News");
		if (!empty($params)) {
			$obj->setParams($params);
		}
		$res = $obj->fetch($conditions, $limit, $page, "t.NDate desc");

		return ['status' => 200, 'message' => 'OK', 'data' => $res, 'count' => $obj->count];
	}

	/**
	 * GET v1/news.item — новость по id
	 */
	public function getNewsItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int)($this->get['id'] ?? 0);

		$obj = new Data("News");
		$obj->setParams([$id]);
		$res = $obj->fetch("t.Id = ? AND t.IsHidden = 0", 1, 1);

		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'News item not found', 'data' => null];
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

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? []];
	}
}
