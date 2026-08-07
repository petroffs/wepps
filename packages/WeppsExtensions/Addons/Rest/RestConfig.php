<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Utils;

/**
 * Конфигурация REST API методов.
 *
 * Единый реестр эндпоинтов платформы: используется для маршрутизации запросов
 * (класс Rest::routeRequest()), автодокументации и валидации данных.
 *
 * Структура конфигурации:
 * ```php
 * 'v1' => [                     // версия API
 *     'get' => [                // HTTP-метод: get | post | put | delete | cli
 *         'goods' => [          // имя эндпоинта (становится частью URL)
 *             'class'  => RestV1APP::class,  // класс-обработчик
 *             'method' => 'getGoods',        // метод обработчика
 *             'note'   => '...',             // описание для документации
 *             // опциональные ключи:
 *             'auth_required'   => true,     // требуется Bearer-токен
 *             'auth_optional'   => true,     // токен опционален (не блокирует запрос)
 *             'role_required'   => [1, 2],   // допустимые UserPermissions
 *             'validation'      => [...],    // правила валидации тела JSON
 *             'query_validation'=> [...],    // правила валидации GET-параметров
 *             'custom_response' => true,     // ответ без обёртки status/message/data
 *             'log'             => false,    // отключить логирование запроса
 *             'async'           => true,     // поставить в очередь s_Tasks (202 Accepted)
 *         ],
 *     ],
 * ],
 * ```
 *
 * Правила валидации (validation / query_validation):
 * - Простые поля: `'name' => ['type' => 'string', 'required' => true]`
 * - Вложенные (dot-notation): `'items[].name'` или `'data.items[].name'`
 *   валидируют поле в каждом элементе вложенного массива
 * - Типы: int, int2, float, float2, string, email, date, phone, guid, barcode, object
 *   (массивы типов — с суффиксом `[]`, например `'int[]'`)
 *
 * Особенности M2M (версия 'm2m'):
 * - POST поддерживает одиночный объект или batch (массив, макс. 100):
 *   ответ 201 для одиночного, 207 Multi-Status для batch с per-item статусами
 * - PUT: ID передаётся в теле JSON `{"id": 123, ...}` (в query-строке не поддерживается)
 * - DELETE: batch-формат тела `{"data": [123, 456, ...]}`
 * - Для get/put/delete отсутствующие правила валидации достраиваются
 *   автоматически методом inheritEndpointConfig()
 */
