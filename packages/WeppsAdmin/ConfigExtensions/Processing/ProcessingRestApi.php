<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WeppsCore\Connect;
use WeppsCore\Utils;

class ProcessingRestApi
{
	public function mappingTypes(): void
	{
		$sql = "UPDATE s_ConfigFields SET ApiFieldType = CASE 
			WHEN `FType` = 'int' THEN 'int'
			WHEN `FType` = 'flag' THEN 'int'
			WHEN `FType` = 'guid' THEN 'guid'
			WHEN `FType` = 'date' THEN 'date'
			WHEN `FType` = 'email' THEN 'email'
			WHEN `FType` = 'json' THEN 'json'
			WHEN `FType` = 'digit' THEN 'string' -- В основном для финансовых даннвх, поэтому оставляем как string, чтобы не было проблем с точностью
			ELSE 'string'
		END
		WHERE ApiFieldType IS NULL OR ApiFieldType = ''";
		Connect::$instance->query($sql);
	}
	public function mappingNames(): void
	{
		$sql = "SELECT Id, TableField FROM s_ConfigFields WHERE ApiMapping IS NULL OR ApiMapping = '' ORDER BY TableName, TableField";
		$fields = Connect::$instance->fetch($sql);

		if (empty($fields)) {
			return;
		}

		$updateSql = "UPDATE s_ConfigFields SET ApiMapping = ? WHERE Id = ?";
		$updatedCount = 0;

		foreach ($fields as $field) {
			$apiMapping = $this->fieldApiMappingToCamelCase($field['TableField']);
			$result = Connect::$instance->query($updateSql, [$apiMapping, $field['Id']]);
			if ($result > 0) {
				$updatedCount++;
			}
		}
	}

	/**
	 * Преобразует имя поля БД в camelCase формат для REST API
	 * 
	 * @param string $key Имя поля из БД
	 * @return string Преобразованное имя в camelCase
	 */
	private function fieldApiMappingToCamelCase(string $key): string
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

