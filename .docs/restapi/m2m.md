# Wepps M2M REST API

- **Базовый URL:** `https://platform.wepps`
- **Сгенерировано:** 2026-10-17 23:58
- **Обёртка ответа:** `{"status": 200, "message": "OK", "data": ...}`
- **HTML-версия (Swagger UI):** [`m2m.html`](./m2m.html)

M2M API — машинный CRUD-доступ к таблицам платформы (пользователи, заказы,
товары, файлы). Доступ только для учётных записей с `UserPermissions = 1`.

Особенности:

- **Авторизация:** Bearer-токен из `POST /rest/v1/auth.login` — подробности в [auth.md](./auth.md).
- **GET:** пагинация `page` / `limit`.
- **POST:** одиночный объект или batch-массив (макс. 100) → `201` для одиночного,
  `207 Multi-Status` для batch с per-item статусами; часть эндпоинтов работает
  асинхронно → `202 Accepted` с `task_id` (очередь `s_Tasks`).
- **PUT:** обновление по `id` из тела JSON (`{"id": 123, ...}`), также поддерживается batch.
- **DELETE:** только batch-формат тела `{"data": [123, 456, ...]}`;
  `?id=` в query-строке не поддерживается.

## Примеры ответов

**Успех:**

```json
{
    "status": 200,
    "message": "OK",
    "data": []
}
```

**Ошибка:**

```json
{
    "status": 400,
    "message": "Field 'name' is required",
    "data": null
}
```

## Обзор эндпоинтов

| Метод | Путь | Авторизация | Описание |
|---|---|---|---|
| GET | `/rest/m2m/tasks.result` | `Bearer`, права: 1 | M2M: получение результата async-задачи по id |
| GET | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: список пользователей |
| GET | `/rest/m2m/users.item` | `Bearer`, права: 1 | M2M: пользователь по id |
| GET | `/rest/m2m/orders` | `Bearer`, права: 1 | M2M: список заказов |
| GET | `/rest/m2m/orders.item` | `Bearer`, права: 1 | M2M: заказ по id |
| GET | `/rest/m2m/goods` | `Bearer`, права: 1 | M2M: список товаров |
| GET | `/rest/m2m/goods.item` | `Bearer`, права: 1 | M2M: товар по id |
| GET | `/rest/m2m/goods.navigator` | `Bearer`, права: 1 | M2M: категории товаров |
| GET | `/rest/m2m/goods.statuses` | `Bearer`, права: 1 | M2M: статусы товаров |
| GET | `/rest/m2m/goods.attributes` | `Bearer`, права: 1 | M2M: свойства |
| GET | `/rest/m2m/goods.attributesGroups` | `Bearer`, права: 1 | M2M: группы свойств товаров |
| GET | `/rest/m2m/goods.attributesValues` | `Bearer`, права: 1 | M2M: значения свойств товаров |
| GET | `/rest/m2m/goods.variations` | `Bearer`, права: 1 | M2M: вариации товаров |
| GET | `/rest/m2m/goods.stocks` | `Bearer`, права: 1 | M2M: остатки товаров |
| GET | `/rest/m2m/files` | `Bearer`, права: 1 | M2M: файлы |
| POST | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: создание пользователя |
| POST | `/rest/m2m/orders` | `Bearer`, права: 1 | M2M: создание заказа |
| POST | `/rest/m2m/goods` | `Bearer`, права: 1 | M2M: создание товара |
| POST | `/rest/m2m/goods.navigator` | `Bearer`, права: 1 | M2M: создание категории |
| POST | `/rest/m2m/goods.statuses` | `Bearer`, права: 1 | M2M: создание статуса |
| POST | `/rest/m2m/goods.attributes` | `Bearer`, права: 1 | M2M: создание свойства |
| POST | `/rest/m2m/goods.attributesValues` | `Bearer`, права: 1 | M2M: создание значения |
| POST | `/rest/m2m/goods.variations` | `Bearer`, права: 1 | M2M: создание вариации |
| POST | `/rest/m2m/files` | `Bearer`, права: 1 | M2M: создание файлов |
| PUT | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: обновление пользователя по id |
| PUT | `/rest/m2m/orders` | `Bearer`, права: 1 | M2M: обновление заказа по id |
| PUT | `/rest/m2m/goods` | `Bearer`, права: 1 | M2M: обновление товара по id |
| PUT | `/rest/m2m/goods.navigator` | `Bearer`, права: 1 | M2M: обновление категории каталога по id |
| PUT | `/rest/m2m/goods.statuses` | `Bearer`, права: 1 | M2M: обновление статуса товаров по id |
| PUT | `/rest/m2m/goods.attributes` | `Bearer`, права: 1 | M2M: обновление свойства товаров по id |
| PUT | `/rest/m2m/goods.attributesValues` | `Bearer`, права: 1 | M2M: обновление значения свойства товаров по id |
| PUT | `/rest/m2m/goods.variations` | `Bearer`, права: 1 | M2M: обновление вариации |
| PUT | `/rest/m2m/goods.stocks` | `Bearer`, права: 1 | M2M: обновление остатков товаров |
| PUT | `/rest/m2m/files` | `Bearer`, права: 1 | M2M: обновление файлов |
| DELETE | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: удаление пользователя |
| DELETE | `/rest/m2m/orders` | `Bearer`, права: 1 | M2M: удаление заказа |
| DELETE | `/rest/m2m/goods` | `Bearer`, права: 1 | M2M: удаление товара |
| DELETE | `/rest/m2m/goods.navigator` | `Bearer`, права: 1 | M2M: удаление категории |
| DELETE | `/rest/m2m/goods.statuses` | `Bearer`, права: 1 | M2M: удаление статуса |
| DELETE | `/rest/m2m/goods.attributes` | `Bearer`, права: 1 | M2M: удаление свойства |
| DELETE | `/rest/m2m/goods.attributesValues` | `Bearer`, права: 1 | M2M: удаление значения |
| DELETE | `/rest/m2m/goods.variations` | `Bearer`, права: 1 | M2M: удаление вариации |
| DELETE | `/rest/m2m/files` | `Bearer`, права: 1 | M2M: удаление файлов по id |

