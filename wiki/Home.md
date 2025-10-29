# Wepps

**Платформа для построения веб-проектов**

Wepps — это гибкая платформа для создания сайтов и REST API проектов на PHP. Использует Smarty для шаблонизации, MySQL для хранения данных и предоставляет удобный интерфейс администрирования.

## Документация

- [Установка](Installation.md) — подробное руководство по установке платформы
- [Конфигурация](Configuration.md) — настройка конфигурационных файлов
- [Обновление](Updates.md) — обновление платформы до новых версий
- [Структура проекта](Project-Structure.md) — описание структуры файлов и директорий
- [Возможности](Features.md) — обзор возможностей платформы

## Быстрый старт

### Требования

**Серверное ПО:**
- Apache 2.x (требуется отдельный виртуальный хост)
- PHP 7.4+ (рекомендуется PHP 8.4 с PHP-FPM)
- MySQL 5.7+ (тестировалось на MySQL 8.0)

**Необходимые модули:**

```bash
# PHP-модули
sudo apt install php8.4-{curl,xml,mbstring,zip,pdo,gd,memcached}
sudo service php8.4-fpm restart

# Apache-модули
sudo a2enmod rewrite
sudo service apache2 restart
```

## Поддержка

- Официальный сайт: [wepps.dev](https://wepps.dev)
- Автор: [@petroffs](https://github.com/petroffs)
- [Создать issue](https://github.com/petroffs/wepps/issues)

## Лицензия

Этот проект распространяется под лицензией MIT. Подробнее см. в файле [LICENSE](../LICENSE).