	/**
	 * Сканирует папку .tools/bruno/WeppsPlatformV1/clientM2M и возвращает структурированный список .bru файлов
	 * 
	 * @param string $type 'get' для GET запросов (source) или 'not_get' для остальных (destination)
	 * @return array Массив с группировкой: ['goods' => ['goods.get.bru' => 'goods/goods.get.bru', ...], ...]
	 */
	public function scanBruFiles(string $type = 'get'): array
	{
		$bruPath = realpath(__DIR__ . '/../../../../.tools/bruno/WeppsPlatformV1/clientM2M');

		if (!is_dir($bruPath)) {
			return [];
		}

		$result = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($bruPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			// Пропускаем папки и файлы без расширения .bru
			if (!$file->isFile() || $file->getExtension() !== 'bru') {
				continue;
			}

			$filename = $file->getFilename();

			// Фильтруем по типу
			$isGetFile = stripos($filename, '.get.bru') !== false;
			if ($type === 'get' && !$isGetFile) {
				continue;
			}
			if ($type === 'not_get' && $isGetFile) {
				continue;
			}

			// Определяем группу (имя папки относительно clientM2M)
			$relativePath = str_replace($bruPath . DIRECTORY_SEPARATOR, '', $file->getPath());
			$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

			// Получаем группу (первая папка или корень)
			$pathParts = explode('/', $relativePath);
			$group = !empty($pathParts[0]) ? $pathParts[0] : 'root';

			// Удаляем расширение для label
			$label = preg_replace('/\.bru$/', '', $filename);

			// Формируем относительный путь от clientM2M
			if ($relativePath !== '.') {
				$fileRelativePath = $relativePath . '/' . $filename;
			} else {
				$fileRelativePath = $filename;
			}

			if (!isset($result[$group])) {
				$result[$group] = [];
			}

			$result[$group][$label] = $fileRelativePath;
		}

		// Сортируем по ключам (группы и файлы)
		ksort($result);
		foreach ($result as &$group) {
			ksort($group);
		}
		return $result;
	}
	public function addTests(): void
	{
		// Получаем данные из формы
		$source = $_REQUEST['source'] ?? '';
		$destination = $_REQUEST['destination'] ?? [];
		$m2mToken = $_REQUEST['m2m_token'] ?? '';

		// Нормализуем destination в массив
		if (!is_array($destination)) {
			$destination = !empty($destination) ? [$destination] : [];
		}

		if (empty($source) || empty($destination) || empty($m2mToken)) {
			throw new \Exception('Источник, назначение и токен M2M должны быть выбраны и заполнены');
		}
		


		// Получаем путь к папке clientM2M
		$platformRoot = Connect::$projectDev['root']??'';
		$clientM2MPath = $platformRoot . '/.tools/bruno/WeppsPlatformV1/clientM2M';
		
		// Проверяем, что папка существует
		if (!is_dir($clientM2MPath)) {
			throw new \Exception('Папка clientM2M не найдена: ' . $clientM2MPath);
		}
		
		// Парсим source для получения группы
		$sourcePathParts = explode('/', $source);
		$sourceGroup = $sourcePathParts[0] ?? 'root';
		$sourceFilename = array_pop($sourcePathParts) ?? '';
		
		// Извлекаем имя метода из source (например "goods.get.bru" → "get")
		$sourceMethodMatch = [];
		if (preg_match('/\.(\w+)\.bru$/', $sourceFilename, $sourceMethodMatch)) {
			$sourceMethod = strtoupper($sourceMethodMatch[1]); // "GET"
		} else {
			throw new \Exception('Некорректное имя source файла: ' . $sourceFilename);
		}
		
		// Проверяем что это GET запрос
		if ($sourceMethod !== 'GET') {
			throw new \Exception('Исходный запрос должен быть GET, получено: ' . $sourceMethod);
		}

		// Читаем .bru файл для получения URL
		$bruFilePath = $clientM2MPath . '/' . $source;
		if (!file_exists($bruFilePath)) {
			throw new \Exception('Файл запроса не найден: ' . $bruFilePath);
		}
		
		// Получаем base_url ПЕРЕД чтением .bru файла
		$baseUrl = $this->getBaseUrlFromBruEnvironment($clientM2MPath);
		if (empty($baseUrl)) {
			// Fallback на $_SERVER если не найдено
			$scheme = ($_SERVER['REQUEST_SCHEME'] ?? 'https');
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
			$baseUrl = $scheme . '://' . $host;
		}
		
		// Читаем содержимое .bru файла
		$bruContent = file_get_contents($bruFilePath);
		
		// Извлекаем URL из .bru файла (может содержать {{base_url}})
		$urlFromBru = $this->extractUrlFromBru($bruContent);
		
		if (empty($urlFromBru)) {
			throw new \Exception('Не удалось извлечь URL из файла: ' . $sourceFilename);
		}
		
		// Заменяем переменные {{base_url}} на реальное значение
		if (strpos($urlFromBru, '{{base_url}}') !== false) {
			$fullUrl = str_replace('{{base_url}}', $baseUrl, $urlFromBru);
		} else {
			// Если нет переменной, работаем с URL как с путем
			$urlPath = parse_url($urlFromBru, PHP_URL_PATH) ?: $urlFromBru;
			$fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($urlPath, '/');
		}
		
		// Отправляем GET запрос
		$data = $this->performGetRequest($fullUrl, $m2mToken);
		
		// Создаем папку .tests если не существует
		$testsPath = $clientM2MPath . '/.tests/' . $sourceGroup;
		if (!is_dir($testsPath)) {
			mkdir($testsPath, 0755, true);
		}
		
		// Для каждого destination создаём template
		foreach ($destination as $destFile) {
			$this->createTestFromSource($destFile, $sourceGroup, $data, $clientM2MPath, $testsPath);
		}
	}

