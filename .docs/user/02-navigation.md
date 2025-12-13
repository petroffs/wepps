# Управление навигацией

Полное руководство по созданию и настройке структуры сайта.

## Что такое навигация?

**Навигация (s_Navigator)** - это таблица, которая определяет:
- Структуру разделов сайта
- Меню и подменю
- URL-адреса страниц
- Функциональность каждого раздела

## Основные поля

### Name (Название)

Отображаемое название раздела в меню.

**Примеры:**
- `Главная`
- `О компании`
- `Каталог товаров`
- `Контакты`

### Url (URL адрес)

Уникальный адрес раздела на сайте.

**Правила:**
- Начинается с `/`
- Только латиница, цифры, дефисы
- Без пробелов и спецсимволов
- Уникален для каждого раздела

**Примеры:**
```
/                    → Главная страница
/about              → О компании
/catalog            → Каталог
/catalog/products   → Товары (вложенный)
/contacts           → Контакты
```

**Обращение к странице:**
```
https://ваш-сайт.com/catalog/products
```

> **Примечание:** Платформа использует mod_rewrite для ЧПУ (человекопонятных URL). 
> Все URL автоматически преобразуются: `/about/` → `/?weppsurl=/about/` (внутренняя обработка).

### ParentDir (Родительский раздел)

ID родительского раздела для создания иерархии.

**Значения:**
- `0` - корневой раздел (верхний уровень)
- `ID раздела` - вложенный раздел

**Пример иерархии:**
```
Главная (Id: 1, ParentDir: 0)
Каталог (Id: 2, ParentDir: 0)
├── Товары (Id: 3, ParentDir: 2)
├── Акции (Id: 4, ParentDir: 2)
└── Бренды (Id: 5, ParentDir: 2)
Контакты (Id: 6, ParentDir: 0)
```

### Extension (Расширение)

Функциональный модуль для раздела (выбирается из списка `s_Extensions`).

**Основные расширения:**

| Расширение | Описание | Использование |
|------------|----------|---------------|
| **Template** | Статическая страница | О компании, Доставка, FAQ |
| **Products** | Каталог товаров | Магазин, Каталог |
| **News** | Новости и статьи | Блог, Новости |
| **Gallery** | Фотогалерея | Портфолио, Галерея работ |
| **Contacts** | Контактная форма | Контакты |
| **Cart** | Корзина покупок | Оформление заказа |
| **Profile** | Личный кабинет | Профиль пользователя |

### Template (Шаблон)

Имя шаблона для расширения **Template** (статические страницы).

Шаблоны находятся в: `WeppsExtensions/Template/templates/`

**Примеры:**
- `about.tpl` - о компании
- `delivery.tpl` - доставка и оплата
- `privacy.tpl` - политика конфиденциальности

### IsHidden (Скрыт)

Видимость раздела в меню.

- `0` - раздел виден в меню ✅
- `1` - раздел скрыт из меню ❌ (но доступен по прямой ссылке)

**Когда скрывать:**
- Корзина (доступна через виджет)
- Личный кабинет (доступен после авторизации)
- Страница благодарности после заказа
- Служебные страницы

### Priority (Приоритет)

Порядок сортировки в меню. Чем выше число, тем выше в списке.

**Пример:**
```
Priority: 100 → Главная
Priority: 90  → О компании
Priority: 80  → Каталог
Priority: 70  → Новости
Priority: 60  → Контакты
```

### LanguageId (Язык)

ID языка для мультиязычных сайтов.

- `0` или `1` - основной язык (русский)
- `2` - английский
- `3` - другие языки

Для каждого языка создается дубликат раздела с разным `LanguageId`.

### SEO поля

**MetaTitle** - заголовок страницы (`<title>`)
```
Купить качественные товары с доставкой | Название магазина
```

**MetaDescription** - описание для поисковиков
```
Интернет-магазин товаров с доставкой по всей России. 
Широкий ассортимент, низкие цены, гарантия качества.
```

**MetaKeywords** - ключевые слова (менее актуально)
```
купить товары, интернет магазин, доставка
```

## Создание структуры сайта

