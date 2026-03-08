<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Products\ProductsUtils;
use WeppsExtensions\Template\Filters\Filters;

/**
 * REST обработчик для API v1
 * Auth, Profile, Goods, Orders, News, Slides
 */
class RestV1
{
	/**
	 * Режим двухэтапной авторизации.
	 * false — auth.login сразу возвращает access+refresh токены.
	 * true  — auth.login возвращает confirm_token; токены выдаются только после auth.confirm.
	 */
	protected const CONFIRM_AUTH = false;

	private Rest $rest;

	/**
	 * GET параметры запроса
	 * @var array
	 */
	protected array $get = [];

	/**
	 * POST параметры запроса
	 * @var array
	 */
	protected array $post = [];

	/**
	 * Парсированные данные из тела JSON запроса
	 * @var array|null
	 */
	protected ?array $data = null;

	public function __construct(Rest $rest)
	{
		$this->rest = $rest;
		$this->get = &$rest->getGet();
		$this->post = &$rest->getPost();
		$this->data = &$rest->getData();
	}

	// -------------------------------------------------------------------------
	// AUTH
	// -------------------------------------------------------------------------

	/**
	 * POST v1/auth.login — аутентификация и выдача JWT токена
	 */
	public function postAuthLogin($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));
		$password = $data['data']['password'] ?? '';

		$res = Connect::$instance->fetch("SELECT Id, Login, Password FROM s_Users WHERE Login = ? AND IsHidden = 0", [$login]);

		if (empty($res[0]) || !password_verify($password, $res[0]['Password'] ?? '')) {
			return ['status' => 401, 'message' => 'Invalid credentials', 'data' => null];
		}

		$user = $res[0];
		$jwt = new Jwt();

		if (self::CONFIRM_AUTH) {
			$code = random_int(100000, 999999);
			$confirmLifetime = 600; // 10 минут
			$confirmToken = $jwt->token_encode(['typ' => 'confirm', 'id' => $user['Id'], 'code' => $code], $confirmLifetime);
			$confirmData = $jwt->token_decode($confirmToken);

			$tasks = new \WeppsCore\Tasks();
			$tasks->add('rest-auth-confirm', [
				'token' => $confirmToken,
				'code'  => $code,
				'email' => $user['Login'],
				'exp'   => $confirmData['payload']['exp'],
			]);

			return ['status' => 200, 'message' => 'Confirmation required', 'data' => [
				'confirm_token' => $confirmToken,
				'confirm_exp'   => $confirmData['payload']['exp'],
			]];
		}

		$accessLifetime = 3600; // 1 час
		$refreshLifetime = 2592000; // 30 дней

		$accessToken = $jwt->token_encode(['typ' => 'auth', 'id' => $user['Id']], $accessLifetime);
		$refreshToken = $jwt->token_encode(['typ' => 'refresh', 'id' => $user['Id']], $refreshLifetime);
		$accessData = $jwt->token_decode($accessToken);
		$refreshData = $jwt->token_decode($refreshToken);

		return ['status' => 200, 'message' => 'Login successful', 'data' => [
			'access_token'  => $accessToken,
			'access_exp'    => $accessData['payload']['exp'],
			'refresh_token' => $refreshToken,
			'refresh_exp'   => $refreshData['payload']['exp'],
		]];
	}

	/**
	 * POST v1/profile — регистрация нового пользователя
	 */
	public function postProfile($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));
		$password = $data['data']['password'] ?? '';
		$name = $data['data']['name'] ?? '';
		$phone = $data['data']['phone'] ?? '';

		$exists = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Login = ?", [$login]);
		if (!empty($exists[0])) {
			return ['status' => 409, 'message' => 'User with this email already exists', 'data' => null];
		}

		$hash = password_hash($password, PASSWORD_DEFAULT);
		Connect::$instance->query(
			"INSERT INTO s_Users (Login, Password, Name, Phone, IsHidden) VALUES (?, ?, ?, ?, 0)",
			[$login, $hash, $name, $phone]
		);

		return ['status' => 200, 'message' => 'Registration successful', 'data' => null];
	}

	/**
	 * POST v1/auth.refresh — обновление пары токенов по refresh token
	 */
	public function postAuthRefresh($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$refreshToken = $data['data']['refresh_token'] ?? '';

		$jwt = new Jwt();
		$decoded = $jwt->token_decode($refreshToken);

		if ($decoded['status'] !== 200) {
			return ['status' => 401, 'message' => 'Invalid or expired refresh token', 'data' => null];
		}
		if (($decoded['payload']['typ'] ?? '') !== 'refresh') {
			return ['status' => 401, 'message' => 'Invalid token type', 'data' => null];
		}

		$userId = (int)($decoded['payload']['id'] ?? 0);
		$res = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Id = ? AND IsHidden = 0", [$userId]);
		if (empty($res[0])) {
			return ['status' => 401, 'message' => 'User not found or inactive', 'data' => null];
		}

		$accessLifetime = 3600;
		$refreshLifetime = 2592000;

		$accessToken = $jwt->token_encode(['typ' => 'auth', 'id' => $userId], $accessLifetime);
		$newRefreshToken = $jwt->token_encode(['typ' => 'refresh', 'id' => $userId], $refreshLifetime);
		$accessData = $jwt->token_decode($accessToken);
		$refreshData = $jwt->token_decode($newRefreshToken);

		return ['status' => 200, 'message' => 'Token refreshed', 'data' => [
			'access_token' => $accessToken,
			'access_exp' => $accessData['payload']['exp'],
			'refresh_token' => $newRefreshToken,
			'refresh_exp' => $refreshData['payload']['exp'],
		]];
	}

	/**
	 * POST v1/auth.confirm — подтверждение входа через confirm_token из письма.
	 *
	 * Работает только при CONFIRM_AUTH = true в auth.login.
	 * Клиент получает confirm_token из ответа auth.login и сохраняет его.
	 * Письмо содержит ссылку (?token=...) и 6-значный код для ручного ввода.
	 * Оба варианта передают confirm_token; code — опциональная дополнительная проверка.
	 */
	public function postAuthConfirm($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$token = $data['data']['token'] ?? '';
		$code  = isset($data['data']['code']) ? (int)$data['data']['code'] : null;

		$jwt = new Jwt();
		$decoded = $jwt->token_decode($token);

		if ($decoded['status'] !== 200) {
			return ['status' => 401, 'message' => 'Invalid or expired confirmation token', 'data' => null];
		}
		if (($decoded['payload']['typ'] ?? '') !== 'confirm') {
			return ['status' => 401, 'message' => 'Invalid token type', 'data' => null];
		}
		if ($code !== null && (int)($decoded['payload']['code'] ?? 0) !== $code) {
			return ['status' => 401, 'message' => 'Invalid confirmation code', 'data' => null];
		}

		$userId = (int)($decoded['payload']['id'] ?? 0);
		$res = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Id = ? AND IsHidden = 0", [$userId]);
		if (empty($res[0])) {
			return ['status' => 401, 'message' => 'User not found or inactive', 'data' => null];
		}

		$accessLifetime = 3600;
		$refreshLifetime = 2592000;

		$accessToken  = $jwt->token_encode(['typ' => 'auth',    'id' => $userId], $accessLifetime);
		$refreshToken = $jwt->token_encode(['typ' => 'refresh', 'id' => $userId], $refreshLifetime);
		$accessData   = $jwt->token_decode($accessToken);
		$refreshData  = $jwt->token_decode($refreshToken);

		return ['status' => 200, 'message' => 'Login confirmed', 'data' => [
			'access_token'  => $accessToken,
			'access_exp'    => $accessData['payload']['exp'],
			'refresh_token' => $refreshToken,
			'refresh_exp'   => $refreshData['payload']['exp'],
		]];
	}

	// -------------------------------------------------------------------------
	// PROFILE
	// -------------------------------------------------------------------------

	/**
	 * GET v1/profile — профиль текущего пользователя
	 */
	public function getProfile(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();

		return ['status' => 200, 'message' => 'OK', 'data' => [
			'id' => $user['Id'],
			'login' => $user['Login'],
			'name' => $user['Name'] ?? '',
			'phone' => $user['Phone'] ?? '',
		]];
	}

	/**
	 * PUT v1/profile — обновление профиля
	 */
	public function putProfile($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$name = $data['data']['name'] ?? null;
		$phone = $data['data']['phone'] ?? null;
		$email = $data['data']['email'] ?? null;

		$set = [];
		$params = [];

		if ($name !== null) { $set[] = 'Name = ?'; $params[] = $name; }
		if ($phone !== null) { $set[] = 'Phone = ?'; $params[] = $phone; }
		if ($email !== null) { $set[] = 'Login = ?'; $params[] = strtolower($email); }

		if (empty($set)) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		$params[] = $user['Id'];
		Connect::$instance->query("UPDATE s_Users SET " . implode(', ', $set) . " WHERE Id = ?", $params);

		return ['status' => 200, 'message' => 'Profile updated', 'data' => null];
	}

	/**
	 * PUT v1/profile.password — смена пароля
	 */
	public function putProfilePassword($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		$passwordOld = $data['data']['password_old'] ?? '';
		$passwordNew = $data['data']['password_new'] ?? '';

		$res = Connect::$instance->fetch("SELECT Password FROM s_Users WHERE Id = ?", [$user['Id']]);

		if (empty($res[0]) || !password_verify($passwordOld, $res[0]['Password'])) {
			return ['status' => 401, 'message' => 'Current password is incorrect', 'data' => null];
		}

		$hash = password_hash($passwordNew, PASSWORD_DEFAULT);
		Connect::$instance->query("UPDATE s_Users SET Password = ? WHERE Id = ?", [$hash, $user['Id']]);

		return ['status' => 200, 'message' => 'Password changed', 'data' => null];
	}

	/**
	 * DELETE v1/profile — удаление аккаунта
	 */
	public function deleteProfile(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$user = $this->rest->getUser();
		Connect::$instance->query("UPDATE s_Users SET IsHidden = 1 WHERE Id = ?", [$user['Id']]);

		return ['status' => 200, 'message' => 'Account deleted', 'data' => null];
	}

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
	// USERS (M2M)
	// -------------------------------------------------------------------------

	/**
	 * GET v1/users — список пользователей
	 */
	public function getUsers(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$page = max(1, (int)($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int)($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';

		$conditions = "IsHidden = 0";
		$params = [];

		if ($search !== '') {
			$conditions .= " AND (lower(Login) LIKE lower(?) OR lower(Name) LIKE lower(?))";
			$params[] = '%' . $search . '%';
			$params[] = '%' . $search . '%';
		}

		$offset = ($page - 1) * $limit;
		$countRes = Connect::$instance->fetch("SELECT COUNT(*) as cnt FROM s_Users WHERE {$conditions}", $params);
		$total = (int)($countRes[0]['cnt'] ?? 0);

		$res = Connect::$instance->fetch(
			"SELECT Id, Login, Name, Phone, UserPermissions FROM s_Users WHERE {$conditions} ORDER BY Id DESC LIMIT ? OFFSET ?",
			array_merge($params, [$limit, $offset])
		);

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? [], 'count' => $total];
	}

	/**
	 * GET v1/users.item — пользователь по id
	 */
	public function getUsersItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int)($this->get['id'] ?? 0);

		$res = Connect::$instance->fetch(
			"SELECT Id, Login, Name, Phone, UserPermissions FROM s_Users WHERE Id = ? AND IsHidden = 0",
			[$id]
		);

		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'User not found', 'data' => null];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $res[0]];
	}

	/**
	 * POST v1/users — создание пользователя
	 */
	public function postUsers($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));
		$password = $data['data']['password'] ?? '';
		$name = $data['data']['name'] ?? '';
		$phone = $data['data']['phone'] ?? '';

		$exists = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Login = ?", [$login]);
		if (!empty($exists[0])) {
			return ['status' => 409, 'message' => 'User with this email already exists', 'data' => null];
		}

		$hash = password_hash($password, PASSWORD_DEFAULT);
		Connect::$instance->query(
			"INSERT INTO s_Users (Login, Password, Name, Phone, IsHidden) VALUES (?, ?, ?, ?, 0)",
			[$login, $hash, $name, $phone]
		);
		$id = Connect::$instance->db->lastInsertId();

		return ['status' => 200, 'message' => 'User created', 'data' => ['id' => (int)$id]];
	}

	/**
	 * PUT v1/users — обновление пользователя
	 */
	public function putUsers($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int)($data['data']['id'] ?? 0);
		$name = $data['data']['name'] ?? null;
		$phone = $data['data']['phone'] ?? null;
		$email = $data['data']['email'] ?? null;

		$res = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Id = ? AND IsHidden = 0", [$id]);
		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'User not found', 'data' => null];
		}

		$set = [];
		$params = [];

		if ($name !== null) { $set[] = 'Name = ?'; $params[] = $name; }
		if ($phone !== null) { $set[] = 'Phone = ?'; $params[] = $phone; }
		if ($email !== null) { $set[] = 'Login = ?'; $params[] = strtolower($email); }

		if (empty($set)) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		$params[] = $id;
		Connect::$instance->query("UPDATE s_Users SET " . implode(', ', $set) . " WHERE Id = ?", $params);

		return ['status' => 200, 'message' => 'User updated', 'data' => null];
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
