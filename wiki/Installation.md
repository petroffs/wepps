# Установка Wepps

## Установка из релиза

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

Подробнее о конфигурации см. [Конфигурация](Configuration.md).

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

## Требования

### Серверное ПО

- **Apache 2.x** — требуется отдельный виртуальный хост
- **PHP 7.4+** — рекомендуется PHP 8.4 с PHP-FPM
- **MySQL 5.7+** — тестировалось на MySQL 8.0

### Необходимые модули

**PHP-модули:**
```bash
sudo apt install php8.4-{curl,xml,mbstring,zip,pdo,gd,memcached}
sudo service php8.4-fpm restart
```

**Apache-модули:**
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

## Возможные проблемы

### Проблемы с правами доступа

Убедитесь, что веб-сервер имеет права на запись в следующие директории:
- `_wepps/`
- `packages/WeppsAdmin/Updates/`

### Проблемы с Apache

Убедитесь, что модуль `rewrite` включён:
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### Проблемы с PHP

Убедитесь, что все необходимые PHP-модули установлены:
```bash
php -m | grep -E "curl|xml|mbstring|zip|pdo|gd|memcached"
```

## Следующие шаги

После успешной установки:
1. Ознакомьтесь с [Конфигурацией](Configuration.md)
2. Изучите [Структуру проекта](Project-Structure.md)
3. Узнайте о [Возможностях платформы](Features.md)
