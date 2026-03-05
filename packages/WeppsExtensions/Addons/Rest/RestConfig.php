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
                        'note' => 'Remove test data by ID',
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
                ],
            ],
            'v1' => [
                'get' => [
                    'profile' => [
                        'class' => RestV1::class,
                        'method' => 'getProfile',
                        'note' => 'Get current user profile',
                        'auth_required' => true,
                    ],
                    'goods' => [
                        'class' => RestV1::class,
                        'method' => 'getGoods',
                        'note' => 'Get list of goods with filtering and pagination',
                        'query_validation' => [
                            'page' => ['type' => 'int2', 'required' => false],
                            'limit' => ['type' => 'int2', 'required' => false],
                            'sort' => ['type' => 'string', 'required' => false],
                            'order' => ['type' => 'string', 'required' => false],
                            'search' => ['type' => 'string', 'required' => false],
                            'category' => ['type' => 'int2', 'required' => false],
                        ],
                    ],
                    'goods.item' => [
                        'class' => RestV1::class,
                        'method' => 'getGoodsItem',
                        'note' => 'Get single goods item by id',
                        'query_validation' => [
                            'id' => ['type' => 'int2', 'required' => true],
                        ],
                    ],
                    'goods.categories' => [
                        'class' => RestV1::class,
                        'method' => 'getGoodsCategories',
                        'note' => 'Get list of goods categories',
                    ],
                    'orders' => [
                        'class' => RestV1::class,
                        'method' => 'getOrders',
                        'note' => 'Get list of current user orders',
                        'auth_required' => true,
                        'query_validation' => [
                            'page' => ['type' => 'int2', 'required' => false],
                            'limit' => ['type' => 'int2', 'required' => false],
                        ],
                    ],
                    'orders.item' => [
                        'class' => RestV1::class,
                        'method' => 'getOrdersItem',
                        'note' => 'Get single order by id',
                        'auth_required' => true,
                        'query_validation' => [
                            'id' => ['type' => 'int2', 'required' => true],
                        ],
                    ],
                    'news' => [
                        'class' => RestV1::class,
                        'method' => 'getNews',
                        'note' => 'Get list of news with pagination',
                        'query_validation' => [
                            'page' => ['type' => 'int2', 'required' => false],
                            'limit' => ['type' => 'int2', 'required' => false],
                            'search' => ['type' => 'string', 'required' => false],
                        ],
                    ],
                    'news.item' => [
                        'class' => RestV1::class,
                        'method' => 'getNewsItem',
                        'note' => 'Get single news item by id',
                        'query_validation' => [
                            'id' => ['type' => 'int2', 'required' => true],
                        ],
                    ],
                    'slides' => [
                        'class' => RestV1::class,
                        'method' => 'getSlides',
                        'note' => 'Get list of active slides',
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
                    'profile' => [
                        'class' => RestV1::class,
                        'method' => 'postProfile',
                        'note' => 'Register new user account',
                        'validation' => [
                            'login' => ['type' => 'email', 'required' => true],
                            'password' => ['type' => 'string', 'required' => true],
                            'name' => ['type' => 'string', 'required' => false],
                            'phone' => ['type' => 'phone', 'required' => false],
                        ],
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
                            'code'  => ['type' => 'int2',   'required' => false],
                        ],
                    ],
                    'goods' => [
                        'class' => RestV1::class,
                        'method' => 'postGoods',
                        'note' => 'Create a new goods item',
                        'role_required' => [1, 2],
                        'validation' => [
                            'name' => ['type' => 'string', 'required' => true],
                            'price' => ['type' => 'float2', 'required' => false],
                            'category' => ['type' => 'int2', 'required' => false],
                        ],
                    ],
                    'orders' => [
                        'class' => RestV1::class,
                        'method' => 'postOrders',
                        'note' => 'Create a new order',
                        'auth_required' => true,
                        'validation' => [
                            'name' => ['type' => 'string', 'required' => true],
                            'phone' => ['type' => 'phone', 'required' => true],
                            'email' => ['type' => 'email', 'required' => false],
                            'positions' => ['type' => 'string', 'required' => true],
                        ],
                    ],
                ],
                'delete' => [
                    'profile' => [
                        'class' => RestV1::class,
                        'method' => 'deleteProfile',
                        'note' => 'Delete current user account',
                        'auth_required' => true,
                    ],
                    'goods' => [
                        'class' => RestV1::class,
                        'method' => 'deleteGoods',
                        'note' => 'Delete goods item by id',
                        'role_required' => [1, 2],
                        'query_validation' => [
                            'id' => ['type' => 'int2', 'required' => true],
                        ],
                    ],
                    'orders' => [
                        'class' => RestV1::class,
                        'method' => 'deleteOrders',
                        'note' => 'Cancel order by id',
                        'auth_required' => true,
                        'query_validation' => [
                            'id' => ['type' => 'int2', 'required' => true],
                        ],
                    ],
                ],
                'put' => [
                    'profile' => [
                        'class' => RestV1::class,
                        'method' => 'putProfile',
                        'note' => 'Update current user profile',
                        'auth_required' => true,
                        'validation' => [
                            'name' => ['type' => 'string', 'required' => false],
                            'phone' => ['type' => 'phone', 'required' => false],
                            'email' => ['type' => 'email', 'required' => false],
                        ],
                    ],
                    'profile.password' => [
                        'class' => RestV1::class,
                        'method' => 'putProfilePassword',
                        'note' => 'Change current user password',
                        'auth_required' => true,
                        'validation' => [
                            'password_old' => ['type' => 'string', 'required' => true],
                            'password_new' => ['type' => 'string', 'required' => true],
                        ],
                    ],
                    'goods' => [
                        'class' => RestV1::class,
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
                        'class' => RestV1::class,
                        'method' => 'putOrdersStatus',
                        'note' => 'Update order status by id',
                        'role_required' => [1, 2],
                        'validation' => [
                            'id' => ['type' => 'int', 'required' => true],
                            'status' => ['type' => 'string', 'required' => true],
                        ],
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
                ],
            ],
        ];
    }
}