## tasks

### `GET /rest/m2m/tasks.result`

M2M: получение результата async-задачи по id

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `id` | `int2` | да |

## users

### `GET /rest/m2m/users`

M2M: список пользователей (конфигурируется через s_Config)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/users.item`

M2M: пользователь по id

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `id` | `int2` | да |

### `POST /rest/m2m/users`

M2M: создание пользователя(ей). Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `nameFirst` | `string` | да |
| `nameSurname` | `string` | да |
| `namePatronymic` | `string` | нет |
| `login` | `string` | да |
| `email` | `string` | да |
| `phone` | `string` | да |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "nameFirst": "string",
            "nameSurname": "string",
            "login": "string",
            "email": "string",
            "phone": "string"
        }
    ]
}
```

### `PUT /rest/m2m/users`

M2M: обновление пользователя по id. ID передаётся в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `nameFirst` | `string` | нет |
| `nameSurname` | `string` | нет |
| `namePatronymic` | `string` | нет |
| `login` | `string` | нет |
| `email` | `string` | нет |
| `phone` | `string` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `DELETE /rest/m2m/users`

M2M: удаление пользователя(ей) по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

## orders

### `GET /rest/m2m/orders`

M2M: список заказов (конфигурируется через s_Config)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/orders.item`

M2M: заказ по id

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `id` | `int2` | да |

### `POST /rest/m2m/orders`

M2M: создание заказа(ов). Batch (массив, макс. 100). Возвращает 207 с per-item статусом. Поддерживает вложенные data.items[] для позиций заказа

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `isHidden` | `int` | нет |
| `userId` | `int` | да |
| `phone` | `string` | да |
| `email` | `string` | да |
| `status` | `string` | да |
| `sum` | `float2` | да |
| `date` | `date` | да |
| `delivery` | `int2` | да |
| `payment` | `int2` | да |
| `postalCode` | `string` | нет |
| `address` | `string` | нет |
| `city` | `string` | нет |
| `region` | `string` | нет |
| `country` | `string` | нет |
| `data` | `object` | да |

Вложенные поля тела: `data.items[]`, `data.items[].id`, `data.items[].idv`, `data.items[].name`, `data.items[].quantity`, `data.items[].stocks`, `data.items[].active`, `data.items[].price`, `data.items[].sum`, `data.items[].priceBefore`, `data.items[].sumBefore`, `data.items[].sumBeforeTotal`, `data.items[].sumSaving`, `data.items[].quantityActive`, `data.items[].sumActive`, `data.items[].url`, `data.items[].image`.

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "userId": 1,
            "phone": "string",
            "email": "string",
            "status": "string",
            "sum": 99.9,
            "date": "2026-01-15",
            "delivery": 1,
            "payment": 1,
            "data": {
                "items": [
                    {
                        "id": 1,
                        "idv": 1,
                        "name": "string",
                        "quantity": 1,
                        "sum": 99.9
                    }
                ]
            }
        }
    ]
}
```

