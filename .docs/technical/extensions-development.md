# Разработка расширений

## Что такое расширение?

**Расширение (Extension)** - это модуль функциональности, который можно привязать к разделу сайта через таблицу `s_Navigator`. Расширения находятся в `packages/WeppsExtensions/`.

## Типы расширений

1. **Frontend расширения** - для разделов сайта (`WeppsExtensions/`)
2. **Кастомные аддоны** - специфичные для проекта (`WeppsExtensions/Addons/`)
3. **Системные расширения** - для админки (`WeppsAdmin/ConfigExtensions/`)

## Создание расширения

### Шаг 1: Регистрация в админке (рекомендуемый способ)

1. Перейдите в админку → список **"Расширения"** (`s_Extensions`)
2. Создайте новую запись с параметрами:
   - **Name**: `MyExtension` - имя расширения (имя класса)
   - **FileExt**: `jpg,png,pdf` - разрешенные расширения файлов для загрузки
   - **Lists**: `["MyTable"]` - связанные таблицы данных
   - **Создать файлы расширения** - выбрать шаблон структуры:
     - `1.0` - простая структура (копируется из `_Example10/`)
     - `1.1` - расширенная структура для работы со списками (копируется из `_Example11/`)

3. При сохранении записи автоматически срабатывает триггер, который:
   - Создает папку `WeppsExtensions/MyExtension/`
   - Копирует файлы из выбранного шаблона (`_Example10` или `_Example11`)
   - Переименовывает классы и файлы под указанное название

4. Привяжите расширение к разделу через `s_Navigator` (поле `Extension`)

**Шаблоны структуры:**
- **`_Example10`** - минималистичная структура (основной класс + шаблон)
- **`_Example11`** - расширенная структура для работы со списками (Data, CRUD, фильтры)

### Шаг 2: Структура файлов расширения

```
WeppsExtensions/MyExtension/
├── MyExtension.php           # Основной класс с логикой
├── Request.php               # AJAX-обработчик (опционально, для асинхронных запросов)
├── MyExtension.tpl           # Smarty шаблон для списка
├── MyExtensionItem.tpl       # Smarty шаблон для детальной страницы (опционально)
├── MyExtension.css           # Стили
├── MyExtension.js            # JavaScript
├── RequestFilters.tpl       # Шаблон для AJAX-ответа (если есть Request.php)
├── RequestFilters.tpl.css   # Стили для AJAX-шаблона
├── RequestFilters.tpl.js    # JS для AJAX-шаблона
└── files/                    # Статические файлы (изображения, документы)
```

**Соглашения по именованию:**
- **Основные файлы** - совпадают с именем класса: `MyExtension.php`, `MyExtension.tpl`, `MyExtension.css`
- **AJAX-шаблоны** - префикс `Request*`: `RequestFilters.tpl` для асинхронной подгрузки

### Шаг 3: Основной класс

`MyExtension.php`:

```php
<?php
namespace WeppsExtensions\MyExtension;

use WeppsCore\Extension;
use WeppsCore\Data;
use WeppsCore\Smarty;

class MyExtension extends Extension {
    
    /**
     * Основной метод обработки запроса
     */
    public function request() {
        // Получение данных из БД
        $data = new Data('MyTable');
        $items = $data->fetch('t.IsActive=1', 10, $this->page, 't.Priority DESC');
        
        // Получение Smarty и передача данных
        $smarty = Smarty::getSmarty();
        $smarty->assign('items', $items);
        $smarty->assign('title', 'Заголовок страницы');
        $smarty->assign('paginator', $data->paginator);
        
        // Подключение стилей и скриптов (короткий путь /ext/ + версионирование)
        $this->headers->css("/ext/MyExtension/MyExtension.{$this->rand}.css");
        $this->headers->js("/ext/MyExtension/MyExtension.{$this->rand}.js");
        
        // Установка шаблона (полный путь от корня)
        $this->tpl = 'packages/WeppsExtensions/MyExtension/MyExtension.tpl';
    }
}
```

### Шаг 4: Шаблон

`MyExtension.tpl`:

