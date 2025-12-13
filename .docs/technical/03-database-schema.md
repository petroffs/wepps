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
| `Name` | VARCHAR(128) | Название раздела |
| `Priority` | INT | Порядок сортировки в меню |
| `Text1` | TEXT | Дополнительный текст 1 |
| `Url` | VARCHAR(255) | URL-путь раздела |
| `ParentDir` | INT | ID родительского раздела (1 для корневых) |
| `NGroup` | VARCHAR(128) | Группа раздела |
| `NameMenu` | VARCHAR(255) | Название в меню |
| `Date` | DATETIME | Дата создания/изменения |
| `Images` | INT | Количество изображений |
| `Files` | INT | Количество файлов |
| `MetaKeyword` | VARCHAR(255) | SEO: ключевые слова |
| `MetaDescription` | TEXT | SEO: описание |
| `MetaTitle` | VARCHAR(255) | SEO: заголовок страницы |
| `Template` | VARCHAR(128) | Имя шаблона (для Template расширения) |
| `Extension` | INT | ID расширения из `s_Extensions` |
| `IsHidden` | INT | Видимость раздела (0=виден, 1=скрыт) |
| `LanguageId` | INT | ID языка (для мультиязычности) |
| `TableId` | INT | ID связанной таблицы данных |
| `Text2` | TEXT | Дополнительный текст 2 |
| `UrlMenu` | VARCHAR(255) | URL для меню |
| `DisplayFirst` | INT | Отображать первым |
| `IsBlocksActive` | INT | Активность блоков |

**Связи:**
- `ParentDir` → `s_Navigator.Id` (иерархия разделов)
- `Extension` → `s_Extensions.Id` (привязка функционала)

**Примечание:** Управление разделами осуществляется через админку в разделе «Навигация».

---

### s_Extensions

Зарегистрированные расширения для разделов сайта.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор расширения |
| `Name` | VARCHAR(128) | Название расширения (класс в WeppsExtensions/) |
| `Priority` | INT | Порядок сортировки |
| `FileExt` | VARCHAR(255) | Разрешенные расширения файлов для загрузки |
| `CopyFiles` | DECIMAL(2,1) | Версия структуры файлов (1.0 или 1.1) |
| `Lists` | VARCHAR(255) | Связанные таблицы данных |
| `Descr` | TEXT | Описание расширения |

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
| `Name` | VARCHAR(128) | Название расширения |
| `Alias` | VARCHAR(128) | Алиас для URL админки |
| `IsHidden` | INT | Скрыть из меню (0=виден, 1=скрыт) |
| `Priority` | INT | Порядок сортировки |
| `Descr` | TEXT | Описание расширения |
| `Images` | INT | Количество изображений |
| `Files` | INT | Количество файлов |
| `ENav` | TEXT | JSON-массив навигационных элементов |
| `CopyFiles` | VARCHAR(128) | Настройки копирования файлов |

**Примечание:** Системные расширения создаются через админку в списке «Системные расширения».

**Расположение:** `WeppsAdmin/ConfigExtensions/{Name}/`

---

### s_Config

