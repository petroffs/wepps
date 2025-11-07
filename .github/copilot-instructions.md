# Инструкции для AI-агентов - Wepps Platform

## Архитектура платформы

**Wepps** - модульная PHP-платформа для создания веб-сайтов и REST API с использованием Smarty-шаблонизации и MySQL.

### Основные компоненты

- **`WeppsCore/`** - ядро системы (навигация, подключение к БД, пользователи, шаблоны)
- **`WeppsAdmin/`** - административная панель управления контентом
- **`WeppsExtensions/`** - расширения функционала (Template, Products, Cart, News и др.)

### Структура базы данных

#### Системные таблицы (префикс `s_`)

**`s_Navigator`** - основная таблица навигации:
- `Id` - уникальный идентификатор раздела
- `Name` - название раздела
- `Url` - URL-адрес раздела
- `ParentDir` - ID родительского раздела
- `Template` - имя шаблона
- `Extension` - ID расширения
- `LanguageId` - ID языка
- `DisplayOff` - флаг видимости (0=виден, 1=скрыт)

**`s_Extensions`** - зарегистрированные расширения:
- `Id`, `Name` - идентификатор и название расширения
- `FileExt` - расширения файлов для загрузки
- `Lists` - связанные списки данных

**`s_ConfigExtensions`** -  системные расширения:
- `Id`, `Name`, `Alias` - базовая информация
- `ENav` - навигационные элементы расширения

**`s_Config`** - конфигурация таблиц данных:
- `Id` - уникальный идентификатор
- `TableName` - имя таблицы
- `Name` - название таблицы
- `Category` - категория
- `Priority` - приоритет сортировки
- `DisplayOff` - флаг видимости

**`s_ConfigFields`** - поля конфигурации:
- `TableName` - имя таблицы
- `Field` - имя поля
- `Type` - тип поля (text, select, checkbox и т.д.)
- `Required` - обязательность поля

**`s_Users`** - пользователи системы:
- `Id`, `Login`, `Password` - аутентификация
- `UserPermissions` - уровень прав доступа
- `JCart`, `JFav` - JSON-данные корзины и избранного

**`s_Files`** - файловое хранилище:
- `Id`, `Name` - базовая информация
- `InnerName` - внутреннее имя файла
- `TableName`, `TableNameId` - связь с таблицей и записью
- `FileUrl` - URL доступа к файлу

**Контентные таблицы:**
- `Products` - товары
- `News` - новости
- `Gallery` - галерея изображений
- `Contacts` - контактная информация
- `Brands` - бренды
- `DataTbls` - тестовая таблица

### Ключевые паттерны



#### 1. Наследование от Extension
Все расширения наследуются от `WeppsCore\Extension`. Структура:
```php
class MyExtension extends Extension {
    public function request() {
        // Логика обработки запроса
    }
}
```

#### 2. Конфигурация через массивы
Настройки в `config.php` организованы иерархически:
```php
$projectSettings = [
    'DB' => [...],      // Подключение к MySQL
    'Dev' => [...],     // Базовые настройки проекта
    'Info' => [...],    // Информация о проекте
    'Services' => [...] // Настройки сервисов
];
```

#### 3. Навигация через Navigator
Класс `Navigator` управляет маршрутизацией и контентом:
- `$navigator->path` - текущий раздел
- `$navigator->content` - данные раздела
- `$navigator->parent/child` - иерархия разделов

#### 4. Шаблонизация Smarty
- Шаблоны в `*.tpl` файлах
- Переменные передаются через `$smarty->assign()`
- CSS/JS подключаются через `$this->headers->css()` / `$this->headers->js()`

#### 5. Глобальный экземпляр Connect
Класс `Connect` предоставляет глобальный экземпляр `Connect::$instance` - центральный объект для работы с базой данных через PDO:
- `Connect::$instance->db` - PDO-объект для выполнения SQL-запросов
- `Connect::$instance->memcached` - объект Memcached для кэширования
- Все методы для работы с БД доступны через этот экземпляр
- Используется для всех операций с базой данных в платформе

#### 6. Ранний return для обработки ошибок (Guard Clause)
Используйте паттерн "guard clause" для улучшения читаемости кода:
- Сначала проверяйте условия ошибок и выполняйте ранний `return` с соответствующим статусом
- Избегайте глубоких вложений `if-else`
- Основной успешный код должен идти в конце метода без `else`

Пример:
```php
// Неправильно (глубокая вложенность)
if ($condition) {
    // много кода
} else {
    return ['status' => 400, 'message' => 'Error'];
}

// Правильно (guard clause)
if (!$condition) {
    return ['status' => 400, 'message' => 'Error'];
}
// основной код
```

### Рабочие процессы

#### Установка и настройка
```bash
# 1. Настройка config.php
# 2. Создание БД MySQL
mysql -u root -p -e "CREATE DATABASE db_name CHARACTER SET utf8mb4;"

# 3. Установка зависимостей
cd packages && composer install

# 4. Запуск установщика
php install.php
```

#### Обновления платформы
```bash
# Проверка версии
php packages/WeppsAdmin/Updates/Request.php version

# Просмотр доступных обновлений
php packages/WeppsAdmin/Updates/Request.php list

# Установка обновления
php packages/WeppsAdmin/Updates/Request.php update [tag]
```

#### Разработка расширений
1. Создать папку в `WeppsExtensions/`
2. Реализовать класс, наследующий от `Extension`
3. Добавить `Request.php` для обработки запросов
4. Создать `*.tpl` шаблоны в подпапке

### Соглашения по коду

#### Структура файлов расширения
```
MyExtension/
├── MyExtension.php      # Основной класс
├── Request.php          # Файл обработки запросов
├── Request.tpl          # Шаблон
├── Request.css          # Стили
├── Request.js           # JavaScript
└── files/              # Статические файлы
```

#### Работа с базой данных
```php
// Через Connect (глобальный экземпляр)
$result = Connect::$db->query("SELECT * FROM table");

// Подготовленные запросы
$sth = Connect::$db->prepare("SELECT * FROM table WHERE id = ?");
$sth->execute([$id]);
```

#### Кэширование
```php
// Memcached через Connect
Connect::$memcached->set('key', $data, $ttl);
$data = Connect::$memcached->get('key');
```

### Отладка и разработка

#### Режим разработки
В `config.php` установить `'debug' => 1` для:
- Отключения кэширования CSS/JS
- Детального логирования ошибок
- Рандомизации версий файлов

#### Структура URL
- Frontend: `/?weppsurl=path/to/page`
- Admin: `/admin/` с отдельной навигацией

### Важные замечания

- **Безопасность**: Никогда не коммитить `config.*` с реальными паролями
- **Обновления**: Всегда создавать бэкапы перед обновлением платформы
- **Расширения**: Новые расширения создаются автоматически из адимнки (список `s_Extensions`) в `WeppsExtensions/`
- **Кастомные расширения для фронтенда**: размещать в `WeppsExtensions/Addons`
- **Кастомные расширения для админки**: создаются автоматически из админки (список `s_ConfigExtensions`) в `WeppsAdmin/ConfigExtensions/`
- **База данных**: Использовать utf8mb4 для поддержки Unicode