```smarty
<div class="my-extension">
    <h1>{$title}</h1>
    
    <div class="items-list">
        {foreach $items as $item}
            <div class="item">
                <h2>{$item.Name}</h2>
                <p>{$item.Description}</p>
                
                {* Вывод изображений *}
                {if $item.Images}
                    <img src="{$item.Images[0].FileUrl}" alt="{$item.Name}">
                {/if}
            </div>
        {foreachelse}
            <p>Нет данных для отображения</p>
        {/foreach}
    </div>
</div>
```

### Шаг 6: Привязка к разделу

1. В админке откройте **Навигация → s_Navigator**
2. Создайте или отредактируйте раздел:
   - **Url**: `/my-section`
   - **Extension**: выберите `MyExtension`
3. Теперь раздел доступен по адресу: `/?weppsurl=my-section`

## Доступные свойства Extension

### $this->headers

Управление CSS/JS файлами:

```php
// Подключить CSS (короткий путь /ext/ + версионирование)
$this->headers->css("/ext/MyExtension/MyExtension.{$this->rand}.css");
$this->headers->css('https://cdn.example.com/style.css'); // Внешний

// Подключить JS (короткий путь /ext/ + версионирование)
$this->headers->js("/ext/MyExtension/MyExtension.{$this->rand}.js");
$this->headers->js("/ext/MyExtension/MyExtension.{$this->rand}.js", 'defer'); // С атрибутом defer
```

**Подключение файлов из других расширений:**

```php
// Можно подключать CSS/JS из других расширений при необходимости
$this->headers->css("/ext/Template/Template.{$this->rand}.css");
$this->headers->js("/ext/Cart/Cart.{$this->rand}.js");

// Внешние библиотеки
$this->headers->css('https://cdn.example.com/library.css');
$this->headers->js('https://cdn.jsdelivr.net/npm/library@1.0.0/dist/library.min.js');
```

**Минификация и версионирование:**

Минификация CSS/JS файлов настраивается в конфигурации:

```php
// В config.php
'Services' => [
    'minify' => [
        'active' => true,   // Включить минификацию (true/false)
        'lifetime' => 300,  // Время жизни минифицированных файлов в секундах
    ],
]
```

**Как работает минификация:**

- **`minify.active` => true**: 
  - Все CSS/JS файлы собираются в один файл
  - Минифицируются через библиотеку MatthiasMullie\Minify
  - Сохраняются в `/files/tpl/minify/{hash}` где hash = MD5 от всех подключенных файлов
  - Встраиваются inline в HTML через `<style>` и `<script>` теги
  - Пересоздаются при истечении `lifetime` или изменении набора файлов

- **`minify.active` => false**: 
  - Файлы подключаются отдельными ссылками
  - Версионирование через `{$this->rand}` для предотвращения кэширования браузером

**Режим отладки (`debug`):**
- **`debug` => 1**: `{$this->rand}` меняется при каждом запросе (удобно для разработки)
- **`debug` => 0**: `{$this->rand}` стабильный (для production)

Пример результата в HTML:
```html
<!-- minify.active = true: -->
<style>/* минифицированный CSS всех файлов */</style>
<script type="text/javascript">/* минифицированный JS всех файлов */</script>

<!-- minify.active = false: -->
<link rel="stylesheet" href="/ext/MyExtension/MyExtension.1234567890.css">
<script src="/ext/MyExtension/MyExtension.1234567890.js"></script>
```

### $this->navigator

Объект текущего раздела навигации (Navigator):

```php
// Текущий раздел
$current = $this->navigator->path;
echo $current['Name'];  // Название
echo $current['Url'];   // URL

// Родительские разделы
$parent = $this->navigator->parent;

// Дочерние разделы текущего раздела
$child = $this->navigator->child;

// Путь до текущего раздела (хлебные крошки)
$way = $this->navigator->way;

// Контент раздела (дополнительные поля из s_Navigator)
$content = $this->navigator->content;
```

### Текущий пользователь

Доступ к данным пользователя через глобальный объект Connect:

