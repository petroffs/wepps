# Конфигурация Wepps

## Основные конфигурационные файлы

### config.php

Основной конфигурационный файл проекта, содержащий параметры подключения к базе данных, пути к директориям и другие настройки приложения.

**Расположение:** `/var/www/your-project/config.php`

**Основные параметры:**
- Параметры подключения к БД (хост, порт, пользователь, пароль, имя БД)
- Пути к директориям проекта
- Настройки окружения (production/development)
- Параметры кэширования

### config.cnf

Конфигурационный файл MySQL для CLI-операций, используемый при работе с базой данных через командную строку.

**Расположение:** `/var/www/your-project/config.cnf` (рекомендуется вынести за пределы веб-директории)

**Формат:**
```ini
[client]
host=localhost
port=3306
user=your_db_user
password=your_db_password
```

> ⚠️ **Безопасность**: Рекомендуется вынести `config.cnf` за пределы папки веб-сервера для предотвращения несанкционированного доступа к учётным данным базы данных.

**Рекомендуемое расположение:**
```bash
# Переместите config.cnf за пределы веб-директории
sudo mv /var/www/your-project/config.cnf /var/www/config/your-project.cnf
sudo chmod 600 /var/www/config/your-project.cnf
```

Затем обновите ссылки на файл в скриптах обновления и резервного копирования.

### configloader.php

Загрузчик конфигурации, отвечающий за загрузку и инициализацию настроек приложения.

**Расположение:** `/var/www/your-project/configloader.php`

Этот файл автоматически загружается при инициализации приложения и обрабатывает конфигурационные параметры из `config.php`.

## Настройка базы данных

### Создание базы данных

```bash
mysql -u root -p -e "CREATE DATABASE your_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Права доступа пользователя

Создайте пользователя БД с необходимыми правами:

```sql
CREATE USER 'wepps_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON your_database.* TO 'wepps_user'@'localhost';
FLUSH PRIVILEGES;
```

### Кодировка

Wepps использует кодировку `utf8mb4` с collation `utf8mb4_unicode_ci` для полной поддержки Unicode, включая эмодзи и специальные символы.

## Настройка веб-сервера

### Apache

**Виртуальный хост:**

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/your-project
    
    <Directory /var/www/your-project>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/your-project-error.log
    CustomLog ${APACHE_LOG_DIR}/your-project-access.log combined
</VirtualHost>
```

**Необходимые модули:**
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo service apache2 restart
```

### PHP-FPM

Для лучшей производительности рекомендуется использовать PHP-FPM:

```bash
sudo apt install php8.4-fpm
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.4-fpm
sudo service apache2 restart
```

## Права доступа

### Директории с правами записи

Веб-сервер должен иметь права на запись в следующие директории:

```bash
sudo chown -R www-data:www-data /var/www/your-project/_wepps
sudo chown -R www-data:www-data /var/www/your-project/packages/WeppsAdmin/Updates
sudo chmod -R 755 /var/www/your-project/_wepps
sudo chmod -R 755 /var/www/your-project/packages/WeppsAdmin/Updates
```

### Безопасность файлов конфигурации

```bash
sudo chmod 600 /var/www/your-project/config.php
sudo chown www-data:www-data /var/www/your-project/config.php
```

## Настройка Composer

### Установка зависимостей

```bash
cd /var/www/your-project/packages
php composer.phar install
```

### Обновление зависимостей

```bash
cd /var/www/your-project/packages
php composer.phar self-update
php composer.phar update
```

## Оптимизация производительности

### Кэширование

Wepps поддерживает кэширование с помощью Memcached:

```bash
sudo apt install memcached php8.4-memcached
sudo service memcached start
sudo service php8.4-fpm restart
```

### OPcache

Включите OPcache для PHP:

```ini
# /etc/php/8.4/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

После изменения перезапустите PHP-FPM:
```bash
sudo service php8.4-fpm restart
```

## Переменные окружения

Для различных окружений (production, development, staging) можно настроить соответствующие параметры в `config.php`.

**Пример для development:**
- Включить отображение ошибок
- Отключить кэширование шаблонов
- Включить режим отладки

**Пример для production:**
- Отключить отображение ошибок
- Включить кэширование
- Оптимизировать производительность

## Дополнительные настройки

### HTTPS

Для использования HTTPS настройте SSL-сертификат:

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/your-project
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    <Directory /var/www/your-project>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Резервное копирование

Настройте регулярное резервное копирование базы данных и файлов:

```bash
# Пример скрипта резервного копирования
mysqldump --defaults-file=/var/www/config/your-project.cnf your_database > /backup/db-$(date +%Y%m%d).sql
tar -czf /backup/files-$(date +%Y%m%d).tar.gz /var/www/your-project
```

## См. также

- [Установка](Installation.md)
- [Обновление](Updates.md)
- [Структура проекта](Project-Structure.md)
