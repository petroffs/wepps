# Методология тестирования REST API методов

## 🎯 Цель

Установить единый стандарт проверки всех методов чтения и обработки данных (REST API endpoints) для обеспечения надежности, корректности и консистентности API.

---

## 📋 Структура тестирования

Каждый метод должен проверяться по 5 уровням:

### **Уровень 1: Базовая корректность ответа**
- ✅ HTTP статус код (200, 400, 404 и т.д.)
- ✅ Структура JSON ответа (наличие `status`, `message`, `data`)
- ✅ Тип данных `data` (array, object, null)

### **Уровень 2: Структура данных**
- ✅ Наличие всех обязательных полей в объектах
- ✅ Типы данных полей соответствуют спецификации
- ✅ Вложенные объекты/массивы имеют правильную структуру

### **Уровень 3: Валидация значений**
- ✅ Значения находятся в допустимых диапазонах
- ✅ Дата/время в корректном формате
- ✅ URL-адреса корректны и полные
- ✅ Числовые значения логичны (не отрицательные цены, реальные ID и т.д.)

### **Уровень 4: Логика и связи данных**
- ✅ Данные соответствуют переданным параметрам фильтрации
- ✅ Связи между сущностями корректны (категория товара, связанные объекты)
- ✅ Пагинация согласована (count, page, limit)
- ✅ Результаты сортировки соответствуют параметру `sort`

### **Уровень 5: Граничные случаи и ошибки**
- ✅ Обработка пустых результатов
- ✅ Некорректные параметры возвращают 400 с понятным сообщением
- ✅ Несуществующие ресурсы возвращают 404
- ✅ Недостаточные права возвращают 401/403

---

## 📐 Шаблон тестового файла (Структура .bru файла)

```
meta {
  name: <HTTP_METHOD> <ENDPOINT>
  type: http
  seq: <ПОРЯДОК>
}

<METHOD> {
  url: {{base_url}}<ENDPOINT>?<PARAMS>
  auth: <AUTH_TYPE>
}

params:query {
  <PARAM_1>: <VALUE>
  ~<OPTIONAL_PARAM>: <VALUE>
}

tests {
  // ========== УРОВЕНЬ 1: Базовая корректность ==========
  test("status 200", function() {
    expect(res.status).to.equal(200);
  });

  test("response has status field", function() {
    expect(res.body).to.have.property("status");
  });

  test("response has message field", function() {
    expect(res.body).to.have.property("message");
  });

  test("response has data field", function() {
    expect(res.body).to.have.property("data");
  });

  // ========== УРОВЕНЬ 2: Структура данных ==========
  test("data is array", function() {
    expect(res.body.data).to.be.an("array");
  });

  test("each item has required fields", function() {
    res.body.data.forEach(item => {
      expect(item).to.have.property("Id");
      expect(item).to.have.property("Name");
    });
  });

  // ========== УРОВЕНЬ 3: Валидация значений ==========
  test("all items have positive Id", function() {
    res.body.data.forEach(item => {
      expect(item.Id).to.be.a("number");
      expect(item.Id).to.be.greaterThan(0);
    });
  });

  test("Name is non-empty string", function() {
    res.body.data.forEach(item => {
      expect(item.Name).to.be.a("string");
      expect(item.Name.length).to.be.greaterThan(0);
    });
  });

  // ========== УРОВЕНЬ 4: Логика и связи ==========
  test("items match category filter", function() {
    if (req.getEnvironmentVariable("category")) {
      res.body.data.forEach(item => {
        expect(item.NavigatorId).to.equal(parseInt(req.getEnvironmentVariable("category")));
      });
    }
  });

  test("pagination is consistent", function() {
    expect(res.body.pagination).to.have.property("count");
    expect(res.body.pagination).to.have.property("page");
    expect(res.body.pagination).to.have.property("limit");
    expect(res.body.pagination.page).to.be.lessThanOrEqual(Math.ceil(res.body.pagination.count / res.body.pagination.limit));
  });

  // ========== УРОВЕНЬ 5: Граничные случаи ==========
  test("handles empty results gracefully", function() {
    expect(res.body.data).to.be.an("array");
    if (res.body.data.length === 0) {
      expect(res.body.pagination.count).to.equal(0);
    }
  });
}

docs {
  <ОПИСАНИЕ_МЕТОДА>
  Параметры: <СПИСОК_ПАРАМЕТРОВ>
  Возвращает: <СТРУКТУРА_ОТВЕТА>
}
```

---

## 🔍 Применение к методам `goods` и `goodsVariations`

### **Метод: GET /v1/goods**