```php
use WeppsCore\Users;
use WeppsCore\Connect;

// Проверка авторизации (выполняется один раз при инициализации)
// На платформе этот вызов уже выполнен в configloader.php:
// $users = new Users();
// $users->getAuth();  // Проверяет JWT токен и устанавливает Connect::$projectData['user']

// Доступ к данным пользователя (если авторизован)
if (!empty(Connect::$projectData['user'])) {
    $user = Connect::$projectData['user'];
    echo $user['Login'];
    echo $user['Email'];
    
    // Проверка прав (1=Администратор, 2=Редактор, 3=Посетитель)
    if ($user['UserPermissions'] == 1) {
        // Администратор
    }
}
```

**Важно:** 
- Платформа **не использует PHP сессии (SESSION)** для аутентификации
- Авторизация реализована через **JWT токены**, которые хранятся в cookie (`wepps_token`) или передаются в Bearer заголовке
- `getAuth()` уже вызывается автоматически при загрузке платформы в `configloader.php`
- В расширениях просто проверяйте наличие `Connect::$projectData['user']`

### Smarty

Для работы с шаблонами получайте объект через статический метод:

```php
use WeppsCore\Smarty;

$smarty = Smarty::getSmarty();

// Передать одну переменную
$smarty->assign('var', $value);

// Передать несколько переменных (массив)
$smarty->assign([
    'products' => $products,
    'paginator' => $paginator,
    'totalCount' => $totalCount
]);

// Получить HTML без вывода (для вложенных шаблонов)
$html = $smarty->fetch('path/to/template.tpl');

// Можно использовать шаблоны из других расширений
$html = $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl');
```

## Работа с данными

### Класс Data

Класс `Data` - это высокоуровневая обёртка над PDO для работы с базой данных. Это **не ORM**, а удобный, безопасный и очевидный способ выполнения типовых операций с данными: выборка с автоматическими JOIN по схеме, управление файлами, пагинация, CRUD-операции.

```php
use WeppsCore\Data;
use WeppsCore\Smarty;

$data = new Data('Products');

// Простая выборка (без автоматических JOIN)
$products = $data->fetchmini('IsActive=1 AND Price>1000', 20, $this->page, 'Priority DESC');

// Выборка с JOIN по схеме полей (автоматически подтягивает связи и файлы)
$products = $data->fetch('t.IsActive=1 AND t.Price>1000', 20, $this->page, 't.Priority DESC');

// Настройка запроса перед fetch()
$data->setFields('Id,Name,Price');  // Только нужные поля
$data->setJoin('LEFT JOIN Brands b ON b.Id = t.BrandId');
$products = $data->fetch('t.IsActive=1');

// Пагинация доступна после fetch/fetchmini
$paginator = $data->paginator;  // ['current' => 1, 'pages' => [1,2,3], ...]
$totalCount = $data->count;     // Общее количество

// Создание записи
$id = $data->add([
    'Name' => 'Новый товар',
    'Price' => 1500,
    'IsActive' => 1
]);

// Обновление записи
$data->set($id, [
    'Name' => 'Обновленное название',
    'Price' => 2000
]);

// Удаление записи (и связанных файлов)
$data->remove($id);

// Передача в шаблон
$smarty = Smarty::getSmarty();
$smarty->assign('products', $products);
$smarty->assign('paginator', $data->paginator);
```

### Прямые SQL-запросы

Для сложных запросов, которые нельзя реализовать через класс Data:

```php
use WeppsCore\Connect;

// 1. Через Connect::$instance->fetch() - высокоуровневая обёртка с кэшированием
// Рекомендуется для SELECT запросов с JOIN - автоматически кэширует в memcached (если активно)
$products = Connect::$instance->fetch("
    SELECT p.Id, p.Name, COUNT(o.Id) as OrdersCount
    FROM Products p
    LEFT JOIN Orders o ON p.Id = o.ProductId
    WHERE p.IsActive = ? AND p.Price > ?
    GROUP BY p.Id
    HAVING OrdersCount > ?
", [1, 1000, 10]);

// 2. Через Connect::$db - прямой PDO (prepared statements)
// Используйте для INSERT/UPDATE/DELETE и когда не нужно кэширование

// INSERT
$sth = Connect::$db->prepare("
    INSERT INTO Products (Name, Price, IsActive) 
    VALUES (?, ?, ?)
");
$sth->execute(['Товар', 1500, 1]);
$newId = Connect::$db->lastInsertId();

// UPDATE
$sth = Connect::$db->prepare("
    UPDATE Products SET Price = ? WHERE Id = ?
");
$sth->execute([2000, 15]);

// DELETE
$sth = Connect::$db->prepare("DELETE FROM Products WHERE Id = ?");
$sth->execute([15]);

// SELECT через PDO (если не нужно кэширование)
$sth = Connect::$db->prepare("
    SELECT * FROM Products WHERE Category = ? ORDER BY Priority DESC
");
$sth->execute(['Electronics']);
$products = $sth->fetchAll(\PDO::FETCH_ASSOC);
```

