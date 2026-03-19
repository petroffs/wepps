<?php
/**
 * Скрипт для заполнения поля ApiMapping в s_ConfigFields
 * Преобразует Field в camelCase формат согласно логике Rest.php
 */

// Загружаем конфигурацию
require 'configloader.php';

/**
 * Конвертирует PascalCase в camelCase
 * OStatus → status, JData → data, Images_FileUrl → imagesFileUrl
 */
function toCamelCase($key) {
    $parts = explode('_', $key);
    $result = '';
    foreach ($parts as $part) {
        // Убираем однобуквенный PascalCase-префикс: OStatus → status, JData → data
        if (preg_match('/^[A-Z]([A-Z][a-z].*)$/', $part, $m)) {
            $part = $m[1];
        }
        $result .= $result === '' ? lcfirst($part) : ucfirst($part);
    }
    return $result;
}

// Получаем все поля
$sql = "SELECT TableName, Field, ApiMapping FROM s_ConfigFields ORDER BY TableName, Field";
$result = \WeppsCore\Connect::$instance->fetch($sql);

echo "Обрабатываю " . count($result) . " полей...\n";

$updates = [];
$count = 0;

foreach ($result as $row) {
    $field = $row['Field'];
    $currentMapping = $row['ApiMapping'];
    $newMapping = toCamelCase($field);
    
    // Если маппинг уже установлен и отличается от вычисленного - пропускаем
    if (!empty($currentMapping)) {
        echo "ПРОПУСК: {$row['TableName']}.{$field} уже имеет маппинг: {$currentMapping}\n";
        continue;
    }
    
    // Обновляем
    $sql = "UPDATE s_ConfigFields SET ApiMapping = ? WHERE TableName = ? AND Field = ?";
    try {
        \WeppsCore\Connect::$instance->query($sql, [$newMapping, $row['TableName'], $field]);
        echo "✓ {$row['TableName']}.{$field} → {$newMapping}\n";
        $count++;
    } catch (Exception $e) {
        echo "✗ ОШИБКА {$row['TableName']}.{$field}: " . $e->getMessage() . "\n";
    }
}

echo "\nОбновлено полей: {$count}\n";
echo "Готово!\n";