#### Параметры:
- `page` (optional, default=1) - номер страницы
- `limit` (optional, default=20, max=100) - кол-во товаров на странице
- `category` (optional) - ID категории
- `f_*` (optional) - фильтры по свойствам (например `f_1=red|blue`)
- `sort` (optional) - тип сортировки (`priceasc`, `pricedesc`, `nameasc`, пусто=по приоритету)
- `search` (optional) - поиск по названию

#### Ожидаемая структура ответа:
```json
{
  "status": 200,
  "message": "OK",
  "data": [
    {
      "Id": 123,
      "Name": "Товар 1",
      "Price": 1500.00,
      "Url": "http://...",
      "Images_FileUrl": "http://...",
      "NavigatorId": 8,
      "W_Attributes": [...],
      "IsHidden": 0
    }
  ],
  "pagination": {
    "count": 245,
    "page": 1,
    "limit": 24
  }
}
```

#### Тесты (расширенный набор):
```
✅ status 200
✅ data is array
✅ pagination fields present (count, page, limit)
✅ count is positive number
✅ page <= ceil(count/limit)
✅ limit matches request parameter
✅ all items have Id (positive number)
✅ all items have Name (non-empty string)
✅ all items have Price (number ≥ 0)
✅ all items have Url (valid URL starting with http)
✅ Images_FileUrl is valid URL (when present)
✅ items match category filter (when category specified)
✅ items match sort order (when sort specified)
✅ items contain substring from search (when search specified)
✅ W_Attributes is array when present
✅ each attribute has id, name, values
✅ IsHidden is 0 for all items (фильтруется на бэке)
✅ items returned are ≤ limit
```

---

### **Метод: GET /v1/goods.item**

#### Параметры:
- `id` (required) - числовой ID или строковый alias товара

#### Ожидаемая структура ответа:
```json
{
  "status": 200,
  "message": "OK",
  "data": {
    "Id": 123,
    "Name": "Товар 1",
    "Price": 1500.00,
    "Url": "http://...",
    "Images_FileUrl": "http://...",
    "W_Variations": [
      {
        "Id": 456,
        "Name": "Размер M",
        "Price": 1500.00,
        "Stock": 10
      }
    ],
    "W_Attributes": [...],
    "Description": "..."
  }
}
```

#### Тесты:
```
✅ status 200 (when item found)
✅ status 404 (when item not found)
✅ status 400 (when id not provided)
✅ data is object (not array)
✅ data has all required fields (Id, Name, Price, Url)
✅ Id is positive number
✅ Name is non-empty string
✅ Price is number ≥ 0
✅ Url is valid HTTP URL
✅ W_Variations is array
✅ each variation has Id, Name, Price, Stock
✅ variation Stock is non-negative number
✅ W_Attributes is array
✅ Image URLs are complete and valid
✅ Works with numeric ID
✅ Works with string alias
```

---

### **Метод: GET /v1/goods.categories**

#### Параметры: нет

#### Ожидаемая структура ответа:
```json
{
  "status": 200,
  "message": "OK",
  "data": [
    {
      "Id": 8,
      "Name": "Категория 1",
      "Url": "http://...",
      "ParentDir": 0,
      "Extension": 1
    }
  ]
}
```

#### Тесты:
```
✅ status 200
✅ data is array
✅ all items have Id, Name, Url, ParentDir, Extension
✅ Id is positive number
✅ Name is non-empty string
✅ Url is valid HTTP URL
✅ ParentDir is number ≥ 0
✅ Extension is number
✅ array is not empty (или check if empty - это корректно)
✅ items are sorted by Priority
```

---

### **Метод: GET /v1/goods.filters**

#### Параметры:
- `category` (optional) - ID категории для контекстной фильтрации
- `search` (optional) - поиск по названию

#### Ожидаемая структура ответа:
```json
{
  "status": 200,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "name": "Размер",
      "values": [
        {
          "alias": "s",
          "value": "S",
          "count": 45
        },
        {
          "alias": "m",
          "value": "M",
          "count": 32
        }
      ]
    }
  ]
}
```

#### Тесты:
```
✅ status 200
✅ data is array
✅ all items have id, name, values
✅ id is positive number
✅ name is non-empty string
✅ values is array
✅ each value has alias, value, count
✅ count is positive number
✅ alias is lowercase string
✅ filters match category (when category specified)
✅ filters match search query (when search specified)
✅ when category doesn't exist - returns 404 or empty array
```

---

### **Метод: GET /v1/goods.favorites**

#### Параметры: нет
#### Требует: авторизация (Bearer token)

