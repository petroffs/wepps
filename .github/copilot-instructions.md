# Инструкции для AI-агентов - Wepps Platform

## Архитектура платформы

**Wepps** - модульная PHP-платформа для создания веб-сайтов и REST API с использованием Smarty-шаблонизации и MySQL.

### Основные компоненты

- **`WeppsCore/`** - ядро системы (навигация, подключение к БД, пользователи, шаблоны)
- **`WeppsAdmin/`** - административная панель управления контентом
- **`WeppsExtensions/`** - расширения функционала (Template, Products, Cart, News и др.)

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
    'Dev' => [...],     // Режим разработки
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
├── Request.php          # Основной класс
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

- **Безопасность**: Никогда не коммитить `config.cnf` с реальными паролями
- **Обновления**: Всегда создавать бэкапы перед обновлением платформы
- **Расширения**: Новые модули размещать в `WeppsExtensions/`
- **База данных**: Использовать utf8mb4 для поддержки Unicode</content>
<parameter name="filePath">d:\var\home\wepps.platform\.github\copilot-instructions.md