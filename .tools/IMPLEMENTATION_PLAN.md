# План внедрения методологии тестирования REST API

## 📅 Этап 1: Утверждение и подготовка (1-2 дня)

### Шаг 1.1: Обзор методологии
- [x] Создана документация `TESTING_METHODOLOGY.md`
- [x] Определены 5 уровней тестирования
- [x] Создан шаблон тестового файла
- **Действие**: Провести review с командой разработки, обсудить и утвердить подход

### Шаг 1.2: Подготовка инструментов
- [x] Создана структура Bruno .bru файлов
- [x] Созданы примеры полных тестовых наборов для `goods`
- **Действие**: 
  - Настроить переменные окружения в Bruno (`base_url`, `access_token`, `product_id`)
  - Проверить подключение к тестовому API

### Шаг 1.3: Запуск пилотного проекта
- **Действие**: Запустить все тесты для методов `goods`:
  ```
  goods.get.full-tests.bru
  goods.item.full-tests.bru
  goods.categories.full-tests.bru
  goods.filters.full-tests.bru
  goods.favorites.full-tests.bru
  ```

---

## 📋 Этап 2: Применение к существующим методам (1 неделя)

### Шаг 2.1: Инвентаризация методов
**Действие**: Составить список всех REST API методов

```php
// Методы в RestV1APP.php:
✅ getHome()           // GET /v1/home
✅ getGoods()          // GET /v1/goods
✅ getGoodsItem()      // GET /v1/goods.item
✅ getGoodsCategories()  // GET /v1/goods.categories
✅ getGoodsFilters()   // GET /v1/goods.filters
✅ getGoodsFavorites() // GET /v1/goods.favorites
✅ postGoods()         // POST /v1/goods
🔲 getOrders()
🔲 getNews()
🔲 getSlides()
🔲 ... (остальные)
```

### Шаг 2.2: Приоритизация методов

**Приоритет 1 (ВЫСОКИЙ)** - Методы чтения данных:
1. `getGoods()` - основной метод каталога ✅
2. `getGoodsItem()` - деталь товара ✅
3. `getGoodsVariations()` - вариации товаров (TODO)
4. `getOrders()` - заказы пользователя
5. `getNews()` - новости
6. `getSlides()` - слайды

**Приоритет 2 (СРЕДНИЙ)** - Методы фильтрации и поиска:
7. `getGoodsCategories()` - категории ✅
8. `getGoodsFilters()` - фильтры ✅
9. `getGoodsFavorites()` - избранное ✅
10. `getNewsCategories()` - категории новостей

**Приоритет 3 (НИЗКИЙ)** - Методы создания/изменения:
11. `postGoods()` - создание товара
12. `putGoods()` - изменение товара
13. Остальные POST/PUT методы

### Шаг 2.3: Создание тестов для методов Приоритет 1

**Для каждого метода:**

1. **Создать файл** `<method>.full-tests.bru`:
   ```
   .tools/bruno/WeppPlatformV1/clientAPP/
   ├── goods/
   │   ├── goods.get.full-tests.bru ✅
   │   ├── goods.item.full-tests.bru ✅
   │   ├── goods.categories.full-tests.bru ✅
   │   ├── goods.filters.full-tests.bru ✅
   │   ├── goods.favorites.full-tests.bru ✅
   │   └── goods.variations.full-tests.bru 👈 TODO
   ├── orders/
   │   ├── orders.get.full-tests.bru 👈 TODO
   │   └── orders.item.full-tests.bru 👈 TODO
   ├── news/
   │   ├── news.get.full-tests.bru 👈 TODO
   │   └── news.item.full-tests.bru 👈 TODO
   └── slides/
       └── slides.get.full-tests.bru 👈 TODO
   ```

2. **Заполнить шаблон** (использовать из `TESTING_METHODOLOGY.md`):
   - Базовая корректность (Уровень 1)
   - Структура данных (Уровень 2)
   - Валидация значений (Уровень 3)
   - Логика и связи (Уровень 4)
   - Граничные случаи (Уровень 5)

3. **Запустить тесты**:
   ```bash
   bruno run --no-exit <method>.full-tests.bru
   ```

4. **Исправить** ошибки в тестах или коде

### Шаг 2.4: Документирование результатов

**Для каждого метода создать запись:**