**Важно:** 
- **Класс `Data`** - для стандартных SELECT/INSERT/UPDATE/DELETE с автоматической работой с файлами
- **`Connect::$instance->fetch()`** - для сложных SELECT (GROUP BY, HAVING) с автоматическим кэшированием в memcached
- **`Connect::$db`** - прямой PDO для prepared statements, когда не нужно кэширование или для модификации данных

**Безопасность:**
- **Всегда используйте prepared statements** (подготовленные запросы) с плейсхолдерами `?` или именованными параметрами
- **Никогда не подставляйте** пользовательские данные напрямую в SQL через конкатенацию строк
- Prepared statements автоматически **защищают от SQL-инъекций**, экранируя параметры на уровне драйвера БД
- Все три метода (`Data`, `Connect::$instance`, `Connect::$db`) используют prepared statements и безопасны

```php
// ❌ ОПАСНО - SQL-инъекция!
$id = $_GET['id'];
Connect::$db->query("SELECT * FROM Products WHERE Id = $id");

// ✅ БЕЗОПАСНО - prepared statement с параметрами
$id = $_GET['id'];
$sth = Connect::$db->prepare("SELECT * FROM Products WHERE Id = ?");
$sth->execute([$id]);
```

### Frontend часть (JS)

`MyExtension.js`:

```javascript
/**
 * Инициализация расширения MyExtension
 * 
 * Этот паттерн удобен тем, что:
 * - Изолирует логику в отдельной функции
 * - Легко тестировать и дебажить
 * - Можно переиспользовать функцию в других местах
 * - Избегает глобального загрязнения пространства имён
 * 
 * Использование .off().on():
 * - .off('event') снимает все старые обработчики события перед назначением новых
 * - Предотвращает дублирование обработчиков при повторной инициализации
 * - Обеспечивает безопасную переинициализацию расширения
 */
var readyMyExtensionInit = function () {
    // jQuery доступен глобально в проекте через $
    
    // Загрузка элементов через layoutWepps.request()
    $('.load-items').off('click').on('click', function() {
        const page = $(this).data('page') || 1;
        
        layoutWepps.request({
            url: '/ext/MyExtension/Request.php',
            data: 'action=load-items&page=' + page,
            obj: $('.items-container')  // Результат вставляется в этот элемент
        });
    });
    
    // Добавление элемента с обработкой результата
    $('.add-item-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        const name = $(this).find('[name="name"]').val();
        
        layoutWepps.request({
            url: '/ext/MyExtension/Request.php',
            data: 'action=add-item&name=' + name
            // Без параметра obj - результат обрабатывается автоматически
        });
    });
    
    // Удаление элемента
    $('.delete-btn').off('click').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        
        layoutWepps.request({
            url: '/ext/MyExtension/Request.php',
            data: 'action=delete-item&id=' + id
        });
    });
    
    // Сохранение с указанием контейнера для результата
    $('.save-item').off('click').on('click', function() {
        const id = $(this).data('id');
        const title = $('#item-title').val();
        
        layoutWepps.request({
            url: '/ext/MyExtension/Request.php',
            data: 'action=save-item&id=' + id + '&title=' + title,
            obj: $('#result-container')  // Результат вставится в этот элемент
        });
    });
    
    // Открытие модального окна с AJAX-содержимым
    $('.open-modal-btn').off('click').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        layoutWepps.modal({
            size: 'medium',  // small, medium, large
            url: '/ext/MyExtension/Request.php',
            data: 'action=item-details&id=' + id
        });
    });
    
    // Модальное окно с существующим контентом
    $('.show-info').off('click').on('click', function(e) {
        e.preventDefault();
        
        layoutWepps.modal({
            size: 'small',
            content: $('#info-block')  // DOM-элемент для отображения
        });
    });
};

// Вызов инициализации при загрузке DOM
$(document).ready(readyMyExtensionInit);
```

