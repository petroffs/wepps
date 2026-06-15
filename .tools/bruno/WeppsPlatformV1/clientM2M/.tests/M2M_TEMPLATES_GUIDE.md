# 🔧 M2M API Templates - Генерация и использование

## Что это?

Автоматизация работы с M2M API для операций Create/Update/Delete товаров.

**Процесс:**
1. **Скрипт** запросит GET /rest/m2m/goods и сохранит результат
2. **Скрипт** создаст JSON templates для POST (создание) и PUT (обновление)
3. **Вы** открываете template в Bruno, редактируете и отправляете

---

## 📋 Структура

```
.tools/bruno/WeppsPlatformV1/clientM2M/tests/
├── generate-m2m-templates.php      ← PHP скрипт (основной)
├── generate-m2m-templates.sh       ← Bash обёртка (удобный запуск)
├── M2M_TEMPLATES_GUIDE.md          ← Это руководство
├── M2M_TEMPLATES_SUMMARY.md        ← Краткое резюме
└── goods/
    ├── goods-get-response.json                 (результат GET)
    ├── goods-get-response-sample-3.json        (первые 3)
    ├── goods-post-template.json                (для CREATE всех)
    ├── goods-post-template-sample-3.json       (для CREATE первых 3)
    ├── goods-put-template.json                 (для UPDATE всех с маркером)
    ├── goods-put-template-sample-3.json        (для UPDATE первых 3)
    ├── EXAMPLE-post-template.json              (пример POST структуры)
    ├── EXAMPLE-put-template-with-marker.json   (пример PUT с маркером)
    ├── EXAMPLE-put-template-without-marker.json (пример PUT без маркера)
    └── README.md                               (инструкции для Bruno)
```

---

## 🚀 Как использовать

### Шаг 1: Сгенерировать templates

**Вариант A: Из root папки проекта**
```bash
cd /path/to/wepps.platform
php .tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.php
```

**Вариант B: Через bash скрипт**
```bash
cd /path/to/wepps.platform
chmod +x .tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.sh
./.tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.sh
```

**Вариант C: С кастомным лимитом**
```bash
php .tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.php --limit 5
# Создаст templates с первыми 5 товарами вместо 3
```

**Результат:**
```
🔄 Generating M2M templates for goods...
   Base URL: http://localhost
   Sample limit: 3
   Output: .tools/bruno/WeppsPlatformV1/clientM2M/tests/goods

📥 Fetching goods from GET /rest/m2m/goods...
   ✅ Fetched 245 goods

✅ Created: goods-post-template.json (245 items)
✅ Created: goods-post-template-sample-3.json (3 items)
✅ Created: goods-put-template.json (245 items with pagination marker)
✅ Created: goods-put-template-sample-3.json (3 items)
✅ Created: goods-get-response.json (reference)
✅ Created: goods-get-response-sample-3.json (reference)
✅ Created: README.md

✅ All templates generated successfully!
```

---

### Шаг 2: Использовать templates в Bruno

#### Вариант A: CREATE новые товары (все)

1. Откройте файл: `goods/goods-post-template.json`
2. Скопируйте весь JSON
3. В Bruno: откройте `POST /rest/m2m/goods` 
4. Вставьте в body
5. Отредактируйте значения если нужно (name, price, etc.)
6. Отправьте запрос
7. Результат: `201 Created` для одного или `207 Multi-Status` для batch

#### Вариант B: CREATE (первые 3 товара, для теста)

1. Откройте файл: `goods/goods-post-template-sample-3.json`
2. Скопируйте весь JSON
3. В Bruno: откройте `POST /rest/m2m/goods`
4. Вставьте в body
5. **УДАЛИТЕ** все поля `"id"` из каждого товара (иначе будет conflict)
6. Отредактируйте name, price если хотите разные значения
7. Отправьте
8. Результат: новые товары созданы

#### Вариант C: UPDATE товары (без маркера перегрузки)

1. Откройте файл: `goods/goods-put-template-sample-3.json`
2. Скопируйте весь JSON
3. **УДАЛИТЕ** поле `"pagination"` - это важно!
4. В Bruno: откройте `PUT /rest/m2m/goods`
5. Вставьте body (только `data` с товарами с id)
6. Отредактируйте: name, article, другие поля
7. Отправьте
8. Результат: обновлены только товары из JSON

#### Вариант D: UPDATE товары (с маркером полной перегрузки)

1. Откройте файл: `goods/goods-put-template.json`
2. Скопируйте весь JSON
3. **НЕ удаляйте** поле `"pagination"` - это маркер!
4. В Bruno: откройте `PUT /rest/m2m/goods`
5. Вставьте body (полный JSON с `pagination`)
6. Отредактируйте значения
7. Отправьте
8. Результат: **ВСЕ товары будут перезаписаны** с этими данными

---

## 📊 Полная схема работы