#### Ожидаемая структура ответа:
```json
{
  "status": 200,
  "message": "OK",
  "data": [
    {
      "Id": 123,
      "Name": "Товар 1",
      "Price": 1500.00,
      "Images_FileUrl": "http://..."
    }
  ],
  "pagination": {
    "count": 5,
    "page": 1,
    "limit": 100
  }
}
```

#### Тесты:
```
✅ status 200 (when authorized)
✅ status 401 (when not authorized)
✅ data is array
✅ pagination fields present
✅ each item has Id, Name, Price, Images_FileUrl
✅ all items have IsHidden = 0
✅ items are only those marked as favorites by user
✅ returns empty array when no favorites
```

---

### **Предложение: Метод: GET /v1/goods.variations**

#### Параметры:
- `goodsId` (required) - ID товара
- `page` (optional, default=1)
- `limit` (optional, default=20)

#### Ожидаемая структура ответа:
```json
{
  "status": 200,
  "message": "OK",
  "data": [
    {
      "Id": 456,
      "GoodsId": 123,
      "Name": "Размер M - Красный",
      "Price": 1500.00,
      "Stock": 25,
      "SKU": "SKU-123-M-RED",
      "Properties": {
        "size": "M",
        "color": "Красный"
      }
    }
  ],
  "pagination": {
    "count": 15,
    "page": 1,
    "limit": 20
  }
}
```

#### Тесты:
```
✅ status 200 (when goodsId exists)
✅ status 404 (when goodsId doesn't exist)
✅ status 400 (when goodsId not provided)
✅ data is array
✅ pagination fields present
✅ all items have Id, GoodsId, Name, Price, Stock
✅ all items.GoodsId === request.goodsId
✅ Id is positive number
✅ Stock is non-negative number
✅ Price is number ≥ 0
✅ SKU is unique string
✅ Properties is object with key-value pairs
✅ returns empty array when good has no variations
✅ stock values are realistic
✅ all items have IsHidden = 0
```

---

## 🛠️ Инструменты и окружение

### Bruno REST Client
- Используется для написания и запуска тестов
- Файлы с расширением `.bru`
- Встроенная поддержка assertions через Chai

### Структура переменных окружения
```
{{base_url}} - базовый URL API (http://localhost/rest/v1)
{{access_token}} - bearer token для авторизованных запросов
{{product_id}} - ID продукта для тестирования
{{category}} - ID категории для тестирования
```

---

## 📊 Матрица покрытия тестами

Каждый метод должен быть протестирован:

| Метод | Успешный запрос | Некорректные параметры | Пустой результат | Граничные значения | Структура данных |
|-------|-----------------|------------------------|------------------|--------------------|------------------|
| GET /v1/goods | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /v1/goods.item | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /v1/goods.categories | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /v1/goods.filters | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /v1/goods.favorites | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /v1/goods.variations | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## ✨ Чек-лист для нового метода

Перед добавлением нового метода API:

- [ ] Определена спецификация (параметры, ответ)
- [ ] Написаны функциональные тесты (Уровень 1-3)
- [ ] Написаны логические тесты (Уровень 4)
- [ ] Написаны тесты граничных случаев (Уровень 5)
- [ ] Все тесты проходят успешно
- [ ] Документация в `docs` разделе полная и актуальная
- [ ] Переменные окружения настроены в `environments.json`
- [ ] Метод добавлен в матрицу покрытия
- [ ] Проведен code review тестов

---

## 🔄 Цикл разработки

```
1. Написать спецификацию метода в docs
2. Написать базовые тесты (Уровень 1-2)
3. Реализовать метод в PHP
4. Запустить базовые тесты
5. Написать полные тесты (Уровень 3-5)
6. Запустить все тесты
7. Оптимизировать при необходимости
8. Задокументировать финальный результат
```

---

## 📝 Примеры типичных ошибок

### ❌ Плохой тест
```javascript
test("works", function() {
  expect(res.status).to.not.equal(500);
  expect(res.body).to.exist;
});
```

### ✅ Хороший тест
```javascript
test("returns 200 status", function() {
  expect(res.status).to.equal(200);
});

test("response has status field with correct value", function() {
  expect(res.body).to.have.property("status").that.equals(200);
});

test("data array contains expected fields", function() {
  res.body.data.forEach(item => {
    expect(item).to.have.all.keys("Id", "Name", "Price", "Url");
  });
});
```

---

## 🎯 Заключение

Эта методология обеспечивает:
- ✅ Систематическую проверку всех методов
- ✅ Раннее выявление проблем
- ✅ Документирование поведения API
- ✅ Регрессионное тестирование при изменениях
- ✅ Удобство разработки новых методов

Применяйте эту методологию при разработке каждого нового endpoint'а.