Конфигурация таблиц данных для админки.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Category` | VARCHAR(128) | Категория группировки в меню |
| `TableName` | VARCHAR(255) UNIQUE | Имя таблицы в БД (уникальное) |
| `Name` | VARCHAR(255) | Отображаемое название |
| `Priority` | INT | Порядок сортировки |
| `ItemsOnPage` | INT | Количество записей на странице |
| `IsOrderBy` | VARCHAR(255) | Поле сортировки по умолчанию |
| `ItemsFields` | VARCHAR(128) | Поля для отображения в списке |
| `ActionModify` | VARCHAR(255) | Действие при изменении записи |
| `ActionDrop` | VARCHAR(255) | Действие при удалении записи |
| `ActionShow` | VARCHAR(255) | Действие при просмотре записи |
| `ActionShowId` | VARCHAR(255) | ID для действия просмотра |

**Создание таблицы:**
При добавлении записи в `s_Config` через админку (раздел «Списки данных») автоматически:
1. Создается новая таблица в БД с базовой структурой
2. Устанавливаются стандартные индексы (PRIMARY KEY, Priority, IsActive и др.)
3. Настраивается utf8mb4 кодировка

---

### s_ConfigFields

Настройки полей таблиц для автогенерации форм в админке.

**Примечание:** Поля можно создавать через системное расширение "Загрузки из Excel" в админке.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `TableName` | VARCHAR(32) | Имя таблицы |
| `Name` | VARCHAR(32) | Отображаемое название поля |
| `Description` | TEXT | Описание поля |
| `Field` | VARCHAR(32) | Имя поля в БД |
| `Priority` | INT | Порядок в форме |
| `Required` | INT | Обязательное поле (0/1) |
| `Type` | VARCHAR(128) | Тип поля: `text`, `textarea`, `select`, `checkbox`, `file`, `date` и др. |
| `CreateMode` | ENUM('','hidden','disabled') | Режим при создании |
| `ModifyMode` | ENUM('','hidden','disabled') | Режим при изменении |
| `IsHidden` | INT | Скрыть поле (0/1) |
| `FGroup` | VARCHAR(255) | Группа полей |

**Типы полей:**
- `text` - текстовое поле
- `textarea` - многострочный текст
- `wysiwyg` - визуальный редактор
- `select` - выпадающий список
- `checkbox` - чекбокс
- `file` - загрузка файлов
- `date` - дата
- `number` - числовое поле

---

### s_Permissions

Права доступа для пользователей в административной панели.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор уровня прав |
| `Name` | VARCHAR(128) | Название уровня прав |
| `Priority` | INT | Порядок сортировки |
| `TableName` | TEXT | Список таблиц, доступных для этого уровня (через запятую) |
| `RightView` | INT | Право просмотра записей (0/1) |
| `RightCreate` | INT | Право создания записей (0/1) |
| `RightDrop` | INT | Право удаления записей (0/1) |
| `RightModify` | INT | Право изменения записей (0/1) |
| `RightAdditional` | VARCHAR(255) | Дополнительные права (например, navigator=1) |
| `SystemExt` | VARCHAR(255) | Доступ к системным расширениям (ID через запятую) |

**Стандартные уровни:**
- `1` - Администратор (полный доступ ко всем таблицам и системным расширениям)
- `2` - Редактор (ограниченный доступ к контентным таблицам)
- `3` - Посетитель сайта (нет доступа в админку)

**Примечание:** Управление уровнями прав осуществляется через админку в списке "Права доступа".

---

### s_Users

Пользователи системы.

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор пользователя |
| `Login` | VARCHAR(64) UNIQUE | Логин для входа |
| `Password` | VARCHAR(255) | Хеш пароля (password_hash) |
| `Name` | VARCHAR(128) | Имя пользователя |
| `Priority` | INT | Порядок сортировки |
| `IsHidden` | INT | Скрыть пользователя (0/1) |
| `ShowAdmin` | INT | Доступ в админку (0/1) |
| `Email` | VARCHAR(32) | Email адрес |
| `UserPermissions` | INT | ID уровня прав из таблицы `s_Permissions` |
| `AuthDate` | DATETIME | Дата последней авторизации |
| `AuthIP` | VARCHAR(32) | IP последней авторизации |
| `Phone` | VARCHAR(32) | Телефон |
| `City` | VARCHAR(32) | Город |
| `Address` | VARCHAR(128) | Адрес |
| `CreateDate` | DATETIME | Дата регистрации |
| `UComment` | TEXT | Комментарий администратора |
| `Region` | VARCHAR(32) | Регион |
| `PostalCode` | VARCHAR(32) | Почтовый индекс |
| `NameFirst` | VARCHAR(128) | Имя |
| `NameSurname` | VARCHAR(128) | Фамилия |
| `NamePatronymic` | VARCHAR(32) | Отчество |
| `Country` | VARCHAR(32) | Страна |
| `JCart` | TEXT | JSON-данные корзины |
| `JFav` | TEXT | JSON-данные избранного |
| `JData` | TEXT | JSON-дополнительные данные |

**Связи:**
- `UserPermissions` → `s_Permissions.Id` (уровень прав доступа)

**Стандартные уровни прав:**
- `1` - Администратор (полный доступ ко всем таблицам и системным расширениям)
- `2` - Редактор (ограниченный доступ к контентным таблицам)
- `3` - Посетитель сайта (без доступа в админку)

**Примечание:** Управление пользователями осуществляется через админку в списке «Пользователи».

---

### s_Files

Файловое хранилище (загруженные файлы, изображения).

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор файла |
| `Name` | VARCHAR(128) | Оригинальное имя файла |
| `Priority` | INT | Порядок сортировки |
| `InnerName` | VARCHAR(255) UNIQUE | Внутреннее имя (уникальное) |
| `TableName` | VARCHAR(128) | К какой таблице привязан |
| `FileDate` | DATETIME | Дата загрузки |
| `FileSize` | VARCHAR(255) | Размер файла |
| `FileExt` | VARCHAR(255) | Расширение файла |
| `FileType` | VARCHAR(255) | MIME-тип файла |
| `TableNameId` | INT | ID записи в таблице |
| `FileDescription` | VARCHAR(255) | Описание файла |
| `TableNameField` | VARCHAR(255) | Поле таблицы для привязки |
| `FileUrl` | VARCHAR(255) | URL файла |

**Связи:**
- `TableName` + `TableNameId` → связь с любой контентной таблицей

**Структура хранения:
- Оригиналы: `/files/lists/{TableName}/`
- Превью: `/pic/preview/files/{InnerName}`
- Средние: `/pic/medium/files/{InnerName}`

---

## Контентные таблицы

### Products (Товары)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор товара |
| `Name` | VARCHAR(128) | Название товара |
| `Alias` | VARCHAR(128) | Алиас для URL |
| `IsHidden` | INT | Скрыт (0=активен, 1=скрыт) |
| `Priority` | INT | Порядок сортировки |
| `NavigatorId` | VARCHAR(128) | ID раздела навигатора |
| `PriceBefore` | DECIMAL(12,2) | Старая цена (для скидок) |
| `Images` | INT | Количество изображений |
| `Price` | DECIMAL(12,2) | Цена |
| `PStatus` | VARCHAR(255) | Статус товара |
| `Article` | VARCHAR(128) | Артикул |
| `Descr` | TEXT | Описание товара |
| `MetaTitle` | VARCHAR(255) | SEO: заголовок |
| `MetaDescription` | VARCHAR(255) | SEO: описание |
| `MetaKeyword` | VARCHAR(255) | SEO: ключевые слова |
| `PCategory` | VARCHAR(128) | Категория товара |
| `WeightPack` | DECIMAL(10,2) | Вес упаковки |
| `DisplayFirst` | INT | Отображать первым |
| `Variations` | MEDIUMTEXT | Вариации товара (JSON) |

**Связи:**
- `BrandId` → `Brands.Id`

---

### News (Новости)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор новости |
| `Name` | VARCHAR(128) | Заголовок |
| `Alias` | VARCHAR(128) | Алиас для URL |
| `IsHidden` | INT | Скрыта (0=активна, 1=скрыта) |
| `Priority` | INT | Порядок сортировки |
| `Images` | INT | Количество изображений |
| `NDate` | DATETIME | Дата публикации |
| `Announce` | TEXT | Анонс (краткое описание) |
| `Descr` | TEXT | Полное содержание |
| `MetaTitle` | VARCHAR(255) | SEO: заголовок |
| `MetaDescription` | VARCHAR(255) | SEO: описание |
| `MetaKeyword` | VARCHAR(255) | SEO: ключевые слова |

---

### Gallery (Галерея)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Name` | VARCHAR(128) | Название альбома/фото |
| `Alias` | VARCHAR(128) | Алиас для URL |
| `IsHidden` | INT | Скрыта (0=активна, 1=скрыта) |
| `Priority` | INT | Порядок сортировки |
| `NavigatorId` | VARCHAR(255) | ID раздела навигатора |
| `Images` | INT | Количество изображений |
| `Descr` | TEXT | Описание |