### `PUT /rest/m2m/orders`

M2M: обновление заказа по id. ID можно передавать в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `isHidden` | `int` | нет |
| `userId` | `int` | нет |
| `phone` | `string` | нет |
| `email` | `string` | нет |
| `status` | `string` | нет |
| `sum` | `float2` | нет |
| `date` | `date` | нет |
| `delivery` | `int2` | нет |
| `payment` | `int2` | нет |
| `postalCode` | `string` | нет |
| `address` | `string` | нет |
| `city` | `string` | нет |
| `region` | `string` | нет |
| `country` | `string` | нет |
| `data` | `object` | нет |

Вложенные поля тела: `data.items[]`, `data.items[].id`, `data.items[].idv`, `data.items[].name`, `data.items[].quantity`, `data.items[].stocks`, `data.items[].active`, `data.items[].price`, `data.items[].sum`, `data.items[].priceBefore`, `data.items[].sumBefore`, `data.items[].sumBeforeTotal`, `data.items[].sumSaving`, `data.items[].quantityActive`, `data.items[].sumActive`, `data.items[].url`, `data.items[].image`.

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `DELETE /rest/m2m/orders`

M2M: удаление заказа(ов) по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

## goods

### `GET /rest/m2m/goods`

M2M: список товаров (конфигурируется через s_Config)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/goods.item`

M2M: товар по id

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `id` | `int2` | да |

### `GET /rest/m2m/goods.navigator`

M2M: категории товаров (разделы навигатора)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/goods.statuses`

M2M: статусы товаров

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/goods.attributes`

M2M: свойства (атрибуты) товаров

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/goods.attributesGroups`

M2M: группы свойств товаров

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/goods.attributesValues`

M2M: значения свойств товаров

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `list` | `string` | нет |
| `listId` | `int2` | нет |
| `listField` | `string` | нет |
| `page` | `string` | нет |
| `limit` | `int2` | нет |

### `GET /rest/m2m/goods.variations`

M2M: вариации товаров (sku, цены, остатки)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |
| `goodsId` | `int2` | нет |

### `GET /rest/m2m/goods.stocks`

M2M: остатки товаров (sku, количества)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |
| `goodsId` | `int2` | нет |

### `POST /rest/m2m/goods`

M2M: создание товара(ов). Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1
- **Async:** `202 Accepted` + `task_id` (очередь `s_Tasks`)

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `alias` | `string` | да |
| `navigatorId` | `int2` | да |
| `price` | `float2` | да |
| `article` | `string` | нет |
| `descr` | `string` | нет |
| `isHidden` | `int2` | нет |
| `priceBefore` | `float2` | нет |
| `status` | `int2` | нет |
| `metaTitle` | `string` | нет |
| `metaDescription` | `string` | нет |
| `metaKeywords` | `string` | нет |
| `weightPack` | `float2` | нет |
| `displayFirst` | `int2` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "alias": "string",
            "navigatorId": 1,
            "price": 99.9
        }
    ]
}
```

### `POST /rest/m2m/goods.navigator`

M2M: создание категории(й) каталога. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `url` | `string` | да |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "url": "string"
        }
    ]
}
```

### `POST /rest/m2m/goods.statuses`

M2M: создание статуса(ов) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `alias` | `string` | да |
| `priority` | `int` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "alias": "string"
        }
    ]
}
```

### `POST /rest/m2m/goods.attributes`

M2M: создание свойства(й) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `alias` | `string` | да |
| `group` | `int` | нет |
| `priority` | `int` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "alias": "string"
        }
    ]
}
```

### `POST /rest/m2m/goods.attributesValues`

M2M: создание значения(й) свойств товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `w_guid` | `guid` | нет |
| `attributesId` | `int` | да |
| `alias` | `string` | да |
| `list` | `string` | да |
| `listId` | `int` | да |
| `listField` | `string` | да |
| `value` | `string` | да |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "attributesId": 1,
            "alias": "string",
            "list": "string",
            "listId": 1,
            "listField": "string",
            "value": "string"
        }
    ]
}
```

### `POST /rest/m2m/goods.variations`

