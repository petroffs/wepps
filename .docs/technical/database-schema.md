# Схема базы данных

## Обзор

База данных Wepps использует MySQL/MariaDB с кодировкой **utf8mb4** для полной поддержки Unicode.

Таблицы делятся на две категории:
- **Системные** (префикс `s_`) - управляют структурой и конфигурацией
- **Контентные** (без префикса) - хранят данные сайта

## Системные таблицы

### s_Navigator

Основная таблица навигации и структуры сайта.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Уникальный идентификатор раздела |
| `Name` | VARCHAR(255) | Название раздела (отображается в меню) |
| `Url` | VARCHAR(255) | URL-путь раздела (например, `/catalog/products`) |
| `ParentDir` | INT | ID родительского раздела (0 для корневых) |
| `Template` | VARCHAR(100) | Имя шаблона (для Template расширения) |
| `Extension` | INT | ID расширения из `s_Extensions` |
| `LanguageId` | INT | ID языка (для мультиязычности) |
| `IsHidden` | TINYINT(1) | Видимость раздела (0=виден, 1=скрыт) |
| `Priority` | INT | Порядок сортировки в меню |
| `MetaTitle` | TEXT | SEO: заголовок страницы |
| `MetaDescription` | TEXT | SEO: описание |
| `MetaKeywords` | TEXT | SEO: ключевые слова |

**Связи:**
- `ParentDir` → `s_Navigator.Id` (иерархия разделов)
- `Extension` → `s_Extensions.Id` (привязка функционала)

**Пример:**
```sql
INSERT INTO s_Navigator (Name, Url, ParentDir, Extension, IsHidden, Priority) 
VALUES ('Каталог', '/catalog', 0, 5, 0, 10);

-- Вложенный раздел
INSERT INTO s_Navigator (Name, Url, ParentDir, Extension, IsHidden, Priority) 
VALUES ('Товары', '/catalog/products', 1, 5, 0, 1);
```

---

### s_Extensions

Зарегистрированные расширения для разделов сайта.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор расширения |
| `Name` | VARCHAR(100) | Название расширения (класс в WeppsExtensions/) |
| `FileExt` | VARCHAR(255) | Разрешенные расширения файлов для загрузки |
| `Lists` | TEXT | JSON-массив связанных таблиц данных |
| `Config` | TEXT | JSON-конфигурация расширения |

**Пример:**
```sql
INSERT INTO s_Extensions (Name, FileExt, Lists) 
VALUES (
    'Products', 
    'jpg,png,webp,pdf',
    '["Products","Brands"]'
);
```

**Создание нового расширения:**
1. Создается запись в `s_Extensions` через админку
2. Автоматически создается папка `WeppsExtensions/{Name}/`
3. Привязывается к разделу через `s_Navigator.Extension`

---

### s_ConfigExtensions

Системные расширения для административной панели.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Name` | VARCHAR(100) | Название расширения |
| `Alias` | VARCHAR(100) | Алиас для URL админки |
| `ENav` | TEXT | JSON-массив навигационных элементов |
| `Icon` | VARCHAR(50) | CSS-класс иконки |

**Пример:**
```sql
INSERT INTO s_ConfigExtensions (Name, Alias, Icon) 
VALUES ('Настройки сайта', 'settings', 'fa-cog');
```

**Расположение:** `WeppsAdmin/ConfigExtensions/{Name}/`

---

### s_Config

Конфигурация таблиц данных для админки.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `TableName` | VARCHAR(100) | Имя таблицы в БД |
| `Name` | VARCHAR(255) | Отображаемое название |
| `Category` | VARCHAR(100) | Категория группировки в меню |
| `Priority` | INT | Порядок сортировки |
| `IsHidden` | TINYINT(1) | Скрыть из меню админки |

**Пример:**
```sql
INSERT INTO s_Config (TableName, Name, Category, Priority) 
VALUES ('Products', 'Товары', 'Каталог', 10);
```

---

### s_ConfigFields

Настройки полей таблиц для автогенерации форм в админке.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `TableName` | VARCHAR(100) | Имя таблицы |
| `Field` | VARCHAR(100) | Имя поля |
| `Name` | VARCHAR(255) | Отображаемое название поля |
| `Type` | VARCHAR(50) | Тип поля: `text`, `textarea`, `select`, `checkbox`, `file`, `date` |
| `Required` | TINYINT(1) | Обязательное поле |
| `Priority` | INT | Порядок в форме |
| `Options` | TEXT | JSON-опции для select/checkbox |

**Типы полей:**
- `text` - текстовое поле
- `textarea` - многострочный текст
- `wysiwyg` - визуальный редактор
- `select` - выпадающий список
- `checkbox` - чекбокс
- `file` - загрузка файлов
- `date` - дата
- `number` - числовое поле

**Пример:**
```sql
INSERT INTO s_ConfigFields (TableName, Field, Name, Type, Required, Priority) 
VALUES ('Products', 'Name', 'Название товара', 'text', 1, 1);