class RestConfig
{
	/**
	 * Получить полную конфигурацию всех REST-эндпоинтов.
	 *
	 * Собирает конфиги по версиям API (v0, v1, wepps, m2m, cli) и возвращает
	 * итоговый массив. Для версии 'm2m' дополнительно достраивает правила
	 * валидации через inheritEndpointConfig().
	 *
	 * @return array Конфигурация вида [версия][HTTP-метод][эндпоинт] => настройки
	 */
	public static function getConfig(): array
	{
		$config = [
			'v0' => [
				'get' => [
					'test' => [
						'class' => RestAd::class,
						'method' => 'getTest',
						'note' => 'Получение тестовых данных с опциональной фильтрацией',
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
						'note' => 'Создание или обновление тестовых данных с валидацией',
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
						'note' => 'Удаление тестовых данных. Поддерживает ?id=123 или batch через тело {"ids": [123, 456, ...]}',
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
						'note' => 'Обновление существующих тестовых данных',
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
						'note' => 'Удаление локальных файлов логов и очистка таблицы s_Tasks',
					],
					'test' => [
						'class' => RestCli::class,
						'method' => 'cliTest',
						'note' => 'Выполнение тестовых операций CLI',
					],
					'tasks.process' => [
						'class' => RestCli::class,
						'method' => 'tasksProcess',
						'note' => 'Обработка отложенных async-задач из очереди s_Tasks',
					],
					'tasks.result' => [
						'class' => RestCli::class,
						'method' => 'tasksResult',
						'note' => 'Получение результата задачи по id из очереди s_Tasks',
					],
				],
			],
			'v1' => [
				'get' => [
					'home' => [
						'class' => RestV1APP::class,
						'method' => 'getHome',
						'note' => 'Агрегированные данные главного экрана: слайды, категории, новости, товары, активный заказ (если авторизован)',
						'auth_optional' => true,
					],
					'profile' => [
						'class' => RestV1::class,
						'method' => 'getProfile',
						'note' => 'Профиль текущего пользователя: персональные данные, контакты',
						'auth_required' => true,
					],
					'profile.settings' => [
						'class' => RestV1::class,
						'method' => 'getProfileSettings',
						'note' => 'Настройки приложения текущего пользователя (тема, уведомления)',
						'auth_required' => true,
					],
					'goods' => [
						'class' => RestV1APP::class,
						'method' => 'getGoods',
						'note' => 'Список товаров с фильтрацией и пагинацией',
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
						'note' => 'Получение товара по id',
						'query_validation' => [
							'id' => ['type' => 'string', 'required' => true],
						],
					],
					'goods.categories' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsCategories',
						'note' => 'Список категорий товаров с ParentId для построения дерева',
					],
					'goods.favorites' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsFavorites',
						'note' => 'Избранные товары текущего пользователя',
						'auth_required' => true,
					],
					'goods.filters' => [
						'class' => RestV1APP::class,
						'method' => 'getGoodsFilters',
						'note' => 'Доступные свойства-фильтры для списка товаров',
						'query_validation' => [
							'category' => ['type' => 'int2', 'required' => false],
							'search' => ['type' => 'string', 'required' => false],
						],
					],
					'orders' => [
						'class' => RestV1APP::class,
						'method' => 'getOrders',
						'note' => 'Список заказов текущего пользователя',
						'auth_required' => true,
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
						],
					],
					'orders.item' => [
						'class' => RestV1APP::class,
						'method' => 'getOrdersItem',
						'note' => 'Получение заказа по id',
						'auth_required' => true,
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'orders.messages' => [
						'class' => RestV1APP::class,
						'method' => 'getOrdersMessages',
						'note' => 'Сообщения по заказу (по id)',
						'auth_required' => true,
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'news' => [
						'class' => RestV1APP::class,
						'method' => 'getNews',
						'note' => 'Список новостей с пагинацией',
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
							'search' => ['type' => 'string', 'required' => false],
						],
					],
					'news.item' => [
						'class' => RestV1APP::class,
						'method' => 'getNewsItem',
						'note' => 'Получение новости по id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'slides' => [
						'class' => RestV1APP::class,
						'method' => 'getSlides',
						'note' => 'Список активных слайдов',
					],
					'cart' => [
						'class' => RestV1APP::class,
						'method' => 'getCart',
						'note' => 'Корзина текущего пользователя с позициями и итогами',
						'auth_required' => true,
					],
					'cart.checkout' => [
						'class' => RestV1APP::class,
						'method' => 'getCartCheckout',
						'note' => 'Доступные способы доставки и оплаты для текущей корзины',
						'auth_required' => true,
					],
					'cart.city' => [
						'class' => RestV1APP::class,
						'method' => 'getCartCity',
						'note' => 'Поиск городов по строке запроса (?q=...)',
						'auth_required' => true,
						'query_validation' => [
							'q' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.delivery' => [
						'class' => RestV1APP::class,
						'method' => 'getCartDelivery',
						'note' => 'Доступные способы доставки для города (?citiesId=...)',
						'auth_required' => true,
						'query_validation' => [
							'citiesId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.metrics' => [
						'class' => RestV1APP::class,
						'method' => 'getCartMetrics',
						'note' => 'Количество позиций корзины и их id (работает для анонимных и авторизованных)',
						'auth_optional' => true,
					],
				],
				'post' => [
					'auth.login' => [
						'class' => RestV1::class,
						'method' => 'postAuthLogin',
						'note' => 'Аутентификация пользователя и выдача JWT-токенов (access + refresh)',
						'log' => false,
						'validation' => [
							'login' => ['type' => 'email', 'required' => true],
							'password' => ['type' => 'string', 'required' => true],
						],
					],
					'auth.logout' => [
						'class' => RestV1::class,
						'method' => 'postAuthLogout',
						'note' => 'Завершение сессии текущего пользователя (клиент должен удалить оба токена из локального хранилища)',
						'auth_required' => true,
						'log' => false,
					],
					'auth.refresh' => [
						'class' => RestV1::class,
						'method' => 'postAuthRefresh',
						'note' => 'Обновление access-токена по refresh-токену',
						'log' => false,
						'validation' => [
							'refresh_token' => ['type' => 'string', 'required' => true],
						],
					],
					'auth.confirm' => [
						'class' => RestV1::class,
						'method' => 'postAuthConfirm',
						'note' => 'Подтверждение входа по confirm_token из письма (режим CONFIRM_AUTH)',
						'log' => false,
						'validation' => [
							'token' => ['type' => 'string', 'required' => true],
							'code' => ['type' => 'int2', 'required' => false],
						],
					],
					'register.confirm' => [
						'class' => RestV1::class,
						'method' => 'postRegisterConfirm',
						'note' => 'Завершение регистрации по токену из письма. Возвращает access+refresh токены',
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
						'note' => 'Инициация регистрации: валидация данных и отправка письма с подтверждением',
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
						'note' => 'Запрос восстановления пароля: отправка ссылки сброса на email',
						'validation' => [
							'login' => ['type' => 'email', 'required' => true],
						],
					],
					'goods' => [
						'class' => RestV1APP::class,
						'method' => 'postGoods',
						'note' => 'Создание товара(ов). Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
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
						'note' => 'Добавление товара в корзину (или обновление количества, если уже есть)',
						'auth_required' => true,
						'validation' => [
							'id' => ['type' => 'string', 'required' => true],
							'quantity' => ['type' => 'int2', 'required' => false],
						],
					],
					'cart.placeOrder' => [
						'class' => RestV1APP::class,
						'method' => 'postCartPlaceOrder',
						'note' => 'Оформление заказа из текущей корзины (контактные данные берутся из профиля)',
						'auth_required' => true,
					],
					'orders.messages' => [
						'class' => RestV1APP::class,
						'method' => 'postOrdersMessages',
						'note' => 'Добавление сообщения к заказу',
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
						'note' => 'Удаление аккаунта текущего пользователя (2 шага: слово «УДАЛИТЬ» → код подтверждения)',
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
						'note' => 'Удаление товара по id или batch. Поддерживает ?id=123 или batch через тело {"ids": [123, 456, ...]}',
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
						'note' => 'Удаление позиции(й) из корзины. Поддерживает ?id=item_id или batch через тело {"ids": ["item_1", "item_2", ...]}',
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
						'note' => 'Отмена заказа(ов). Поддерживает ?id=123 или batch через тело {"ids": [123, 456, ...]}',
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
						'note' => 'Обновление ФИО и адреса текущего пользователя. Email и телефон меняются через отдельные эндпоинты с подтверждением',
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
						'note' => 'Смена email (2 шага). Шаг 1: {email} → получение кода подтверждения. Шаг 2: {email, code} → подтверждение смены',
						'auth_required' => true,
						'validation' => [
							'email' => ['type' => 'email', 'required' => true],
							'code' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.phone' => [
						'class' => RestV1::class,
						'method' => 'putProfilePhone',
						'note' => 'Смена телефона (2 шага). Шаг 1: {phone} → получение кода на email. Шаг 2: {phone, code} → подтверждение смены',
						'auth_required' => true,
						'validation' => [
							'phone' => ['type' => 'phone', 'required' => true],
							'code' => ['type' => 'string', 'required' => false],
						],
					],
					'profile.settings' => [
						'class' => RestV1::class,
						'method' => 'putProfileSettings',
						'note' => 'Обновление настроек приложения текущего пользователя (частичное обновление)',
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
						'note' => 'Смена пароля текущего пользователя (2 шага: отправка кода → подтверждение кодом)',
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
						'note' => 'Обновление товара по id',
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
						'note' => 'Обновление статуса заказа по id',
						'role_required' => [1, 2],
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'status' => ['type' => 'string', 'required' => true],
						],
					],
					'cart' => [
						'class' => RestV1APP::class,
						'method' => 'putCart',
						'note' => 'Обновление количества и активности товара в корзине',
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
						'note' => 'Установка города доставки для корзины (шаг 1). Возвращает доступные способы доставки',
						'auth_required' => true,
						'validation' => [
							'citiesId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.delivery' => [
						'class' => RestV1APP::class,
						'method' => 'putCartDelivery',
						'note' => 'Установка способа доставки для корзины (шаг 2). Возвращает доступные способы оплаты',
						'auth_required' => true,
						'validation' => [
							'deliveryId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.payment' => [
						'class' => RestV1APP::class,
						'method' => 'putCartPayment',
						'note' => 'Установка способа оплаты для корзины',
						'auth_required' => true,
						'validation' => [
							'paymentsId' => ['type' => 'string', 'required' => true],
						],
					],
					'cart.deliveryOperations' => [
						'class' => RestV1APP::class,
						'method' => 'putCartDeliveryOperations',
						'note' => 'Сохранение выбранного ПВЗ или адреса доставки. Передаёт параметры вида operations-id, operations-title и т.д.',
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
						'note' => 'Аутентификация пользователя и выдача JWT-токена (для админки)',
						'log' => false,
						'query_validation' => [
							'login' => ['type' => 'string', 'required' => true],
							'password' => ['type' => 'string', 'required' => true]
						]
					],
					'list_items' => [
						'class' => RestAd::class,
						'method' => 'getListItems',
						'note' => 'Получение списка доступных элементов списка (для полей админки)',
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
					'tasks.result' => [
						'class' => RestV1M2M::class,
						'method' => 'getTasksResult',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: получение результата async-задачи по id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					// ===== Users =====
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'getUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: список пользователей (конфигурируется через s_Config)',
					],
					'users.item' => [
						'class' => RestV1M2M::class,
						'method' => 'getUsersItem',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: пользователь по id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					// ===== Orders =====
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'getOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: список заказов (конфигурируется через s_Config)',
					],
					'orders.item' => [
						'class' => RestV1M2M::class,
						'method' => 'getOrdersItem',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: заказ по id',
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
						'note' => 'M2M: список товаров (конфигурируется через s_Config)',
					],
					'goods.item' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsItem',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: товар по id',
						'query_validation' => [
							'id' => ['type' => 'int2', 'required' => true],
						],
					],
					'goods.navigator' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsNavigator',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: категории товаров (разделы навигатора)',
					],
					'goods.statuses' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsStatuses',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: статусы товаров',
					],
					'goods.attributes' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsAttributes',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: свойства (атрибуты) товаров',
					],
					'goods.attributesGroups' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsAttributesGroups',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: группы свойств товаров',
					],
					'goods.attributesValues' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsAttributesValues',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: значения свойств товаров',
						'query_validation' => [
							'list' => ['type' => 'string', 'required' => false],
							'listId' => ['type' => 'int2', 'required' => false],
							'listField' => ['type' => 'string', 'required' => false],
							'page' => ['type' => 'string', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.variations' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: вариации товаров (sku, цены, остатки)',
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
							'goodsId' => ['type' => 'int2', 'required' => false],
						],
					],
					'goods.stocks' => [
						'class' => RestV1M2M::class,
						'method' => 'getGoodsStocks',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: остатки товаров (sku, количества)',
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
							'goodsId' => ['type' => 'int2', 'required' => false],
						],
					],
					'files' => [
						'class' => RestV1M2M::class,
						'method' => 'getFiles',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: файлы (с пагинацией)',
						'query_validation' => [
							'page' => ['type' => 'int2', 'required' => false],
							'limit' => ['type' => 'int2', 'required' => false],
							'list' => ['type' => 'string', 'required' => false],
							'listField' => ['type' => 'string', 'required' => false],
							'listId' => ['type' => 'int2', 'required' => false],
							'description' => ['type' => 'string', 'required' => false],
							'filter' => ['type' => 'string', 'required' => false],
						]
					]
				],
				'post' => [
					// ===== Users =====
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'postUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: создание пользователя(ей). Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'name' => ['type' => 'string', 'required' => true],
							'nameFirst' => ['type' => 'string', 'required' => true],
							'nameSurname' => ['type' => 'string', 'required' => true],
							'namePatronymic' => ['type' => 'string', 'required' => false],
							'login' => ['type' => 'string', 'required' => true],
							'email' => ['type' => 'string', 'required' => true],
							'phone' => ['type' => 'string', 'required' => true]
						],
					],
					// ===== Orders =====
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'postOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: создание заказа(ов). Batch (массив, макс. 100). Возвращает 207 с per-item статусом. Поддерживает вложенные data.items[] для позиций заказа',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'name' => ['type' => 'string', 'required' => true],
							'isHidden' => ['type' => 'int', 'required' => false],
							'userId' => ['type' => 'int', 'required' => true],
							'phone' => ['type' => 'string', 'required' => true],
							'email' => ['type' => 'string', 'required' => true],
							'status' => ['type' => 'string', 'required' => true],
							'sum' => ['type' => 'float2', 'required' => true],
							'date' => ['type' => 'date', 'required' => true],
							'delivery' => ['type' => 'int2', 'required' => true],
							'payment' => ['type' => 'int2', 'required' => true],
							'postalCode' => ['type' => 'string', 'required' => false],
							'address' => ['type' => 'string', 'required' => false],
							'city' => ['type' => 'string', 'required' => false],
							'region' => ['type' => 'string', 'required' => false],
							'country' => ['type' => 'string', 'required' => false],
							// Вложенная валидация: data.items[].field (dot-notation, nested)
							'data' => ['type' => 'object', 'required' => true],
							'data.items[]' => ['type' => 'object[]', 'required' => true],
							'data.items[].id' => ['type' => 'int', 'required' => true],
							'data.items[].idv' => ['type' => 'int', 'required' => true],
							'data.items[].name' => ['type' => 'string', 'required' => true],
							'data.items[].quantity' => ['type' => 'int', 'required' => true],
							'data.items[].stocks' => ['type' => 'int', 'required' => false],
							'data.items[].active' => ['type' => 'int', 'required' => false],
							'data.items[].price' => ['type' => 'float2', 'required' => false],
							'data.items[].sum' => ['type' => 'float2', 'required' => true],
							'data.items[].priceBefore' => ['type' => 'float2', 'required' => false],
							'data.items[].sumBefore' => ['type' => 'float2', 'required' => false],
							'data.items[].sumBeforeTotal' => ['type' => 'float2', 'required' => false],
							'data.items[].sumSaving' => ['type' => 'float2', 'required' => false],
							'data.items[].quantityActive' => ['type' => 'int', 'required' => false],
							'data.items[].sumActive' => ['type' => 'float2', 'required' => false],
							'data.items[].url' => ['type' => 'string', 'required' => false],
							'data.items[].image' => ['type' => 'string', 'required' => false],
						],
					],
					// ===== Goods =====
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoods',
						'role_required' => [1],
						'auth_required' => true,
						'async' => true,
						'note' => 'M2M: создание товара(ов). Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
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
					'goods.navigator' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsNavigator',
						'role_required' => [1],
						'auth_required' => true,
						'async' => false, //выполнять немедленно
						'note' => 'M2M: создание категории(й) каталога. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'name' => ['type' => 'string', 'required' => true],
							'url' => ['type' => 'string', 'required' => true],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.statuses' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsStatuses',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: создание статуса(ов) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'name' => ['type' => 'string', 'required' => true],
							'alias' => ['type' => 'string', 'required' => true],
							'priority' => ['type' => 'int', 'required' => false],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.attributes' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsAttributes',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: создание свойства(й) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'name' => ['type' => 'string', 'required' => true],
							'alias' => ['type' => 'string', 'required' => true],
							'group' => ['type' => 'int', 'required' => false],
							'priority' => ['type' => 'int', 'required' => false],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.attributesValues' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsAttributesValues',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: создание значения(й) свойств товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'w_guid' => ['type' => 'guid', 'required' => false],
							'attributesId' => ['type' => 'int', 'required' => true],
							'alias' => ['type' => 'string', 'required' => true],
							'list' => ['type' => 'string', 'required' => true],
							'listId' => ['type' => 'int', 'required' => true],
							'listField' => ['type' => 'string', 'required' => true],
							'value' => ['type' => 'string', 'required' => true],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.variations' => [
						'class' => RestV1M2M::class,
						'method' => 'postGoodsVariations',
						'role_required' => [1],
						'auth_required' => true,
						'async' => false,
						// ! сделать асинхронным (т.е. через s_Tasks, после тестирования), т.к. может быть много вариаций и долго обрабатываться
						// 'async' => true,
						'note' => 'M2M: создание вариации(й) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							//'name' => ['type' => 'string', 'required' => true],
							'goodsId' => ['type' => 'int', 'required' => true],
							'guid' => ['type' => 'guid', 'required' => true],
							'color' => ['type' => 'string', 'required' => false],
							'size' => ['type' => 'string', 'required' => false],
							'sku' => ['type' => 'string', 'required' => true],
							'priority' => ['type' => 'int', 'required' => false],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'files' => [
						'class' => RestV1M2M::class,
						'method' => 'postFiles',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: создание файлов. Поддерживает одиночный объект или batch (массив, макс. 100). Загрузка через base64 или url. Возвращает 201 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'guid' => ['type' => 'guid', 'required' => true],
							'name' => ['type' => 'string', 'required' => true],
							'list' => ['type' => 'string', 'required' => true],
							'listField' => ['type' => 'string', 'required' => true],
							'listId' => ['type' => 'int', 'required' => true],
							'description' => ['type' => 'string', 'required' => false],
							'filter' => ['type' => 'string', 'required' => false],
							'url' => ['type' => 'string', 'required' => false],
							'base64' => ['type' => 'string', 'required' => false],
						]
					]
				],
				'put' => [
					// ===== Users =====
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'putUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление пользователя по id. ID передаётся в теле JSON {"id": 123, ...}',
						// validation - необязательный в put, inheritance от post, но можно переопределить.
						// при этом id - ставится обязательным, т.к. без него не понятно что обновлять. 
						// Остальные поля - необязательные, т.к. put - частичное обновление.
						// 'validation' => [
						// 	'id' => ['type' => 'int', 'required' => true],
						// ]
					],
					// ===== Orders =====
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'putOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление заказа по id. ID можно передавать в теле JSON {"id": 123, ...}',
					],
					// ===== Goods =====
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoods',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление товара по id. ID можно передавать в теле JSON {"id": 123, ...}',
					],
					'goods.navigator' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsNavigator',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление категории каталога по id. ID можно передавать в теле JSON {"id": 123, ...}',
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'parentId' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.statuses' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsStatuses',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление статуса товаров по id. ID можно передавать в теле JSON {"id": 123, ...}',
					],
					'goods.attributes' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsAttributes',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление свойства товаров по id. ID можно передавать в теле JSON {"id": 123, ...}',
					],
					'goods.attributesValues' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsAttributesValues',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление значения свойства товаров по id. ID можно передавать в теле JSON {"id": 123, ...}',
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'guid' => ['type' => 'guid', 'required' => false],
							'attributesId' => ['type' => 'int', 'required' => true],
							'alias' => ['type' => 'string', 'required' => true],
							'list' => ['type' => 'string', 'required' => true],
							'listId' => ['type' => 'int', 'required' => true],
							'listField' => ['type' => 'string', 'required' => true],
							'value' => ['type' => 'string', 'required' => true],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.variations' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsVariations',
						'role_required' => [1],
						'auth_required' => true,
						'async' => false,
						// ! сделать асинхронным (после тестирования), т.к. может быть много вариаций и долго обрабатываться
						// 'async' => true,
						'note' => 'M2M: обновление вариации(й) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 200 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'goodsId' => ['type' => 'int', 'required' => false],
							'guid' => ['type' => 'guid', 'required' => false],
							'color' => ['type' => 'string', 'required' => true],
							'size' => ['type' => 'string', 'required' => true],
							'sku' => ['type' => 'string', 'required' => true],
							'priority' => ['type' => 'int', 'required' => false],
							'isHidden' => ['type' => 'int', 'required' => false],
						],
					],
					'goods.stocks' => [
						'class' => RestV1M2M::class,
						'method' => 'putGoodsStocks',
						'role_required' => [1],
						'auth_required' => true,
						'async' => false,
						'note' => 'M2M: обновление остатков товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 200 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'stocks' => ['type' => 'int', 'required' => true],
						],
					],
					'files' => [
						'class' => RestV1M2M::class,
						'method' => 'putFiles',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: обновление файлов. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 200 для одиночного или 207 для batch с per-item статусом',
						'validation' => [
							'id' => ['type' => 'int', 'required' => true],
							'guid' => ['type' => 'guid', 'required' => false],
							'name' => ['type' => 'string', 'required' => false],
							'list' => ['type' => 'string', 'required' => false],
							'listField' => ['type' => 'string', 'required' => false],
							'listId' => ['type' => 'int', 'required' => false],
							'description' => ['type' => 'string', 'required' => false],
							'filter' => ['type' => 'string', 'required' => false],
							'priority' => ['type' => 'int', 'required' => false],
						]
					]
				],
				'delete' => [
					// ===== Users =====
					'users' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteUsers',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление пользователя(ей) по id. Формат тела: {"data": [123, 456, ...]}'
						// validation - необязательный в delete, т.к. генерируется автоматически 
						// в inheritEndpointConfig() и требует массив id для удаления.
					],
					// ===== Orders =====
					'orders' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteOrders',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление заказа(ов) по id. Формат тела: {"data": [123, 456, ...]}'
					],
					// ===== Goods =====
					'goods' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoods',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление товара(ов) по id. Формат тела: {"data": [123, 456, ...]}'
					],
					'goods.navigator' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsNavigator',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление категории(й) каталога по id. Формат тела: {"data": [123, 456, ...]}'
					],
					'goods.statuses' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsStatuses',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление статуса(ов) товаров по id. Формат тела: {"data": [123, 456, ...]}'
					],
					'goods.attributes' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsAttributes',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление свойства(й) товаров по id. Формат тела: {"data": [123, 456, ...]}'
					],
					'goods.attributesValues' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsAttributesValues',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление значения(й) свойств товаров по id. Формат тела: {"data": [123, 456, ...]}'
					],
					'goods.variations' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteGoodsVariations',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление вариации(й) товаров по id. Формат тела: {"data": [123, 456, ...]}'
					],
					'files' => [
						'class' => RestV1M2M::class,
						'method' => 'deleteFiles',
						'role_required' => [1],
						'auth_required' => true,
						'note' => 'M2M: удаление файлов по id. Формат тела: {"data": [123, 456, ...]}'
					]
				]
			],
			'cli' => [
				'cli' => [
					'removeLogLocal' => [
						'class' => RestCli::class,
						'method' => 'removeLogLocal',
						'note' => 'Удаление локальных файлов логов и очистка таблицы s_Tasks',
					],
					'test' => [
						'class' => RestCli::class,
						'method' => 'cliTest',
						'note' => 'Выполнение тестовых операций CLI',
					],
					'tasks.process' => [
						'class' => RestCli::class,
						'method' => 'tasksProcess',
						'note' => 'Обработка отложенных async-задач из очереди s_Tasks',
					],
					'tasks.result' => [
						'class' => RestCli::class,
						'method' => 'tasksResult',
						'note' => 'Получение результата задачи по id из очереди s_Tasks',
					],
				],
			],
		];

		$config['m2m'] = self::inheritEndpointConfig($config['m2m']);
		return $config;
	}

	/**
	 * Достроить правила валидации для M2M-эндпоинтов, у которых они не заданы явно.
	 *
	 * Для каждого типа запроса применяются правила «по умолчанию»:
	 * - GET: query_validation = {page, limit} (если не задано явно)
	 * - PUT: validation = {id: int, required} + поля из POST-валидации как необязательные
	 *   (частичное обновление; поля POST наследуются и помечаются required=false)
	 * - DELETE: validation = {ARRAY: int[], required} — batch-удаление по массиву id
	 *
	 * @param array $baseConfig Конфигурация версии 'm2m' ([get][post][put][delete])
	 * @param array $overrides  Зарезервировано для точечных переопределений (в текущей версии не используется)
	 * @return array Конфигурация с достроенными правилами валидации
	 */
	private static function inheritEndpointConfig(array $baseConfig, array $overrides = []): array
	{
		foreach ($baseConfig['get'] ?? [] as $key => $value) {
			$getValidation = $value['query_validation'] ?? [
				'page' => ['type' => 'int2', 'required' => false],
				'limit' => ['type' => 'int2', 'required' => false],
			];
			$baseConfig['get'][$key]['query_validation'] = $getValidation;
		}
		foreach ($baseConfig['put'] ?? [] as $key => $value) {
			$putValidation = $value['validation'] ?? [
				'id' => ['type' => 'int', 'required' => true],
			];
			$postValidation = $baseConfig['post'][$key]['validation'] ?? [];
			if (!empty($postValidation)) {
				foreach ($postValidation as $field => $rules) {
					$putValidation[$field] = $rules;
					$putValidation[$field]['required'] = false;
				}
			}
			$baseConfig['put'][$key]['validation'] = $putValidation;
		}
		foreach ($baseConfig['delete'] ?? [] as $key => $value) {
			$deleteValidation = $value['validation'] ?? [
				'ARRAY' => ['type' => 'int', 'required' => true],
			];
			$baseConfig['delete'][$key]['validation'] = $deleteValidation;
		}
		return $baseConfig;
	}
}