<?php
namespace WeppsExtensions\Addons\Rest;

/**
 * Конфигурация REST API методов
 * Используется для маршрутизации и автодокументации
 * 
 * Примеры JSON для v1.post.test и v1.put.test:
 * - Объект: {"type": "test", "data": {"id": 1, "title": "test 1", "date": "2023-10-01", "email": "test@example.com", "phone": "1234567890", "guid": "550e8400-e29b-41d4-a716-446655440000", "barcode": "1234567890128"}}
 * - Массив:  {"type": "test", "data": [{"id": 1, "title": "test 1", "date": "2023-10-01", "email": "test@example.com", "phone": "1234567890", "guid": "550e8400-e29b-41d4-a716-446655440000", "barcode": "1234567890128"}]}
 */
class RestConfig
{
    public static function getConfig(): array
    {
        return [
            'v1' => [
                'post' => [
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'setTest',
                        'note' => 'POST request processed',
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
                'get' => [
                    'getList' => [
                        'class' => RestLists::class,
                        'method' => 'getLists',
                        'note' => 'List retrieved successfully',
                    ],
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'getTest',
                        'note' => 'GET request processed',
                    ],
                ],
                'delete' => [
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'removeTest',
                        'note' => 'DELETE request processed',
                    ],
                ],
                'put' => [
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'setTest',
                        'note' => 'PUT request processed',
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
                        'note' => 'Local log removed',
                    ],
                    'test' => [
                        'class' => RestCli::class,
                        'method' => 'cliTest',
                        'note' => 'CLI test executed',
                    ],
                ],
            ],
            'v2' => [
                'post' => [
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'setTest',
                        'note' => 'POST request processed',
                    ],
                ],
                'get' => [
                    'getList' => [
                        'class' => RestLists::class,
                        'method' => 'getLists',
                        'note' => 'List retrieved successfully',
                    ],
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'getTest',
                        'note' => 'GET request processed',
                    ],
                ],
                'delete' => [
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'removeTest',
                        'note' => 'DELETE request processed',
                    ],
                ],
                'put' => [
                    'test' => [
                        'class' => RestLists::class,
                        'method' => 'setTest',
                        'note' => 'PUT request processed',
                    ],
                ],
            ],
            'cli' => [
                'cli' => [
                    'removeLogLocal' => [
                        'class' => RestCli::class,
                        'method' => 'removeLogLocal',
                        'note' => 'Local log removed',
                    ],
                    'test' => [
                        'class' => RestCli::class,
                        'method' => 'cliTest',
                        'note' => 'CLI test executed',
                    ],
                ],
            ],
        ];
    }
}