INSERT INTO s_ConfigFields (TableName, Field, Name, Type, Options, Priority) 
VALUES ('Products', 'CategoryId', 'Категория', 'select', '{"source":"Categories","value":"Id","label":"Name"}', 2);
```

---

### s_Users

Пользователи системы.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор пользователя |
| `Login` | VARCHAR(100) UNIQUE | Логин для входа |
| `Password` | VARCHAR(255) | Хеш пароля (password_hash) |
| `Email` | VARCHAR(255) | Email адрес |
| `UserPermissions` | INT | Уровень прав (0=admin, 1=manager, 2=user) |
| `JCart` | TEXT | JSON-данные корзины |
| `JFav` | TEXT | JSON-данные избранного |
| `CreatedAt` | DATETIME | Дата регистрации |
| `LastLogin` | DATETIME | Последний вход |

**Уровни прав:**
- `0` - Администратор (полный доступ)
- `1` - Менеджер (управление контентом)
- `2` - Пользователь (только frontend)

**Пример:**
```sql
-- Создание администратора
INSERT INTO s_Users (Login, Password, Email, UserPermissions) 
VALUES ('admin', '$2y$10$...', 'admin@example.com', 0);
```

---

### s_Files

Файловое хранилище (загруженные файлы, изображения).

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор файла |
| `Name` | VARCHAR(255) | Оригинальное имя файла |
| `InnerName` | VARCHAR(255) | Внутреннее имя (уникальное) |
| `TableName` | VARCHAR(100) | К какой таблице привязан |
| `TableNameId` | INT | ID записи в таблице |
| `FileUrl` | VARCHAR(500) | Путь к файлу |
| `FileType` | VARCHAR(50) | MIME-тип файла |
| `FileSize` | INT | Размер файла в байтах |
| `Priority` | INT | Порядок сортировки |
| `UploadedAt` | DATETIME | Дата загрузки |

**Связи:**
- `TableName` + `TableNameId` → связь с любой контентной таблицей

**Пример:**
```sql
INSERT INTO s_Files (Name, InnerName, TableName, TableNameId, FileUrl) 
VALUES ('product.jpg', 'abc123.jpg', 'Products', 15, '/files/lists/Products/abc123.jpg');
```

**Структура хранения:**
- Оригиналы: `/files/lists/{TableName}/`
- Превью: `/pic/preview/files/{InnerName}`
- Средние: `/pic/medium/files/{InnerName}`

---

## Контентные таблицы

### Products (Товары)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор товара |
| `Name` | VARCHAR(255) | Название товара |
| `Url` | VARCHAR(255) | ЧПУ для товара |
| `Description` | TEXT | Описание товара |
| `Price` | DECIMAL(10,2) | Цена |
| `OldPrice` | DECIMAL(10,2) | Старая цена (для скидок) |
| `Article` | VARCHAR(100) | Артикул |
| `BrandId` | INT | ID бренда |
| `IsActive` | TINYINT(1) | Активность товара |
| `IsHit` | TINYINT(1) | Хит продаж |
| `IsNew` | TINYINT(1) | Новинка |
| `Priority` | INT | Порядок сортировки |
| `CreatedAt` | DATETIME | Дата создания |

**Связи:**
- `BrandId` → `Brands.Id`

---

### News (Новости)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор новости |
| `Name` | VARCHAR(255) | Заголовок |
| `Url` | VARCHAR(255) | ЧПУ |
| `Description` | TEXT | Краткое описание |
| `Content` | TEXT | Полное содержание |
| `PublishedAt` | DATETIME | Дата публикации |
| `IsActive` | TINYINT(1) | Активность |
| `Priority` | INT | Порядок |

---

### Gallery (Галерея)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Name` | VARCHAR(255) | Название альбома/фото |
| `Description` | TEXT | Описание |
| `Priority` | INT | Порядок |
| `IsActive` | TINYINT(1) | Активность |

