#!/usr/bin/env php
<?php
/**
 * Generate M2M API templates for goods operations
 * Usage: php generate-m2m-templates.php [--limit 3]
 * 
 * Creates:
 * - goods-get-response.json           (все товары с пагинацией)
 * - goods-get-response-sample-3.json  (первые N товаров)
 * - goods-post-template.json          (для CREATE всех товаров)
 * - goods-post-template-sample-3.json (для CREATE первых N)
 * - goods-put-template.json           (для UPDATE всех товаров с пагинацией маркером)
 * - goods-put-template-sample-3.json  (для UPDATE первых N)
 */

// Определяем пути
$rootPath = realpath(__DIR__ . '/../../');
$configPath = $rootPath . '/config.php';
$outputPath = $rootPath . '/.tools/bruno/WeppsPlatformV1/clientM2M/tests/goods';

// Проверяем config.php
if (!file_exists($configPath)) {
    die("❌ Config not found: $configPath\n");
}

// Загружаем конфиг
require_once $configPath;
require_once $rootPath . '/packages/vendor/autoload.php';

// Параметры
$limit = isset($argv[1]) && $argv[1] === '--limit' ? (int)($argv[2] ?? 3) : 3;
$baseUrl = $projectSettings['Dev']['protocol'] . $projectSettings['Dev']['host'];
$m2mToken = $projectSettings['Services']['rest']['m2m_client_token'] ?? '';

if (empty($m2mToken)) {
    die("❌ M2M token not configured in config.php at Services.rest.m2m_client_token\n");
}

echo "🔄 Generating M2M templates for goods...\n";
echo "   Base URL: $baseUrl\n";
echo "   Sample limit: $limit\n";
echo "   Output: $outputPath\n\n";

// =========================================================================
// 1. GET all goods
// =========================================================================
echo "📥 Fetching goods from GET /rest/m2m/goods...\n";

$getResponse = fetchM2M("$baseUrl/rest/m2m/goods", $m2mToken);

if (!$getResponse || !isset($getResponse['data'])) {
    die("❌ Failed to fetch goods\n");
}

$allGoods = $getResponse['data'];
$totalCount = count($allGoods);

echo "   ✅ Fetched $totalCount goods\n";

// =========================================================================
// 2. Extract fields for POST and PUT
// =========================================================================

// Поля для POST (требуемые + опциональные)
$postRequiredFields = ['name', 'alias', 'navigatorId', 'price'];
$postOptionalFields = ['article', 'descr', 'isHidden', 'priceBefore', 'status', 
                       'metaTitle', 'metaDescription', 'metaKeyword', 'weightPack', 'displayFirst'];

// Поля для PUT (id + поля для обновления)
$putFields = ['id', 'name', 'article'];

// =========================================================================
// 3. Create templates for all goods
// =========================================================================

// Template для POST (все товары)
$postAllTemplate = [
    'data' => extractFields($allGoods, array_merge($postRequiredFields, $postOptionalFields), false)
];
saveJsonFile("$outputPath/goods-post-template.json", $postAllTemplate);
echo "✅ Created: goods-post-template.json (" . count($postAllTemplate['data']) . " items)\n";

// Template для PUT (все товары с пагинацией маркером)
$putAllTemplate = [
    'data' => extractFields($allGoods, $putFields, true),
    'pagination' => [
        'page' => $getResponse['page'] ?? 1,
        'limit' => $getResponse['limit'] ?? count($allGoods),
        'total' => $totalCount,
        'note' => 'Marker: if "pagination" field exists in JSON, full data overwrite will be performed'
    ]
];
saveJsonFile("$outputPath/goods-put-template.json", $putAllTemplate);
echo "✅ Created: goods-put-template.json (" . count($putAllTemplate['data']) . " items with pagination marker)\n";

// =========================================================================
// 4. Create sample templates (first N items)
// =========================================================================

$sampleGoods = array_slice($allGoods, 0, $limit);
$sampleCount = count($sampleGoods);

// Template для POST (первые N)
$postSampleTemplate = [
    'data' => extractFields($sampleGoods, array_merge($postRequiredFields, $postOptionalFields), false)
];
saveJsonFile("$outputPath/goods-post-template-sample-$limit.json", $postSampleTemplate);
echo "✅ Created: goods-post-template-sample-$limit.json ($sampleCount items)\n";

// Template для PUT (первые N с пагинацией маркером)
$putSampleTemplate = [
    'data' => extractFields($sampleGoods, $putFields, true),
    'pagination' => [
        'page' => 1,
        'limit' => $limit,
        'total' => $sampleCount,
        'note' => 'Sample for update - REMOVE pagination field if updating only selected items'
    ]
];
saveJsonFile("$outputPath/goods-put-template-sample-$limit.json", $putSampleTemplate);
echo "✅ Created: goods-put-template-sample-$limit.json ($sampleCount items)\n";

// =========================================================================
// 5. Save full GET response for reference
// =========================================================================

