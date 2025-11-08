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
            'v1' => [
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
                        'auth_required' => true,
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
            'wepps' => [
                'get' => [
                    'token' => [
                        'class' => RestAd::class,
                        'method' => 'getToken',
                        'note' => 'Authenticate user and return JWT token',
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