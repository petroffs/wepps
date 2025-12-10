# Разработка расширений

## Что такое расширение?

**Расширение (Extension)** - это модуль функциональности, который можно привязать к разделу сайта через таблицу `s_Navigator`. Расширения находятся в `packages/WeppsExtensions/`.

## Типы расширений

1. **Frontend расширения** - для разделов сайта (`WeppsExtensions/`)
2. **Кастомные аддоны** - специфичные для проекта (`WeppsExtensions/Addons/`)
3. **Админские расширения** - для админки (`WeppsAdmin/ConfigExtensions/`)

## Создание расширения

### Шаг 1: Регистрация в админке

1. Войдите в админку: `/_wepps/`
2. Перейдите в **Списки → s_Extensions**
3. Создайте новую запись:
   - **Name**: `MyExtension` (имя класса)
   - **FileExt**: `jpg,png,pdf` (разрешенные файлы)
   - **Lists**: `["MyTable"]` (связанные таблицы)

При сохранении автоматически создастся папка `WeppsExtensions/MyExtension/`.

### Шаг 2: Структура файлов

```
WeppsExtensions/MyExtension/
├── MyExtension.php      # Основной класс
├── Request.php          # Точка входа
├── Request.tpl          # Smarty шаблон
├── Request.css          # Стили (опционально)
├── Request.js           # JavaScript (опционально)
└── files/              # Статические файлы
```

### Шаг 3: Основной класс

`MyExtension.php`:

```php
<?php
namespace WeppsExtensions\MyExtension;

use WeppsCore\Extension;
use WeppsCore\Data;

class MyExtension extends Extension {
    
    /**
     * Основной метод обработки запроса
     */
    public function request() {
        // Получение данных из БД
        $data = new Data('MyTable');
        $items = $data->getList([
            'where' => ['IsActive' => 1],
            'orderBy' => 'Priority DESC',
            'limit' => 10
        ]);
        
        // Передача данных в шаблон
        $this->smarty->assign('items', $items);
        $this->smarty->assign('title', 'Заголовок страницы');
        
        // Подключение стилей и скриптов
        $this->headers->css(__DIR__ . '/Request.css');
        $this->headers->js(__DIR__ . '/Request.js');
        
        // Рендеринг шаблона
        $this->smarty->display(__DIR__ . '/Request.tpl');
    }
}
```

### Шаг 4: Точка входа

`Request.php`:

```php
<?php
require_once __DIR__ . '/MyExtension.php';

use WeppsExtensions\MyExtension\MyExtension;

// Создание и запуск расширения
$extension = new MyExtension();
$extension->request();
```

### Шаг 5: Шаблон

`Request.tpl`:

```smarty
<div class="my-extension">
    <h1>{$title}</h1>
    
    <div class="items-list">
        {foreach $items as $item}
            <div class="item">
                <h2>{$item.Name}</h2>
                <p>{$item.Description}</p>
                
                {* Вывод изображений *}
                {if $item.files}
                    <img src="{$item.files[0].FileUrl}" alt="{$item.Name}">
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

### $this->smarty

Объект Smarty для работы с шаблонами:

```php
// Передать переменную
$this->smarty->assign('var', $value);

// Отрендерить шаблон
$this->smarty->display(__DIR__ . '/Template.tpl');

// Получить HTML без вывода
$html = $this->smarty->fetch(__DIR__ . '/Template.tpl');
```

### $this->headers

Управление CSS/JS файлами:

```php
// Подключить CSS
$this->headers->css(__DIR__ . '/style.css');
$this->headers->css('https://cdn.example.com/style.css'); // Внешний

// Подключить JS
$this->headers->js(__DIR__ . '/script.js');
$this->headers->js(__DIR__ . '/script.js', 'defer'); // С атрибутом defer

// В режиме debug=1 файлы не минифицируются и версионируются рандомно
```

### $this->navigator

Объект текущего раздела навигации:

```php
// Текущий раздел
$current = $this->navigator->path;
echo $current['Name'];  // Название
echo $current['Url'];   // URL

// Родительский раздел
$parent = $this->navigator->parent;

// Дочерние разделы
$childs = $this->navigator->childs;