---

### Contacts (Контакты)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Name` | VARCHAR(128) | Название контакта |
| `Alias` | VARCHAR(128) | Алиас |
| `IsHidden` | INT | Скрыт (0=активен, 1=скрыт) |
| `Priority` | INT | Порядок сортировки |
| `Country` | VARCHAR(128) | Страна |
| `City` | VARCHAR(128) | Город |
| `Phone` | VARCHAR(255) | Телефон |
| `Address` | VARCHAR(128) | Адрес |
| `LatLng` | VARCHAR(128) | Координаты (широта, долгота) |
| `Email` | VARCHAR(255) | Email |
| `Image` | INT | Изображение |
| `Descr` | TEXT | Описание |

---

### Brands (Бренды)

| Поле | Тип | Описание |
|------|-----|----------|
| `Id` | INT PRIMARY KEY | Идентификатор |
| `Name` | VARCHAR(128) | Название бренда |
| `Alias` | VARCHAR(128) | Алиас для URL |
| `IsHidden` | INT | Скрыт (0=активен, 1=скрыт) |
| `Priority` | INT | Порядок сортировки |
| `Image` | INT | Изображение бренда |
| `Descr` | TEXT | Описание бренда |

---

## Общие паттерны

### Стандартные поля

При автоматическом создании таблиц через `s_Config` создаются базовые поля:

- `Id` - PRIMARY KEY AUTO_INCREMENT
- `Name` - название/заголовок
- `Alias` - алиас для URL или внутреннего использования
- `IsActive` - флаг активности (0/1)
- `Priority` - порядок сортировки

Дополнительные поля (такие как `Url`, `Description`, `CreatedAt` и др.) добавляются через `s_ConfigFields` в зависимости от требований проекта.

### Связь с файлами

Файлы привязываются через таблицу `s_Files`:

```sql
-- Получить файлы товара
SELECT * FROM s_Files 
WHERE TableName = 'Products' AND TableNameField='Images' AND TableNameId = 15
ORDER BY Priority;
```

### Мультиязычность

Для реализации мультиязычности в таблицах создаются дополнительные поля:
- `TableId` (INT) - ID записи на основном языке (связь с оригинальной записью)
- `LanguageId` (INT) - ID языка из системной таблицы языков

Все языковые версии хранятся в одной таблице. Система автоматически извлекает нужные записи на основе `TableId` и `LanguageId` в соответствии с языком, выбранным на frontend.

## Создание новых таблиц

### Через админку (рекомендуемый способ)

1. Перейти в админку → список `s_Config`
2. Создать запись с названием таблицы
3. Таблица будет создана автоматически со стандартными полями и индексами
4. Настроить поля через `s_ConfigFields`

### Регистрация таблицы

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

### Автоматические индексы

При создании таблиц через `s_Config` автоматически устанавливаются стандартные индексы:
- `PRIMARY KEY` на поле `Id`
- INDEX на `Priority` (для сортировки)
- INDEX на `IsActive` (для фильтрации)

### Дополнительные индексы

Добавлять кастомные индексы следует в соответствии с требованиями конкретного приложения:

```sql
-- Пример: индекс для связи с другой таблицей
CREATE INDEX idx_brand ON Products(BrandId);

-- Пример: составной индекс для частых запросов
CREATE INDEX idx_active_priority ON Products(IsActive, Priority);
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