| Метод | Статус | Дата | Покрыто Тестами | Примечания |
|-------|--------|------|-----------------|-----------|
| GET /v1/goods | ✅ ГОТОВ | 2026-06-15 | Все 5 уровней | Все тесты проходят |
| GET /v1/goods.item | ✅ ГОТОВ | 2026-06-15 | Все 5 уровней | Все тесты проходят |
| GET /v1/goods.categories | ✅ ГОТОВ | 2026-06-15 | Все 5 уровней | Все тесты проходят |
| GET /v1/goods.filters | ✅ ГОТОВ | 2026-06-15 | Все 5 уровней | Все тесты проходят |
| GET /v1/goods.favorites | ✅ ГОТОВ | 2026-06-15 | Все 5 уровней | Требует авторизации |
| GET /v1/goods.variations | 📋 TODO | - | - | Ожидает реализации |

---

## 🎯 Этап 3: Применение для новых методов (текущий процесс)

### Шаг 3.1: Реализация метода `GET /v1/goods.variations`

#### Спецификация:
```php
/**
 * GET v1/goods.variations — список вариаций товара
 * @param goodsId - ID товара
 * @param page - страница (default 1)
 * @param limit - на странице (default 20)
 */
public function getGoodsVariations(): array
{
    $goodsId = (int)($this->get['goodsId'] ?? 0);
    $page = max(1, (int)($this->get['page'] ?? 1));
    $limit = min(100, max(1, (int)($this->get['limit'] ?? 20)));

    if (empty($goodsId)) {
        return ['status' => 400, 'message' => 'goodsId required', 'data' => null];
    }

    // Логика получения вариаций...
    return [
        'status' => 200,
        'message' => 'OK',
        'data' => [...],
        'pagination' => ['count' => $count, 'page' => $page, 'limit' => $limit]
    ];
}
```

#### Процесс разработки:
1. **Написать docs** с полной спецификацией метода
2. **Написать базовые тесты** (L1-L3)
3. **Реализовать метод** в `RestV1APP.php`
4. **Запустить базовые тесты** → ✅ Проходят
5. **Написать полные тесты** (L4-L5)
6. **Запустить все тесты** → ✅ Проходят
7. **Создать файл** `goods.variations.full-tests.bru` в Bruno

### Шаг 3.2: Новые методы - чек-лист

Перед добавлением каждого нового метода:

- [ ] **Спецификация**
  - [ ] Параметры определены
  - [ ] Возвращаемая структура описана
  - [ ] Коды ошибок задокументированы
  - [ ] Требования к авторизации ясны

- [ ] **Тесты**
  - [ ] Базовые тесты (L1-L2) написаны
  - [ ] Тесты валидации (L3) написаны
  - [ ] Логические тесты (L4) написаны
  - [ ] Граничные тесты (L5) написаны
  - [ ] Все тесты проходят успешно
  - [ ] Тесты документированы в `docs` разделе

- [ ] **Реализация**
  - [ ] Метод написан в `RestV1APP.php` (или другом файле)
  - [ ] Использует guard clauses для обработки ошибок
  - [ ] Использует Connect::$instance для БД
  - [ ] URL обработаны корректно (полные пути)
  - [ ] Ответ имеет формат `{status, message, data, pagination?}`

- [ ] **Документация**
  - [ ] Добавлена в `TESTING_METHODOLOGY.md`
  - [ ] Добавлена в матрицу покрытия
  - [ ] .bru файл создан в правильной папке
  - [ ] Переменные окружения настроены

- [ ] **Review**
  - [ ] Code review пройден
  - [ ] Tests review пройден
  - [ ] Спецификация утверждена

---

## 📂 Текущее состояние: Методы `goods` и `goodsVariations`

### ✅ Завершено для `goods`:

1. **Файлы тестов:**
   - ✅ `goods.get.full-tests.bru` - Все 5 уровней
   - ✅ `goods.item.full-tests.bru` - Все 5 уровней
   - ✅ `goods.categories.full-tests.bru` - Все 5 уровней
   - ✅ `goods.filters.full-tests.bru` - Все 5 уровней
   - ✅ `goods.favorites.full-tests.bru` - Все 5 уровней

2. **Документация:**
   - ✅ `TESTING_METHODOLOGY.md` - Полная методология
   - ✅ Спецификация каждого метода описана
   - ✅ Матрица покрытия создана

### 📋 TODO для `goodsVariations`:

**Этап 1: Спецификация и базовые тесты**
- [ ] Определить точную структуру вариаций в БД
- [ ] Написать спецификацию метода `GET /v1/goods.variations`
- [ ] Создать файл `goods.variations.full-tests.bru`
- [ ] Написать базовые тесты (L1-L3)

