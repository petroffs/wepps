# Архитектура платформы

## Обзор

**Wepps** - модульная PHP-платформа для создания веб-сайтов и REST API. Основана на принципах:
- Модульность через систему расширений
- Единая точка входа (Front Controller)
- Шаблонизация через Smarty
- Маршрутизация через Navigator
- ORM-подобный доступ к данным

## Структура проекта

```
wepps/
├── index.php              # Точка входа для frontend
├── config.php             # Конфигурация (не в git)
├── install.php            # Скрипт установки
├── _wepps/                # Админка (отдельная точка входа)
├── packages/              # Основной код платформы
│   ├── WeppsCore/        # Ядро системы
│   ├── WeppsAdmin/       # Административная панель
│   ├── WeppsExtensions/  # Расширения функционала
│   ├── vendor/           # Зависимости Composer
│   └── vendor_local/     # Сторонние библиотеки (не из Composer)
├── files/                 # Загруженные файлы
│   ├── lists/            # Файлы по таблицам данных
│   └── tpl/              # Smarty кэш и компиляция
└── pic/                   # Изображения (оригиналы и ресайзы)
```

## Основные компоненты

### 1. WeppsCore - Ядро системы

Базовые классы и функциональность (описаны ключевые, полный список см. в `packages/WeppsCore/`):

#### **Connect.php**
Центральный класс для подключения к БД, сервисам и хранения конфигурации:

**PDO и обертка:**
```php
Connect::$db                  // Прямой доступ к PDO
Connect::$instance            // Обертка с удобными методами:
                              //   - fetch()    - выборка с кэшированием
                              //   - query()    - выполнение запроса
                              //   - insert()   - вставка с auto-Priority
                              //   - prepare()  - преобразование данных
                              //   - cached()   - управление кэшированием
```

**Конфигурация из config.php:**
```php
Connect::$projectInfo         // Info секция (название, версия и т.д.)
Connect::$projectDev          // Dev секция (debug, http, adminUrl и т.д.)
Connect::$projectDB           // DB секция (host, port, dbname, user и т.д.)
Connect::$projectServices     // Services секция (memcached, wepps и т.д.)
Connect::$projectData         // Временные данные (инициализируется как [])
```

**Пример использования конфигурации:**
```php
// Проверка режима отладки
if (Connect::$projectDev['debug'] == 1) {
    // Детальное логирование
}

// Получение Email разработчика проекта
$adminEmail = Connect::$projectDev['email'];

// Настройки Memcached
$memcachedHost = Connect::$projectServices['memcached']['host'];
$memcachedActive = Connect::$projectServices['memcached']['active'];
```

#### **Navigator.php**
Управление навигацией и маршрутизацией:
```php
$navigator->path           // Текущий раздел (объект)
Navigator::$pathItem       // Раздел сайта/ITEM.html (статическое свойство)
$navigator->content        // Данные раздела из s_Navigator
$navigator->parent         // Подразделы родительского уровня
$navigator->child          // Подразделы текущего раздела
$navigator->way            // Путь до текущего раздела (хлебные крошки)
$navigator->nav            // Навигация верхнего уровня
```

#### **Extension.php**
Базовый класс для всех расширений:
```php
abstract class Extension {
    // Абстрактный метод (обязателен к реализации)
    abstract public function request();     // Обработка запроса расширения
    
    // Основные свойства
    public $navigator;                      // Навигатор (Navigator)
    public $headers;                        // Управление CSS/JS (TemplateHeaders)
    public $get;                            // Входные данные (массив)
    public $page;                           // Текущая страница пагинации
    public $rand;                           // Случайное число для версионирования
    public $tpl;                            // Наименование шаблона
    public $targetTpl;                      // Содержание шаблона (по умолчанию 'extension')
    public $extensionData;                  // Флаги для Template (например, ['element'=>1] 
                                            // указывает, что обработана детальная страница)
    
    // Вспомогательный метод
    public function getItem($tableName, $condition = ''); // Получить элемент для детальной страницы
}
```