// Контент раздела (дополнительные поля из s_Navigator)
$content = $this->navigator->content;
```

### $this->user

Текущий пользователь (если авторизован):

```php
if ($this->user) {
    echo $this->user['Login'];
    echo $this->user['Email'];
    
    // Проверка прав
    if ($this->user['UserPermissions'] == 0) {
        // Администратор
    }
}
```

## Работа с данными

### Класс Data

```php
use WeppsCore\Data;

$data = new Data('Products');

// Получить список
$products = $data->getList([
    'where' => [
        'IsActive' => 1,
        'Price >' => 1000
    ],
    'orderBy' => 'Priority DESC, Name ASC',
    'limit' => 20,
    'offset' => 0
]);

// Получить по ID (с файлами)
$product = $data->getById(15);
// $product['files'] - массив файлов из s_Files

// Сохранить/создать
$id = $data->save([
    'Id' => null,  // null для создания нового
    'Name' => 'Новый товар',
    'Price' => 1500
]);

// Удалить
$data->delete(15);
```

### Прямые SQL-запросы

```php
use WeppsCore\Connect;

// SELECT через PDO
$sth = Connect::$db->prepare("
    SELECT p.*, b.Name as BrandName 
    FROM Products p
    LEFT JOIN Brands b ON p.BrandId = b.Id
    WHERE p.IsActive = ? AND p.Price > ?
    ORDER BY p.Priority DESC
");
$sth->execute([1, 1000]);
$products = $sth->fetchAll(\PDO::FETCH_ASSOC);

// INSERT через PDO
$sth = Connect::$db->prepare("
    INSERT INTO Products (Name, Price, IsActive) 
    VALUES (?, ?, ?)
");
$sth->execute(['Товар', 1500, 1]);
$newId = Connect::$db->lastInsertId();

// UPDATE через PDO
$sth = Connect::$db->prepare("
    UPDATE Products SET Price = ? WHERE Id = ?
");
$sth->execute([2000, 15]);

// DELETE через PDO
$sth = Connect::$db->prepare("DELETE FROM Products WHERE Id = ?");
$sth->execute([15]);

// ИЛИ через обертку Connect::$instance
$products = Connect::$instance->fetch("
    SELECT p.*, b.Name as BrandName 
    FROM Products p
    LEFT JOIN Brands b ON p.BrandId = b.Id
    WHERE p.IsActive = ? AND p.Price > ?
    ORDER BY p.Priority DESC
", [1, 1000]);
```

## Обработка AJAX запросов

### Frontend часть (JS)

`Request.js`:

```javascript
document.querySelector('.add-to-cart').addEventListener('click', function() {
    fetch('/api/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            productId: 15,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 200) {
            alert('Товар добавлен в корзину');
        }
    });
});
```

### Backend часть (PHP)

Создайте `Api.php` в папке расширения:

```php
<?php
namespace WeppsExtensions\MyExtension;

use WeppsCore\Users;
use WeppsCore\Connect;

class Api {
    
    public function addToCart() {
        // Получение POST данных
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Валидация
        if (!isset($input['productId'])) {
            return ['status' => 400, 'message' => 'productId required'];
        }
        
        // Проверка авторизации
        $user = Users::getCurrent();
        if (!$user) {
            return ['status' => 401, 'message' => 'Unauthorized'];
        }
        
        // Логика добавления в корзину
        $cart = json_decode($user['JCart'], true) ?: [];
        $cart[] = [
            'productId' => $input['productId'],
            'quantity' => $input['quantity'] ?? 1
        ];
        
        // Сохранение
        $sth = Connect::$db->prepare("
            UPDATE s_Users SET JCart = ? WHERE Id = ?
        ");
        $sth->execute([json_encode($cart), $user['Id']]);
        
        return ['status' => 200, 'message' => 'Added to cart'];
    }
}

// Роутинг API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new Api();
    $action = $_GET['action'] ?? '';
    
    $result = match($action) {
        'add' => $api->addToCart(),
        default => ['status' => 404, 'message' => 'Not found']
    };
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
```

## Guard Clause паттерн

Используйте ранний `return` для валидации:

```php
public function request() {
    // Проверка авторизации
    if (!$this->user) {
        $this->smarty->display(__DIR__ . '/Unauthorized.tpl');
        return;
    }
    
    // Проверка прав
    if ($this->user['UserPermissions'] > 1) {
        $this->smarty->display(__DIR__ . '/Forbidden.tpl');
        return;
    }
    
    // Основной код выполняется только если все проверки пройдены
    $this->smarty->display(__DIR__ . '/Request.tpl');
}
```

## Работа с файлами

### Загрузка файлов

```php
use WeppsCore\Utils;

if ($_FILES['image']) {
    $file = $_FILES['image'];
    
    // Генерация уникального имени
    $innerName = Utils::generateFileName($file['name']);
    
    // Путь сохранения
    $uploadDir = __DIR__ . '/../../files/lists/Products/';
    $uploadPath = $uploadDir . $innerName;
    
    // Перемещение файла
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Сохранение в БД
        $sth = Connect::$db->prepare("
            INSERT INTO s_Files (Name, InnerName, TableName, TableNameId, FileUrl)
            VALUES (?, ?, ?, ?, ?)
        ");
        $sth->execute([
            $file['name'],
            $innerName,
            'Products',
            $productId,
            '/files/lists/Products/' . $innerName
        ]);
    }
}
```

### Получение файлов записи

```php
$data = new Data('Products');
$product = $data->getById(15);

// Файлы автоматически загружаются в поле 'files'
foreach ($product['files'] as $file) {
    echo $file['FileUrl'];      // URL файла
    echo $file['Name'];         // Оригинальное имя
    echo $file['InnerName'];    // Внутреннее имя
}
```

## Кэширование

### Memcached

```php
use WeppsCore\Connect;

// Используйте Connect::$instance->fetch() - он автоматически кэширует join-запросы
$products = Connect::$instance->fetch("
    SELECT p.*, b.Name as BrandName 
    FROM Products p 
    LEFT JOIN Brands b ON p.BrandId = b.Id 
    WHERE p.IsActive = 1
");
// Первый запрос - из БД, сохраняется в memcached
// Последующие - из кэша

$this->smarty->assign('products', $products);
```

### Smarty кэш

Кэширование на уровне шаблонов:

```php
// Включить кэширование шаблона
$this->smarty->caching = 1;
$this->smarty->cache_lifetime = 3600; // 1 час

$cacheId = 'products_' . $categoryId;

if (!$this->smarty->isCached('Request.tpl', $cacheId)) {
    // Загрузка данных только если кэш не валиден
    $products = (new Data('Products'))->getList();
    $this->smarty->assign('products', $products);
}

$this->smarty->display('Request.tpl', $cacheId);
```

## Примеры расширений

### Простой каталог

```php
class Catalog extends Extension {
    public function request() {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 12;
        
        $data = new Data('Products');
        $products = $data->getList([
            'where' => ['IsActive' => 1],
            'orderBy' => 'Priority DESC',
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage
        ]);
        
        // Подсчет общего количества
        $sth = Connect::$db->query("
            SELECT COUNT(*) FROM Products WHERE IsActive = 1
        ");
        $total = $sth->fetchColumn();
        
        $this->smarty->assign([
            'products' => $products,
            'page' => $page,
            'totalPages' => ceil($total / $perPage)
        ]);
        
        $this->headers->css(__DIR__ . '/Request.css');
        $this->smarty->display(__DIR__ . '/Request.tpl');
    }
}
```

### Детальная страница товара

```php
class ProductDetail extends Extension {
    public function request() {
        // ID товара из URL: /?weppsurl=product/15
        $urlParts = explode('/', $_GET['weppsurl'] ?? '');
        $productId = (int)end($urlParts);
        
        if (!$productId) {
            header('Location: /catalog');
            exit;
        }
        
        $data = new Data('Products');
        $product = $data->getById($productId);
        
        if (!$product) {
            header('HTTP/1.0 404 Not Found');
            $this->smarty->display(__DIR__ . '/404.tpl');
            return;
        }
        
        $this->smarty->assign('product', $product);
        $this->smarty->display(__DIR__ . '/Detail.tpl');
    }
}
```

## Отладка

### Режим debug

В `config.php` установите `'debug' => 1`:

```php
'Dev' => [
    'debug' => 1,
]
```

### Логирование

```php
// Логирование в файл
error_log("Debug info: " . print_r($data, true));

// Вывод в консоль браузера
echo '<script>console.log(' . json_encode($data) . ');</script>';
```

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