M2M: создание вариации(й) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `goodsId` | `int` | да |
| `guid` | `guid` | да |
| `color` | `string` | нет |
| `size` | `string` | нет |
| `sku` | `string` | да |
| `priority` | `int` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "goodsId": 1,
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "sku": "string"
        }
    ]
}
```

### `PUT /rest/m2m/goods`

M2M: обновление товара по id. ID можно передавать в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `alias` | `string` | нет |
| `navigatorId` | `int2` | нет |
| `price` | `float2` | нет |
| `article` | `string` | нет |
| `descr` | `string` | нет |
| `isHidden` | `int2` | нет |
| `priceBefore` | `float2` | нет |
| `status` | `int2` | нет |
| `metaTitle` | `string` | нет |
| `metaDescription` | `string` | нет |
| `metaKeywords` | `string` | нет |
| `weightPack` | `float2` | нет |
| `displayFirst` | `int2` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `PUT /rest/m2m/goods.navigator`

M2M: обновление категории каталога по id. ID можно передавать в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `parentId` | `int` | нет |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `url` | `string` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `PUT /rest/m2m/goods.statuses`

M2M: обновление статуса товаров по id. ID можно передавать в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `alias` | `string` | нет |
| `priority` | `int` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `PUT /rest/m2m/goods.attributes`

M2M: обновление свойства товаров по id. ID можно передавать в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `alias` | `string` | нет |
| `group` | `int` | нет |
| `priority` | `int` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `PUT /rest/m2m/goods.attributesValues`

M2M: обновление значения свойства товаров по id. ID можно передавать в теле JSON {"id": 123, ...}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `attributesId` | `int` | нет |
| `alias` | `string` | нет |
| `list` | `string` | нет |
| `listId` | `int` | нет |
| `listField` | `string` | нет |
| `value` | `string` | нет |
| `isHidden` | `int` | нет |
| `w_guid` | `guid` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `PUT /rest/m2m/goods.variations`

M2M: обновление вариации(й) товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 200 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `goodsId` | `int` | нет |
| `guid` | `guid` | нет |
| `color` | `string` | нет |
| `size` | `string` | нет |
| `sku` | `string` | нет |
| `priority` | `int` | нет |
| `isHidden` | `int` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `PUT /rest/m2m/goods.stocks`

M2M: обновление остатков товаров. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 200 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `stocks` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1,
            "stocks": 1
        }
    ]
}
```

### `DELETE /rest/m2m/goods`

M2M: удаление товара(ов) по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

### `DELETE /rest/m2m/goods.navigator`

M2M: удаление категории(й) каталога по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

### `DELETE /rest/m2m/goods.statuses`

M2M: удаление статуса(ов) товаров по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

### `DELETE /rest/m2m/goods.attributes`

M2M: удаление свойства(й) товаров по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

### `DELETE /rest/m2m/goods.attributesValues`

M2M: удаление значения(й) свойств товаров по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

### `DELETE /rest/m2m/goods.variations`

M2M: удаление вариации(й) товаров по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

## files

### `GET /rest/m2m/files`

M2M: файлы (с пагинацией)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |
| `list` | `string` | нет |
| `listField` | `string` | нет |
| `listId` | `int2` | нет |
| `description` | `string` | нет |
| `filter` | `string` | нет |

### `POST /rest/m2m/files`

M2M: создание файлов. Поддерживает одиночный объект или batch (массив, макс. 100). Загрузка через base64 или url. Возвращает 201 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `guid` | `guid` | да |
| `name` | `string` | да |
| `list` | `string` | да |
| `listField` | `string` | да |
| `listId` | `int` | да |
| `description` | `string` | нет |
| `filter` | `string` | нет |
| `url` | `string` | нет |
| `base64` | `string` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "guid": "a6a5a7b0-520a-fb8d-4b24-23451de424e6",
            "name": "string",
            "list": "string",
            "listField": "string",
            "listId": 1
        }
    ]
}
```

### `PUT /rest/m2m/files`

M2M: обновление файлов. Поддерживает одиночный объект или batch (массив, макс. 100). Возвращает 200 для одиночного или 207 для batch с per-item статусом

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `id` | `int` | да |
| `guid` | `guid` | нет |
| `name` | `string` | нет |
| `list` | `string` | нет |
| `listField` | `string` | нет |
| `listId` | `int` | нет |
| `description` | `string` | нет |
| `filter` | `string` | нет |
| `priority` | `int` | нет |
| `url` | `string` | нет |
| `base64` | `string` | нет |

**Пример запроса:**

```json
{
    "data": [
        {
            "id": 1
        }
    ]
}
```

### `DELETE /rest/m2m/files`

M2M: удаление файлов по id. Формат тела: {"data": [123, 456, ...]}

- **Авторизация:** `Bearer`, права: 1

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `data[] (массив id)` | `int` | да |

**Пример запроса:**

```json
{
    "data": [
        1,
        2
    ]
}
```

