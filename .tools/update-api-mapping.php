<?php
/**
 * Скрипт для заполнения ApiMapping в s_ConfigFields
 * 
 * Преобразует имена полей БД в camelCase формат для REST API:
 * - Product_Name → productName
 * - Order_Status → orderStatus
 * - OStatus → status (удаляет однобуквенный префикс)
 * - w_product_list → wProductList (служебный префикс W_ сохраняется)
 * 
 * Использование:
 * php tools/update-api-mapping.php
 */

// Подключить конфиг и ядро платформы
require __DIR__ . '/../configloader.php';
require_once __DIR__ . '/../packages/vendor/autoload.php';

use WeppsCore\Connect;

echo "🔄 Запуск обновления ApiMapping в s_ConfigFields...\n\n";

// Инициализировать Connect (автоматически подключается к БД)
$connect = Connect::$instance;

/**
 * Преобразует имя поля БД в camelCase формат для REST API
 * 
 * @param string $key Имя поля из БД
 * @return string Преобразованное имя в camelCase
 */
function fieldApiMappingToCamelCase(string $key): string
{
	$parts = explode('_', $key);
	$result = '';
	foreach ($parts as $part) {
		// Убираем однобуквенный PascalCase-префикс внутри слова: OStatus → status, JData → data
		// W_ не трогаем — это служебный префикс, даёт wVariations
		if (preg_match('/^[A-Z]([A-Z][a-z].*)$/', $part, $m)) {
			$part = $m[1];
		}
		$result .= $result === '' ? lcfirst($part) : ucfirst($part);
	}
	return $result;
}

try {
	// Получить все поля, у которых ApiMapping пуст
	$sql = "SELECT Id, Field FROM s_ConfigFields WHERE (ApiMapping IS NULL OR ApiMapping = '') ORDER BY TableName, Field";
	$fields = $connect->fetch($sql);
	
	if (empty($fields)) {
		echo "ℹ️  Нет полей с пустым ApiMapping\n";
		exit(0);
	}
	
	// Обновить ApiMapping для каждого поля
	$updateSql = "UPDATE s_ConfigFields SET ApiMapping = ? WHERE Id = ?";
	$updatedCount = 0;
	
	foreach ($fields as $field) {
		$apiMapping = fieldApiMappingToCamelCase($field['Field']);
		$result = $connect->query($updateSql, [$apiMapping, $field['Id']]);
		if ($result > 0) {
			$updatedCount++;
		}
	}
	
	echo "✅ SQL успешно выполнен!\n";
	echo "📊 Обновлено записей: {$updatedCount}\n\n";
	
	// Показать статистику по таблицам
	$statSql = "SELECT TableName, COUNT(*) as cnt FROM s_ConfigFields WHERE ApiMapping IS NOT NULL AND ApiMapping != '' GROUP BY TableName ORDER BY TableName";
	$statResult = $connect->fetch($statSql);
	
	echo "📈 Распределение по таблицам:\n";
	echo str_repeat("─", 50) . "\n";
	$totalFields = 0;
	foreach ($statResult as $row) {
		printf("  %-30s: %5d полей\n", $row['TableName'], $row['cnt']);
		$totalFields += $row['cnt'];
	}
	echo str_repeat("─", 50) . "\n";
	echo "  Всего полей с ApiMapping: {$totalFields}\n\n";
	
	// Показать несколько примеров преобразований
	$exampleSql = "SELECT Field, ApiMapping FROM s_ConfigFields WHERE ApiMapping IS NOT NULL LIMIT 5";
	$examples = $connect->fetch($exampleSql);
	
	echo "📝 Примеры преобразований:\n";
	echo str_repeat("─", 50) . "\n";
	foreach ($examples as $example) {
		printf("  %s → %s\n", str_pad($example['Field'], 25), $example['ApiMapping']);
	}
	echo str_repeat("─", 50) . "\n\n";
	
	echo "✨ Система готова к использованию M2M API с правильным маппингом полей!\n";
	
} catch (\Exception $e) {
	echo "❌ Ошибка: " . $e->getMessage() . "\n";
	exit(1);
}
?>
