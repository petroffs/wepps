<?php
// Загрузить конфиг и Connect
require_once __DIR__ . '/../configloader.php';
require_once __DIR__ . '/../packages/WeppsCore/Connect.php';

use WeppsCore\Memcached;

$action = $argv[1] ?? 'flush'; // 'flush' (по умолчанию) или 'delete'

echo "Очистка Memcached...\n";
$mem = new Memcached('auto', true); // системный кэш

if ($action === 'delete') {
	// Удаляем конкретные ключи
	$mem->delete('api_validation_rules_v1_Products');
	$mem->delete('api_validation_rules_v1_s_Users');
	$mem->delete('api_validation_rules_v1_Orders');
	echo "✅ Удалены validation rules!\n";
} else {
	// Полная очистка кэша
	$mem->flushAll();
	echo "✅ Весь кэш очищен!\n";
}