	/**
	 * Создает JSON template для тестового запроса на основе данных от GET
	 */
	private function createTestFromSource(string $destFile, string $sourceGroup, array $data, string $clientM2MPath, string $testsPath): void
	{
		// Парсим destination для получения метода
		$destPathParts = explode('/', $destFile);
		$destFilename = array_pop($destPathParts) ?? '';
		$destGroup = $destPathParts[0] ?? $sourceGroup;



		// Извлекаем метод из имени файла (например "goods.post.bru" → "post")
		$destMethodMatch = [];
		if (!preg_match('/\.(\w+)\.bru$/', $destFilename, $destMethodMatch)) {
			throw new \Exception('Некорректное имя destination файла: ' . $destFilename);
		}

		$destMethod = strtoupper($destMethodMatch[1]); // "POST", "PUT", "DELETE"
		
		// Извлекаем ресурс (например "goods.post.bru" → "goods")
		$resourceName = preg_replace('/\.(get|post|put|delete|patch)\.bru$/i', '', $destFilename);
		

		
		// Получаем список требуемых полей для этого метода
		$requiredFields = $this->getRequiredFieldsForMethod($destMethod, $resourceName);
		
		// Если обязательных полей меньше 3 для POST/PUT - добавляем 2-е и 3-е поле из ответа
		if (count($requiredFields) < 3 && in_array($destMethod, ['POST', 'PUT'])) {
			if (!empty($data) && is_array($data[0])) {
				$firstItem = $data[0];
				$keys = array_keys($firstItem);
				
				// Пропускаем уже существующие требуемые поля и добавляем недостающие
				foreach ($keys as $index => $key) {
					if (!in_array($key, $requiredFields) && count($requiredFields) < 3) {
						$requiredFields[] = $key;
					}
				}
			}
		}
		
		// Фильтруем данные - оставляем только требуемые поля
		$filteredData = $this->filterDataByRequiredFields($data, $requiredFields);
		
		$templateName = preg_replace('/\.bru$/', '-template.json', $destFilename);
		$templatePath = $testsPath . '/' . $templateName;

		// Создаём template в зависимости от метода
		$template = $this->generateTemplateForMethod($destMethod, $filteredData);

		// Сохраняем JSON
		$json = json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if (file_put_contents($templatePath, $json) === false) {
			throw new \Exception('Не удалось сохранить файл: ' . $templatePath);
		}
	}

	/**
	 * Генерирует JSON template в зависимости от типа запроса
	 */
	private function generateTemplateForMethod(string $method, array $data): array
	{
		switch ($method) {
			case 'POST':
			case 'PUT':
				// Для POST - берем только первый элемент, убираем ID
				if ($method === 'POST') {
					$item = $data[0] ?? [];
					$item = array_filter($item, fn($k) => $k !== 'id', ARRAY_FILTER_USE_KEY);
					return ['data' => [$item]];
				}
				
				// Для PUT - берем элемент с ID
				$items = [];
				if (!empty($data)) {
					$item = $data[0];
					if (isset($item['id'])) {
						$items[] = $item;
					}
				}
				return [
					'data' => $items,
					'pagination' => [
						'count' => 1,
						'limit' => 100,
						'page' => 1,
						'note' => 'Template for update - remove pagination if updating only selected items'
					]
				];

			case 'DELETE':
				// Для DELETE - берем только ID
				$ids = [];
				foreach (array_slice($data, 0, 1) as $item) {
					if (isset($item['id'])) {
						$ids[] = $item['id'];
					}
				}
				return ['data' => $ids];

			default:
				return ['data' => []];
		}
	}

	/**
	 * Получает base_url из конфигурации проекта или Bruno окружения
	 */
	private function getBaseUrlFromBruEnvironment(string $clientM2MPath): string
	{
		// 1. Попробуем получить из конфига config.php (самый надежный способ)
		$configPath = dirname($clientM2MPath, 4) . '/config.php';
		if (file_exists($configPath)) {
			// Читаем конфиг временно для получения projectSettings
			ob_start();
			include $configPath;
			ob_end_clean();
			
			if (!empty($projectSettings['Dev']['protocol']) && !empty($projectSettings['Dev']['host'])) {
				// protocol может быть 'https://' или 'https', хост это 'platform.wepps'
				$protocol = $projectSettings['Dev']['protocol'];
			// Убираем ':' и '/' в конце если они есть
			$protocol = rtrim($protocol, ':/');
				$host = $projectSettings['Dev']['host'];
				return $protocol . '://' . $host;
			}
		}
		
		// 2. Fallback - читаем из local.bru
		$localEnvPath = dirname($clientM2MPath) . '/environments/local.bru';
		if (file_exists($localEnvPath)) {
			$content = file_get_contents($localEnvPath);
			if (preg_match('/^\s*base_url\s*:\s*(\S+)/m', $content, $matches)) {
				return trim($matches[1]);
			}
		}
		
		return '';
	}