---

### Contacts (Контакты)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Type` | VARCHAR(50) | Тип контакта (phone, email, address) |
| `Value` | VARCHAR(255) | Значение |
| `Label` | VARCHAR(255) | Описание |
| `Priority` | INT | Порядок |

---

### Brands (Бренды)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Name` | VARCHAR(255) | Название бренда |
| `Url` | VARCHAR(255) | ЧПУ |
| `Description` | TEXT | Описание бренда |
| `IsActive` | TINYINT(1) | Активность |
| `Priority` | INT | Порядок |

---

## Общие паттерны

### Стандартные поля

Большинство контентных таблиц содержат:

- `Id` - PRIMARY KEY AUTO_INCREMENT
- `Name` - название/заголовок
- `Url` - ЧПУ (человекопонятный URL)
- `Description` - описание
- `IsActive` - флаг активности (0/1)
- `Priority` - порядок сортировки
- `CreatedAt` - дата создания

### Связь с файлами

Файлы привязываются через таблицу `s_Files`:

```sql
-- Получить файлы товара
SELECT * FROM s_Files 
WHERE TableName = 'Products' AND TableNameId = 15
ORDER BY Priority;
```

### Мультиязычность

Для мультиязычных полей используется суффикс `_{LanguageId}`:

```sql
ALTER TABLE Products 
ADD COLUMN Name_en VARCHAR(255),
ADD COLUMN Description_en TEXT;
```

## SQL-примеры

### Создание контентной таблицы

```sql
CREATE TABLE MyTable (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Url VARCHAR(255),
    Description TEXT,
    IsActive TINYINT(1) DEFAULT 1,
    Priority INT DEFAULT 0,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Регистрация в админке

```sql
-- Добавить в список таблиц
INSERT INTO s_Config (TableName, Name, Category, Priority) 
VALUES ('MyTable', 'Моя таблица', 'Контент', 50);

-- Настроить поля
INSERT INTO s_ConfigFields (TableName, Field, Name, Type, Required, Priority) VALUES
('MyTable', 'Name', 'Название', 'text', 1, 1),
('MyTable', 'Description', 'Описание', 'wysiwyg', 0, 2),
('MyTable', 'IsActive', 'Активно', 'checkbox', 0, 3);
```

## Индексы и оптимизация

### Рекомендуемые индексы

```sql
-- Навигация
CREATE INDEX idx_parent ON s_Navigator(ParentDir);
CREATE INDEX idx_url ON s_Navigator(Url);

-- Файлы
CREATE INDEX idx_table_files ON s_Files(TableName, TableNameId);

-- Контентные таблицы
CREATE INDEX idx_active ON Products(IsActive);
CREATE INDEX idx_priority ON Products(Priority);
CREATE INDEX idx_url ON Products(Url);
```

### Полнотекстовый поиск

```sql
ALTER TABLE Products ADD FULLTEXT idx_search (Name, Description);

-- Использование
SELECT * FROM Products 
WHERE MATCH(Name, Description) AGAINST('поисковый запрос' IN BOOLEAN MODE);
```

## Миграции

При обновлении структуры БД создавайте миграции:

```php
// packages/WeppsAdmin/Updates/migrations/001_add_field.php
Connect::$db->exec("ALTER TABLE Products ADD COLUMN NewField VARCHAR(255)");
```

## Дополнительные материалы

- [Архитектура платформы](architecture.md)
- [Справочник API](api-reference.md)
- [Разработка расширений](extensions-development.md)
