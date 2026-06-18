<?php
namespace WeppsExtensions\Addons\Rest;

/**
 * Конфигурация REST API методов
 * Используется для маршрутизации и автодокументации
 * 
 * Примеры JSON для v1.post.test и v1.put.test:
 * - Объект: {"type": "test", "data": {"id": 1, "title": "test 1", "date": "2023-10-01", "email": "test@example.com", "phone": "1234567890", "guid": "550e8400-e29b-41d4-a716-446655440000", "barcode": "1234567890128"}}
 * - Массив:  {"type": "test", "data": [{"id": 1, "title": "test 1", "date": "2023-10-01", "email": "test@example.com", "phone": "1234567890", "guid": "550e8400-e29b-41d4-a716-446655440000", "barcode": "1234567890128"}]}
 * 
 * M2M POST запросы (поддерживают batch-создание):
 * - Одиночное: POST /rest/m2m/goods с телом {"name": "Товар", "price": 99.99}
 * - Batch (макс 100): POST /rest/m2m/goods с телом [{"name": "Товар 1", "price": 99.99}, {"name": "Товар 2", "price": 199.99}]
 * - Возвращает 201 для одиночного, 207 для batch с per-item status'ами
 * 
 * M2M PUT запросы:
 * - Вариант 1: PUT /rest/m2m/goods?id=123 с телом {"Price": 1200, "Name": "Название"}
 * - Вариант 2: PUT /rest/m2m/goods с телом {"id": 123, "Price": 1200, "Name": "Название"}
 * ID можно передавать либо как GET параметр ?id={{id}}, либо в теле JSON
 * 
 * validation: валидация данных из тела JSON
 * query_validation: валидация GET-параметров, например для фильтрации и сортировки
 * type: строка с типом данных ('int', 'int2', 'float', 'float2', 'string', 'email', 'date', 'phone', 'guid', 'barcode')
 * custom_response: если true, ответ возвращается без стандартной структуры status/message/data
 * log: если false, запрос не логируется (по умолчанию true)
 */
