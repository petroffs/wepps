# Wepps REST API — Авторизация

- **Базовый URL:** `https://platform.wepps`
- **Сгенерировано:** 2026-10-17 23:48
- **Обёртка ответа:** `{"status": 200, "message": "OK", "data": ...}`
- **HTML-версия (Swagger UI):** [`auth.html`](./auth.html)

Платформа Wepps предоставляет три типа REST API. Токены всех типов — JWT (`typ=auth`)
и передаются в заголовке `Authorization: Bearer <token>`.

### 1. CClient (v1) — клиентское API

Используется клиентскими приложениями (витриной, мобильным приложением). Токены
выдаются по логину и паролю:

```http
POST /rest/v1/auth.login
Content-Type: application/json

{"data": {"login": "admin@wepps.loc", "password": "secret"}}
```

Ответ содержит `access_token` (1 час) и `refresh_token` (30 дней):

- обновление пары токенов — `POST /rest/v1/auth.refresh`;
- подтверждение входа (двухфакторный режим `CONFIRM_AUTH`) — `POST /rest/v1/auth.confirm`;
- выход из сессии — `POST /rest/v1/auth.logout`.

### 2. M2M — машинное взаимодействие

M2M-эндпоинты (`/rest/m2m/*`) **не имеют собственного эндпоинта авторизации**:
используется тот же `access_token`, что и в CClient, полученный учётной записью
с правами `UserPermissions = 1` (это проверяется на каждом M2M-эндпоинте).
Ниже в разделе M2M приведён пример защищённого эндпоинта.

### 3. Wepps (админка)

```http
GET /rest/wepps/token?login=admin@wepps.loc&password=secret
```

Ответ: `{"token": "...", "exp": 1760000000}` — срок жизни ~24 часа (86 200 с).
Пользователь должен иметь `ShowAdmin = 1`.

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
| POST | `/rest/v1/auth.login` | не требуется | Аутентификация пользователя и выдача JWT-токенов |
| POST | `/rest/v1/auth.logout` | `Bearer` | Завершение сессии текущего пользователя |
| POST | `/rest/v1/auth.refresh` | не требуется | Обновление access-токена по refresh-токену |
| POST | `/rest/v1/auth.confirm` | не требуется | Подтверждение входа по confirm_token из письма |
| POST | `/rest/v1/register.confirm` | не требуется | Завершение регистрации по токену из письма |
| POST | `/rest/v1/register` | не требуется | Инициация регистрации: валидация данных и отправка письма с подтверждением |
| POST | `/rest/v1/profile.password-reset` | не требуется | Запрос восстановления пароля: отправка ссылки сброса на email |
| GET | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: список пользователей |
| POST | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: создание пользователя |
| PUT | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: обновление пользователя по id |
| DELETE | `/rest/m2m/users` | `Bearer`, права: 1 | M2M: удаление пользователя |
| GET | `/rest/wepps/token` | не требуется | Аутентификация пользователя и выдача JWT-токена |

## CClient (v1)

### `POST /rest/v1/auth.login`

Аутентификация пользователя и выдача JWT-токенов (access + refresh)

- **Авторизация:** не требуется

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `login` | `email` | да |
| `password` | `string` | да |

**Пример запроса:**

```json
{
    "data": {
        "login": "user@example.com",
        "password": "string"
    }
}
```

### `POST /rest/v1/auth.logout`

Завершение сессии текущего пользователя (клиент должен удалить оба токена из локального хранилища)

- **Авторизация:** `Bearer`

### `POST /rest/v1/auth.refresh`

Обновление access-токена по refresh-токену

- **Авторизация:** не требуется

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `refresh_token` | `string` | да |

**Пример запроса:**

```json
{
    "data": {
        "refresh_token": "string"
    }
}
```

### `POST /rest/v1/auth.confirm`

Подтверждение входа по confirm_token из письма (режим CONFIRM_AUTH)

- **Авторизация:** не требуется

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `token` | `string` | да |
| `code` | `int2` | нет |

**Пример запроса:**

```json
{
    "data": {
        "token": "string"
    }
}
```

### `POST /rest/v1/register.confirm`

Завершение регистрации по токену из письма. Возвращает access+refresh токены

- **Авторизация:** не требуется

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `token` | `string` | да |
| `password` | `string` | да |
| `password2` | `string` | да |

**Пример запроса:**

```json
{
    "data": {
        "token": "string",
        "password": "string",
        "password2": "string"
    }
}
```

### `POST /rest/v1/register`

Инициация регистрации: валидация данных и отправка письма с подтверждением

- **Авторизация:** не требуется

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `login` | `email` | да |
| `phone` | `phone` | да |
| `nameSurname` | `string` | да |
| `nameFirst` | `string` | да |
| `namePatronymic` | `string` | нет |

**Пример запроса:**

```json
{
    "data": {
        "login": "user@example.com",
        "phone": "79001234567",
        "nameSurname": "string",
        "nameFirst": "string"
    }
}
```

### `POST /rest/v1/profile.password-reset`

Запрос восстановления пароля: отправка ссылки сброса на email

- **Авторизация:** не требуется

**Тело запроса** (JSON, ключ `data`):

| Поле | Тип | Обязательный |
|---|---|---|
| `login` | `email` | да |

**Пример запроса:**

```json
{
    "data": {
        "login": "user@example.com"
    }
}
```

## M2M

### `GET /rest/m2m/users`

M2M: список пользователей (конфигурируется через s_Config)

- **Авторизация:** `Bearer`, права: 1

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `page` | `int2` | нет |
| `limit` | `int2` | нет |

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

## Wepps Admin

### `GET /rest/wepps/token`

Аутентификация пользователя и выдача JWT-токена (для админки)

- **Авторизация:** не требуется

**Query-параметры:**

| Имя | Тип | Обязательный |
|---|---|---|
| `login` | `string` | да |
| `password` | `string` | да |