**Рекомендуемый паттерн структуры:**

- **Именование функции**: `ready` + название расширения + `Init` (например, `readyMyExtensionInit`)
- **Изоляция логики**: вся логика инициализации помещается в отдельную функцию
- **Вызов через `$(document).ready()`**: обеспечивает выполнение после загрузки DOM
- **Переиспользование**: функцию можно вызвать повторно при необходимости (например, после динамической подгрузки контента)
- **Использование `.off().on()`**: предотвращает дублирование обработчиков событий при повторной инициализации

**Важно:** 
- Всегда используйте `.off('event').on('event', handler)` - это снимает старые обработчики перед назначением новых и предотвращает дублирование при повторной инициализации
- Для динамически создаваемых элементов (которых нет в DOM на момент инициализации) используйте делегирование через `$(document).on('event', '.selector', handler)`

**API платформы `layoutWepps`:**

```javascript
// layoutWepps.request() - AJAX-запрос с автоматической обработкой
// Loader отображается автоматически во время выполнения запроса
layoutWepps.request({
    url: '/ext/MyExtension/Request.php',      // URL для запроса
    data: 'action=load&id=1',                  // Данные (строка или объект)
    obj: $('#target-element')                  // Опционально: элемент для вставки результата
});

// layoutWepps.modal() - открытие модального окна
layoutWepps.modal({
    size: 'medium',                            // small, medium, large
    url: '/ext/MyExtension/Request.php',      // URL для загрузки содержимого
    data: 'action=details&id=1',               // Данные запроса
    content: $('#existing-element')            // Или готовый DOM-элемент
});

// layoutWepps.remove() - закрытие текущего модального окна
layoutWepps.remove();
```

**Параметры `layoutWepps.request()`:**
- **`url`** (обязательно) - адрес Request.php расширения
- **`data`** - данные запроса (строка формата `key=value&key2=value2` или объект)
- **`obj`** - jQuery-элемент для вставки результата HTML-ответа
- **Loader** - отображается автоматически на время выполнения запроса

**Параметры `layoutWepps.modal()`:**
- **`size`** - размер модального окна: `'small'`, `'medium'`, `'large'`
- **`url`** + **`data`** - для загрузки содержимого через AJAX
- **`content`** - jQuery-элемент с готовым содержимым

## Guard Clause паттерн

Используйте ранний `return` для валидации:

```php
use WeppsCore\Users;
use WeppsCore\Connect;
use WeppsCore\Smarty;

public function request() {
    // Проверка авторизации (getAuth вызывается один раз при инициализации приложения)
    // Здесь только проверяем наличие данных пользователя
    if (empty(Connect::$projectData['user'])) {
        $smarty = Smarty::getSmarty();
        $smarty->assign('message', 'Требуется авторизация');
        $this->tpl = 'packages/WeppsExtensions/MyExtension/Unauthorized.tpl';
        return;
    }
    
    $user = Connect::$projectData['user'];
    
    // Проверка прав (1=Администратор, 2=Редактор, 3=Посетитель)
    if ($user['UserPermissions'] > 1) {
        $smarty = Smarty::getSmarty();
        $smarty->assign('message', 'Недостаточно прав');
        $this->tpl = 'packages/WeppsExtensions/MyExtension/Forbidden.tpl';
        return;
    }
    
    // Основной код выполняется только если все проверки пройдены
    $this->tpl = 'packages/WeppsExtensions/MyExtension/MyExtension.tpl';
}
```

## Обработка AJAX запросов

Для обработки асинхронных HTTP/AJAX запросов создается файл `Request.php` в папке расширения.

### Структура Request.php

