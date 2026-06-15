# ✅ M2M Templates - Автоматизация REST API для goods

**Дата**: 2026-06-16  
**Статус**: ✅ ЗАВЕРШЕНО  

---

## 📋 Что было сделано

### 1. ✅ Обновлена конфигурация REST API

**Файл**: `packages/WeppsExtensions/Addons/Rest/RestConfig.php`

**Изменения**:
- Добавлена validation для `POST /rest/m2m/goods`
- Обязательные поля: `name`, `alias`, `navigatorId`, `price`
- Опциональные поля: `article`, `descr`, `isHidden`, `priceBefore`, `status`, `metaTitle`, `metaDescription`, `metaKeyword`, `weightPack`, `displayFirst`

```php
'goods' => [
    'class' => RestV1M2M::class,
    'method' => 'postGoods',
    'validation' => [
        'name' => ['type' => 'string', 'required' => true],
        'alias' => ['type' => 'string', 'required' => true],
        'navigatorId' => ['type' => 'int2', 'required' => true],
        'price' => ['type' => 'float', 'required' => true],
        // ... опциональные поля
    ],
],
```

---

### 2. ✅ Создана автоматизация через PHP скрипт

**Файл**: `.tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.php`

**Функционал**:
- Запрашивает GET /rest/m2m/goods через M2M API
- Парсит ответ и создает JSON templates
- Сохраняет в `./goods/`

**Параметры**:
```bash
php generate-m2m-templates.php [--limit 3]
```

**Создает файлы**:
- `goods/goods-get-response.json` - все товары (справка)
- `goods/goods-get-response-sample-3.json` - первые 3 товара (справка)
- `goods/goods-post-template.json` - для CREATE всех товаров
- `goods/goods-post-template-sample-3.json` - для CREATE первых 3
- `goods/goods-put-template.json` - для UPDATE всех товаров (с маркером пагинации)
- `goods/goods-put-template-sample-3.json` - для UPDATE первых 3
- `goods/README.md` - инструкции

---

### 3. ✅ Создана bash обёртка

**Файл**: `.tools/bruno/WeppsPlatformV1/clientM2M/tests/generate-m2m-templates.sh`

**Использование**:
```bash
chmod +x generate-m2m-templates.sh
./generate-m2m-templates.sh [--limit 3]
```

---

### 4. ✅ Создана документация

**Файлы**:
- `M2M_TEMPLATES_GUIDE.md` - полное руководство
- `M2M_TEMPLATES_SUMMARY.md` - этот файл (краткое резюме)

**Содержит**:
- Полное описание структуры
- Как генерировать templates
- Как использовать в Bruno
- Примеры сценариев
- Troubleshooting

---

### 5. ✅ Созданы примеры JSON структур

**Файлы** в папке `goods/`:
- `EXAMPLE-post-template.json` - структура для POST
- `EXAMPLE-put-template-with-marker.json` - структура PUT с маркером
- `EXAMPLE-put-template-without-marker.json` - структура PUT без маркера

---

## 🚀 Как использовать

### Быстрый старт

```bash
# 1. Перейти в папку
cd /path/to/wepps.platform/.tools/bruno/WeppsPlatformV1/clientM2M/tests

# 2. Сгенерировать templates
php generate-m2m-templates.php

# 3. Найти созданные файлы
ls goods/
```

**Результат**:
```
✅ Created: goods-post-template.json (245 items)
✅ Created: goods-post-template-sample-3.json (3 items)
✅ Created: goods-put-template.json (245 items with pagination marker)
✅ Created: goods-put-template-sample-3.json (3 items)
✅ Created: goods-get-response.json (reference)
✅ Created: goods-get-response-sample-3.json (reference)
✅ Created: README.md
```

---

## 📊 Workflow

### CREATE товары (POST)
```
1. Сгенерировать templates: php generate-m2m-templates.php
2. Открыть: goods/goods-post-template-sample-3.json
3. Скопировать JSON
4. В Bruno: POST /rest/m2m/goods
5. Вставить в body
6. Отредактировать values (name, price, etc.)
7. Отправить
8. Результат: 201 Created или 207 Multi-Status
```

### UPDATE товары (PUT без маркера)
```
1. Открыть: goods/goods-put-template-sample-3.json
2. УДАЛИТЬ поле "pagination"
3. Скопировать JSON (только data)
4. В Bruno: PUT /rest/m2m/goods
5. Вставить в body
6. Отредактировать: name, article
7. Отправить
8. Результат: обновлены только выбранные товары
```

### OVERWRITE все товары (PUT с маркером)
```
1. Открыть: goods/goods-put-template.json
2. ОСТАВИТЬ поле "pagination"
3. Скопировать полный JSON
4. В Bruno: PUT /rest/m2m/goods
5. Вставить в body
6. Отредактировать values
7. Отправить
8. Результат: ВСЕ товары перезаписаны
```

---

## 🔑 Ключевые моменты

### POST (создание):
- ✅ Требуемые поля: `name`, `alias`, `navigatorId`, `price`
- ❌ **Никогда** не передавайте `id`
- ✅ Опциональные: article, descr, isHidden и др.

### PUT (обновление):
- ✅ Всегда требуется `id`
- ⚠️  Если есть поле `"pagination"` → ПОЛНАЯ ПЕРЕЗАПИСЬ всех товаров
- ✅ Если нет `"pagination"` → обновление только выбранных товаров