	/**
	 * Извлекает URL из содержимого .bru файла (может содержать {{base_url}})
	 */
	private function extractUrlFromBru(string $bruContent): string
	{
		// Ищем URL в строке после "url:" - до конца строки или до следующего параметра (body, auth, etc)
		// Формат может быть:
		//   url: {{base_url}}/rest/m2m/goods
		//   или
		//   url: {{base_url}}/rest/m2m/goods body: none
		if (preg_match('/url\s*:\s*(.+?)(?:\s+\w+\s*:|$)/i', $bruContent, $matches)) {
			$urlLine = trim($matches[1]);
			
			// Убираем пробелы и точку с запятой в конце
			$urlLine = trim($urlLine, " \t\n\r\0\x0B;");
			
			// Возвращаем URL как есть (с переменными если они есть)
			return $urlLine;
		}
		
		return '';
	}

	/**
	 * Выполняет GET запрос с токеном M2M
	 */
	private function performGetRequest(string $url, string $token): array
	{
		$ch = curl_init($url);
		
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json',
				'Accept: application/json',
			],
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		]);
		
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if ($httpCode !== 200) {
			throw new \Exception("HTTP {$httpCode}");
		}
		
		$data = json_decode($response, true);
		
		if (!is_array($data)) {
			throw new \Exception('Invalid JSON response');
		}
		
		if (!isset($data['data']) || !is_array($data['data'])) {
			throw new \Exception("Missing 'data' in response");
		}
		
		return $data['data'];
	}

	/**
	 * Получает список требуемых полей для метода из RestConfig
	 * 
	 * @param string $method POST, PUT, DELETE
	 * @param string $resourceName Название ресурса (goods, users, orders и т.д.)
	 * @return array Массив названий требуемых полей
	 */
	private function getRequiredFieldsForMethod(string $method, string $resourceName): array
	{
		try {
			// Используем RestConfig для получения конфигурации
			$restConfig = \WeppsExtensions\Addons\Rest\RestConfig::getConfig();
			
			// Путь в конфиге: m2m -> {method} -> {resourceName}
			$methodLower = strtolower($method);
			
			if (!isset($restConfig['m2m'][$methodLower][$resourceName])) {
				// Если конфига нет, возвращаем пустой массив (все поля допустимы)
				return [];
			}
			
			$config = $restConfig['m2m'][$methodLower][$resourceName];
			
			// Если нет validation, возвращаем все поля
			if (!isset($config['validation']) || !is_array($config['validation'])) {
				return [];
			}
			
			// Собираем только требуемые поля
			$requiredFields = [];
			foreach ($config['validation'] as $fieldName => $fieldConfig) {
				if (!empty($fieldConfig['required'])) {
					$requiredFields[] = $fieldName;
				}
			}
			
			return $requiredFields;
		} catch (\Exception $e) {
			// Если что-то пошло не так, возвращаем пустой массив (используем все поля)
			return [];
		}
	}

	/**
	 * Фильтрует данные, оставляя только требуемые поля
	 * 
	 * @param array $data Исходные данные (массив объектов)
	 * @param array $requiredFields Список требуемых полей
	 * @return array Отфильтрованные данные
	 */
	private function filterDataByRequiredFields(array $data, array $requiredFields): array
	{
		// Если нет ограничений на поля, возвращаем все данные
		if (empty($requiredFields)) {
			return $data;
		}
		
		// Фильтруем каждый элемент массива
		$filtered = [];
		foreach ($data as $item) {
			if (!is_array($item)) {
				continue;
			}
			
			$filteredItem = [];
			foreach ($requiredFields as $fieldName) {
				if (isset($item[$fieldName])) {
					$filteredItem[$fieldName] = $item[$fieldName];
				}
			}
			
			// Добавляем только если что-то осталось
			if (!empty($filteredItem)) {
				$filtered[] = $filteredItem;
			}
		}
		
		return !empty($filtered) ? $filtered : $data;
	}
}