```
┌─────────────────────────────────────────────────────┐
│ Скрипт: generate-m2m-templates.php                   │
│                                                     │
│ 1. Запросит GET /rest/m2m/goods                     │
│ 2. Сохранит ответ → goods-get-response.json        │
│ 3. Создаст templates для POST (без id)             │
│ 4. Создаст templates для PUT (с id)                │
│ 5. Создаст sample версии (первые 3)                │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│ JSON Files                                           │
│                                                     │
│ goods-get-response.json (для справки)              │
│ goods-post-template.json (CREATE)                  │
│ goods-put-template.json (UPDATE с маркером)        │
│ goods-post-template-sample-3.json                  │
│ goods-put-template-sample-3.json                   │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│ Bruno REST Client                                    │
│                                                     │
│ Копируешь JSON из файла → вставляешь в body      │
│ Редактируешь если нужно → отправляешь запрос      │
│ Результат: 201/207                                 │
└─────────────────────────────────────────────────────┘
```

---

## 🔑 Ключевые поля и их типы

### POST (создание) - обязательные поля:
```json
{
  "name": "Свитер",        // string
  "alias": "sviter-01",    // string (уникальный)
  "navigatorId": 8,        // int (ID категории)
  "price": 1500.00         // float
}
```

### POST - опциональные поля:
```json
{
  "article": "ART-123",
  "descr": "Описание товара",
  "isHidden": 0,
  "priceBefore": 2000.00,
  "status": 0,
  "metaTitle": "Meta",
  "metaDescription": "Description",
  "metaKeyword": "keywords",
  "weightPack": 1.5,
  "displayFirst": 0
}
```

### PUT (обновление) - обязательные:
```json
{
  "id": 123,          // Всегда требуется ID
  "name": "Новое имя" // Что обновляем
}
```

### PUT - опциональные для обновления:
```json
{
  "id": 123,
  "name": "...",
  "article": "..."
  // ... любые другие поля
}
```

---

## 🎯 Примеры использования

### Сценарий 1: Создать 3 тестовых товара

```bash
# 1. Сгенерировать templates
php .tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.php

# 2. В Bruno:
# - Откройте goods/goods-post-template-sample-3.json
# - Скопируйте содержимое
# - POST /rest/m2m/goods
# - Вставьте JSON в body
# - Отправьте
```

### Сценарий 2: Обновить цену у 3 товаров

```bash
# 1. Открыть goods/goods-put-template-sample-3.json
# 2. УДАЛИТЬ поле "pagination"
# 3. Измените в каждом товаре:
#    "price": 1500.00 → "price": 1200.00
# 4. В Bruno PUT /rest/m2m/goods
# 5. Вставьте в body
# 6. Отправьте
```

### Сценарий 3: Полная перезапись всех товаров

```bash
# 1. Открыть goods/goods-put-template.json
# 2. ОСТАВИТЬ поле "pagination"
# 3. Отредактировать данные
# 4. В Bruno PUT /rest/m2m/goods
# 5. Вставьте в body (полный JSON с pagination)
# 6. Отправьте
# ⚠️  Это перезапишет ВСЕ товары!
```

---

## ⚙️ Требования

### config.php должен содержать:
```php
$projectSettings = [
    'Dev' => [
        'protocol' => 'http://',  // или https://
        'host' => 'localhost',
    ],
    'Services' => [
        'rest' => [
            'm2m_client_token' => 'ваш-токен-м2м',  // ← важно!
        ]
    ]
];
```

### PHP расширения:
- curl (для HTTP запросов)
- json (для JSON обработки)

---

## 🛠️ Troubleshooting

### "Config not found"
```
❌ Config not found: /path/to/config.php
```
**Решение:** убедитесь что скрипт запущен из root папки проекта

### "M2M token not configured"
```
❌ M2M token not configured in config.php
```
**Решение:** добавьте token в `config.php`:
```php
'Services' => [
    'rest' => [
        'm2m_client_token' => 'your-token-here',
    ]
]
```

### "HTTP 401/403"
```
❌ HTTP 401: Unauthorized
```
**Решение:** проверьте что token корректный и указан правильно

### "HTTP 404"
```
❌ HTTP 404: Not Found
```
**Решение:** убедитесь что API на `{{base_url}}/rest/m2m/goods` работает

---

## 📝 Примечания

1. **Batch операции** - max 100 товаров в одном запросе
2. **Пагинация маркер** - если в PUT JSON есть поле `"pagination"`, то будет полная перезапись
3. **POST** - никогда не передавайте `id` для новых товаров
4. **PUT** - всегда требуется `id` для обновления
5. **Async** - POST операции асинхронные, результат можно получить через tasks.result
6. **Sample files** - всегда используйте sample для тестирования перед полной перезаписью

---

## 🔄 Workflow для production

```
1. Сгенерировать templates
   php .tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.php

2. Отредактировать goods/goods-post-template-sample-3.json
   - Изменить price, name, article
   - УДАЛИТЬ поле "id" если есть

3. Проверить в Bruno (POST)
   - Скопировать → вставить в body
   - Проверить что нет ошибок
   - Отправить

4. Если все ОК - использовать goods/goods-post-template.json
   - Но сначала на stage!
   - Потом на production

5. Для обновления - аналогично с PUT
```

---

**Версия:** 1.0  
**Дата:** 2026-06-16  
**Статус:** ✅ Готово к использованию