### Маркер пагинации:
```json
"pagination": {
  "page": 1,
  "limit": 20,
  "total": 245,
  "note": "Marker: if 'pagination' field exists, full overwrite will be performed"
}
```
**Если этот блок есть в PUT** → система понимает что это полная перезапись

---

## 📁 Структура файлов

```
.tools/bruno/WeppsPlatformV1/clientM2M/tests/
├── generate-m2m-templates.php          ← Основной скрипт
├── generate-m2m-templates.sh           ← Bash обёртка
├── M2M_TEMPLATES_GUIDE.md              ← Полное руководство
├── M2M_TEMPLATES_SUMMARY.md            ← Этот файл
└── goods/
    ├── goods-get-response.json
    ├── goods-get-response-sample-3.json
    ├── goods-post-template.json
    ├── goods-post-template-sample-3.json
    ├── goods-put-template.json
    ├── goods-put-template-sample-3.json
    ├── EXAMPLE-post-template.json
    ├── EXAMPLE-put-template-with-marker.json
    ├── EXAMPLE-put-template-without-marker.json
    └── README.md
```

---

## ✨ Возможности

| Операция | Файл | Маркер | Результат |
|----------|------|--------|-----------|
| CREATE все товары | goods-post-template.json | - | 201/207 |
| CREATE первые 3 | goods-post-template-sample-3.json | - | 201/207 |
| UPDATE все (перезапись) | goods-put-template.json | ✅ есть | Перезаписаны ВСЕ |
| UPDATE первые 3 | goods-put-template-sample-3.json | ❌ удалить | Обновлены только 3 |
| Справка GET | goods-get-response.json | - | Данные |

---

## 🔍 Требования

### config.php
```php
$projectSettings = [
    'Dev' => [
        'protocol' => 'http://',
        'host' => 'localhost',  // или ваш хост
    ],
    'Services' => [
        'rest' => [
            'm2m_client_token' => 'ваш-токен-м2м',  // ← Обязательно!
        ]
    ]
];
```

### PHP модули
- curl (для HTTP запросов)
- json (для JSON обработки)

---

## 🎯 Примеры использования

### Пример 1: Создать 3 товара

```bash
# Сгенерировать templates
php generate-m2m-templates.php

# В Bruno откройте goods/goods-post-template-sample-3.json
# Скопируйте → вставьте в POST body
# Отправьте
# Результат: 3 новых товара
```

### Пример 2: Обновить цену у товаров

```bash
# Открыть goods/goods-put-template-sample-3.json
# УДАЛИТЬ "pagination"
# Изменить "price": 1500.00 → 1200.00
# В Bruno PUT → вставить → отправить
# Результат: цена обновлена
```

### Пример 3: Полная перезапись каталога

```bash
# Открыть goods/goods-put-template.json
# ОСТАВИТЬ "pagination"
# Отредактировать данные
# В Bruno PUT → вставить полный JSON → отправить
# ⚠️  Результат: ВСЕ товары перезаписаны!
```

---

## 📝 Примечания

1. **Batch max 100** - в одном запросе max 100 товаров
2. **Async операции** - POST асинхронные, результат через tasks.result
3. **Пагинация маркер** - уникальный механизм для полной перезаписи
4. **Sample файлы** - всегда сначала тестируйте на sample-3
5. **Опциональные поля** - можете передавать любые поля из таблицы
6. **M2M Authorization** - требуется Bearer token в config

---

## 🔄 CI/CD интеграция

Можно добавить в процесс разработки:

```bash
# Перед каждым деплоем: обновить templates
php generate-m2m-templates.php

# Или в script в package.json:
{
  "scripts": {
    "m2m:templates": "php generate-m2m-templates.php"
  }
}
```

Запуск:
```bash
npm run m2m:templates
```

---

## ✅ Чек-лист для использования

- [ ] Обновлен `config.php` с M2M токеном
- [ ] Запущен скрипт генерации: `php generate-m2m-templates.php`
- [ ] Проверены созданные файлы в папке `goods/`
- [ ] Прочитан `README.md` в папке goods
- [ ] Открыты примеры: `EXAMPLE-*.json`
- [ ] Скопирована `POST` структура в Bruno и отправлена
- [ ] Скопирована `PUT` структура в Bruno и отправлена
- [ ] Проверено что маркер пагинации работает
- [ ] Все готово для использования в production

---

## 🚨 Troubleshooting

### "Config not found"
Убедитесь что скрипт запущен из root папки проекта

### "M2M token not configured"
Добавьте в config.php: `'rest' => ['m2m_client_token' => 'token']`

### "HTTP 401"
Проверьте что token корректный

### "File permission denied"
```bash
chmod 755 generate-m2m-templates.sh
```

---

## 📞 Документация

- **Полное руководство**: `M2M_TEMPLATES_GUIDE.md`
- **Примеры JSON**: `EXAMPLE-*.json` в папке goods
- **REST конфиг**: `packages/WeppsExtensions/Addons/Rest/RestConfig.php`

---

**Версия**: 1.0  
**Автор**: AI Assistant  
**Статус**: ✅ Готово к использованию  
**Дата**: 2026-06-16