class RestConfig
{
	public static function getConfig(): array
	{
		return [
			'v0' => [
				'get' => [
					'test' => [
						'class' => RestAd::class,
						'method' => 'getTest',
						'note' => 'Retrieve test data with optional filtering',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
							'sort' => ['type' => 'string', 'required' => false]
						],
						'custom_response' => true
					],
				],
				'post' => [
					'test' => [
						'class' => RestAd::class,
						'method' => 'setTest',
						'note' => 'Create or update test data with validation',
						#'auth_required' => true,
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'title' => ['type' => 'string', 'required' => true],
							'date' => ['type' => 'date', 'required' => false],
							'email' => ['type' => 'email', 'required' => false],
							'phone' => ['type' => 'phone', 'required' => false],
							'guid' => ['type' => 'guid', 'required' => false],
							'barcode' => ['type' => 'barcode', 'required' => false]
						]
					],
				],
				'delete' => [
					'test' => [
						'class' => RestAd::class,
						'method' => 'removeTest',
						'note' => 'Remove test data by ID. Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
				],
				'put' => [
					'test' => [
						'class' => RestAd::class,
						'method' => 'setTest',
						'note' => 'Update existing test data',
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'title' => ['type' => 'string', 'required' => true]
						]
					],
				],
				'cli' => [
					'removeLogLocal' => [
						'class' => RestCli::class,
						'method' => 'removeLogLocal',
						'note' => 'Remove local log files',
					],
					'test' => [
						'class' => RestCli::class,
						'method' => 'cliTest',
						'note' => 'Execute CLI test operations',
					],
					'tasks.process' => [
						'class' => RestCli::class,
						'method' => 'tasksProcess',
						'note' => 'Process pending async tasks from s_Tasks queue',
					],
					'tasks.result' => [
						'class' => RestCli::class,
						'method' => 'tasksResult',
						'note' => 'Get task result by id from s_Tasks queue',
					],
				],
			],
			'v1' => [
				'get' => [
					'home' => [
						'class' => RestV1APP::class,
						'method' => 'getHome',
						'note' => 'Get aggregated home screen data: slides, categories, news, goods, active order (if authenticated)',
						'auth_optional' => true,
					],
					'profile' => [
						'class' => RestV1::class,
						'method' => 'getProfile',
						'note' => 'Get current user profile: personal info, contacts',
						'auth_required' => true,
					],
					'profile.settings' => [
						'class' => RestV1::class,
						'method' => 'getProfileSettings',
						'note' => 'Get current user app settings (theme, notifications)',
						'auth_required' => true,
					],
					'goods' => [
						'class' => RestV1APP::class,
						'method' => 'getGoods',
						'note' => 'Get list of goods with filtering and pagination',
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
							'sort' => ['type' => 'string', 'required' => false],
							'search' => ['type' => 'string', 'required' => false],
							'category' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.item' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsItem',
						'note' => 'Get single goods item by id',
						'query_validation' => [
							'id' => ['type' => 'string', 'required' => true],
						],
					],
					'goods.categories' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsCategories',
						'note' => 'Get list of goods categories with ParentDir for tree building',
					],
					'goods.favorites' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsFavorites',
						'note' => 'Get current user favorite goods',
						'auth_required' => true,
					],
					'goods.filters' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsFilters',
						'note' => 'Get available property filters for goods list',
						'query_validation' => [
							'category' => ['type' => 'int2', 'required' => false],
							'search' => ['type' => 'string', 'required' => false],
						],
					],
					'orders' => [
						'class' => RestV1APP::class,
						'method' => 'getOrders',
						'note' => 'Get list of current user orders',
						'auth_required' => true,
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
						],
					],
					'orders.item' => [
						'class' => RestV1APP::class,
						'method' => 'getOrdersItem',
						'note' => 'Get single order by id',
						'auth_required' => true,
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'orders.messages' => [
						'class' => RestV1APP::class,
						'method' => 'getOrdersMessages',
						'note' => 'Get messages for order by id',
						'auth_required' => true,
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'news' => [
						'class' => RestV1APP::class,
						'method' => 'getNews',
						'note' => 'Get list of news with pagination',
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
							'search' => ['type' => 'string', 'required' => false],
						],
					],
					'news.item' => [
						'class' => RestV1APP::class,
						'method' => 'getNewsItem',
						'note' => 'Get single news item by id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'slides' => [
						'class' => RestV1APP::class,
						'method' => 'getSlides',
						'note' => 'Get list of active slides',
					],
					'cart' => [
						'class' => RestV1APP::class,
						'method' => 'getCart',
						'note' => 'Get current user cart with items and totals',
						'auth_required' => true,
					],
					'cart.checkout' => [
						'class' => RestV1APP::class,
						'method' => 'getCartCheckout',
						'note' => 'Get available delivery and payment options for current cart',
						'auth_required' => true,
					],
					'cart.city' => [
						'class' => RestV1APP::class,
						'method' => 'getCartCity',
						'note' => 'Search cities by query string (?q=...)',
						'auth_required' => true,
						'query_validation' => [
							'q' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.delivery' => [
						'class' => RestV1APP::class,
						'method' => 'getCartDelivery',
						'note' => 'Get available delivery methods for a city (?citiesId=...)',
						'auth_required' => true,
						'query_validation' => [
							'citiesId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.metrics' => [
						'class' => RestV1APP::class,
						'method' => 'getCartMetrics',
						'note' => 'Get cart item count and item ids (works for anonymous and authenticated users)',
						'auth_optional' => true,
					],
				],
				'post' => [
					'auth.login' => [
						'class' => RestV1::class,
						'method' => 'postAuthLogin',
						'note' => 'Authenticate user and return JWT token',
						'log' => false,
						'validation' => [
							'login' => ['type' => 'email', 'required' => true],
							'password' => ['type' => 'string', 'required' => true],
						],
					],
					'auth.logout' => [
						'class' => RestV1::class,
						'method' => 'postAuthLogout',
						'note' => 'Logout current user (client must delete both tokens from local storage)',
						'auth_required' => true,
						'log' => false,
					],
					'auth.refresh' => [
						'class' => RestV1::class,
						'method' => 'postAuthRefresh',
						'note' => 'Refresh access token using refresh token',
						'log' => false,
						'validation' => [
							'refresh_token' => ['type' => 'string', 'required' => true],
						],
					],
					'auth.confirm' => [
						'class' => RestV1::class,
						'method' => 'postAuthConfirm',
						'note' => 'Confirm login via confirm_token from email (CONFIRM_AUTH mode)',
						'log' => false,
						'validation' => [
							'token' => ['type' => 'string', 'required' => true],
							'code' => ['type' => 'int2', 'required' => false],
						],
					],
					'register.confirm' => [
						'class' => RestV1::class,
						'method' => 'postRegisterConfirm',
						'note' => 'Complete registration via token from email. Returns access+refresh tokens.',
						'log' => false,
						'validation' => [
							'token' => ['type' => 'string', 'required' => true],
							'password' => ['type' => 'string', 'required' => true],
							'password2' => ['type' => 'string', 'required' => true],
						],
					],
					'register' => [
						'class' => RestV1::class,
						'method' => 'postRegister',
						'note' => 'Initiate registration: validate data and send confirmation email',
						'validation' => [
							'login' => ['type' => 'email', 'required' => true],
							'phone' => ['type' => 'phone', 'required' => true],
							'nameSurname' => ['type' => 'string', 'required' => true],
							'nameFirst' => ['type' => 'string', 'required' => true],
							'namePatronymic' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.password-reset' => [
						'class' => RestV1::class,
						'method' => 'postAuthPasswordReset',
						'note' => 'Request password reset: send recovery link to email',
						'validation' => [
							'login' => ['type' => 'email', 'required' => true],
						],
					],
					'goods' => [
						'class' => RestV1APP::class,
						'method' => 'postGoods',
						'note' => 'Create new goods item(s). Supports single item (object) or batch (array of objects, max 100). Returns 201 for single item or 207 for batch with per-item status.',
						'role_required' => [1, 2],
						'validation' => [
							'name' => ['type' => 'string', 'required' => true],
							'price' => ['type' => 'float2', 'required' => false],
							'category' => ['type' => 'int2', 'required' => false],
						],
					],
					'cart' => [
						'class' => RestV1APP::class,
						'method' => 'postCart',
						'note' => 'Add item to cart or update quantity if already in cart',
						'auth_required' => true,
						'validation' => [
							'id' => ['type' => 'string', 'required' => true],
							'quantity' => ['type' => 'int2', 'required' => false],
						],
					],
					'cart.placeOrder' => [
						'class' => RestV1APP::class,
						'method' => 'postCartPlaceOrder',
						'note' => 'Place an order from current cart (contact info taken from user profile)',
						'auth_required' => true,
					],
					'orders.messages' => [
						'class' => RestV1APP::class,
						'method' => 'postOrdersMessages',
						'note' => 'Add message to order',
						'auth_required' => true,
						'validation' => [
							'id' => ['type' => 'int2', 'required' => true],
							'message' => ['type' => 'string', 'required' => true],
						],
					],
				],
				'delete' => [
					'profile' => [
						'class' => RestV1::class,
						'method' => 'deleteProfile',
						'note' => 'Delete current user account (2-step: word "УДАЛИТЬ" → code confirmation). Supports batch via body {"ids": [...]} (if implemented in handler)',
						'auth_required' => true,
						'validation' => [
							'word' => ['type' => 'string', 'required' => false],
							'code' => ['type' => 'string', 'required' => false],
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods' => [
						'class' => RestV1APP::class,
						'method' => 'deleteGoods',
						'note' => 'Delete goods item by id or batch. Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'role_required' => [1, 2],
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
					'cart' => [
						'class' => RestV1APP::class,
						'method' => 'deleteCart',
						'note' => 'Remove item(s) from cart. Supports ?id=item_id or batch via body {"ids": ["item_1", "item_2", ...]}',
						'auth_required' => true,
						'query_validation' => [
							'id' => ['type' => 'string', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'string', 'required' => false],
						],
					],
					'orders' => [
						'class' => RestV1APP::class,
						'method' => 'deleteOrders',
						'note' => 'Cancel order(s). Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'auth_required' => true,
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
				],
				'put' => [
					'profile' => [
						'class' => RestV1::class,
						'method' => 'putProfile',
						'note' => 'Update current user name (ФИО) and address. Email and phone are changed via separate endpoints with confirmation.',
						'auth_required' => true,
						'validation' => [
							'nameSurname' => ['type' => 'string', 'required' => false],
							'nameFirst' => ['type' => 'string', 'required' => false],
							'namePatronymic' => ['type' => 'string', 'required' => false],
							'city' => ['type' => 'string', 'required' => false],
							'address' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.email' => [
						'class' => RestV1::class,
						'method' => 'putProfileEmail',
						'note' => 'Change email (2-step). Step 1: send {email} → receive confirmation code. Step 2: send {email, code} → confirm change.',
						'auth_required' => true,
						'validation' => [
							'email' => ['type' => 'email', 'required' => true],
							'code' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.phone' => [
						'class' => RestV1::class,
						'method' => 'putProfilePhone',
						'note' => 'Change phone (2-step). Step 1: send {phone} → receive code via email. Step 2: send {phone, code} → confirm change.',
						'auth_required' => true,
						'validation' => [
							'phone' => ['type' => 'phone', 'required' => true],
							'code' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.settings' => [
						'class' => RestV1::class,
						'method' => 'putProfileSettings',
						'note' => 'Update current user app settings (partial update)',
						'auth_required' => true,
						'validation' => [
							'theme' => ['type' => 'string', 'required' => false],
							'notificationsOrders' => ['type' => 'string', 'required' => false],
							'notificationsPromotions' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.password' => [
						'class' => RestV1::class,
						'method' => 'putProfilePassword',
						'note' => 'Change current user password (2-step: send code → confirm with code)',
						'auth_required' => true,
						'validation' => [
							'password_new' => ['type' => 'string', 'required' => true],
							'password_new2' => ['type' => 'string', 'required' => true],
							'code' => ['type' => 'string', 'required' => false],
						],
					],
					'goods' => [
						'class' => RestV1APP::class,
						'method' => 'putGoods',
						'note' => 'Update goods item by id',
						'role_required' => [1, 2],
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'name' => ['type' => 'string', 'required' => false],
							'price' => ['type' => 'float2', 'required' => false],
						],
					],
					'orders.status' => [
						'class' => RestV1APP::class,
						'method' => 'putOrdersStatus',
						'note' => 'Update order status by id',
						'role_required' => [1, 2],
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'status' => ['type' => 'string', 'required' => true],
						],
					],
					'cart' => [
						'class' => RestV1APP::class,
						'method' => 'putCart',
						'note' => 'Update item quantity and activity in cart',
						'auth_required' => true,
						'validation' => [
							'id' => ['type' => 'string', 'required' => true],
							'quantity' => ['type' => 'int2', 'required' => true],
							'active' => ['type' => 'int', 'required' => false],
						],
					],
					'cart.city' => [
						'class' => RestV1APP::class,
						'method' => 'putCartCity',
						'note' => 'Set delivery city for cart (step 1). Returns available delivery methods.',
						'auth_required' => true,
						'validation' => [
							'citiesId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.delivery' => [
						'class' => RestV1APP::class,
						'method' => 'putCartDelivery',
						'note' => 'Set delivery method for cart (step 2). Returns available payment methods.',
						'auth_required' => true,
						'validation' => [
							'deliveryId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.payment' => [
						'class' => RestV1APP::class,
						'method' => 'putCartPayment',
						'note' => 'Set payment method for cart',
						'auth_required' => true,
						'validation' => [
							'paymentsId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.deliveryOperations' => [
						'class' => RestV1APP::class,
						'method' => 'putCartDeliveryOperations',
						'note' => 'Save selected pickup point or delivery address. Sends parameters like operations-id, operations-title, etc.',
						'auth_required' => true,
					],
				],
				'cli' => [],
			],
			'wepps' => [
				'get' => [
					'token' => [
						'class' => RestAd::class,
						'method' => 'getToken',
						'note' => 'Authenticate user and return JWT token',
						'log' => false,
						'query_validation' => [
							'login' => ['type' => 'string', 'required' => true],
							'password' => ['type' => 'string', 'required' => true]
						]
					],
					'list_items' => [
						'class' => RestAd::class,
						'method' => 'getListItems',
						'note' => 'Retrieve list of available items',
						'auth_required' => true,
						'custom_response' => true,
						'log' => false,
						'query_validation' => [
							'list' => ['type' => 'string', 'required' => true],
							'field' => ['type' => 'int2', 'required' => true],
							'search' => ['type' => 'string', 'required' => false],
							'page' => ['type' => 'int2', 'required' => false],
						]
					],
				],
				'post' => [],
				'delete' => [],
				'put' => [],
			],
			'm2m' => [
				'get' => [
					// ===== Users =====
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'getUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get list of users (configurable via s_Config)',
					],
					'users.item' => [
						'class' => RestV1M2M::class,
						'method' => 'getUsersItem',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get single user by id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					// ===== Goods =====
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoods',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get list of goods (configurable via s_Config)',
					],
					'goods.item' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsItem',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get single goods by id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'goods.categories' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsCategories',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get goods categories (navigators)',
					],
					'goods.filters' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsFilters',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get available goods filters (properties)',
						'query_validation' => [
							'category' => ['type' => 'int2', 'required' => false],
							'search' => ['type' => 'string', 'required' => false],
						],
					],
					'goods.stocks' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsStocks',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get goods warehouse stocks',
						'query_validation' => [
							'goods_id' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.prices' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsPrices',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get goods prices',
						'query_validation' => [
							'goods_id' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.images' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsImages',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get goods images (paginated)',
						'query_validation' => [
							'goods_id' => ['type' => 'int2', 'required' => false],
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.imagesVariations' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsImagesVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get goods variations images (paginated)',
						'query_validation' => [
							'goodsv_id' => ['type' => 'int2', 'required' => false],
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.variations' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get goods variations (paginated)',
						'query_validation' => [
							'goods_id' => ['type' => 'int2', 'required' => false],
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
						],
					],
					// ===== Orders =====
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'getOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get list of orders (configurable via s_Config)',
					],
					'orders.item' => [
						'class' => RestV1M2M::class,
						'method' => 'getOrdersItem',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get single order by id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'tasks.result' => [
						'class' => RestV1M2M::class,
						'method' => 'getTasksResult',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Get async task result by id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
				],
				'post' => [
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'postUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Create user(s). Supports single item (object) or batch (array, max 100). Returns 201 for single or 207 for batch with per-item status.',
					],
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoods',
						'role_required' => [1],
						'auth_required' => true,
						'async' => true,
						'note' => 'M2M: Create goods item(s). Supports single item (object) or batch (array, max 100). Returns 201 for single or 207 for batch with per-item status.',
						'validation' => [
							'name' => ['type' => 'string', 'required' => true],
							'alias' => ['type' => 'string', 'required' => true],
							'navigatorId' => ['type' => 'int2', 'required' => true],
							'price' => ['type' => 'float2', 'required' => true],
							'article' => ['type' => 'string', 'required' => false],
							'descr' => ['type' => 'string', 'required' => false],
							'isHidden' => ['type' => 'int2', 'required' => false],
							'priceBefore' => ['type' => 'float2', 'required' => false],
							'status' => ['type' => 'int2', 'required' => false],
							'metaTitle' => ['type' => 'string', 'required' => false],
							'metaDescription' => ['type' => 'string', 'required' => false],
							'metaKeyword' => ['type' => 'string', 'required' => false],
							'weightPack' => ['type' => 'float2', 'required' => false],
							'displayFirst' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.images' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsImages',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Add image to goods',
					],
					'goods.imagesVariations' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsImagesVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Add image to goods variation',
					],
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'postOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Create order(s). Supports single item (object) or batch (array, max 100). Returns 201 for single or 207 for batch with per-item status.',
					],
				],
				'delete' => [
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Delete user by id (configurable via s_Config). Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoods',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Delete goods by id(s). Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Delete order(s). Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.images' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsImages',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Delete goods image(s). Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.imagesVariations' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsImagesVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Delete goods variation image(s). Supports ?id=123 or batch via body {"ids": [123, 456, ...]}',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => false],
						],
						'validation' => [
							'ids' => ['type' => 'int2', 'required' => false],
						],
					],
				],
				'put' => [
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'putUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update user by id. ID can be passed as ?id={{id}} or in JSON body {"id": 123, ...}',
					],
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoods',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update goods by id. ID can be passed as ?id={{id}} or in JSON body {"id": 123, ...}',
						'validation' => [
							'id' => ['type' => 'int2', 'required' => true],
							'name' => ['type' => 'string', 'required' => false],
							'alias' => ['type' => 'string', 'required' => false],
							'price' => ['type' => 'float', 'required' => false],
							'article' => ['type' => 'string', 'required' => false],
							'descr' => ['type' => 'string', 'required' => false],
							'isHidden' => ['type' => 'int2', 'required' => false],
							'priceBefore' => ['type' => 'float', 'required' => false],
							'status' => ['type' => 'int2', 'required' => false],
							'metaTitle' => ['type' => 'string', 'required' => false],
							'metaDescription' => ['type' => 'string', 'required' => false],
							'metaKeyword' => ['type' => 'string', 'required' => false],
							'weightPack' => ['type' => 'float', 'required' => false],
							'displayFirst' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.stocks' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsStocks',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update goods warehouse stocks',
					],
					'goods.prices' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsPrices',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update goods prices',
					],
					'goods.images' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsImages',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update goods image by id',
					],
					'goods.imagesVariations' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsImagesVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update goods variation image by id',
					],
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'putOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: Update order by id. ID can be passed as ?id={{id}} or in JSON body {"id": 123, ...}',
					],
				],
				'patch' => [
					'goods.filters' => [
						'class' => RestV1M2M::class,
						'method' => 'patchGoodsFilters',
						'auth_required' => true,
						'note' => 'M2M: Sync all goods filters/properties (overwrite)',
					],
				],
			],
			'cli' => [
				'cli' => [
					'removeLogLocal' => [
						'class' => RestCli::class,
						'method' => 'removeLogLocal',
						'note' => 'Remove local log files',
					],
					'test' => [
						'class' => RestCli::class,
						'method' => 'cliTest',
						'note' => 'Execute CLI test operations',
					],
					'tasks.process' => [
						'class' => RestCli::class,
						'method' => 'tasksProcess',
						'note' => 'Process pending async tasks from s_Tasks queue',
					],
					'tasks.result' => [
						'class' => RestCli::class,
						'method' => 'tasksResult',
						'note' => 'Get task result by id from s_Tasks queue',
					],
				],
			],
		];
	}
}