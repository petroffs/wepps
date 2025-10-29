# Wepps

**Платформа для построения веб-проектов**

Wepps — это гибкая платформа для создания сайтов и REST API проектов на PHP. Использует Smarty для шаблонизации, MySQL для хранения данных и предоставляет удобный интерфейс администрирования.

---

## ⚡ Быстрый старт

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

---

## 📦 Установка из релиза

### 1. Скачайте последний релиз

Перейдите на [wepps.dev](https://wepps.dev) и скачайте последнюю версию платформы в виде архива.

### 2. Настройте виртуальный хост Apache

Создайте конфигурацию виртуального хоста:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/your-project
    
    <Directory /var/www/your-project>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Активируйте хост:
```bash
sudo a2ensite your-project.conf
sudo service apache2 reload
```

### 3. Распакуйте архив

```bash
cd /var/www/your-project
unzip wepps-release.zip
# или
tar -xzf wepps-release.tar.gz
```

### 4. Настройте конфигурационные файлы

**config.php** — основная конфигурация проекта (параметры подключения к БД, пути и т.д.)

**config.cnf** — конфигурация MySQL для CLI-операций

> ⚠️ **Важно**: Рекомендуется вынести `config.cnf` за пределы папки веб-сервера для безопасности.

Пример `config.cnf`:
```ini
[client]
host=localhost
port=3306
user=your_db_user
password=your_db_password
```

### 5. Создайте базу данных

```bash
mysql -u root -p -e "CREATE DATABASE your_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6. Запустите установку

Перейдите в корневую папку проекта и выполните:

```bash
php install.php
```

**Установщик автоматически:**
1. Проверит наличие composer-зависимостей
2. Предложит установить/обновить зависимости (если нужно):
   ```bash
   cd /var/www/your-project/packages && php composer.phar self-update && php composer.phar install && cd ../
   ```
3. Заполнит базу данных из дампа (включённого в релиз)
4. Запросит логин и пароль администратора

### 7. Готово!

Откройте ваш сайт в браузере и войдите с учётными данными администратора.

---

## 🔄 Обновление платформы

> ⚠️ **Перед обновлением обязательно создайте резервные копии файлов и базы данных!**

### Проверка текущей версии

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php version
```

### Просмотр изменённых файлов

Показать список локально изменённых файлов, которые будут исключены из обновления:

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php modified
```

### Список доступных обновлений

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php list
```

### Установка обновления

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php update [tag]
```

где `[tag]` — версия для обновления из предыдущей команды.

**После обновления в папке** `/packages/WeppsAdmin/Updates/files/updates/[tag]` **будут созданы:**
- `log.conf` — журнал разрешённых/запрещённых файлов
- `log-db.conf` — журнал операций с таблицами БД
- `wepps.platform-diff.zip` — новые файлы из обновления
- `wepps.platform-rollback.zip` — перезаписанные файлы (для отката)
- `wepps.platform-updates.zip` — все файлы обновления

---

## 📁 Структура проекта

```
wepps/
├── index.php              # Точка входа
├── install.php            # Скрипт установки
├── config.php             # Конфигурация проекта
├── config.cnf             # Конфигурация MySQL
├── configloader.php       # Загрузчик конфигурации
├── packages/              # Основные модули платформы
│   ├── WeppsCore/        # Ядро системы
│   ├── WeppsAdmin/       # Административная панель
│   ├── WeppsExtensions/  # Расширения
│   ├── composer.json     # Зависимости
│   └── vendor/           # Composer-пакеты
└── _wepps/               # Служебная директория
```

---

## 🛠️ Возможности платформы

- Создание веб-сайтов и REST API
- Административная панель для управления контентом
- Гибкая система списков и полей
- Шаблонизатор Smarty
- Система обновлений с откатом изменений
- Резервное копирование БД и файлов
- Модульная архитектура

---

## 🤝 Участие в разработке

Нашли ошибку или есть предложение? Создайте issue в репозитории!

---

## 📄 Лицензия

Этот проект распространяется под лицензией MIT. Подробнее см. в файле [LICENSE](LICENSE).

---

## 📞 Поддержка

- Официальный сайт: [wepps.dev](https://wepps.dev)
- Автор: [@petroffs](https://github.com/petroffs)