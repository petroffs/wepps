<?php
/**
 * Скрипт для заполнения ApiFieldType в s_ConfigFields
 * 
 * Маппинг типов БД на REST API типы:
 * - int → int
 * - flag → int
 * - guid → guid
 * - date → date
 * - email → email
 * - digit → float
 * - остальные → string
 * 
 * Использование:
 * php tools/update-api-field-types.php
 */

// Подключить конфиг и ядро платформы
require __DIR__ . '/../configloader.php';
require_once __DIR__ . '/../packages/vendor/autoload.php';

use WeppsCore\Connect;

echo "🔄 Запуск обновления ApiFieldType в s_ConfigFields...\n\n";

// Инициализировать Connect (автоматически подключается к БД)
$connect = Connect::$instance;

// SQL для заполнения ApiFieldType
$sql = "UPDATE s_ConfigFields SET ApiFieldType = CASE 
    WHEN `Type` = 'int' THEN 'int'
    WHEN `Type` = 'flag' THEN 'int'
    WHEN `Type` = 'guid' THEN 'guid'
    WHEN `Type` = 'date' THEN 'date'
    WHEN `Type` = 'email' THEN 'email'
    WHEN `Type` = 'digit' THEN 'float'
    ELSE 'string'
END
WHERE ApiFieldType IS NULL OR ApiFieldType = ''";

try {
    // Выполнить запрос
    $result = $connect->query($sql);
    
    // Получить количество обновленных записей
    $countSql = "SELECT COUNT(*) as cnt FROM s_ConfigFields WHERE ApiFieldType IS NOT NULL AND ApiFieldType != ''";
    $countResult = $connect->fetch($countSql);
    
    echo "✅ SQL успешно выполнен!\n";
    echo "📊 Всего записей с ApiFieldType: " . $countResult[0]['cnt'] . "\n\n";
    
    // Показать статистику по типам
    $statSql = "SELECT ApiFieldType, COUNT(*) as cnt FROM s_ConfigFields WHERE ApiFieldType IS NOT NULL GROUP BY ApiFieldType ORDER BY cnt DESC";
    $statResult = $connect->fetch($statSql);
    
    echo "📈 Распределение по типам:\n";
    echo str_repeat("─", 40) . "\n";
    foreach ($statResult as $row) {
        printf("  %-15s: %5d записей\n", $row['ApiFieldType'], $row['cnt']);
    }
    echo str_repeat("─", 40) . "\n\n";
    
    echo "✨ Система готова к использованию M2M API с автоматической валидацией!\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
?>
