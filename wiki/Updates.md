# Обновление Wepps

> ⚠️ **Перед обновлением обязательно создайте резервные копии файлов и базы данных!**

## Проверка текущей версии

Чтобы узнать текущую версию установленной платформы:

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php version
```

## Просмотр изменённых файлов

Перед обновлением рекомендуется просмотреть список локально изменённых файлов, которые будут исключены из обновления:

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php modified
```

Эта команда покажет файлы, которые были изменены локально и не будут перезаписаны при обновлении, чтобы сохранить ваши изменения.

## Список доступных обновлений

Получите список всех доступных версий для обновления:

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php list
```

Команда выведет список доступных тегов версий, на которые можно обновиться.

## Установка обновления

Для установки конкретной версии используйте:

```bash
php /var/www/site/packages/WeppsAdmin/Updates/Request.php update [tag]
```

где `[tag]` — версия для обновления, полученная из предыдущей команды (например, `v1.2.0`).

### Пример

```bash
# Получить список доступных версий
php /var/www/site/packages/WeppsAdmin/Updates/Request.php list

# Установить обновление до версии v1.2.0
php /var/www/site/packages/WeppsAdmin/Updates/Request.php update v1.2.0
```

## Файлы обновления

После успешного обновления в папке `/packages/WeppsAdmin/Updates/files/updates/[tag]` будут созданы следующие файлы:

### log.conf
Журнал разрешённых и запрещённых файлов при обновлении. Содержит информацию о том, какие файлы были обновлены, а какие пропущены из-за локальных изменений.

### log-db.conf
Журнал операций с таблицами базы данных. Фиксирует все изменения схемы БД, выполненные в процессе обновления.

### wepps.platform-diff.zip
Архив, содержащий только новые файлы из обновления, которых не было в предыдущей версии.

### wepps.platform-rollback.zip
Архив с перезаписанными файлами из предыдущей версии. Используется для отката обновления в случае необходимости.

### wepps.platform-updates.zip
Полный архив всех файлов обновления, включая новые и изменённые файлы.

## Откат обновления

Если после обновления возникли проблемы, можно выполнить откат:

### Откат файлов

```bash
cd /var/www/site
unzip -o packages/WeppsAdmin/Updates/files/updates/[tag]/wepps.platform-rollback.zip
```

### Откат базы данных

Восстановите базу данных из резервной копии, созданной перед обновлением:

```bash
mysql --defaults-file=/var/www/config/your-project.cnf your_database < /backup/db-before-update.sql
```

## Резервное копирование

### Резервное копирование базы данных

**Перед обновлением обязательно создайте резервную копию БД:**

```bash
mysqldump --defaults-file=/var/www/config/your-project.cnf your_database > /backup/db-before-update-$(date +%Y%m%d-%H%M%S).sql
```

### Резервное копирование файлов

**Создайте резервную копию всех файлов проекта:**

```bash
tar -czf /backup/files-before-update-$(date +%Y%m%d-%H%M%S).tar.gz /var/www/site
```

### Автоматическое резервное копирование

Создайте скрипт автоматического резервного копирования перед обновлением:

```bash
#!/bin/bash
BACKUP_DIR="/backup"
PROJECT_DIR="/var/www/site"
DB_NAME="your_database"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Резервное копирование БД
mysqldump --defaults-file=/var/www/config/your-project.cnf $DB_NAME > $BACKUP_DIR/db-$TIMESTAMP.sql

# Резервное копирование файлов
tar -czf $BACKUP_DIR/files-$TIMESTAMP.tar.gz $PROJECT_DIR

echo "Backup completed: $TIMESTAMP"
```

## Процесс обновления

### Полный процесс обновления

1. **Создайте резервные копии:**
   ```bash
   # БД
   mysqldump --defaults-file=/var/www/config/your-project.cnf your_database > /backup/db-$(date +%Y%m%d).sql
   
   # Файлы
   tar -czf /backup/files-$(date +%Y%m%d).tar.gz /var/www/site
   ```

2. **Проверьте текущую версию:**
   ```bash
   php /var/www/site/packages/WeppsAdmin/Updates/Request.php version
   ```

3. **Просмотрите локальные изменения:**
   ```bash
   php /var/www/site/packages/WeppsAdmin/Updates/Request.php modified
   ```

4. **Получите список доступных обновлений:**
   ```bash
   php /var/www/site/packages/WeppsAdmin/Updates/Request.php list
   ```

5. **Установите обновление:**
   ```bash
   php /var/www/site/packages/WeppsAdmin/Updates/Request.php update [tag]
   ```

6. **Проверьте работоспособность:**
   - Откройте сайт в браузере
   - Проверьте административную панель
   - Убедитесь, что все функции работают корректно

7. **В случае проблем выполните откат:**
   ```bash
   # Откат файлов
   cd /var/www/site
   unzip -o packages/WeppsAdmin/Updates/files/updates/[tag]/wepps.platform-rollback.zip
   
   # Откат БД
   mysql --defaults-file=/var/www/config/your-project.cnf your_database < /backup/db-$(date +%Y%m%d).sql
   ```

## Обновление зависимостей

После обновления платформы может потребоваться обновление Composer-зависимостей:

```bash
cd /var/www/site/packages
php composer.phar self-update
php composer.phar update
```

## Миграции базы данных

Некоторые обновления могут включать изменения схемы базы данных. Эти изменения применяются автоматически во время процесса обновления и фиксируются в `log-db.conf`.

### Проверка выполненных миграций

После обновления проверьте `log-db.conf`, чтобы убедиться, что все миграции БД выполнены успешно:

```bash
cat /var/www/site/packages/WeppsAdmin/Updates/files/updates/[tag]/log-db.conf
```

## Рекомендации

### Перед обновлением

- ✅ Создайте полные резервные копии БД и файлов
- ✅ Проверьте список локально изменённых файлов
- ✅ Убедитесь, что у вас есть доступ к серверу в случае проблем
- ✅ Выполняйте обновление в период низкой активности пользователей

### После обновления

- ✅ Проверьте работоспособность всех функций
- ✅ Проверьте логи на наличие ошибок
- ✅ Обновите Composer-зависимости при необходимости
- ✅ Очистите кэш приложения

### Безопасность

- 🔒 Храните резервные копии в безопасном месте
- 🔒 Ограничьте доступ к скриптам обновления
- 🔒 Используйте безопасное соединение (SSH) при работе с сервером

## Устранение проблем

### Обновление не завершается

- Проверьте права доступа к директориям
- Убедитесь, что достаточно места на диске
- Проверьте логи ошибок PHP и Apache

### Ошибки после обновления

- Проверьте `log.conf` на наличие конфликтующих файлов
- Проверьте `log-db.conf` на наличие ошибок миграции
- Выполните откат и повторите обновление

### Потеря локальных изменений

- Используйте команду `modified` для просмотра изменённых файлов перед обновлением
- Сохраните изменённые файлы отдельно
- Повторно примените изменения после обновления

## См. также

- [Установка](Installation.md)
- [Конфигурация](Configuration.md)
- [Структура проекта](Project-Structure.md)