### Простой одноуровневый сайт

```
Главная         (/, Extension: Template)
О компании      (/about, Extension: Template)
Каталог         (/catalog, Extension: Products)
Новости         (/news, Extension: News)
Контакты        (/contacts, Extension: Contacts)
```

**Создание:**

1. **Главная страница**
   - Name: `Главная`
   - Url: `/`
   - ParentDir: `0`
   - Extension: `Template`
   - Template: `home.tpl`
   - Priority: `100`

2. **О компании**
   - Name: `О компании`
   - Url: `/about/`
   - ParentDir: `0`
   - Extension: `Template`
   - Template: `about.tpl`
   - Priority: `90`

3. **Каталог**
   - Name: `Каталог`
   - Url: `/catalog/`
   - ParentDir: `0`
   - Extension: `Products`
   - Priority: `80`

### Сайт с подразделами

```
Главная (/)
Каталог (/catalog)
├── Все товары (/catalog/all)
├── Акции (/catalog/promotions)
└── Бренды (/catalog/brands)
О компании (/about)
├── История (/about/history)
├── Команда (/about/team)
└── Вакансии (/about/jobs)
Контакты (/contacts)
```

**Создание:**

1. **Создайте родительский раздел "Каталог"**
   - Name: `Каталог`
   - Url: `/catalog/`
   - ParentDir: `0`
   - Extension: `Products`
   - IsHidden: `0`
   - Priority: `80`
   - → Запомните его **Id** (например, Id=5)

2. **Создайте подразделы**
   
   **Все товары:**
   - Name: `Все товары`
   - Url: `/catalog/all`
   - ParentDir: `5` (Id каталога)
   - Extension: `Products`
   - Priority: `10`
   
   **Акции:**
   - Name: `Акции`
   - Url: `/catalog/promotions`
   - ParentDir: `5`
   - Extension: `Products`
   - Priority: `9`

### Многоуровневая иерархия

```
Каталог (/catalog)
├── Электроника (/catalog/electronics)
│   ├── Смартфоны (/catalog/electronics/smartphones)
│   └── Ноутбуки (/catalog/electronics/laptops)
├── Одежда (/catalog/clothing)
│   ├── Мужская (/catalog/clothing/men)
│   └── Женская (/catalog/clothing/women)
└── Бытовая техника (/catalog/appliances)
```

**Порядок создания:**

1. Создайте **Каталог** (ParentDir: 0, Id: 10)
2. Создайте **Электроника** (ParentDir: 10, Id: 11)
3. Создайте **Смартфоны** (ParentDir: 11)
4. Создайте **Ноутбуки** (ParentDir: 11)
5. И так далее...

## Типовые сценарии

### Главная страница

```
Name: Главная
Url: /
ParentDir: 0
Extension: Template
Template: home.tpl
IsHidden: 0
Priority: 100
MetaTitle: Название вашего сайта
MetaDescription: Описание сайта для поисковиков
```

### Каталог товаров

```
Name: Каталог
Url: /catalog
ParentDir: 0
Extension: Products
IsHidden: 0
Priority: 80
MetaTitle: Каталог товаров - Название магазина
MetaDescription: Широкий выбор товаров по выгодным ценам
```

### Страница товара (детальная)

```
Name: Товар
Url: /product
ParentDir: 0
Extension: Products (с параметром детального просмотра)
IsHidden: 1  ← Скрыта из меню
Priority: 0
```

URL товара будет формироваться динамически:
```
/product/123
или
/product/notebook-lenovo-ideapad  (ЧПУ из поля Url товара)
```

### Корзина

```
Name: Корзина
Url: /cart
ParentDir: 0
Extension: Cart
IsHidden: 1  ← Доступ через виджет корзины
Priority: 0
```

### Личный кабинет

```
Name: Личный кабинет
Url: /profile
ParentDir: 0
Extension: Profile
IsHidden: 1  ← Показывается только авторизованным
Priority: 0
```

### Страница 404

```
Name: Страница не найдена
Url: /404
ParentDir: 0
Extension: Error404
IsHidden: 1
Priority: 0
```

## Работа с меню

