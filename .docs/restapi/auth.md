# Wepps REST API — Авторизация

- **Базовый URL:** `/rest/...` — относительно текущего домена сайта (в HTML-версии домен определяется автоматически при открытии)
- **Сгенерировано:** 2026-10-18 01:12
- **Обёртка ответа:** `{"status": 200, "message": "OK", "data": ...}`
- **HTML-версия (Swagger UI):** [`auth.html`](./auth.html)

Авторизация REST API едина для всех типов. Токены — JWT (`typ=auth`)
и передаются в заголовке `Authorization: Bearer <token>`.

### 1. ClientAPP (/rest/v1) — клиентское API

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

### 2. M2M (/rest/m2m) — машинное взаимодействие

M2M-эндпоинты (`/rest/m2m/*`) **не имеют собственного эндпоинта авторизации**:
используется тот же `access_token`, что и в ClientAPP, полученный учётной записью
с правами `UserPermissions = 1` (это проверяется на каждом M2M-эндпоинте).

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

## ClientAPP

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