`Request.php` - это отдельная точка входа для HTTP/AJAX запросов, которая:
- Загружает конфигурацию платформы через `configloader.php`
- Наследуется от класса `WeppsCore\Request`
- Обрабатывает различные действия (actions) через `switch`
- Может подключать CSS/JS и использовать шаблоны
- Возвращает HTML или JSON ответы

### Пример Request.php

`packages/WeppsExtensions/MyExtension/Request.php`:

```php
<?php
require_once '../../../configloader.php';

use WeppsCore\Smarty;
use WeppsCore\Request;
use WeppsCore\Exception;

class RequestMyExtension extends Request
{
    public function request($action = "")
    {
        switch ($action) {
            case 'load-items':
                $this->loadItems();
                break;
            case 'add-item':
                $this->addItem();
                break;
            case 'delete-item':
                $this->deleteItem();
                break;
            default:
                Exception::error404();
                break;
        }
    }
    
    /**
     * Загрузка элементов
     */
    private function loadItems()
    {
        // Установка шаблона для ответа
        $this->tpl = 'RequestItems.tpl';
        
        // Получение данных
        $data = new Data('MyTable');
        $items = $data->fetch('t.IsActive=1', 10, $this->get['page'] ?? 1);
        
        // Передача данных в шаблон через метод assign()
        $this->assign('items', $items);
        $this->assign('paginator', $data->paginator);
    }
    
    /**
     * Добавление элемента
     */
    private function addItem()
    {
        if (empty($this->get['name'])) {
            Exception::error(400);
        }
        
        $data = new Data('MyTable');
        $id = $data->add([
            'Name' => $this->get['name'],
            'IsActive' => 1
        ]);
        
        // JSON ответ
        echo json_encode([
            'status' => 200,
            'id' => $id,
            'message' => 'Элемент добавлен'
        ]);
        exit();
    }
    
    /**
     * Удаление элемента
     */
    private function deleteItem()
    {
        if (empty($this->get['id'])) {
            Exception::error(400);
        }
        
        $data = new Data('MyTable');
        $data->remove($this->get['id']);
        
        // JSON ответ
        echo json_encode([
            'status' => 200,
            'message' => 'Элемент удален'
        ]);
        exit();
    }
}

// Инициализация и выполнение запроса
$request = new RequestMyExtension($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);
```

### Шаблон для AJAX-ответа

`packages/WeppsExtensions/MyExtension/RequestItems.tpl`:

```smarty
{foreach $items as $item}
    <div class="item" data-id="{$item.Id}">
        <h3>{$item.Name}</h3>
        <button class="delete-btn" data-id="{$item.Id}">Удалить</button>
    </div>
{/foreach}

{if $paginator}
    <div class="pagination">
        {foreach $paginator.pages as $page}
            <a href="#" data-page="{$page}">{$page}</a>
        {/foreach}
    </div>
{/if}

{* Вставка CSS/JS - обычно размещается в конце *}
{$get.cssjs}
```

**Системные переменные в AJAX-шаблонах:**

- **`{$get}`** - объект класса Request с доступом к параметрам запроса и системным свойствам:
  - `{$get.action}` - текущее действие (action из параметра запроса)
  - `{$get.id}`, `{$get.page}` - параметры из GET/POST запроса
  - `{$get.cssjs}` - переменная для вставки автоматически подключенных CSS/JS

- **`{$get.cssjs}`** - вставляет теги `<style>` и `<script>` с содержимым файлов `RequestItems.tpl.css` и `RequestItems.tpl.js`. Рекомендуется размещать в конце шаблона для удобства.

```smarty
{* Пример с использованием параметров запроса *}
<div class="items-page-{$get.page}">
    {* Ваш контент *}
</div>

{* Вставка стилей и скриптов (обычно в конце) *}
{$get.cssjs}
```

**Автоматическое подключение стилей и скриптов:**

Если в папке расширения создать файлы с тем же именем, что и шаблон, но с суффиксами `.css` и `.js`:
- `RequestItems.tpl.css` - стили для шаблона
- `RequestItems.tpl.js` - скрипты для шаблона

То платформа **автоматически подхватит и подключит** эти файлы при рендере шаблона `RequestItems.tpl`. Явное подключение через `$this->headers` не требуется.