### Вывод меню в шаблоне

В Smarty шаблонах доступна переменная `$navigator`:

```smarty
{* Корневые разделы (ParentDir = 0) *}
<nav class="main-menu">
    <ul>
        {foreach $navigator->getRootItems() as $item}
            {if !$item.IsHidden}
                <li>
                    <a href="{$item.Url}">{$item.Name}</a>
                    
                    {* Подразделы *}
                    {if $item.childs}
                        <ul class="submenu">
                            {foreach $item.childs as $child}
                                <li>
                                    <a href="{$child.Url}">{$child.Name}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                </li>
            {/if}
        {/foreach}
    </ul>
</nav>
```

### Хлебные крошки (Breadcrumbs)

```smarty
<div class="breadcrumbs">
    {foreach $navigator->getBreadcrumbs() as $crumb}
        {if !$crumb@last}
            <a href="{$crumb.Url}">{$crumb.Name}</a>
            <span class="separator">→</span>
        {else}
            <span class="current">{$crumb.Name}</span>
        {/if}
    {/foreach}
</div>
```

### Активный раздел

```smarty
<li class="{if $item.Id == $navigator->path.Id}active{/if}">
    <a href="{$item.Url}">{$item.Name}</a>
</li>
```

## Полезные советы

### ЧПУ (человекопонятные URL)

**Плохо:**
```
/page?id=123
/catalog?category=5
```

**Хорошо:**
```
/about-company
/catalog/smartphones
/news/new-product-release
```

### Уникальность URL

Каждый URL должен быть уникальным! Система предупредит при дублировании.

### Логичная структура

Группируйте связанные разделы:

```
✅ Хорошо:
/services/design
/services/development
/services/support

❌ Плохо:
/design
/services
/dev-service
/support-page
```

### Порядок в меню

Используйте шаг Priority = 10 для гибкости:

```
Priority: 100 → Главная
Priority: 90  → О компании
Priority: 80  → Каталог
Priority: 70  → Новости
Priority: 60  → Контакты
```

Если нужно вставить раздел между "Каталог" (80) и "Новости" (70), 
используйте Priority = 75.

### Тестирование структуры

После создания разделов:
1. Откройте frontend сайта
2. Проверьте меню - все разделы на месте?
3. Проверьте каждую ссылку - открывается корректно?
4. Проверьте хлебные крошки
5. Проверьте мобильную версию меню

## Распространенные ошибки

### ❌ Дублирующиеся URL

```
Раздел 1: /about
Раздел 2: /about  ← Ошибка!
```

**Решение:** Сделайте уникальные URL:
```
/about
/about-company
```

### ❌ Неправильный ParentDir

```
Каталог (Id: 5)
└── Товары (ParentDir: 10)  ← Ошибка! Раздела с Id=10 не существует
```

**Решение:** Указывайте существующий Id родительского раздела.

### ❌ Забыли про IsHidden

```
Корзина (IsHidden: 0)  ← Отображается в главном меню
```

**Решение:** Для служебных страниц `IsHidden = 1`.

### ❌ Некорректный URL формат

```
❌ catalog products
❌ каталог
❌ /catalog/товары
❌ catalog/products/

✅ /catalog-products
✅ /catalog/products
```

## Мультиязычность

### Создание версий на разных языках

1. **Создайте разделы для русской версии** (LanguageId: 1)
   ```
   Главная      (/ru, LanguageId: 1)
   О компании   (/ru/about, LanguageId: 1)
   Каталог      (/ru/catalog, LanguageId: 1)
   ```

2. **Дублируйте для английской версии** (LanguageId: 2)
   ```
   Home         (/en, LanguageId: 2)
   About        (/en/about, LanguageId: 2)
   Catalog      (/en/catalog, LanguageId: 2)
   ```

### Переключение языка

В шаблоне:

```smarty
<div class="language-switcher">
    <a href="/?lang=ru">RU</a>
    <a href="/?lang=en">EN</a>
</div>
```

## Дополнительные материалы

- [Первые шаги](getting-started.md)
- [Управление контентом](content-management.md)
- [Работа с товарами](products.md)