**Этап 2: Реализация**
- [ ] Реализовать метод в `RestV1APP.php`
- [ ] Убедиться что связь с основным товаром корректна
- [ ] Протестировать пагинацию
- [ ] Протестировать фильтрацию по stock

**Этап 3: Полное тестирование**
- [ ] Написать логические тесты (L4)
- [ ] Написать граничные тесты (L5)
- [ ] Запустить все тесты в Bruno
- [ ] Исправить ошибки

**Этап 4: Документирование**
- [ ] Добавить в `TESTING_METHODOLOGY.md`
- [ ] Обновить матрицу покрытия
- [ ] Создать примеры вызовов API

---

## 🚀 Этап 4: Масштабирование (2-3 недели)

### План применения ко всем методам:

**Неделя 1: Методы Приоритет 1 (чтение)**
```
✅ getGoods
✅ getGoodsItem
✅ getGoodsCategories
✅ getGoodsFilters
✅ getGoodsFavorites
❌→✅ getGoodsVariations
📋 getOrders
📋 getOrderItem
📋 getNews
📋 getNewsItem
📋 getSlides
```

**Неделя 2: Методы Приоритет 2 (поиск/фильтр)**
```
📋 getNewsCategories
📋 getOrdersStatuses
📋 getDeliveryMethods
📋 getPaymentMethods
```

**Неделя 3: Методы Приоритет 3 (создание/изменение)**
```
📋 postGoods
📋 putGoods
📋 deleteGoods
📋 postOrder
📋 putOrder (если нужно)
```

---

## 📊 Метрики успеха

### К концу Этапа 2:
- ✅ Методы `goods` полностью протестированы (все 5 уровней)
- ✅ `goodsVariations` добавлены в систему и тестируются
- ✅ Методология утверждена командой
- ✅ Все базовые тесты проходят

### К концу Этапа 3:
- ✅ Все методы Приоритет 1 имеют полные тест-наборы
- ✅ Coverage > 90% для критических методов
- ✅ Документация актуальна и полная
- ✅ Zero регрессий в текущих методах

### К концу Этапа 4:
- ✅ Все методы API имеют тесты (5 уровней)
- ✅ Методология применяется во всех новых разработках
- ✅ CI/CD pipeline интегрирует запуск тестов
- ✅ Тесты выполняются автоматически при каждом commit'е

---

## 🔧 Технические детали

### Структура папок Bruno:
```
.tools/bruno/WeppPlatformV1/clientAPP/
├── environments.json          # Переменные окружения
├── bruno.json                 # Конфигурация Bruno
├── goods/
│   ├── goods.get.bru         # Минимальные тесты (оригинал)
│   ├── goods.get.full-tests.bru     # Полные тесты (новое)
│   ├── goods.item.bru
│   ├── goods.item.full-tests.bru
│   └── ...
├── orders/
│   ├── orders.get.bru
│   ├── orders.get.full-tests.bru
│   └── ...
└── news/
    └── ...
```

### Запуск тестов в Bruno:
```bash
# Запустить один файл
bruno run goods/goods.get.full-tests.bru

# Запустить всю папку
bruno run goods/ --output-format json

# Запустить с переменными окружения
bruno run goods/ --env production
```

### Интеграция с CI/CD:
```yaml
# .github/workflows/api-tests.yml
name: API Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run API Tests
        run: |
          npm install -g @usebruno/cli
          bruno run .tools/bruno/WeppPlatformV1/clientAPP/
      - name: Report Results
        if: always()
        uses: dorny/test-reporter@v1
```

---

## ✅ Финальный чек-лист

Перед началом внедрения методологии:

- [ ] Все заинтересованные лица ознакомились с `TESTING_METHODOLOGY.md`
- [ ] Команда согласна с 5-уровневым подходом
- [ ] Bruno установлен и настроен
- [ ] Переменные окружения настроены
- [ ] Доступ к тестовому API подтвержден
- [ ] Примеры тестов для `goods` запущены успешно
- [ ] План согласован и утвержден

---

## 📞 Контакты и вопросы

При возникновении вопросов:
1. Обратитесь к документации `TESTING_METHODOLOGY.md`
2. Проверьте примеры в папке `goods/`
3. Проведите code review с командой
4. Обновите методологию при необходимости

**Версия**: 1.0  
**Дата**: 2026-06-15  
**Статус**: ✅ Готово к утверждению