#### **Data.php**
ORM-подобная работа с таблицами БД:
```php
$data = new Data('Products');

// Основные методы выборки
$items = $data->fetchmini($conditions, $onPage, $currentPage, $orderBy);  // Простая выборка
$items = $data->fetch($conditions, $onPage, $currentPage, $orderBy);      // С JOIN по схеме полей

// CRUD операции
$id = $data->add($row, $insertOnly);           // Добавление записи
$data->set($id, $row, $settings);              // Обновление записи
$data->remove($id);                            // Удаление записи

// Настройки запроса
$data->setFields('Id,Name,Price');             // Выбрать только определённые поля
$data->setConcat('CONCAT(Name, Price) total'); // Дополнительные поля
$data->setJoin('LEFT JOIN Brands...');         // Кастомные JOIN
$data->setParams([1, 'active']);               // Параметры для prepared statements
$data->setGroup('t.CategoryId');               // GROUP BY
$data->setHaving('COUNT(*) > 5');              // HAVING

// Свойства после выборки
$data->count;                                  // Количество записей
$data->paginator;                              // Данные пагинации
$data->sql;                                    // Сформированный SQL (для отладки)
```

#### **Users.php**
Управление пользователями и аутентификацией:
```php
$users = new Users(['login' => $login, 'password' => $password]);

// Авторизация пользователя
if ($users->signIn()) {
    // Успешный вход, JWT токен установлен в cookie
} else {
    $errors = $users->errors();  // ['login' => '...'] или ['password' => '...']
}

// Проверка аутентификации (из JWT токена в cookie или Bearer заголовке)
if ($users->getAuth()) {
    // Пользователь авторизован
    $currentUser = Connect::$projectData['user'];  // Данные пользователя из s_Users
}

// Выход из системы (удаление токена)
$users->removeAuth();

// Генерация случайного пароля
$newPassword = $users->password();  // Например: "aT5k-2!mN"
```

### 2. WeppsAdmin - Административная панель

Модули администрирования:

- **Admin/** - Главная страница админки
- **Lists/** - Управление списками данных
- **NavigatorAd/** - Управление навигацией
- **ConfigExtensions/** - Системные расширения
- **Updates/** - Система обновлений

### 3. WeppsExtensions - Расширения

Функциональные модули:

- **Template/** - Базовые шаблоны и структура
- **Products/** - Каталог товаров
- **Cart/** - Корзина покупок
- **News/** - Новости и статьи
- **Gallery/** - Галерея изображений
- **Profile/** - Личный кабинет
- **Addons/** - Кастомные расширения для frontend

## Жизненный цикл запроса

### Frontend запрос

```
1. index.php
   ↓
2. configloader.php → загружает config.php
   ↓
3. Connect → подключение к БД и сервисам
   ↓
4. Navigator → определение текущего раздела из URL
   ↓
5. Template Extension → инициализация структуры страницы
   ↓
6. Content Extension → загрузка расширения раздела (Products, News и т.д.)
   ↓
7. Extension->request() → обработка бизнес-логики, установка $this->tpl
   ↓
8. Template → сборка финальной страницы (header, content, footer)
   ↓
9. Smarty → рендеринг собранного шаблона с данными
   ↓
10. HTML ответ
```

### Admin запрос

```
1. _wepps/index.php
   ↓
2. configloader.php → загружает config.php (Memcached отключен для админки)
   ↓
3. Admin класс → инициализация, получение URL
   ↓
4. Проверка авторизации → Connect::$projectData['user']['ShowAdmin']
   ↓
5. Определение модуля по URL → Home/Lists/NavigatorAd/ConfigExtensions
   ↓
6. Подключение библиотек → jQuery, Select2, Bootstrap Icons
   ↓
7. Загрузка модуля → new WeppsAdmin\{Module}\{Module}
   ↓
8. Обработка AJAX/POST запросов в модуле
   ↓
9. Smarty → рендеринг Admin.tpl с данными модуля
   ↓
10. JSON/HTML ответ
```

## Паттерны и соглашения

### 1. Наследование Extension

Все расширения наследуются от `WeppsCore\Extension` для:
- **Доступа к базовым методам** - `getItem()` для детальных страниц
- **Доступа к свойствам** - `$navigator`, `$headers`, `$get`, `$page`
- **Возможности переопределения** - метод `getItem()` можно перегрузить для кастомной логики
- **Единообразия архитектуры** - все расширения работают по одному паттерну

```php
namespace WeppsExtensions\Products;

use WeppsCore\Extension;

class Products extends Extension {
    public function request() {
        // Для детальной страницы (если есть /catalog/item.html)
        if (Navigator::$pathItem != '') {
            $product = $this->getItem('Products', 't.IsActive=1');
            $smarty = Smarty::getSmarty();
            $smarty->assign('product', $product);
            $this->tpl = 'packages/WeppsExtensions/Products/ProductsItem.tpl';
            return;
        }
        
        // Для списка товаров
        $data = new Data('Products');
        $products = $data->fetch('t.IsActive=1', 20, $this->page, 't.Priority DESC');
        
        // Получение Smarty и передача данных
        $smarty = Smarty::getSmarty();
        $smarty->assign('products', $products);
        $smarty->assign('paginator', $data->paginator);
        
        // Подключение стилей/скриптов (короткий путь /ext/ + версионирование)
        $this->headers->css("/ext/Products/Products.{$this->rand}.css");
        $this->headers->js("/ext/Products/Products.{$this->rand}.js");
        
        // Установка шаблона (полный путь от корня)
        $this->tpl = 'packages/WeppsExtensions/Products/Products.tpl';
    }
}
```

### 2. Структура файлов расширения

```
MyExtension/
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
- **AJAX-шаблоны** - префикс `Request*`: `RequestMyFilters.tpl` для асинхронной подгрузки

### 3. Guard Clause для обработки ошибок

Используйте ранний `return` для валидации:

```php
// ❌ Плохо (глубокая вложенность)
if ($user) {
    if ($permission) {
        // много кода
    } else {
        return ['status' => 403];
    }
} else {
    return ['status' => 401];
}

// ✅ Хорошо (guard clause)
if (!$user) {
    return ['status' => 401, 'message' => 'Unauthorized'];
}

if (!$permission) {
    return ['status' => 403, 'message' => 'Forbidden'];
}

// основной код
```

### 4. Работа с базой данных

#### Через PDO (прямые запросы):
```php
use WeppsCore\Connect;

// SELECT через PDO
$sth = Connect::$db->prepare("SELECT * FROM Products WHERE Id = ?");
$sth->execute([$id]);
$product = $sth->fetch(\PDO::FETCH_ASSOC);

// INSERT через PDO
$sth = Connect::$db->prepare("INSERT INTO Products (Name, Price) VALUES (?, ?)");
$sth->execute([$name, $price]);
$newId = Connect::$db->lastInsertId();
```

#### Через Connect::$instance (обертка с удобными методами):
```php
use WeppsCore\Connect;

// SELECT с автоматическим кэшированием (join-запросы)
$products = Connect::$instance->fetch(
    "SELECT p.*, b.Name as BrandName FROM Products p LEFT JOIN Brands b ON p.BrandId = b.Id WHERE p.IsActive = ?",
    [1]
);

// INSERT с автоматическим Priority
$id = Connect::$instance->insert('Products', [
    'Name' => 'Новый товар',
    'Price' => 1500,
    'IsActive' => 1
]);

// UPDATE через query()
$prepare = Connect::$instance->prepare([
    'Name' => 'Обновленное название',
    'Price' => 2000
]);
Connect::$instance->query(
    "UPDATE Products SET {$prepare['update']} WHERE Id = ?",
    array_merge($prepare['row'], [$id])
);
```

#### Через класс Data (ORM-подобный):

**Важно:** Класс `Data` работает на основе метаданных из системных таблиц:
- `s_Config` - описание таблиц БД
- `s_ConfigFields` - описание полей таблиц (типы, связи, валидация)

Метод `fetch()` автоматически строит JOIN-запросы на основе типов полей (`select`, `remote`, `file`), определённых в `s_ConfigFields`. Использовать `setJoin()` нужно только для кастомных связей, не описанных в метаданных.

**Кэширование:** Если Memcached включен, все запросы с JOIN автоматически кэшируются (ключ = md5 от SQL + параметров).

```php
use WeppsCore\Data;

$data = new Data('Products');

// Простая выборка (без автоматических JOIN)
$products = $data->fetchmini("IsActive=1 AND Price>100", 20, 1, "Priority DESC");

// Выборка с JOIN по схеме полей (автоматически подтягивает связанные таблицы и файлы)
$products = $data->fetch("t.IsActive=1", 20, 1, "t.Priority DESC");

// Настройка запроса
$data->setFields('Id,Name,Price');  // Выбрать только нужные поля
$data->setJoin('LEFT JOIN Brands b ON b.Id = t.BrandId');
$products = $data->fetch("t.IsActive=1");

// Добавление записи
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

// Пагинация (доступна после fetch/fetchmini)
$paginator = $data->paginator;  // ['current' => 1, 'pages' => [1,2,3], 'next' => 2, ...]
$totalCount = $data->count;     // Общее количество записей
```

### 5. Шаблонизация Smarty

```smarty
{* Переменные *}
{$product.Name}
{$product.Price|number_format:0:',':' '} ₽

{* Циклы *}
{foreach $products as $product}
    <div class="product">
        <h3>{$product.Name}</h3>
        <p>{$product.Price} ₽</p>
    </div>
{/foreach}

{* Условия *}
{if $user}
    Привет, {$user.Login}!
{else}
    <a href="/login">Войти</a>
{/if}
```

## База данных

### Системные таблицы (префикс `s_`)

- **s_Navigator** - структура разделов сайта
- **s_Extensions** - зарегистрированные расширения
- **s_ConfigExtensions** - системные расширения админки
- **s_Config** - конфигурация таблиц данных
- **s_ConfigFields** - настройки полей таблиц
- **s_Users** - пользователи системы
- **s_Files** - файловое хранилище

### Контентные таблицы

- **Products** - товары
- **News** - новости
- **Gallery** - галерея
- **Contacts** - контакты
- **Brands** - бренды
- и другие (создаются через админку)

## Кэширование

### Memcached

**Конфигурация** в `config.php`:
```php
'Services' => [
    'memcached' => [
        'active' => true,          // Включить/выключить кэширование
        'host' => 'localhost',
        'port' => 11211,
        'expire' => 3600           // Время жизни кэша по умолчанию (секунды)
    ]
]
```

**Автоматическое кэширование:**
```php
use WeppsCore\Connect;

// fetch() автоматически кэширует join-запросы (если memcached активен)
$products = Connect::$instance->fetch(
    "SELECT p.*, b.Name FROM Products p LEFT JOIN Brands b ON p.BrandId = b.Id"
);
// Первый вызов - из БД, сохраняется в memcached
// Последующие - из кэша (ключ = md5(sql + params))
```

**Управление кэшированием:**
```php
// Отключить кэширование для конкретных запросов
Connect::$instance->cached('no');
$data = Connect::$instance->fetch("SELECT * FROM Products");

// Включить обратно
Connect::$instance->cached('yes');

// Автоматический режим (из конфига)
Connect::$instance->cached('auto');
```

**Прямой доступ к Memcached** (через внутреннее свойство):
```php
// Класс WeppsCore\Memcached предоставляет методы:
// - set($key, $value, $expire)
// - get($key)
// - delete($key)
// Доступен внутри Connect::$instance->memcached (приватное свойство)
```

### Smarty кэш

Автоматический кэш шаблонов в `files/tpl/compile/`

## Безопасность

### Аутентификация

- JWT токены для API
- Session + cookies для веб-интерфейса
- Хеширование паролей через `password_hash()`

### Валидация данных

```php
use WeppsCore\Validator;

// Все методы статические, возвращают '' при успехе или сообщение об ошибке
$errors = [];

// Проверка на непустоту
$errors['name'] = Validator::isNotEmpty($_POST['name'], 'Имя обязательно');

// Проверка email
$errors['email'] = Validator::isEmail($_POST['email'], 'Некорректный email');

// Проверка URL
$errors['site'] = Validator::isUrl($_POST['site'], 'Некорректный URL');

// Проверка телефона (10 цифр)
$errors['phone'] = Validator::isPhone($_POST['phone'], 'Телефон должен содержать 10 цифр');

// Проверка целого числа
$errors['age'] = Validator::isInt2($_POST['age'], 'Возраст должен быть числом');

// Проверка даты
$errors['date'] = Validator::isDate($_POST['date'], 'Некорректная дата');

// Другие методы: isFloat(), isString(), isLat(), isGuid(), isBarcode()

// Фильтрация ошибок (оставить только непустые)
$errors = array_filter($errors);

if (!empty($errors)) {
    // Есть ошибки валидации
}
```

### Подготовленные запросы

Всегда используйте prepared statements для защиты от SQL-инъекций:

```php
// ✅ Безопасно
$sth = Connect::$db->prepare("SELECT * FROM Users WHERE Login = ?");
$sth->execute([$login]);

// ❌ Опасно!
$query = "SELECT * FROM Users WHERE Login = '$login'";
```

## Расширение функциональности

### Создание нового расширения

**Через админку (рекомендуемый способ):**

1. Перейти в админку → список "Расширения" (`s_Extensions`)
2. Создать новую запись с параметрами:
   - **Название** - имя расширения (например, `MyExtension`)
   - **Создать файлы расширения** - выбрать шаблон структуры:
     - `1.0` - простая структура (копируется из `_Example10/`)
     - `1.1` - структура для списков (копируется из `_Example11/`)
3. При сохранении записи автоматически срабатывает триггер, который:
   - Создает папку `WeppsExtensions/MyExtension/`
   - Копирует файлы из выбранного шаблона (`_Example10` или `_Example11`)
   - Переименовывает классы и файлы под указанное название
4. Привязать расширение к разделу через `s_Navigator` (поле `Extension`)

**Шаблоны структуры:**
- **`_Example10`** - минималистичная структура (основной класс + шаблон)
- **`_Example11`** - расширенная структура для работы со списками (Data, CRUD, фильтры)

Подробнее: [Разработка расширений](extensions-development.md)

## AI-Ready архитектура

Wepps Platform спроектирована с учётом работы с AI-инструментами (GitHub Copilot, ChatGPT, Claude и др.):

### Почему платформа AI-ready?

1. **Структурированная кодовая база**
   - Чёткие соглашения по именованию (`MyExtension.php`, `MyExtension.tpl`, `MyExtension.js`)
   - Предсказуемые паттерны (наследование от `Extension`, метод `request()`)
   - Организованная структура папок

2. **Полная документация в Markdown**
   - Все аспекты платформы задокументированы в `.docs/`
   - Примеры кода для типовых задач
   - API reference с описанием всех методов

3. **Инструкции для AI-агентов**
   - Файл `.github/copilot-instructions.md` с описанием архитектуры, паттернов и best practices
   - Контекст для GitHub Copilot, Cursor AI и других инструментов
   - Правила генерации кода в стиле платформы

4. **Унифицированная обработка запросов**
   - Стандартизированные AJAX-эндпоинты через `Request.php`
   - Гибкий формат ответов: JSON для данных, HTML для шаблонов (через Smarty)
   - Единый формат обработки действий (`action` параметр)

5. **Типизированная схема БД**
   - Чёткие типы полей в `s_ConfigFields`
   - Автоматическая валидация данных
   - Предсказуемая структура таблиц

### Преимущества для разработчика

- **Быстрая генерация кода** - AI понимает паттерны и может создавать новые расширения по аналогии
- **Автодополнение контекста** - AI предлагает правильные классы, методы и параметры
- **Автоматический рефакторинг** - AI может предложить улучшения с учётом best practices платформы
- **Ускорение разработки** - меньше ручного кода, больше фокуса на бизнес-логику

### Пример работы с AI

AI может:
- Создать новое расширение на основе `_Example11`
- Сгенерировать CRUD-операции для таблицы
- Написать валидацию данных по схеме
- Создать Request.php с обработкой всех actions
- Предложить оптимизации SQL-запросов

Благодаря чёткой архитектуре и документации, AI становится эффективным помощником в разработке на Wepps Platform.

## Дополнительные материалы

- [Установка и настройка](installation.md)
- [Схема базы данных](database-schema.md)
- [Справочник API](api-reference.md)
- [Деплой и обновления](deployment.md)