```
WeppsExtensions/MyExtension/
├── Request.php
├── RequestItems.tpl       # Шаблон
├── RequestItems.tpl.css   # Стили (подключаются автоматически)
└── RequestItems.tpl.js    # Скрипты (подключаются автоматически)
```

### Явное подключение стилей и скриптов в Request.php

Если автоматическое подключение через `.tpl.css` и `.tpl.js` не подходит, можно явно подключить CSS/JS файлы через `$this->headers`:

```php
class RequestMyExtension extends Request
{
    public function request($action = "")
    {
        // Явное подключение стилей и скриптов
        $this->headers->css("/ext/MyExtension/RequestItems.{$this->rand}.css");
        $this->headers->js("/ext/MyExtension/RequestItems.{$this->rand}.js");
        
        switch ($action) {
            case 'load-items':
                $this->loadItems();
                break;
            // ...
        }
    }
}
```

### Типы ответов

**HTML ответ (через шаблон):**
```php
private function loadItems()
{
    $this->tpl = 'RequestItems.tpl';
    $this->assign('items', $items);
    // Ответ отдается автоматически через $smarty->display($request->tpl)
}
```

**JSON ответ:**
```php
private function addItem()
{
    // Ваша логика
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 200,
        'data' => $result
    ]);
    exit(); // Важно! Прерываем выполнение для JSON
}
```

**Прямой HTML ответ:**
```php
private function loadItems()
{
    $html = '<div>Мой контент</div>';
    echo $html;
    exit();
}
```

## Примеры расширений

Реальные примеры реализации расширений смотрите в папке `packages/WeppsExtensions/`:
- **Products** - каталог товаров с детальными страницами
- **News** - новости и статьи
- **Gallery** - галерея изображений
- **Cart** - корзина покупок
- **Profile** - личный кабинет пользователя
- **Services** - услуги
- **Contacts** - контактная информация
- **Brands** - бренды

## Отладка

### Режим debug

В `config.php` установите `'debug' => 1`:

```php
'Dev' => [
    'debug' => 1,
]
```

### Логирование

Для отладки используйте метод `Utils::debug()`:

```php
use WeppsCore\Utils;

// Вывод переменной в HTML-блоке (по умолчанию)
Utils::debug($data);

// Режимы вывода:
Utils::debug($data, 0);  // HTML-блок без закрытия соединения (по умолчанию)
Utils::debug($data, 1);  // HTML-блок с закрытием соединения
Utils::debug($data, 3);  // Сырой текст на экран
Utils::debug($data, 31); // Сырой текст и закрытие соединения

// Запись в файл:
Utils::debug($data, 2);  // Перезапись файла debug.conf
Utils::debug($data, 21); // Перезапись и закрытие соединения
Utils::debug($data, 22); // Дописать в конец файла

// Запись в кастомный файл (по умолчанию пишется в /debug.conf):
Utils::debug($data, 2, __DIR__ . '/my-debug.log');

// Альтернативные способы:
error_log("Debug info: " . print_r($data, true));
echo '<script>console.log(' . json_encode($data) . ');</script>';
```

**Возможности `Utils::debug()`:**
- Автоматическое добавление времени и места вызова (файл и строка) при `'debug' => 1` в конфиге
- Форматированный вывод в зелёном блоке с прокруткой
- Запись в файл с возможностью перезаписи или дополнения
- Опциональное закрытие соединения после вывода

### Smarty debug

```php
// В шаблоне
{debug}
```

## Лучшие практики

1. **Используйте prepared statements** для всех SQL-запросов
2. **Валидируйте входные данные** перед использованием
3. **Применяйте Guard Clause** для ранних выходов
4. **Кэшируйте дорогие операции** (DB запросы, API вызовы)
5. **Используйте транзакции** для связанных операций с БД
6. **Создавайте отдельные методы** для логики вместо большого `request()`
7. **Документируйте код** через PHPDoc комментарии

## Дополнительные материалы

- [Архитектура платформы](architecture.md)
- [Справочник API](api-reference.md)
- [Схема базы данных](database-schema.md)