saveJsonFile("$outputPath/goods-get-response.json", $getResponse);
echo "✅ Created: goods-get-response.json (reference)\n";

// Sample GET response
$sampleGetResponse = [
    'data' => $sampleGoods,
    'page' => 1,
    'limit' => $limit,
    'count' => $sampleCount,
];
saveJsonFile("$outputPath/goods-get-response-sample-$limit.json", $sampleGetResponse);
echo "✅ Created: goods-get-response-sample-$limit.json (reference)\n";

// =========================================================================
// 6. Create README for Bruno users
// =========================================================================

$readme = <<<'README'
# M2M API Templates для Goods

## Структура файлов

### GET (получение данных)
- **goods-get-response.json** - полный ответ GET /rest/m2m/goods (все товары)
- **goods-get-response-sample-3.json** - первые 3 товара из ответа GET

### POST (создание товаров)
- **goods-post-template.json** - шаблон для создания всех товаров
  - Удалить поле `id` перед отправкой
  - Вставить в body POST запроса в Bruno
  
- **goods-post-template-sample-3.json** - шаблон для создания первых 3 товаров
  - Удалить поле `id` перед отправкой
  - Вставить в body POST запроса в Bruno

**Требуемые поля для POST:**
- name (строка)
- alias (строка)
- navigatorId (число)
- price (число/float)

### PUT (обновление товаров)
- **goods-put-template.json** - шаблон для обновления всех товаров
  - Содержит поле `pagination` - МАРКЕР что нужна полная перезапись
  - Вставить в body PUT запроса в Bruno
  - Используйте если хотите перезаписать ВСЕ данные

- **goods-put-template-sample-3.json** - шаблон для обновления первых 3 товаров
  - УДАЛИТЬ поле `pagination` перед отправкой!
  - Вставить в body PUT запроса в Bruno
  - Используйте если хотите обновить только выбранные товары

**Поля для PUT:**
- id (обязательно)
- name (опционально)
- article (опционально)

## Как использовать в Bruno

### 1. GET товары
```
GET {{base_url}}/rest/m2m/goods
```
Результат сохранится в JSON файлах выше.

### 2. POST создать товары
- Откройте `goods-post-template-sample-3.json`
- Скопируйте содержимое
- В Bruno > POST /rest/m2m/goods
- Вставьте в body
- Отредактируйте если нужно (измените name, price, etc.)
- Отправьте
- Результат: 201 (успех) или 207 (batch с per-item статусами)

### 3. PUT обновить товары
**Вариант A: Обновить выбранные товары (без маркера)**
- Откройте `goods-put-template-sample-3.json`
- **УДАЛИТЕ** поле `"pagination"`
- Скопируйте содержимое `data` 
- В Bruno > PUT /rest/m2m/goods
- Вставьте в body
- Отредактируйте name, article
- Отправьте

**Вариант B: Полная перезапись (с маркером)**
- Откройте `goods-put-template.json`
- **ОСТАВЬТЕ** поле `"pagination"` - это маркер
- Скопируйте содержимое
- В Bruno > PUT /rest/m2m/goods
- Вставьте в body
- Отправьте (перепишет ВСЕ товары)

## Примечания

- Все файлы автоматически генерируются скриптом `generate-m2m-templates.php`
- ID в POST файлах должны быть удалены перед отправкой (это создание, не обновление)
- Пагинация в PUT маркер - если его удалить, обновится только выбранные товары
- Batch max 100 товаров в одном запросе
- M2M требует авторизации (Bearer token)

README;

file_put_contents("$outputPath/README.md", $readme);
echo "✅ Created: README.md\n";

echo "\n✅ All templates generated successfully!\n";
echo "   Location: $outputPath\n";

// =========================================================================
// Helper Functions
// =========================================================================

/**
 * Fetch data from M2M API
 */
function fetchM2M($url, $token)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ HTTP $httpCode: $response\n";
        return null;
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
        return null;
    }
    
    return $data;
}

/**
 * Extract specified fields from items
 */
function extractFields($items, $fields, $includeId)
{
    $result = [];
    
    foreach ($items as $item) {
        $extracted = [];
        
        // Всегда включаем ID если указано
        if ($includeId && isset($item['id'])) {
            $extracted['id'] = $item['id'];
        }
        
        // Включаем указанные поля если они существуют
        foreach ($fields as $field) {
            if ($field !== 'id' && isset($item[$field])) {
                // Преобразуем типы данных для правильного JSON
                $value = $item[$field];
                
                // Числовые поля остаются числами
                if (is_numeric($value) && !is_bool($value)) {
                    $extracted[$field] = (strpos($value, '.') !== false) ? (float)$value : (int)$value;
                } else {
                    $extracted[$field] = $value;
                }
            }
        }
        
        if (!empty($extracted)) {
            $result[] = $extracted;
        }
    }
    
    return $result;
}

/**
 * Save array as formatted JSON file
 */
function saveJsonFile($filepath, $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filepath, $json) === false) {
        die("❌ Failed to write file: $filepath\n");
    }
}
